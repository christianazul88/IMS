<?php
include "database.php";
include "on_session.php";
$order_Date = $_SESSION['po_order_date'];
$warehouse_selected = $_SESSION['selected_warehouse_id'];

$success = 0;

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if any checkboxes are selected
    if (isset($_POST['product_id']) && is_array($_POST['product_id'])) {
        // Loop through each selected checkbox
        // Count the number of product_id[] items submitted
        $product_count = count($_POST['product_id']);
        $supplier = $_POST['supplier'];
        // Display the count

        $insert_po = "INSERT INTO purchased_order SET warehouse = '$warehouse_selected', supplier = '$supplier', `status` = 0, date_order = '$order_Date', user_id = '$user_id'";
        if($conn->query($insert_po) === TRUE){
            $po_id = $conn->insert_id;
            foreach ($_POST['product_id'] as $selectedProductId) {
                // Retrieve data associated with the selected product id
                $product_key = array_search($selectedProductId, $_POST['product_id']);
                $product_id = $_POST['product_id'][$product_key];
                $product_des = $_POST['product_desc'][$product_key];
                $product_pbarcode = $_POST['parent_barcode'][$product_key];
                $product_brand = $_POST['brand'][$product_key];
                $product_category = $_POST['category'][$product_key];
                $current_stock = $_POST['order_qty'][$product_key];

                
                

                $insert_po_content = "INSERT INTO purchased_order_content SET po_id = '$po_id', product_id = '$product_id', qty = '$current_stock'";
                if($conn->query($insert_po_content) === TRUE){
                    echo "New record created successfully";
                    $success += 1;

                }
                

                
            }
        }
    }
}

// echo "<br>" . $success;
if($success == $product_count){
    $conn->close();
    unset($_SESSION['po_list']);
    header("Location: ../PO-pending/?success=true&elcoco=$po_id");
    exit;
} else {
    $conn->close();
    $_SESSION['new_po'] = "true";
    header("Location: ../PO-logs/?success=false");
    exit;
}   