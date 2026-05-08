<?php

include "../config/database.php";
include "../config/on_session.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_POST['barcode'])) {
        die("No barcode provided.");
    }

    $barcode = trim($_POST['barcode']);
    $audit_id = $_SESSION['audit_id'];
    $outbounded = false;
    $selected_area = $_SESSION['selected_area'];

    // =========================================================
    // JSON FILE SETUP
    // =========================================================
    $json_folder = "../audit_json/";

    if (!is_dir($json_folder)) {
        mkdir($json_folder, 0777, true);
    }

    $json_filename = $audit_id . "-" . $selected_area . ".json";
    $json_path = $json_folder . $json_filename;

    // CREATE FILE IF NOT EXIST
    if (!file_exists($json_path)) {

        file_put_contents($json_path, json_encode([]));

    }

    // LOAD EXISTING JSON DATA
    $json_data = json_decode(file_get_contents($json_path), true);

    // SAFETY CHECK
    if (!is_array($json_data)) {
        $json_data = [];
    }

    // =========================================================
    // GET AUDIT WAREHOUSE
    // =========================================================
    $audit_query = "
        SELECT w.warehouse_name 
        FROM audit_logs al 
        LEFT JOIN warehouse w 
            ON al.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci 
        WHERE al.id = ? 
        LIMIT 1
    ";

    $stmt = $conn->prepare($audit_query);
    $stmt->bind_param("i", $audit_id);
    $stmt->execute();

    $audit_result = $stmt->get_result();

    if ($audit_result->num_rows === 0) {
        die("Audit not found.");
    }

    $audit = $audit_result->fetch_assoc();
    $warehouse_id_audit = $audit['warehouse_name'];

    // =========================================================
    // CHECK IF ITEM IS OUTBOUNDED
    // =========================================================
    $stock_query = "
        SELECT hashed_id 
        FROM outbound_content 
        WHERE unique_barcode = ? 
        AND status = 6 
        LIMIT 1
    ";

    $stmt = $conn->prepare($stock_query);
    $stmt->bind_param("s", $barcode);
    $stmt->execute();

    $stock_result = $stmt->get_result();

    if ($stock_result->num_rows > 0) {

        $outbounded = true;
        $outbound_id = $stock_result->fetch_assoc()['hashed_id'];

    }

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

        if ($outbounded === false) {

            echo "Scan successful for barcode: " . htmlspecialchars($barcode) . " but item is not part of this audit.";

        } else {

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

    } else {

        // =========================================================
        // ITEM EXISTS IN AUDIT
        // =========================================================
        $audit_item = $audit_items_result->fetch_assoc();

        // =========================================================
        // CHECK IF ALREADY SCANNED IN DATABASE
        // =========================================================
        if ($audit_item['audit_status'] === 'scanned') {

            echo "DENIED: Barcode already scanned.";
            exit;

        }

        // =========================================================
        // CHECK IF BARCODE ALREADY EXISTS IN JSON
        // =========================================================
        if (in_array($barcode, $json_data)) {

            echo "DENIED: Barcode already scanned.";
            exit;

        }

        // =========================================================
        // UPDATE ITEM TO SCANNED
        // =========================================================
        $update_item_audit = "
            UPDATE items_to_audit 
            SET audit_status = 'scanned' 
            WHERE audit_id = ? 
            AND unique_barcode = ?
        ";

        $stmt = $conn->prepare($update_item_audit);
        $stmt->bind_param("is", $audit_id, $barcode);

        if ($stmt->execute()) {

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

            $expected_qty = $row['expected_qty'];
            $scanned_qty = $row['scanned_qty'] + 1;

            $variance = $expected_qty - $scanned_qty;

            $variance_value = $row['unit_cost'] * $variance;
            $scanned_value = $row['unit_cost'] * $scanned_qty;

            $update_audit_items = "
                UPDATE audit_items 
                SET scanned_qty = ?,
                    variance_qty = ?,
                    variance_value = ?,
                    scanned_value = ?
                WHERE audit_id = ?
                AND parent_barcode = ?
            ";

            $stmt = $conn->prepare($update_audit_items);

            $stmt->bind_param(
                "idddis",
                $scanned_qty,
                $variance,
                $variance_value,
                $scanned_value,
                $audit_id,
                $parent
            );

            $stmt->execute();

            // =========================================================
            // SAVE SUCCESSFUL SCAN TO JSON
            // =========================================================
            $json_data[] = $barcode;

            // REMOVE DUPLICATES
            $json_data = array_unique($json_data);

            // SAVE BACK TO FILE
            file_put_contents(
                $json_path,
                json_encode(array_values($json_data), JSON_PRETTY_PRINT)
            );

            echo "Scan successful for barcode: " . htmlspecialchars($barcode);

        } else {

            echo "Failed to update scan status for barcode: " . htmlspecialchars($barcode);

        }

    }

}

?>