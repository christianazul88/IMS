<?php
require_once 'database.php'; // Include your database connection
require_once 'on_session.php';

if (isset($_GET['status']) && isset($_GET['po'])) {
    // Sanitize and validate inputs
    $to_status = filter_var($_GET['status'], FILTER_VALIDATE_INT);
    $po_id = filter_var($_GET['po'], FILTER_VALIDATE_INT);

    if ($to_status === false || $po_id === false) {
        // Invalid input
        header("Location: ../PO-logs/?error=InvalidInput");
        exit();
    }

    // Define status text safely
    $statusText = [
        1 => 'Sent to supplier',
        2 => 'Confirmed by supplier',
        3 => 'In Transit/ Shipped',
        4 => 'Received'
    ];

    if (!isset($statusText[$to_status])) {
        // Invalid status
        header("Location: ../PO-logs/?error=InvalidStatus");
        exit();
    }

    $status = $statusText[$to_status];

    // Secure update using prepared statement
    $stmt = $conn->prepare("UPDATE purchased_order SET `status` = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('ii', $to_status, $po_id);
        if ($stmt->execute()) {
            // Prepare audit log
            $action = $user_fullname . " has updated purchased order status to " . $status . "; PO-" . $po_id;

            $log_stmt = $conn->prepare("INSERT INTO logs (title, `action`, `date`, user_id) VALUES ('UPDATE PO STATUS', ?, ?, ?)");
            if ($log_stmt) {
                $log_stmt->bind_param('sss', $action, $currentDateTime, $user_id);
                $log_stmt->execute();
                $log_stmt->close();
            }
            $stmt->close();
            header("Location: ../PO-logs/?success=true");
            exit();
        } else {
            $stmt->close();
            header("Location: ../PO-logs/?error=UpdateFailed");
            exit();
        }
    } else {
        header("Location: ../PO-logs/?error=PrepareFailed");
        exit();
    }
} else {
    header("Location: ../PO-logs/?error=MissingParams");
    exit();
}
?>
