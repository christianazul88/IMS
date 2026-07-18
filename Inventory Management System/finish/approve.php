<?php
include "../config/database.php";
include "../config/on_session.php";

$audit_id = $_SESSION['audit_id'];
$audit_assignment_id = $_GET['id'];
$staff_id = $_GET['user'];
$selected_area = $_GET['area'];
$barcodes = [];

$barcode_query = "
    SELECT  unique_barcode AS barcode,
            warehouse_origin,
            warehouse_onscanned,
            item_location_origin,
            item_location_onscanned,
            outbounded
    FROM items_to_audit
    WHERE audit_id = ?
        AND audit_assignment_id = ?
        AND user_id = ?
        AND audit_status = 'scanned'
    ORDER BY scanned_date ASC
";

$stmt = $conn->prepare($barcode_query);
$stmt->bind_param("iis", $audit_id, $audit_assignment_id, $staff_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $barcodes[] = $row;
}

$stmt->close();

foreach ($barcodes as $row) {
    $barcode = $row['barcode'] ?? '';

    $unique_barcode = $row['barcode'];

    $current_warehouse_id = $row['warehouse_origin'];
    $onscanned_warehouse_id = $row['warehouse_onscanned'];
    $current_location_id = $row['item_location_origin'];
    $onscanned_location_id = $row['item_location_onscanned'];
    $outbounded_value = $row['outbounded'];



    if($outbounded_value === "no"){
        $outbounded = false;
    } else {
        $outbounded = true;
    }
    
    
    
    
    

    $update_items_to_audit_query = "
        UPDATE items_to_audit
        SET
            audit_status = 'approved'
        WHERE unique_barcode = ?
        AND audit_id = ?
    ";

    $stmt = $conn->prepare($update_items_to_audit_query);
    $stmt->bind_param("si", $unique_barcode, $audit_id);
    $stmt->execute();
    // $stmt->close();
    



    //if item is outbounded
    if($outbounded_value === "yes" ){
        $outbounded = true;

        $outbounded_plus = 1;
        $stock_query = "
            SELECT hashed_id 
            FROM outbound_content 
            WHERE unique_barcode = ? 
            AND status = 6 
            ORDER BY id DESC
            LIMIT 1
        ";

        $stmt = $conn->prepare($stock_query);
        $stmt->bind_param("s", $barcode);
        $stmt->execute();

        $stock_result = $stmt->get_result();

        if ($stock_result->num_rows > 0) {

            $outbounded = true;
            $outbound_id = $stock_result->fetch_assoc()['hashed_id'];

            // =========================================================
            // RETURN PROCESS
            // =========================================================
            $amount = 0;
            $warehouse_return = $onscanned_warehouse_id;
            $reason = "OUTBOUND ITEM";
            $fault = "CLIENT FAULT";
            $fault_type = "DELIVERY FAILED";

            // GET SUPPLIER TYPE
            $supplier_info = "
                SELECT sup.local_international 
                FROM stocks s 
                LEFT JOIN supplier sup 
                    ON sup.hashed_id = s.supplier 
                WHERE s.unique_barcode = ? 
                LIMIT 1
            ";

            $stmt_supplier = $conn->prepare($supplier_info);
            $stmt_supplier->bind_param("s", $barcode);
            $stmt_supplier->execute();

            $supplier_info_res = $stmt_supplier->get_result();

            $supplier_type = "unset";

            if ($supplier_info_res->num_rows > 0) {

                $row = $supplier_info_res->fetch_assoc();
                $supplier_type = $row['local_international'] ?? 'unset';

            }

            // VALIDATION
            if (
                ($fault_type === "DELIVERY FAILED" && $fault === "CLIENT FAULT") ||
                ($fault_type === "DELIVERY FAILED" && $fault === "SELLER FAULT") ||
                ($fault_type === "DEFECTIVE" && $fault === "NONE") ||
                ($fault_type === "WRONG ITEM ORDER" && $fault === "NONE")
            ) {

                $good = true;

            } else {

                $good = false;

            }

            if ($good === false) {

                header("Location: ../Create-Return/?success=fault_invalid");
                exit;

            }

            // INSERT RETURN
            $insert = "
                INSERT INTO returns 
                (
                    unique_barcode, 
                    amount, 
                    date, 
                    user_id, 
                    warehouse, 
                    reason, 
                    fault, 
                    fault_type, 
                    supplier_type
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conn->prepare($insert);

            $stmt->bind_param(
                "sdsssssss",
                $barcode,
                $amount,
                $currentDateTime,
                $staff_id,
                $warehouse_return,
                $reason,
                $fault,
                $fault_type,
                $supplier_type
            );

            if ($stmt->execute()) {

                // INSERT STOCK TIMELINE
                $item_logs = "
                    INSERT INTO stock_timeline 
                    (
                        unique_barcode, 
                        title, 
                        action, 
                        date, 
                        user_id
                    ) 
                    VALUES 
                    (
                        ?, 
                        'PRODUCT RETURN', 
                        'Product was returned.', 
                        ?, 
                        ?
                    )
                ";

                $stmt_logs = $conn->prepare($item_logs);
                $stmt_logs->bind_param("sss", $barcode, $currentDateTime, $staff_id);

                if ($stmt_logs->execute()) {

                    // UPDATE STOCK STATUS
                    $update_stock_status = "
                        UPDATE stocks 
                        SET item_status = 0,
                            warehouse = ?
                        WHERE unique_barcode = ?
                    ";

                    $stmt_update_stock_status = $conn->prepare($update_stock_status);
                    $stmt_update_stock_status->bind_param("ss", $onscanned_warehouse_id, $barcode);
                    $stmt_update_stock_status->execute();

                    // INSERT LOGS
                    $logs = "
                        INSERT INTO logs 
                        (
                            title, 
                            action, 
                            date, 
                            user_id
                        ) 
                        VALUES 
                        (
                            'PRODUCT RETURN', 
                            ?, 
                            ?, 
                            ?
                        )
                    ";

                    $stmt_log = $conn->prepare($logs);

                    $log_action = "$barcode was returned.";

                    $stmt_log->bind_param(
                        "sss",
                        $log_action,
                        $currentDateTime,
                        $staff_id
                    );

                    if ($stmt_log->execute()) {

                        // UPDATE OUTBOUND LOGS
                        $update_outbound_logs = "
                            UPDATE outbound_logs 
                            SET status = 1 
                            WHERE hashed_id = ?
                        ";

                        $stmt_outbound_logs = $conn->prepare($update_outbound_logs);
                        $stmt_outbound_logs->bind_param("s", $outbound_id);
                        $stmt_outbound_logs->execute();

                        // UPDATE OUTBOUND CONTENT
                        $update_outbound_content = "
                            UPDATE outbound_content 
                            SET status = 1 
                            WHERE unique_barcode = ?
                        ";

                        $stmt_outbound_content = $conn->prepare($update_outbound_content);
                        $stmt_outbound_content->bind_param("s", $barcode);
                        $stmt_outbound_content->execute();

                        // echo "Outbound item returned successfully.";

                    }

                }

            }

        }
    }


    $belong_to_other_warehouse = true;

    $update_stock_wh_id = "
        UPDATE stocks 
        SET warehouse = ? 
        WHERE unique_barcode = ?";
    $stmt = $conn->prepare($update_stock_wh_id);
    $stmt->bind_param("ss", $onscanned_warehouse_id, $unique_barcode);
    $stmt->execute();
    $stmt->close();


    $belong_to_other_location = true;

    $update_stock_location_id = "
        UPDATE stocks 
        SET item_location = ? 
        WHERE unique_barcode = ?";
    $stmt = $conn->prepare($update_stock_location_id);
    $stmt->bind_param("is", $onscanned_location_id, $unique_barcode);
    $stmt->execute();
    $stmt->close();


}

$get_audit_assignment_Staff_id = "
    SELECT id
    FROM audit_assignment_staffs
    WHERE audit_assignments_id = ?
    AND user_id = ?
    AND `status` = 'for_approval'
    ORDER BY id DESC
    LIMIT 1
";

$stmt = $conn->prepare($get_audit_assignment_Staff_id);
$stmt->bind_param("is", $audit_assignment_id, $staff_id);
$stmt->execute();

$result = $stmt->get_result();

$audit_assignment_staff_id = null;

if ($result->num_rows > 0) {
    $audit_assignment_staff_id = $result->fetch_assoc()['id'];
} else {
    $audit_assignment_staff_id = "NULL";
}


$update_audit_assignment_staffs = "
    UPDATE audit_assignment_staffs
    SET `status` = 'approved'
    WHERE audit_assignments_id = ?
    AND user_id = ?
    AND id = ?
";

$stmt = $conn->prepare($update_audit_assignment_staffs);
$stmt->bind_param("isi", $audit_assignment_id, $staff_id, $audit_assignment_staff_id);
$stmt->execute();
$stmt->close();


header("Location: ../finish/?audit_id=" . $audit_id . "&area=" . $selected_area . "&user_id=" . $staff_id);
exit;
?>