<?php
include "database.php";
include "on_session.php";
if(isset($_SESSION['warehouse_for_transfer'])){
    $warehouse_for_transfer = $_SESSION['warehouse_for_transfer'];

    $warehouse_sql = "SELECT hashed_id FROM warehouse WHERE warehouse_name = '$warehouse_for_transfer' LIMIT 1";
    $res = $conn->query($warehouse_sql);
    if($res->num_rows>0){
        $row = $res->fetch_assoc();
        $warehouse_for_transfer = $row['hashed_id'];
    }

}


$status = $_GET['status'];
if($status === "pending"){
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // echo $warehouse_for_transfer;
        $stock_transfer_query = "SELECT * FROM stock_transfer";
        $result = $conn->query($stock_transfer_query);
        if($result->num_rows>0){
            $create_stock_transfer = "INSERT INTO stock_transfer SET from_warehouse = '$warehouse_for_transfer', `status` = '$status', from_userid = '$user_id'";
            if($conn->query($create_stock_transfer) === TRUE){
                $stock_transferID = $conn->insert_id;
            }
        } else {
            $create_stock_transfer = "INSERT INTO stock_transfer SET id = 10000,from_warehouse = '$warehouse_for_transfer', `status` = '$status', from_userid = '$user_id'";
            if($conn->query($create_stock_transfer) === TRUE){
                $stock_transferID = $conn->insert_id;
            }
        }
        $action = "#" . $stock_transferID . " has been successfully created on pending status.";
        $logs_sql = "INSERT INTO logs SET title = 'STOCK TRANSFER', `action` = '$action', `date` = '$currentDateTime', user_id = '$user_id'";
        if($conn->query($logs_sql) === TRUE){
            echo "successfully inserted to logs<br>";
        }
        
        // Check if any checkboxes are selected
        if (isset($_POST['unique_barcode']) && is_array($_POST['unique_barcode'])) {
            // Loop through each selected checkbox
            foreach ($_POST['unique_barcode'] as $selectedProductId) {
                // Retrieve data associated with the selected product id
                $product_key = array_search($selectedProductId, $_POST['unique_barcode']);
                $unique_barcode = $_POST['unique_barcode'][$product_key];

                $update_stock = "UPDATE stocks SET item_status = 3 WHERE unique_barcode = '$unique_barcode'";
                if($conn->query($update_stock) === TRUE ){
                    echo "<br>successfully updated.<br>";
                }

                $content = "INSERT INTO stock_transfer_content SET unique_barcode = '$unique_barcode', st_id = '$stock_transferID', `status` = 'pending'";
                if($conn->query($content) === TRUE){
                    echo $unique_barcode . " successfully inserted.<br>";
                    $stock_action = "Is pending for transfer. transfer #" . $stock_transferID;
                    $stock_timeline_query = "INSERT INTO stock_timeline SET unique_barcode = '$unique_barcode', title = 'STOCK TRANSFER', `action` = '$stock_action', `date` = '$currentDateTime', user_id = '$user_id'";
                    if($conn->query($stock_timeline_query) === TRUE){
                        echo "<br>successfully inserted to stock_timeline<br>";
                        header("Location: ../Stock-transfer-logs/?pending=success");
                    }
                } 
                
            }
        }
    }
} elseif($status === "enroute"){

    // Check if the request is a POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Validate the inputs
        $id = $conn->real_escape_string($_POST['id'] ?? '');

        if(isset($_POST['to_warehouse'])){
            $toWarehouse = $conn->real_escape_string($_POST['to_warehouse'] ?? '');
            $remarks = $_POST['remarks_sender'];
            $warehousename_sql = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$toWarehouse' LIMIT 1";
            $result = $conn->query($warehouse_sql);
            $row = $result->fetch_assoc();
            $toWarehouse_name = $row['warehouse_name'];
            $update = "UPDATE stock_transfer SET to_warehouse = '$toWarehouse', `status` = 'enroute', date_out = '$currentDateTime', remarks_sender	= '$remarks' WHERE id = '$id'";
            if($conn->query($update) === TRUE){
                $action = "#" . $stock_transferID . " is now enroute to " . $toWarehouse_name  . ".";
                $logs_sql = "INSERT INTO logs SET title = 'STOCK TRANSFER', `action` = '$action', `date` = '$currentDateTime', user_id = '$user_id'";
                if($conn->query($logs_sql) === TRUE){
                    $stock_transfer_content_items_sql = "SELECT unique_barcode FROM stock_transfer_content WHERE st_id = '$id'";
                    $result = $conn->query($stock_transfer_content_items_sql);
                    while($row=$result->fetch_assoc()){
                        $unique_barcode = $row['unique_barcode'];

                        $update_stock = "UPDATE stocks SET item_status = 2, item_location = '' WHERE unique_barcode = '$unique_barcode'";
                        if($conn->query($update_stock) === TRUE){
                            $stock_action = "Is now enroute to " . $toWarehouse_name . ". transfer #" . $id;
                            $stock_timeline_query = "INSERT INTO stock_timeline SET unique_barcode = '$unique_barcode', title = 'STOCK TRANSFER', `action` = '$stock_action', `date` = '$currentDateTime', user_id = '$user_id'";
                            if($conn->query($stock_timeline_query) === TRUE){
                                // echo "<br>successfully inserted to stock_timeline<br>";
                                // header("Location: ../Stock-transfer-logs/?enroute=success");
                            }
                        }
                        
                    }
                }
    

            }
        } else {
            echo "to warehouse is not set";
        }

        if(isset($_POST['receiver_userid'])){
            $receiverUserId = $conn->real_escape_string($_POST['receiver_userid'] ?? '');
            $remarks = $_POST['remarks_receiver'];
            $receiver_warehouse = $_POST['receiver_warehouse'];
            $warehousename_sql = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$receiver_warehouse' LIMIT 1";
            $result = $conn->query($warehousename_sql);
            $row = $result->fetch_assoc();
            $toWarehouse_name = $row['warehouse_name'];
            $update = "UPDATE stock_transfer SET received_userid = '$receiverUserId', `status` = 'received', date_received = '$currentDateTime', remarks_receiver = '$remarks' WHERE id = '$id'";
            if($conn->query($update) === TRUE){
                $action = "#" . $stock_transferID . " has been received by " . $user_fullname  . ".";
                $logs_sql = "INSERT INTO logs SET title = 'STOCK TRANSFER', `action` = '$action', `date` = '$currentDateTime', user_id = '$user_id'";
                if($conn->query($logs_sql) === TRUE){
                    $stock_transfer_content_items_sql = "SELECT unique_barcode FROM stock_transfer_content WHERE st_id = '$id'";
                    $result = $conn->query($stock_transfer_content_items_sql);
                    while($row=$result->fetch_assoc()){
                        $unique_barcode = $row['unique_barcode'];

                        $update_stock = "UPDATE stocks SET item_status = 0, warehouse = '$receiver_warehouse', item_location = '' WHERE unique_barcode = '$unique_barcode'";
                        if($conn->query($update_stock) === TRUE){
                            $stock_action = "has been received by " . $toWarehouse_name . ". transfer #" . $id;
                            $stock_timeline_query = "INSERT INTO stock_timeline SET unique_barcode = '$unique_barcode', title = 'STOCK TRANSFER', `action` = '$stock_action', `date` = '$currentDateTime', user_id = '$user_id'";
                            if($conn->query($stock_timeline_query) === TRUE){
                                
                            }
                        }
                        
                    }
                }

            }
        }

        // echo "<br>successfully inserted to stock_timeline<br>";
        header("Location: ../Stock-transfer-logs/?transfer=success");
    } else {
        echo "<p class='text-danger'>Invalid request method.</p>";
    }

}