<?php
header('Content-Type: application/json');

include 'database.php';
include 'on_session.php';

// This endpoint is hit directly over AJAX, so it needs its own access check —
// it doesn't inherit the one done in index.php.
if (!(strpos($access, "stock") !== false || $user_position_name === "Administrator")) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 9;
    $limit = max(1, min($limit, 100)); // clamp so a caller can't force a huge dump
    $offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $warehouse = isset($_GET['warehouse']) ? $_GET['warehouse'] : '';

    // Whitelist sort options — ORDER BY can't be parameterized in a
    // prepared statement, so the raw $_GET value must never reach the SQL.
    $sortOptions = [
        'date_desc' => 's.date DESC',
        'date_asc'  => 's.date ASC',
        'qty_desc'  => 'quantity DESC',
        'qty_asc'   => 'quantity ASC',
        'name_asc'  => 'p.description ASC',
    ];
    $sortKey = $_GET['sort'] ?? 'date_desc';
    $orderBy = $sortOptions[$sortKey] ?? $sortOptions['date_desc'];

    // Build the WHERE clause once, shared by both the data query and the
    // count query, so they can never drift out of sync again — and so the
    // search condition is skipped entirely when there's nothing to search
    // for, instead of running six LIKE '%%' comparisons on every row.
    $conditions = ["w.hashed_id IN ($user_warehouse_id)"];
    $params = [];
    $types = '';

    if ($search !== '') {
        $searchTerm = "%$search%";
        $conditions[] = "(s.parent_barcode LIKE ? 
                       OR p.description LIKE ? 
                       OR b.brand_name LIKE ? 
                       OR c.category_name LIKE ? 
                       OR u.user_fname LIKE ? 
                       OR u.user_lname LIKE ?)";
        array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $types .= 'ssssss';
    }

    if ($warehouse) {
        $conditions[] = "w.hashed_id = ?";
        $params[] = $warehouse;
        $types .= 's';
    }

    $whereClause = implode(' AND ', $conditions);

    $baseQuery = "
        SELECT 
            COUNT(CASE WHEN s.item_status NOT IN (1, 4, 8) THEN 1 END) AS quantity,
            s.product_id,
            p.id, 
            p.id AS key_product,
            p.description AS product_name, 
            p.safety AS safety,
            c.category_name AS category, 
            b.brand_name AS brand, 
            CONCAT(u.user_fname, ' ', u.user_lname) AS created_by, 
            p.date AS created_date,
            w.warehouse_name AS wh,
            s.warehouse,
            s.parent_barcode
        FROM stocks s
        LEFT JOIN product p ON s.product_id = p.hashed_id
        LEFT JOIN brand b ON p.brand = b.hashed_id
        LEFT JOIN category c ON p.category = c.hashed_id
        LEFT JOIN users u ON p.user_id = u.hashed_id
        LEFT JOIN warehouse w ON s.warehouse = w.hashed_id
        WHERE $whereClause
        GROUP BY s.product_id, s.warehouse
        ORDER BY $orderBy
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($baseQuery);

    $dataParams = array_merge($params, [$limit, $offset]);
    $dataTypes = $types . 'ii';
    bindParamsByRef($stmt, $dataTypes, $dataParams);

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    // Hash the internal numeric id before it ever leaves the server.
    // The key must come from config/environment, never a literal string
    // committed to source — set INVENTORY_HASH_KEY in your server env.
    $secretKey = getenv('INVENTORY_HASH_KEY') ?: null;

    foreach ($data as &$item) {
        if ($secretKey) {
            $item['id'] = hash_hmac('sha256', (string) $item['id'], $secretKey);
        } else {
            // No key configured — drop the raw internal id rather than
            // ship it unhashed. content.php doesn't use this field today.
            unset($item['id']);
        }
    }
    unset($item);

    // Count distinct product+warehouse pairs directly instead of wrapping
    // the whole filtered query in a subquery just to COUNT(*) it — same
    // WHERE clause, one pass instead of materializing every matching row.
    $countQuery = "
        SELECT COUNT(DISTINCT s.product_id, s.warehouse) AS total
        FROM stocks s
        LEFT JOIN product p ON s.product_id = p.hashed_id
        LEFT JOIN brand b ON p.brand = b.hashed_id
        LEFT JOIN category c ON p.category = c.hashed_id
        LEFT JOIN users u ON p.user_id = u.hashed_id
        LEFT JOIN warehouse w ON s.warehouse = w.hashed_id
        WHERE $whereClause
    ";

    $countStmt = $conn->prepare($countQuery);
    if ($types !== '') {
        bindParamsByRef($countStmt, $types, $params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'] ?? 0;

    echo json_encode(['data' => $data, 'total' => (int) $total]);
} catch (Throwable $e) {
    // Log the real error server-side; never echo internal exception detail
    // (query text, table/column names) back to the client.
    error_log('getStockListData.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load product data. Please try again.']);
}

/**
 * mysqli_stmt::bind_param needs its arguments by reference. That doesn't
 * play well with a dynamically-sized $params array built with push/merge,
 * so this builds the reference list bind_param needs from a plain array.
 */
function bindParamsByRef(mysqli_stmt $stmt, string $types, array $params): void
{
    $refs = [];
    $refs[] = $types;
    foreach ($params as $key => $value) {
        $refs[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}
?>
