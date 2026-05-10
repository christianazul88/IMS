<?php
include "../config/database.php";
session_start();


// Ensure `po_list` exists in session
if (!isset($_SESSION['po_list'])) {
    $_SESSION['po_list'] = [];
}

$new_items = [];

if(isset($_GET['blue']) && !empty($_GET['blue'])){
    $po_id = $_GET['blue'];
    $purchased_order_contents = "SELECT p.hashed_id AS product_id, p.description, p.parent_barcode, b.brand_name, c.category_name, poc.* FROM purchased_order_content poc LEFT JOIN product p ON p.hashed_id = poc.product_id LEFT JOIN brand b ON b.hashed_id = p.brand LEFT JOIN category c ON c.hashed_id = p.category WHERE po_id = '$po_id'";
    $purchased_order_results = $conn->query($purchased_order_contents);
    if($purchased_order_results->num_rows>0){
        while($row=$purchased_order_results->fetch_assoc()){
        // Append product details to session
        $new_items[] = [
            "id" => $row['product_id'],
            "description" => $row['description'],
            "brand" => $row['brand_name'],
            "category" => $row['category_name'],
            "barcode" => $row['parent_barcode'],
            "qty" => $row['qty'] // Default quantity to 1
        ];
        }
    }
}

// If new items were added, push them to session
if (!empty($new_items)) {
    $_SESSION['po_list'] = array_merge($_SESSION['po_list'], $new_items);
    header("Location: ../update-po/?blue=$po_id");
} else {
    header("Location: ../PO-logs/?success=false");
}