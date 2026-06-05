<?php 

header('Content-Type: application/json');

include "database.php";
include "on_session.php";

if (isset($_POST['target_id'])) {
    $unique_key = $_POST['target_id'];
    $reason = $_POST['reason_staff'];

    // Prepare the query to prevent SQL injection
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


    // Update inbound_logs
    $stmt1 = $conn->prepare("UPDATE inbound_logs SET `status` = 1, staff_reason = ?, date_request_void = ? WHERE unique_key = ?");
    $stmt1->bind_param("sss", $reason, $currentDateTime, $unique_key);

    if ($stmt1->execute()) {

        // Insert notification
        $notification_message = $user_fullname . ' is requesting to delete inbound with ref #: ' . $unique_key;
        $stmt3 = $conn->prepare("INSERT INTO `notification` (title, `message`, `date`, `status`) VALUES (?, ?, ?, 0)");
        $title = 'Inbound delete request.';
        $stmt3->bind_param("sss", $title, $notification_message, $currentDateTime);

        if ($stmt3->execute()) {

            // Insert log entry
            $log_action = 'Inbound Ref #: ' . $unique_key . ' has been successfully requested to be deleted by ' . $user_fullname . '.';
            $stmt4 = $conn->prepare("INSERT INTO logs (title, action, user_id, date) VALUES ('INBOUND DELETE', ?, ?, ?)");
            $stmt4->bind_param("sss", $log_action, $user_id, $currentDateTime);

            if ($stmt4->execute()) {
                echo json_encode(['success' => true, 'message' => 'Waiting for approval!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to log the request.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create notification.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update inbound logs.']);
    }
}

$conn->close();
