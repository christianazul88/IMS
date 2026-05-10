<?php
session_start();
include "../config/database.php";

$response = ["status" => "error", "message" => "Invalid request."];

// Ensure `po_list` exists in session
if (!isset($_SESSION['po_list'])) {
    $_SESSION['po_list'] = [];
}

// Check if `parent_barcodes` is set and not empty
if (isset($_POST['parent_barcodes']) && is_array($_POST['parent_barcodes'])) {
    $new_items = [];

    foreach ($_POST['parent_barcodes'] as $parent_barcode) {
        // Sanitize input
        $parent_barcode = htmlspecialchars($parent_barcode);

        // Check if barcode already exists in the session
        $exists = false;
        foreach ($_SESSION['po_list'] as $item) {
            if ($item['barcode'] === $parent_barcode) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            // Fetch product details from database
            $sql = "SELECT p.description, b.brand_name, c.category_name, p.parent_barcode, p.hashed_id AS product_id
                    FROM product p 
                    LEFT JOIN brand b ON b.hashed_id = p.brand 
                    LEFT JOIN category c ON c.hashed_id = p.category 
                    WHERE p.parent_barcode = ? LIMIT 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $parent_barcode);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                // Append product details to session
                $new_items[] = [
                    "id" => $row['product_id'],
                    "description" => $row['description'],
                    "brand" => $row['brand_name'],
                    "category" => $row['category_name'],
                    "barcode" => $row['parent_barcode'],
                    "qty" => 0 // Default quantity to 1
                ];
            }
        }
    }

    // If new items were added, push them to session
    if (!empty($new_items)) {
        $_SESSION['po_list'] = array_merge($_SESSION['po_list'], $new_items);
        $response = ["status" => "success", "message" => "Products added successfully."];
        
    } else {
        $response = ["status" => "error", "message" => "Selected product(s) already exist."];
    }
}

// Return JSON response
echo json_encode($response);
?>
