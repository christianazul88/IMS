<?php
include 'database.php';

$secretKey = 'your_secret_key';

try {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $warehouse = isset($_GET['warehouse']) ? $_GET['warehouse'] : '';

    $baseQuery = "
        SELECT 
            COUNT(s.product_id) AS quantity,
            p.id, 
            p.description AS product_name, 
            c.category_name AS category, 
            b.brand_name AS brand, 
            p.parent_barcode, 
            CONCAT(u.user_fname, ' ', u.user_lname) AS created_by, 
            p.date AS created_date
        FROM stocks s
        LEFT JOIN product p ON s.product_id = p.hashed_id
        LEFT JOIN brand b ON p.brand = b.hashed_id
        LEFT JOIN category c ON p.category = c.hashed_id
        LEFT JOIN users u ON p.user_id = u.hashed_id
        LEFT JOIN warehouse w ON s.warehouse = w.hashed_id
        WHERE (p.description LIKE ? 
           OR b.brand_name LIKE ? 
           OR c.category_name LIKE ? 
           OR u.user_fname LIKE ? 
           OR u.user_lname LIKE ?)
    ";

    if ($warehouse) {
        $baseQuery .= " AND w.hashed_id = ?";
    }

    $baseQuery .= " GROUP BY s.product_id ORDER BY s.product_id DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($baseQuery);

    $searchTerm = "%$search%";
    if ($warehouse) {
        $stmt->bind_param('ssssssii', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $warehouse, $limit, $offset);
    } else {
        $stmt->bind_param('sssssii', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($data as &$item) {
        $item['id'] = hash_hmac('sha256', $item['id'], $secretKey);
    }

    $countQuery = "
        SELECT COUNT(DISTINCT s.product_id) AS total
        FROM stocks s
        LEFT JOIN product p ON s.product_id = p.hashed_id
        LEFT JOIN brand b ON p.brand = b.hashed_id
        LEFT JOIN category c ON p.category = c.hashed_id
        LEFT JOIN users u ON p.user_id = u.hashed_id
        LEFT JOIN warehouse w ON s.warehouse = w.hashed_id
        WHERE (p.description LIKE ? 
           OR b.brand_name LIKE ? 
           OR c.category_name LIKE ? 
           OR u.user_fname LIKE ? 
           OR u.user_lname LIKE ?)
    ";

    if ($warehouse) {
        $countQuery .= " AND w.hashed_id = ?";
    }

    $countStmt = $conn->prepare($countQuery);

    if ($warehouse) {
        $countStmt->bind_param('ssssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $warehouse);
    } else {
        $countStmt->bind_param('sssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    }

    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];

    echo json_encode(['data' => $data, 'total' => $total]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
