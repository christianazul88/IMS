<?php
include "../config/database.php";
include "../config/on_session.php";

$audit_id = $_SESSION['audit_id'];

// Fetch audit details
$audit_query = "SELECT al.*, w.warehouse_name 
FROM audit_logs al 
LEFT JOIN warehouse w 
ON al.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci 
WHERE al.id = ?";

$stmt = $conn->prepare($audit_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$audit = $stmt->get_result()->fetch_assoc();
$stmt->close();

$today = date('Y-m-d');
$schedule_date = date('Y-m-d', strtotime($audit['schedule_date']));
$warehouse_id_audit = $audit['warehouse'];

if ($today < $schedule_date) {
    echo "<div class='alert alert-warning'>
        Audit is scheduled for " . date('M d, Y', strtotime($audit['schedule_date'])) . ".
    </div>";
    exit;
}

$selected_area = $_SESSION['selected_area'];

$json_file = "../audit_json/" . $audit_id . "-" . $selected_area . ".json";

$barcodes = [];

if (file_exists($json_file)) {
    $data = json_decode(file_get_contents($json_file), true);
    if (is_array($data)) {
        $barcodes = array_reverse($data);
        
        foreach ($barcodes as $row) {
            $barcode = $row['barcode'] ?? '';
            $outbounded = false;
            $belong_to_other_warehouse = false;
            $belong_to_other_location = false;
            $dont_belong_to_system_stocks = false;

            $unique_barcode = $row['barcode'];
            $current_location_id = $row['item_location'];
            $current_item_status = $row['item_status'];
            $current_warehouse_id = $row['warehouse'];

            $outbounded_plus = 0;
            $belong_to_other_wh_plus = 0;
            $belong_to_other_location_plus = 0;
            $dont_belong_to_system_stocks_plus = 0;

            //check if barcode exists in stocks
            $stock_query = "
                SELECT unique_barcode 
                FROM stocks 
                WHERE unique_barcode = ? 
                LIMIT 1";
            $stmt = $conn->prepare($stock_query);
            $stmt->bind_param("s", $unique_barcode);
            $stmt->execute();
            $stock_result = $stmt->get_result();
            if ($stock_result->num_rows === 0) {
                $dont_belong_to_system_stocks = true;
                $dont_belong_to_system_stocks_plus = 1;
                $dont_belong_to_system_stocks_yes_no = "yes";
            } else {
                $dont_belong_to_system_stocks_yes_no = "no";
            }



            //if item is outbounded
            if($current_item_status != 0 && $dont_belong_to_system_stocks === false){
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
                    $warehouse_return = $warehouse_id_audit;
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
                        $user_id,
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
                        $stmt_logs->bind_param("sss", $barcode, $currentDateTime, $user_id);

                        if ($stmt_logs->execute()) {

                            // UPDATE STOCK STATUS
                            $update_stock_status = "
                                UPDATE stocks 
                                SET item_status = 0,
                                    warehouse = ?
                                WHERE unique_barcode = ?
                            ";

                            $stmt_update_stock_status = $conn->prepare($update_stock_status);
                            $stmt_update_stock_status->bind_param("ss", $warehouse_id_audit, $barcode);
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
                                $user_id
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

                                echo "Outbound item returned successfully.";

                            }

                        }

                    }

                }
            }


            if($current_warehouse_id !== $warehouse_id_audit && $dont_belong_to_system_stocks === false){
                $belong_to_other_warehouse = true;

                $update_stock_wh_id = "
                    UPDATE stocks 
                    SET warehouse = ? 
                    WHERE unique_barcode = ?";
                $stmt = $conn->prepare($update_stock_wh_id);
                $stmt->bind_param("ss", $warehouse_id_audit, $unique_barcode);
                $stmt->execute();
                $stmt->close();
                $belong_to_other_wh_plus = 1;

            }

            if($current_location_id != $selected_area && $dont_belong_to_system_stocks === false){
                $belong_to_other_location = true;

                $update_stock_location_id = "
                    UPDATE stocks 
                    SET item_location = ? 
                    WHERE unique_barcode = ?";
                $stmt = $conn->prepare($update_stock_location_id);
                $stmt->bind_param("ss", $selected_area, $unique_barcode);
                $stmt->execute();
                $stmt->close();
                $belong_to_other_location_plus = 1;

            }


            if($outbounded === true){
                $outbounded_yes_no = "yes";
            } else {
                $outbounded_yes_no = "no";
            }






            //////////////////////////////////////
            // =========================================================
            // CHECK IF BARCODE EXISTS IN AUDIT
            // =========================================================
            $check_audit_items = "
                SELECT id, audit_status 
                FROM items_to_audit 
                WHERE audit_id = ? 
                AND unique_barcode = ? 
                LIMIT 1
            ";

            $stmt = $conn->prepare($check_audit_items);
            $stmt->bind_param("is", $audit_id, $barcode);
            $stmt->execute();

            $audit_items_result = $stmt->get_result();

            // =========================================================
            // ITEM NOT PART OF AUDIT
            // =========================================================
            if ($audit_items_result->num_rows === 0) {

                // Add to items_to_audit with scanned status
                $insert_item_audit = "
                    INSERT INTO items_to_audit 
                    (
                        audit_id, 
                        unique_barcode, 
                        audit_status,
                        scanned_date,
                        outbounded,
                        system_wh,
                        scanned_wh,
                        system_location,
                        scanned_location,
                        belong_to_system_stocks
                    ) 
                    VALUES (?, ?, 'scanned', NOW(), ?, ?, ?, ?, ?, ?)
                ";
                $stmt = $conn->prepare($insert_item_audit);
                $stmt->bind_param("issssiis", $audit_id, $barcode, $outbounded_yes_no, $current_warehouse_id, $warehouse_id_audit, $current_location_id, $selected_area, $dont_belong_to_system_stocks_yes_no);
                $stmt->execute();


        

                
                


            } else {
                // Update existing record to scanned if not already
                $row = $audit_items_result->fetch_assoc();

                if ($row['audit_status'] !== 'scanned') {
                    $update_audit_item = "
                        UPDATE items_to_audit 
                        SET audit_status = 'scanned',
                            scanned_date = NOW(),
                            outbounded = ?,
                            system_wh = ?,
                            scanned_wh = ?,
                            system_location = ?,
                            scanned_location = ?,
                            belong_to_system_stocks = ?
                        WHERE unique_barcode = ?
                        AND audit_id = ?
                    ";
                    $stmt = $conn->prepare($update_audit_item);
                    $stmt->bind_param("ssssiisi", $outbounded_yes_no, $current_warehouse_id, $warehouse_id_audit, $current_location_id, $selected_area, $dont_belong_to_system_stocks_yes_no, $barcode, $audit_id);
                    $stmt->execute();
                }
            }
            //////////////////////////////////////

            // =========================================================
            // UPDATE AUDIT SUMMARY
            // =========================================================
            $parts = explode("-", $barcode);
            $parent = $parts[0];

            $get_audit_item_info = "
                SELECT * 
                FROM audit_items 
                WHERE audit_id = ? 
                AND parent_barcode = ? 
                LIMIT 1
            ";

            $stmt = $conn->prepare($get_audit_item_info);
            $stmt->bind_param("is", $audit_id, $parent);
            $stmt->execute();

            $row = $stmt->get_result()->fetch_assoc();

            $expected_qty = $row['expected_qty'] ?? 0;

            $scanned_qty = ($row['scanned_qty'] ?? 0) + 1;

            $scanned_outbounded_qty = ($row['scanned_outbounded_qty'] ?? 0) + $outbounded_plus;

            $belong_to_other_wh_total = ($row['scanned_belong_to_other_wh'] ?? 0) + $belong_to_other_wh_plus;

            $belong_to_other_location_total = ($row['scanned_belong_to_other_location'] ?? 0) + $belong_to_other_location_plus;

            // $dont_belong_to_system_stocks_total = ($row['dont_belong_to_system_stocks_plus'] ?? 0) + $dont_belong_to_system_stocks_plus;

            $variance = $expected_qty - $scanned_qty;

            $unit_cost = $row['unit_cost'] ?? 0;

            $variance_value = $unit_cost * $variance;
            $scanned_value = $unit_cost * $scanned_qty;

            $update_audit_items = "
                UPDATE audit_items 
                SET scanned_qty = ?,
                    variance_qty = ?,
                    variance_value = ?,
                    scanned_value = ?,
                    scanned_outbounded_qty = ?,
                    scanned_belong_to_other_wh = ?,
                    scanned_belong_to_other_location = ?
                WHERE audit_id = ?
                AND parent_barcode = ?
            ";

            $stmt = $conn->prepare($update_audit_items);

            $stmt->bind_param(
                "iddddisii",
                $scanned_qty,
                $variance,
                $variance_value,
                $scanned_value,
                $scanned_outbounded_qty,
                $belong_to_other_wh_total,
                $belong_to_other_location_total,
                $audit_id,
                $parent

            );

            $stmt->execute();


        }
              
    }
}

$update_status_query = "UPDATE audit_assignments SET `status` = 'approved' WHERE audit_id = ? AND item_location = ?";
$stmt = $conn->prepare($update_status_query);
$stmt->bind_param("ii", $audit_id, $selected_area);
$stmt->execute();
$stmt->close();

header("Location: ../finish/?audit_id=" . $audit_id . "&area=" . $selected_area);
exit;
?>