<?php

header('Content-Type: text/plain');

include "database.php";
include "on_session.php";

if (isset($_POST['outbound_id'])) {
    $outbound_id = htmlspecialchars(filter_input(INPUT_POST, 'outbound_id', FILTER_SANITIZE_STRING));
    $reason = htmlspecialchars(filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING));


    // Update outbound_logs
    $stmt1 = $conn->prepare("UPDATE outbound_logs SET `status` = 3, staff_reason = ?, date_request_void = ? WHERE hashed_id = ?");
    $stmt1->bind_param("sss", $reason, $currentDateTime, $outbound_id);

    if ($stmt1->execute()) {

        // Create notification
        $notification_message = $user_fullname . ' is requesting to void outbound with ref #: ' . $outbound_id;
        $stmt3 = $conn->prepare("INSERT INTO `notification` (title, `message`, `date`, `status`) VALUES (?, ?, ?, 0)");
        $title = 'Outbound Void Request.';
        $stmt3->bind_param("sss", $title, $notification_message, $currentDateTime);

        if ($stmt3->execute()) {

            // Log the action
            $log_action = 'Outbound Ref #: ' . $outbound_id . ' has been successfully requested to be void by ' . $user_fullname . '.';
            $stmt4 = $conn->prepare("INSERT INTO logs (title, action, user_id, date) VALUES ('OUTBOUND VOID', ?, ?, ?)");
            $stmt4->bind_param("sss", $log_action, $user_id, $currentDateTime);

            if ($stmt4->execute()) {
                echo 'Waiting for approval!';
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
