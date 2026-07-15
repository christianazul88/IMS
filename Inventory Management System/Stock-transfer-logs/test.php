<?php
include "../config/database.php";
include "../config/on_session.php";


$Stock_sql = "
    SELECT
        *
    FROM stock_transfer
    WHERE date_out <= DATE_SUB(NOW(), INTERVAL 3 DAY)
        AND status = 'pending'
    GROUP BY id
    ORDER BY id DESC
";

$result = $conn->query($Stock_sql);

if($result->num_rows>0){
    while($row=$result->fetch_assoc()){
        $stock_transfer_id = $row['id'];
        $to_warehouse = $row['to_warehouse'];
        $to_rack = $row['to_rack'] ?? 0;
        
        $receiverUserId = '6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b';
        $remarks = "Received automatically by the system for being pending for 3 days.";
        $receiver_warehouse = $to_warehouse;
        $warehousename_sql = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$receiver_warehouse' LIMIT 1";
        $result = $conn->query($warehousename_sql);
        $row = $result->fetch_assoc();
        $toWarehouse_name = $row['warehouse_name'];
        $update = "UPDATE stock_transfer SET received_userid = '$receiverUserId', `status` = 'received', date_received = '$currentDateTime', remarks_receiver = '$remarks' WHERE id = '$stock_transfer_id'";
        if($conn->query($update) === TRUE){
            $action = "#" . $stock_transfer_id . " has been received by system for staying enroute for more than 3 days.";
            $logs_sql = "INSERT INTO logs SET title = 'STOCK TRANSFER', `action` = '$action', `date` = '$currentDateTime', user_id = '$user_id'";
            if($conn->query($logs_sql) === TRUE){
                $stock_transfer_content_items_sql = "SELECT unique_barcode FROM stock_transfer_content WHERE st_id = '$stock_transfer_id'";
                $result = $conn->query($stock_transfer_content_items_sql);
                while($row=$result->fetch_assoc()){
                    $unique_barcode = $row['unique_barcode'];

                    $update_stock = "UPDATE stocks SET item_status = 0, warehouse = '$receiver_warehouse', item_location = '$to_rack' WHERE unique_barcode = '$unique_barcode'";
                    if($conn->query($update_stock) === TRUE){
                        $stock_action = "has been received by " . $toWarehouse_name . "(System Received). transfer #" . $stock_transfer_id;
                        $stock_timeline_query = "INSERT INTO stock_timeline SET unique_barcode = '$unique_barcode', title = 'STOCK TRANSFER', `action` = '$stock_action', `date` = '$currentDateTime', user_id = '$user_id'";
                        if($conn->query($stock_timeline_query) === TRUE){
                            echo "success<br>";
                        }
                    }
                    
                }
            }

        }
    }
}
