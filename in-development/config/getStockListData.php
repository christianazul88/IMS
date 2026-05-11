<?php
include 'database.php';

$secretKey = 'your_secret_key';

try {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 9;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $warehouse = isset($_GET['warehouse']) ? $_GET['warehouse'] : '';

    $baseQuery = "
        SELECT 
            COUNT(CASE WHEN s.item_status NOT IN (1, 4, 8) AND s.warehouse = w.hashed_id THEN 1 END) AS quantity,
            s.product_id,
            p.id, 
            p.id AS key_product,
            p.description AS product_name, 
            p.product_img, 
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
        WHERE (s.parent_barcode LIKE ? 
           OR p.description LIKE ? 
           OR b.brand_name LIKE ? 
           OR c.category_name LIKE ? 
           OR u.user_fname LIKE ? 
           OR u.user_lname LIKE ?)
    ";

    if ($warehouse) {
        $baseQuery .= " AND w.hashed_id = ?";
    }

    $baseQuery .= " GROUP BY s.product_id, s.warehouse ORDER BY s.date DESC LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($baseQuery);

    $searchTerm = "%$search%";
    if ($warehouse) {
        $stmt->bind_param('sssssssii', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $warehouse, $limit, $offset);
    } else {
        $stmt->bind_param('ssssssii', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($data as &$item) {
        $item['id'] = hash_hmac('sha256', $item['id'], $secretKey);

        // Process product_img to return first image as data URI
        $first_image = '../../assets/img/def_img.png';  // fallback

        if (!empty($item['product_img'])) {
            // Try unserialize, fallback to json_decode if needed
            $images = @unserialize($item['product_img']);
            if ($images === false) {
                $images = json_decode($item['product_img'], true);
            }
            
            if (is_array($images) && count($images) > 0) {
                $first_base64 = $images[0];
                $binary = base64_decode($first_base64);

                if ($binary !== false) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->buffer($binary) ?: 'image/jpeg';

                    $first_image = 'data:' . $mime . ';base64,' . $first_base64;
                }
            }
        }

        $item['product_img'] = $first_image;
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
           AND s.item_status NOT IN (1, 4, 8)
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
