
<?php
header("Content-Type: application/json; charset=UTF-8"); // Set response type to JSON

include "../config/database.php";
include "../config/on_session.php";

$data = [];

if (!empty($user_warehouse_ids)) {
    // Create placeholders for warehouse IDs
    $placeholders = implode(',', array_fill(0, count($user_warehouse_ids), '?'));

    // Query to fetch warehouses securely
    $warehouse_query = "SELECT hashed_id, warehouse_name FROM warehouse WHERE hashed_id IN ($placeholders)";
    $stmt = $conn->prepare($warehouse_query);
    $stmt->bind_param(str_repeat('s', count($user_warehouse_ids)), ...$user_warehouse_ids);
    $stmt->execute();
    $warehouse_res = $stmt->get_result();

    if ($warehouse_res->num_rows > 0) {
        while ($row = $warehouse_res->fetch_assoc()) {
            $warehouse_id = $row['hashed_id'];
            $warehouse_name = $row['warehouse_name'];

            // Secure product query
            $product_query = "SELECT p.hashed_id AS product_id, p.description, p.product_img, 
                                     b.brand_name, c.category_name, p.parent_barcode
                              FROM product p
                              LEFT JOIN brand b ON p.hashed_id = b.id
                              LEFT JOIN category c ON p.hashed_id = c.id";
            $product_res = $conn->query($product_query);

            if ($product_res->num_rows > 0) {
                while ($product_row = $product_res->fetch_assoc()) {
                    $product_id = $product_row['product_id'];

                    // Secure stock query
                    $stock_query = "SELECT COUNT(product_id) AS available_quantity 
                                    FROM stocks 
                                    WHERE product_id = ? 
                                    AND item_status = 0 
                                    AND warehouse = ?";
                    $stmt = $conn->prepare($stock_query);
                    $stmt->bind_param('ss', $product_id, $warehouse_id);
                    $stmt->execute();
                    $stock_res = $stmt->get_result();

                    $quantity = 0;
                    if ($stock_res->num_rows > 0) {
                        $stock_row = $stock_res->fetch_assoc();
                        $quantity = $stock_row['available_quantity'];
                    }

                    if ($quantity > 0) {
                        $product_image = $product_row['product_img'] ?? "../../assets/img/def_img.png";
                        echo '
                            <tr>
                                <td><img src="' . basename($product_image) . '"></td>
                                <td>' . $product_row['description'] . '</td>
                                <td>' . $product_row['brand_name'] . '</td>
                                <td>' . $product_row['category_name'] . '</td>
                                <td>' . $quantity . '</td>
                                <td>' . $warehouse_name . '</td>
                                <td class="text-end">' . $product_row['parent_barcode'] . '</td>
                            </tr>
                        ';
                    }
                }
            }
        }
    }
}

$conn->close();
exit;
?>
