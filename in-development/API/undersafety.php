<?php
header('Content-Type: application/json');
include "../config/database.php";
include "../config/on_session.php";

$notifications = [];

try {
    $sql = "SELECT 
        p.description AS description,
        b.brand_name AS brand_name,
        c.category_name AS category_name,
        p.product_img AS product_img,
        SUM(CASE WHEN s.item_status IN (0, 2, 3) THEN 1 ELSE 0 END) AS quantity,
        p.safety AS safety,
        s.warehouse AS warehouse,
        w.warehouse_name AS warehouse_name
    FROM stocks s
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
    WHERE s.warehouse IN ($user_warehouse_id)
    GROUP BY s.product_id, s.warehouse, p.description, b.brand_name, c.category_name, p.product_img, p.safety, w.warehouse_name"; // Your original query
    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $product_description = $row['description'] ?? 'No Description';
            $brand_name = $row['brand_name'] ?? 'Unknown Brand';
            $category_name = $row['category_name'] ?? 'Unknown Category';
            $product_img = $row['product_img'] ?? '../../assets/img/def_img.png';
            $quantity = $row['quantity'] ?? 0;
            $safety_stock = $row['safety'] ?? 0;

            if ($quantity < $safety_stock) {
                $notifications[] = [
                    'title' => "Safety stock level reached!",
                    'message' => "The product '{$product_description}' (Brand: {$brand_name}, Category: {$category_name}) is now under safety stock level. Current quantity is {$quantity}.",
                    'imageUrl' => $product_img,
                    'linkUrl' => "asdas.php"
                ];
            }
        }
    }

    echo json_encode($notifications);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
