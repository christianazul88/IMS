<?php

header('Content-Type: application/json');

// Include your database connection file
require_once 'database.php';
require_once 'on_session.php';

if (isset($_POST['target_id'], $_POST['response'], $_POST['to_userid'])) {
    $unique_key = htmlspecialchars(filter_input(INPUT_POST, 'target_id', FILTER_SANITIZE_STRING));
    $response = htmlspecialchars(filter_input(INPUT_POST, 'response', FILTER_SANITIZE_STRING));
    $to_userid = filter_input(INPUT_POST, 'to_userid', FILTER_SANITIZE_STRING);
    $reason_raw = filter_input(INPUT_POST, 'reason_admin', FILTER_SANITIZE_STRING);
    $reason_admin = htmlspecialchars($user_fullname . '; ' . $reason_raw);
    $currentDateTime = date('Y-m-d H:i:s'); // Assuming this is needed

    // Check if any outbound_content exists for this unique_key (prevent deleting inbound stock already outbounded)
    $stmt_check_outbound = $conn->prepare("
        SELECT unique_barcode
        FROM stocks 
        WHERE unique_key = ? 
        AND item_status !=0
        LIMIT 1
    ");
    $stmt_check_outbound->bind_param("s", $unique_key);
    $stmt_check_outbound->execute();
    $res_check_outbound = $stmt_check_outbound->get_result();

    if ($res_check_outbound->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'One or more items in the inbound stock may have already been outbounded. Please void it first.']);
        $stmt_check_outbound->close();
        $conn->close();
        exit();
    }
    $stmt_check_outbound->close();

    // Set response values based on input
    if ($response === "approve") {
        $response_value = "approved";
        $status_value = 3;
    } else {
        $response_value = "declined";
        $status_value = 2;
    }

    // Delete from inbound_logs
    $stmt1 = $conn->prepare("DELETE FROM inbound_logs WHERE unique_key = ?");
    $stmt1->bind_param("s", $unique_key);

    if ($stmt1->execute()) {
        // Get all unique_barcodes for this unique_key to delete from stock_timeline
        $stmt_barcodes = $conn->prepare("SELECT unique_barcode FROM stocks WHERE unique_key = ?");
        $stmt_barcodes->bind_param("s", $unique_key);
        $stmt_barcodes->execute();
        $result_barcodes = $stmt_barcodes->get_result();

        if ($result_barcodes->num_rows > 0) {
            $stmt_delete_timeline = $conn->prepare("DELETE FROM stock_timeline WHERE unique_barcode = ?");
            while ($row = $result_barcodes->fetch_assoc()) {
                $unique_barcode = $row['unique_barcode'];
                $stmt_delete_timeline->bind_param("s", $unique_barcode);
                $stmt_delete_timeline->execute();

                //delete outbounds 
                $stmt_outbounds = $conn->prepare("SELECT ol.hashed_id AS outbound_id FROM outbound_logs ol LEFT JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id WHERE oc.unique_barcode = ?");
                $stmt_outbounds->bind_param("s", $unique_barcode);
                $stmt_outbounds->execute();
                $result_outbounds = $stmt_outbounds->get_result();

                if($result_outbounds->num_rows > 0){
                    $row = $result_outbounds->fetch_assoc();
                    $outbound_id = $row['outbound_id'];

                    $stmt_delete_outbound_log = $conn->prepare("DELETE FROM outbound_logs WHERE hashed_id = ?");
                    $stmt_delete_outbound_log->bind_param("s", $outbound_id);
                    $stmt_delete_outbound_log->execute();
                }
                
                $stmt_delete_outbound_content = $conn->prepare("DELETE FROM outbound_content WHERE unique_barcode = ? AND status = 4");
                $stmt_delete_outbound_content->bind_param("s", $unique_barcode);
                $stmt_delete_outbound_content->execute();
            }
            $stmt_delete_timeline->close();
        }
        $stmt_barcodes->close();

        // Delete from stocks
        $stmt2 = $conn->prepare("DELETE FROM stocks WHERE unique_key = ?");
        $stmt2->bind_param("s", $unique_key);
        if (!$stmt2->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete stocks.']);
            exit;
        }
        $stmt2->close();

        // Prepare notification message
        if ($response === "approve") {
            $notification_message = $user_fullname . ' approved your request to delete inbound and stocks with ref #: ' . $unique_key;
        } else {
            $notification_message = $user_fullname . ' declined your request to delete inbound and stocks with ref #: ' . $unique_key;
        }

        // Insert notification
        $stmt3 = $conn->prepare("INSERT INTO notification (title, message, date, to_userid, status) VALUES (?, ?, ?, ?, 0)");
        $title = 'Inbound and Stocks delete request.';
        $stmt3->bind_param("ssss", $title, $notification_message, $currentDateTime, $to_userid);

        if ($stmt3->execute()) {
            $stmt3->close();

            // Insert log
            $log_action = 'Inbound and Stocks Ref #: ' . $unique_key . ' have been ' . $response_value . ' to be deleted by ' . $user_fullname . '.';
            $stmt4 = $conn->prepare("INSERT INTO logs (title, action, user_id, date) VALUES ('INBOUND & STOCKS APPROVAL', ?, ?, ?)");
            $stmt4->bind_param("sss", $log_action, $user_id, $currentDateTime);

            if ($stmt4->execute()) {
                $stmt4->close();
                echo json_encode(['success' => true, 'message' => 'Approved!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to log the request.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create notification.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete inbound logs.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Missing required POST parameters!']);
}

$conn->close();

?>
