<?php
include "database.php";
include "on_session.php";

// Check for required session data
if (!isset($_SESSION['inbound_po_id']) || !isset($_SESSION['user_id'])) {
    die("Session data missing.");
}

// Initialize session values
$user_id = $_SESSION['user_id'];
$session_value = $_SESSION['inbound_po_id'];
$json_filename = "../jsons/inbound-by" . $user_id . "-PO" . $session_value . ".json"; // Save JSON in the ../jsons/ folder

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate data from POST
    
    $rcvd_qty = $_POST['rcvd_qty'] ?? null;

    if (isset($_POST['parent_barcode'])) {
        $parent_barcode = $_POST['parent_barcode'] ?? null;

        $product_query = "SELECT p.*, c.category_name, b.brand_name
                          FROM product p
                          LEFT JOIN category c ON c.id = p.category
                          LEFT JOIN brand b ON b.id = p.brand
                          WHERE p.parent_barcode = '$parent_barcode'";
        $product_res = $conn->query($product_query);
        if ($product_res->num_rows > 0) {
            $row = $product_res->fetch_assoc();
            $product_id = $row['id'];
            $product_desc = $row['description'];
            $category_name = $row['category_name'];
            $brand_name = $row['brand_name'];
        }
    } elseif (isset($_POST['product_id'])) {
        $product_id = $_POST['product_id'] ?? null;
        $product_query = "SELECT p.*, c.category_name, b.brand_name
                          FROM product p
                          LEFT JOIN category c ON c.id = p.category
                          LEFT JOIN brand b ON b.id = p.brand
                          WHERE p.id = '$product_id'";
        $product_res = $conn->query($product_query);
        if ($product_res->num_rows > 0) {
            $row = $product_res->fetch_assoc();
            $product_desc = $row['description'];
            $category_name = $row['category_name'];
            $brand_name = $row['brand_name'];
            $parent_barcode = $row['parent_barcode'];
        }
    }


    // Structure data for JSON
    $data = [
        'product_id' => $product_id,
        'product_desc' => $product_desc,
        'keyword' => "",
        'ordered_qty' => 0,
        'received_qty' => (int)$rcvd_qty,
        'price' => 0,
        'barcode' => $parent_barcode,
        'batch_num' => "",
        'brand_name' => $brand_name,
        'category_name' => $category_name
    ];

    // Load existing data if JSON file exists, else initialize an empty array
    if (file_exists($json_filename)) {
        $json_data = json_decode(file_get_contents($json_filename), true);
        if (!is_array($json_data)) {
            $json_data = [];
        }
    } else {
        $json_data = [];
    }

    // Append new data to the existing data array
    $json_data[] = $data;

    // Save the updated data back to the JSON file
    if (file_put_contents($json_filename, json_encode($json_data, JSON_PRETTY_PRINT))) {
        echo "Data saved successfully.";
    } else {
        echo "Error saving data.";
    }
    
} else {
    echo "Invalid request method.";
}
?>
