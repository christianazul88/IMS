<?php
include "../config/database.php";
include "../config/on_session.php";
header('Content-Type: application/json'); // Set JSON response type

// Check if all required fields are set
if (isset($_POST['parent'], $_POST['start'], $_POST['end'])) {
    $parent = $_POST['parent']; // String
    $start = (int) $_POST['start']; // Convert to int
    $end = (int) $_POST['end']; // Convert to int

    if ($start > $end) {
        echo json_encode(['status' => 'error', 'message' => 'Start must be less than or equal to End.']);
        $conn->close();
        exit;
    }
    $count = 1;
    // Define an empty array for storing data
    $data = [];

    // Prepared statement for data retrieval
    $query = "SELECT stocks.*, product.description, category.category_name, brand.brand_name, product.product_img, product.hashed_id AS product_id
              FROM stocks
              LEFT JOIN product ON product.hashed_id = stocks.product_id
              LEFT JOIN category ON category.hashed_id = product.category
              LEFT JOIN brand ON brand.hashed_id = product.brand
              WHERE stocks.parent_barcode = ? AND stocks.barcode_extension BETWEEN ? AND ? AND stocks.item_status = 0
              ORDER BY product.id DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $parent, $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $item = [
                'image' => $row['product_img'],
                'product_id' => $row['product_id'],
                'unique_barcode' => $row['unique_barcode'],
                'warehouse' => $row['warehouse'],
                'description' => $row['description'],
                'brand_name' => $row['brand_name'],
                'category_name' => $row['category_name']
            ];

            $data[] = $item;
            if($count < 500){
                $count ++;
            } else {
                break;
            }
        }
    }

    $stmt->close();
    $conn->close();

    // Initialize session variable if not set
    if (!isset($_SESSION['stored_data'])) {
        $_SESSION['stored_data'] = [];
    }

    // Prevent duplicate entries in session
    foreach ($data as $newItem) {
        $exists = false;

        foreach ($_SESSION['stored_data'] as $existingItem) {
            if ($existingItem['unique_barcode'] === $newItem['unique_barcode']) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $_SESSION['stored_data'][] = $newItem;
        }
    }
    if($count < 10){
        // Return updated session data
        echo json_encode(['status' => 'success', 'data' => 'data added successfully! maximum of 500 data only']);
    } else {
        // Return updated session data
        echo json_encode(['status' => 'success', 'data' => 'data added successfully!']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid form submission.']);
}
?>
