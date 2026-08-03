<?php
require_once '../config/database.php'; // expects this to provide $conn (mysqli)
require_once '../config/on_session.php'; // expects this to provide $conn (mysqli) and $user_id

header('Content-Type: text/plain');


$barcodes = json_decode($_SESSION['scanned_void'], true);

if (empty($barcodes) || !is_array($barcodes)) {
    echo 'No items to void.';
    exit;
}

$remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

$conn->begin_transaction();

try {
    // 1. Create the parent void_logs row (status defaults to pending, approved_by stays null)
    $stmt = $conn->prepare(
        "INSERT INTO void_logs (date, user_id, remarks, status) VALUES (NOW(), ?, ?, 'pending')"
    );
    $stmt->bind_param('ss', $user_id, $remarks);
    $stmt->execute();
    $void_log_id = $conn->insert_id;
    $stmt->close();

    // 2. Insert every scanned barcode into void_items, tied to that void_logs row
    $itemStmt = $conn->prepare(
        "INSERT INTO void_items (void_log_id, unique_barcode) VALUES (?, ?)"
    );
    foreach ($barcodes as $item) {
        $barcode = $item['unique_barcode'];

        $itemStmt->bind_param('is', $void_log_id, $barcode);
        $itemStmt->execute();
    }
    $itemStmt->close();

    $conn->commit();

    // Clear the working session now that it's been persisted
    $_SESSION['scanned_void'] = [];

    // echo 'Void request submitted successfully.';
    header("Location: ../inbound-void-logs/?success=true");
    exit;
} catch (Exception $e) {
    $conn->rollback();
    echo 'Failed to submit void request: ' . $e->getMessage();
}