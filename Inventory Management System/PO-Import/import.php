<?php
session_start();
include "../config/database.php";

$response = ["status" => "error", "message" => "Invalid request."];
$po_id = $_SESSION['inbound_po_id'];

// Namespace po_list by po_id so different POs never mix,
// and key each PO's items by barcode so duplicates are structurally impossible.
if (!isset($_SESSION['po_list'][$po_id])) {
    $_SESSION['po_list'][$po_id] = [];
}

// Check if `parent_barcodes` is set and not empty
if (isset($_POST['parent_barcodes']) && is_array($_POST['parent_barcodes'])) {
    $added = 0;

    $sql = "SELECT p.description, b.brand_name, c.category_name, p.parent_barcode 
            FROM product p 
            LEFT JOIN brand b ON b.hashed_id = p.brand 
            LEFT JOIN category c ON c.hashed_id = p.category 
            WHERE p.parent_barcode = ? LIMIT 1";
    $stmt = $conn->prepare($sql);

    foreach ($_POST['parent_barcodes'] as $parent_barcode) {
        // Sanitize input
        $parent_barcode = htmlspecialchars($parent_barcode);

        // Already staged for this PO? Skip - key lookup, not a loop.
        if (isset($_SESSION['po_list'][$po_id][$parent_barcode])) {
            continue;
        }

        // Fetch product details from database
        $stmt->bind_param("s", $parent_barcode);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $_SESSION['po_list'][$po_id][$parent_barcode] = [
                "description" => $row['description'],
                "brand"       => $row['brand_name'],
                "category"    => $row['category_name'],
                "barcode"     => $row['parent_barcode'],
                "qty"         => 0 // Default quantity to 0
            ];
            $added++;
        }
    }

    // If new items were added, report success
    if ($added > 0) {
        $response = ["status" => "success", "message" => "Products added successfully."];
    } else {
        $response = ["status" => "error", "message" => "Selected product(s) already exist."];
    }
}

// Return JSON response
echo json_encode($response);
?>
