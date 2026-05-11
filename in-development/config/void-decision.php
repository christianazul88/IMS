<?php

header('Content-Type: text/plain');

// Include your database connection file
require_once 'database.php';
require_once 'on_session.php';

if (isset($_POST['outbound_id']) && isset($_POST['response']) && isset($_POST['to_userid'])) {
    $outbound_id = htmlspecialchars(filter_input(INPUT_POST, 'outbound_id', FILTER_SANITIZE_STRING));
    $response = htmlspecialchars(filter_input(INPUT_POST, 'response', FILTER_SANITIZE_STRING));
    $to_userid = filter_input(INPUT_POST, 'to_userid', FILTER_SANITIZE_STRING);
    $reason_raw = filter_input(INPUT_POST, 'reason_admin', FILTER_SANITIZE_STRING);
    $reason_admin = htmlspecialchars($user_fullname . '; ' . $reason_raw);

    if ($response === "approve") {
        $response_value = "approved";
        $status_value = 4;
    } else {
        $response_value = "declined";
        $status_value = 5;
    }

    // Update outbound_logs
    $stmt1 = $conn->prepare("UPDATE outbound_logs SET `status` = ?, authorize_reason = ?, date_approved = ? WHERE hashed_id = ?");
    $stmt1->bind_param("isss", $status_value, $reason_admin, $currentDateTime, $outbound_id);

    if ($stmt1->execute()) {

        // If approved, update stocks
        if ($response === "approve") {
            $stmt_select = $conn->prepare("SELECT unique_barcode FROM outbound_content WHERE hashed_id = ?");
            $stmt_select->bind_param("s", $outbound_id);
            $stmt_select->execute();
            $result = $stmt_select->get_result();

            while ($row = $result->fetch_assoc()) {
                $outbound_unique_barcode = $row['unique_barcode'];
                $stmt2 = $conn->prepare("UPDATE stocks SET item_status = 0 WHERE unique_barcode = ?");
                $stmt2->bind_param("s", $outbound_unique_barcode);

                if (!$stmt2->execute()) {
                    echo 'Failed to update stocks.';
                    exit;
                }

                $stmt_oc = $conn->prepare("UPDATE outbound_content SET status = 4 WHERE unique_barcode = ?");
                $stmt_oc->bind_param("s", $outbound_unique_barcode);

                if(!$stmt_oc->execute()) {
                    echo 'Failed update outbound content.';
                    exit;   
                }

                // Insert into stock_timeline
                $action_description = "$outbound_unique_barcode has been successfully voided";
                $title = 'VOIDED OUTBOUND';

                $stmt_history = $conn->prepare("INSERT INTO stock_timeline (unique_barcode, title, action, date, user_id) VALUES (?, ?, ?, ?, ?)");
                $stmt_history->bind_param("sssss", $outbound_unique_barcode, $title, $action_description, $currentDateTime, $user_id);
                if (!$stmt_history->execute()) {
                    echo 'Failed to insert into stock_timeline.';
                    exit;
                }
            }
        }

        // Prepare notification message
        if ($response === "approve") {
            $notification_message = $user_fullname . ' approved your request to delete inbound with ref #: ' . $outbound_id;
        } else {
            $notification_message = $user_fullname . ' declined your request to delete inbound with ref #: ' . $outbound_id;
        }

        // Insert notification
        $stmt3 = $conn->prepare("INSERT INTO `notification` (title, `message`, `date`, to_userid, `status`) VALUES (?, ?, ?, ?, 0)");
        $title = 'Outbound Void Result.';
        $stmt3->bind_param("ssss", $title, $notification_message, $currentDateTime, $to_userid);

        if ($stmt3->execute()) {

            // Insert log
            $log_action = 'Outbound Ref #: ' . $outbound_id . ' has been ' . $response_value  . ' to be voided by ' . $user_fullname . '.';
            $stmt4 = $conn->prepare("INSERT INTO logs (title, `action`, user_id,`date`) VALUES ('OUTBOUND APPROVAL', ?, ?, ?)");
            $stmt4->bind_param("sss", $log_action, $user_id, $currentDateTime);

            if ($stmt4->execute()) {
                echo $response_value;
            } else {
                echo 'Failed to log the request.';
            }

        } else {
            echo 'Failed to create notification.';
        }

    } else {
        echo 'Failed to update outbound logs.';
    }
} else {
    echo 'Missing required fields.';
}

$conn->close();
?>
