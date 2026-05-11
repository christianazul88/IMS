<?php
include "database.php";
include "on_session.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if(!isset($_SESSION['return_supplier'])) {
        $barcode = $_POST["single-barcode"];
        $selected_wh = $_SESSION['hashed_warehouse'];
        $rack_id = $_SESSION['rack'];
        $rack_query = "SELECT location_name FROM item_location WHERE id = '$rack_id' LIMIT 1";
        $rack_res = $conn->query($rack_query);
        if($row=$rack_res->fetch_assoc()){
            $rack_name = $row['location_name'];
        }
        // Ensure existing data is properly decoded as an array
        if (isset($_SESSION['scanned_transfer'])) {
            $existingData = json_decode($_SESSION['scanned_transfer'], true);

            // Check if the decoding resulted in a valid array
            if (!is_array($existingData)) {
                $existingData = []; // Default to an empty array if decoding failed
            }
        } else {
            $existingData = []; // If no session data, default to empty array
        }

        // Check if the barcode already exists
        foreach ($existingData as $item) {
            if (isset($item['unique_barcode']) && $item['unique_barcode'] === $barcode) {
                echo "Barcode already added!";
                exit; // Stop further execution
            }
        }

        // Perform database query to get the barcode details
        $query = "SELECT s.unique_barcode, p.description, b.brand_name, s.parent_barcode, c.category_name, s.supplier, p.product_img
                FROM stocks s
                LEFT JOIN product p ON p.hashed_id = s.product_id
                LEFT JOIN brand b ON b.hashed_id = p.brand
                LEFT JOIN category c ON c.hashed_id = p.category
                WHERE s.unique_barcode = '$barcode' AND s.warehouse = '$selected_wh'
                LIMIT 1";

        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $supplier = $row['supplier'];
            $description = $row['description'];
            $brand_name = $row['brand_name'];
            $category_name = $row['category_name'];
            $parent_barcode = $row['parent_barcode'];
            $product_image = $row['product_img'] ?? "../../assets/img/def_img.png";

            // Create new item data
            $newItem = [
                "unique_barcode" => $barcode,
                "description" => $description,
                "brand_name" => $brand_name,
                "category_name" => $category_name,
                "parent_barcode" => $parent_barcode,
                "product_img" => $product_image,
                "rack_id" => $rack_id,
                "rack_name" => $rack_name 
            ];

            // Append new item to existing data
            $existingData[] = $newItem;

            // JSON Encode the updated data
            $jsonData = json_encode($existingData);

            // Store in Session
            $_SESSION['scanned_transfer'] = $jsonData;

            // Store in Global Variable
            $GLOBALS['scanned_transfer'] = $jsonData;

            // Store in APCu (if available)
            if (function_exists('apcu_store')) {
                apcu_store('scanned_transfer', $jsonData);
            }

            // Output the JSON (for debugging or API response)
            echo "Added successfully!";
        } else {
            echo "Invalid Barcode!";
        }
    } else {
        $supplier = $_SESSION['return_supplier'];
        $barcode = $_POST["single-barcode"];
        $selected_wh = $_SESSION['hashed_warehouse'];
        $rack_id = $_SESSION['rack'];
        $rack_query = "SELECT location_name FROM item_location WHERE id = '$rack_id' LIMIT 1";
        $rack_res = $conn->query($rack_query);
        if($row=$rack_res->fetch_assoc()){
            $rack_name = $row['location_name'];
        }
        // Ensure existing data is properly decoded as an array
        if (isset($_SESSION['scanned_transfer'])) {
            $existingData = json_decode($_SESSION['scanned_transfer'], true);

            // Check if the decoding resulted in a valid array
            if (!is_array($existingData)) {
                $existingData = []; // Default to an empty array if decoding failed
            }
        } else {
            $existingData = []; // If no session data, default to empty array
        }

        // Check if the barcode already exists
        foreach ($existingData as $item) {
            if (isset($item['unique_barcode']) && $item['unique_barcode'] === $barcode) {
                echo "Barcode already added!";
                exit; // Stop further execution
            }
        }

        // Perform database query to get the barcode details
        $query = "SELECT s.unique_barcode, p.description, b.brand_name, s.parent_barcode, c.category_name, s.supplier, p.product_img
                FROM stocks s
                LEFT JOIN product p ON p.hashed_id = s.product_id
                LEFT JOIN brand b ON b.hashed_id = p.brand
                LEFT JOIN category c ON c.hashed_id = p.category
                WHERE s.unique_barcode = '$barcode' AND s.warehouse = '$selected_wh' AND s.item_status = 0
                LIMIT 1";

        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $description = $row['description'];
            $brand_name = $row['brand_name'];
            $category_name = $row['category_name'];
            $parent_barcode = $row['parent_barcode'];
            $product_image = $row['product_img'] ?? "../../assets/img/def_img.png";

            // Create new item data
            $newItem = [
                "unique_barcode" => $barcode,
                "description" => $description,
                "brand_name" => $brand_name,
                "category_name" => $category_name,
                "parent_barcode" => $parent_barcode,
                "product_img" => $product_image,
                "rack_id" => $rack_id,
                "rack_name" => $rack_name 
            ];

            // Append new item to existing data
            $existingData[] = $newItem;

            // JSON Encode the updated data
            $jsonData = json_encode($existingData);

            // Store in Session
            $_SESSION['scanned_transfer'] = $jsonData;

            // Store in Global Variable
            $GLOBALS['scanned_transfer'] = $jsonData;

            // Store in APCu (if available)
            if (function_exists('apcu_store')) {
                apcu_store('scanned_transfer', $jsonData);
            }

            // Output the JSON (for debugging or API response)
            echo "Added successfully!";
        } else {
            echo "Barcode maybe invalid or not the same supplier as the first entry";
        }
    }
}
?>
