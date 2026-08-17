<?php
include "../config/database.php";
include "../config/on_session.php"; // must exit/redirect if user is not authenticated

header('Content-Type: application/json');

$response = ["status" => "error", "message" => "Invalid request."];

// --- CSRF check ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(["status" => "error", "message" => "Invalid session token. Please refresh the page."]);
    exit;
}

// Ensure `po_list` exists in session
if (!isset($_SESSION['po_list'])) {
    $_SESSION['po_list'] = [];
}

// Check if `parent_barcodes` is set and not empty
if (isset($_POST['parent_barcodes']) && is_array($_POST['parent_barcodes'])) {
    $new_items = [];

    foreach ($_POST['parent_barcodes'] as $parent_barcode) {
        // Validate format instead of HTML-encoding (this value is used in a
        // DB lookup, not rendered as HTML — htmlspecialchars() here was the
        // wrong tool and could break legitimate lookups for barcodes
        // containing &, ', etc.)
        if (!preg_match('/^[A-Za-z0-9\-_]+$/', $parent_barcode)) {
            continue;
        }

        // Check if barcode already exists in the session
        $exists = false;
        foreach ($_SESSION['po_list'] as $item) {
            if (hash_equals($item['barcode'], $parent_barcode)) {
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
                // Append product details to session (escape only at render time in preview.php)
                $new_items[] = [
                    "id" => $row['product_id'],
                    "description" => $row['description'],
                    "brand" => $row['brand_name'],
                    "category" => $row['category_name'],
                    "barcode" => $row['parent_barcode'],
                    "qty" => 0 // Default quantity to 0
                ];
            }
        }
    }

    // If new items were added, push them to session
    if (!empty($new_items)) {
        $_SESSION['po_list'] = array_merge($_SESSION['po_list'], $new_items);
        $response = ["status" => "success", "message" => "Products added successfully."];
    } else {
        $response = ["status" => "error", "message" => "Selected product(s) already exist or were invalid."];
    }
}

// Return JSON response
echo json_encode($response);
?>
