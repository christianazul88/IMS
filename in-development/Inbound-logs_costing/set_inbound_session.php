<?php
session_start();
include "../config/database.php";

// Check if all required POST variables are set
if (isset($_POST['supplier'], $_POST['po_id'], $_POST['received_date'], $_POST['warehouse'])) {
    // Sanitize and save each POST value in SESSION with 'inbound_' prefix
    $_SESSION['inbound_supplier'] = htmlspecialchars($_POST['supplier']);
    $_SESSION['inbound_po_id'] = (int)$_POST['po_id'];  // Ensuring it's an integer
    $_SESSION['inbound_received_date'] = htmlspecialchars($_POST['received_date']);
    $_SESSION['inbound_warehouse'] = htmlspecialchars($_POST['warehouse']);

    // Redirect to the next step
    header("Location: ../inbound-select-products/");
    exit();

} elseif (isset($_POST['po_id'], $_POST['received_date'])) {

    // Ensure `po_list` exists in session
    if (!isset($_SESSION['po_list'])) {
        $_SESSION['po_list'] = [];
    }

    $_SESSION['inbound_po_id'] = (int)$_POST['po_id'];  // Ensuring it's an integer
    $_SESSION['inbound_received_date'] = htmlspecialchars($_POST['received_date']);

    $po_id = $_SESSION['inbound_po_id'];

    // Fetch product IDs from the purchased order content table
    $query = "SELECT product_id, qty FROM purchased_order_content WHERE po_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $po_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $product_id = $row['product_id'];
        $quantity = $row['qty'];
        // Fetch product details
        $sql = "SELECT p.description, b.brand_name, c.category_name, p.parent_barcode 
                FROM product p 
                LEFT JOIN brand b ON b.hashed_id = p.brand 
                LEFT JOIN category c ON c.hashed_id = p.category 
                WHERE p.hashed_id = ? LIMIT 1";
        
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param("s", $product_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        
        if ($product_data = $result->fetch_assoc()) {
            // Append the fetched data to the session array
            $_SESSION['po_list'][] = [
                "description" => $product_data['description'],
                "brand" => $product_data['brand_name'],
                "category" => $product_data['category_name'],
                "barcode" => $product_data['parent_barcode'],
                "qty" => $quantity
            ];
        }
    }

    // Redirect to the next step
    header("Location: ../PO-Import/");
    exit();

} else {
    // Redirect back to the form or show an error message if required fields are missing
    echo "Error: Please fill in all required fields.";
}
