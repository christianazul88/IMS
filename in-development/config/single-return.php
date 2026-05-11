<?php
include "database.php";
include "on_session.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $barcode = $_POST["single-barcode"];
    $selected_wh = $_SESSION['hashed_warehouse'];

    // Ensure existing data is properly decoded as an array
    $existingData = isset($_SESSION['scanned_return']) ? json_decode($_SESSION['scanned_return'], true) : [];
    if (!is_array($existingData)) {
        $existingData = [];
    }

    // Check if the barcode already exists
    foreach ($existingData as $item) {
        if (isset($item['unique_barcode']) && $item['unique_barcode'] === $barcode) {
            echo "Barcode already added!";
            exit;
        }
    }

    // Build the base query
    $query = "SELECT s.unique_barcode, p.description, b.brand_name, s.parent_barcode, c.category_name, s.supplier
            FROM stocks s
            LEFT JOIN product p ON p.hashed_id = s.product_id
            LEFT JOIN brand b ON b.hashed_id = p.brand
            LEFT JOIN category c ON c.hashed_id = p.category
            WHERE s.unique_barcode = '$barcode' AND s.warehouse = '$selected_wh' AND s.item_status = 0";

    // Add supplier condition if session return_supplier is set
    if (isset($_SESSION['return_supplier'])) {
        $supplier = $_SESSION['return_supplier'];
        $query .= " AND s.supplier = '$supplier'";
    }

    $query .= " LIMIT 1";

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if (!isset($_SESSION['return_supplier'])) {
            $_SESSION['return_supplier'] = $row['supplier'];
        }

        // Create new item data
        $newItem = [
            "unique_barcode" => $barcode,
            "description" => $row['description'],
            "brand_name" => $row['brand_name'],
            "category_name" => $row['category_name'],
            "parent_barcode" => $row['parent_barcode']
        ];

        // Append new item to existing data
        $existingData[] = $newItem;

        // JSON Encode the updated data
        $jsonData = json_encode($existingData);

        // Store in Session
        $_SESSION['scanned_return'] = $jsonData;

        // Store in Global Variable
        $GLOBALS['scanned_return'] = $jsonData;

        // Store in APCu (if available)
        if (function_exists('apcu_store')) {
            apcu_store('scanned_return', $jsonData);
        }

        echo "Added successfully!";
    } else {
        echo isset($_SESSION['return_supplier']) 
            ? "Barcode maybe invalid or not the same supplier as the first entry" 
            : "Invalid Barcode!";
    }
}
?>
