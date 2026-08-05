[ ] this represents a button
<?php
include "../config/database.php";
include "../config/on_session.php";


$po_id = $_GET['po_id'] ?? 2197;
$unique_key = $_GET['unique_key'] ?? '163067390281';

echo "[ void all this inbound ] --- PO ID: $po_id, Unique Key: $unique_key<br><hr><br>";



$purchased_order_query = "SELECT 
                            poc.product_id, 
                            poc.qty,
                            p.description,
                            b.brand_name,
                            c.category_name
                        FROM purchased_order_content poc 
                        LEFT JOIN product p ON poc.product_id = p.hashed_id
                        LEFT JOIN brand b ON p.brand = b.hashed_id
                        LEFT JOIN category c ON p.category = c.hashed_id
                        WHERE poc.po_id = $po_id";
$purchased_order_result = $conn->query($purchased_order_query);
if ($purchased_order_result->num_rows > 0) {
    while($row=$purchased_order_result->fetch_assoc()){
        $product_id = $row['product_id'];
        $ordered_qty = $row['qty'];
        $description = $row['description'];
        $brand_name = $row['brand_name'];
        $category_name = $row['category_name'];

        echo "[ void all sequence on this product ] --- Description: $description, Brand: $brand_name, Category: $category_name, Ordered Quantity: $ordered_qty<br>";

        $stocks_display_Query = "SELECT s.unique_barcode, s.capital, s.item_status FROM stocks s WHERE s.unique_key = '$unique_key' AND s.product_id = '$product_id'";
        $stocks_display_result = $conn->query($stocks_display_Query);
        if ($stocks_display_result->num_rows > 0) {
            while($stock_row = $stocks_display_result->fetch_assoc()){
                $unique_barcode = $stock_row['unique_barcode'];
                $capital = $stock_row['capital'];
                $item_status = $stock_row['item_status'];

                echo "[ void button ]----------------------------------------------     Unique Barcode: $unique_barcode, Capital: $capital, Item Status: $item_status<br>";
            }
        } else {
            echo "No stocks found for Product ID: $product_id with Unique Key: $unique_key<br>";
        }
       
        echo "<hr>";
        
    }
}

// below is currently how I void barcodes. its for approval thats why I am saving it on void logs:
//      $stmt = $conn->prepare(
//         "INSERT INTO void_logs (date, user_id, remarks, status) VALUES (NOW(), ?, ?, 'pending')"
//     );
//     $stmt->bind_param('ss', $user_id, $remarks);
//     $stmt->execute();
//     $void_log_id = $conn->insert_id;
//     $stmt->close();

//     // 2. Insert every scanned barcode into void_items, tied to that void_logs row
//     $itemStmt = $conn->prepare(
//         "INSERT INTO void_items (void_log_id, unique_barcode) VALUES (?, ?)"
//     );
//     foreach ($barcodes as $item) {
//         $barcode = unique_barcode;

//         $itemStmt->bind_param('is', $void_log_id, $barcode);
//         $itemStmt->execute();
//     }
//     $itemStmt->close();

//     $conn->commit();


// but now this is a single inbound logs so, the administrator will need to approve the void request for this po_id and unique_key. so we might need to add a column for unique_key and po_id on void_logs table. kindly also suggest a way on how we can add additional items or products to stocks table which were not on the purchased_order_content,and once approved, will be added to stocks table. dont generate any code yet.