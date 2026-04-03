<?php
// // Enable error reporting for debugging
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require 'database.php'; // Include database connection
require 'on_session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filtered_input = [];
    $good = true;
    // Filter input: Only process fields that have a name attribute
    foreach ($_POST as $key => $value) {
        if (!empty($key)) {
            $filtered_input[$key] = trim($value);
        }
    }

    // Check if necessary fields exist
    if (isset($filtered_input['barcode'], $filtered_input['amount'], $filtered_input['warehouse'])) {
        $barcode = $filtered_input['barcode'];
        $amount = floatval($filtered_input['amount']);
        $warehouse_return = $filtered_input['warehouse'];
        $outbound_id = $filtered_input['outbound_id'];
        $reason = $filtered_input['reason'];
        $imploded_filenames = "";
        $fault = $filtered_input['fault'];
        $fault_type = $filtered_input['type_reason'];

        $supplier_info = "SELECT sup.local_international FROM stocks s LEFT JOIN supplier sup ON sup.hashed_id = s.supplier WHERE s.unique_barcode = '$barcode' LIMIT 1";
        $supplier_info_res = $conn->query($supplier_info);
        if($supplier_info_res->num_rows>0){
            $row=$supplier_info_res->fetch_assoc();
            $supplier_type = $row['local_international'] ?? 'unset';
        }

        if($fault_type === "DELIVERY FAILED" && $fault === "CLIENT FAULT" || $fault_type === "DELIVERY FAILED" && $fault === "SELLER FAULT"){
            $good = true;
        } elseif($fault_type === "DEFECTIVE" && $fault === "NONE"){
            $good = true;
        } elseif($fault_type === "WRONG ITEM ORDER" && $fault === "NONE"){
            $good = true;
        } else {
            $good = false;
        }

        if($good === false){
            header("Location: ../Create-Return/?success=fault_invalid");
            exit;
            $conn->close();
        }
        

        // Insert return details
        $insert = "INSERT INTO `returns` (unique_barcode, amount, `date`, user_id, warehouse, reason, fault, fault_type, supplier_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param("sdsssssss", $barcode, $amount, $currentDateTime, $user_id, $warehouse_return, $reason, $fault, $fault_type, $supplier_type);

        if ($stmt->execute()) {
            $created_id = $conn->insert_id;
            
            $imageFilenames = [];

            if (!empty($_FILES['images']['name'][0])) {
                $folderName = $created_id;
                $uploadDir = "../../assets/img_return/" . $folderName . "/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    $originalName = basename($_FILES['images']['name'][$key]);
                    $imageType = $_FILES['images']['type'][$key];

                    // // Only check image type (not size)
                    // if (!in_array($imageType, ['image/jpeg','image/jpg', 'image/png', 'image/webp', 'image/gif'])) {
                    //     error_log("Skipped file $originalName due to invalid type: $imageType");
                    //     continue;
                    // }

                    $destination = $uploadDir . $originalName;

                    if (move_uploaded_file($tmp_name, $destination)) {
                        $imageFilenames[] = $originalName;
                    } else {
                        error_log("Failed to move uploaded file: $originalName");
                    }
                }

                // Update rts_logs if there are images
                if (!empty($imageFilenames)) {
                    $imploded_filenames = implode(',', $imageFilenames);
                    $stmt_update_images = $conn->prepare("UPDATE returns SET images = ? WHERE id = ?");
                    $stmt_update_images->bind_param("si", $imploded_filenames, $created_id);
                    $stmt_update_images->execute();
                }
            } 


            // Insert into stock_timeline
            $item_logs = "INSERT INTO stock_timeline (unique_barcode, title, `action`, `date`, user_id) VALUES (?, 'PRODUCT RETURN', 'Product was returned.', ?, ?)";
            $stmt_logs = $conn->prepare($item_logs);
            $stmt_logs->bind_param("sss", $barcode, $currentDateTime, $user_id);

            if ($stmt_logs->execute()) {
                $update_stock_status = "UPDATE stocks SET item_status = 0, warehouse = '9400f1b21cb527d7fa3d3eabba93557a18ebe7a2ca4e471cfe5e4c5b4ca7f767' WHERE unique_barcode = ?";
                $stmt_update_stock_status = $conn->prepare($update_stock_status);
                $stmt_update_stock_status->bind_param("s", $barcode);
                $stmt_update_stock_status->execute();
                // Insert into logs
                $logs = "INSERT INTO logs (title, `action`, `date`, user_id) VALUES ('PRODUCT RETURN', ?, ?, ?)";
                $stmt_log = $conn->prepare($logs);
                $log_action = "$barcode was returned.";
                $stmt_log->bind_param("sss", $log_action, $currentDateTime, $user_id);

                if ($stmt_log->execute()) {
                    // Update outbound_logs to set status = 1
                    $update_outbound_logs = "UPDATE outbound_logs SET status = 1 WHERE hashed_id = ?";
                    $stmt_outbound_logs = $conn->prepare($update_outbound_logs);
                    $stmt_outbound_logs->bind_param("s", $outbound_id);
                    $stmt_outbound_logs->execute();

                    // Update outbound_content to set status = 1
                    $update_outbound_content = "UPDATE outbound_content SET status = 1 WHERE unique_barcode = ?";
                    $stmt_outbound_content = $conn->prepare($update_outbound_content);
                    $stmt_outbound_content->bind_param("s", $barcode);
                    $stmt_outbound_content->execute();

                    // Redirect to return logs page on success
                    header("Location: ../Return-logs/");
                    exit();
                }
            }
        }

        // Close statements
        $stmt->close();
        $stmt_logs->close();
        $stmt_log->close();
        $stmt_outbound_logs->close();
        $stmt_outbound_content->close();
    } else {
        echo "<script>alert('Missing required fields.'); window.history.back();</script>";
    }
}
?>
