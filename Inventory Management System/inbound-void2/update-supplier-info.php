<?php
require_once '../config/database.php';
require_once '../config/on_session.php';

$unique_key    = $_GET['unique_key'] ?? '';
$po_id         = $_GET['po_id'] ?? '';
$supplier      = $_POST['select_supplier'] ?? '';
$prev_supplier = $_GET['supplier'] ?? '';

if ($supplier == '') {
    exit('error');
}

// Reuse the same pending 'void' request content.php/void-function.php would
// reuse, so this doesn't fork off a second void_logs row for the same
// po_id + unique_key.
$check_stmt = $conn->prepare(
    "SELECT id FROM void_logs WHERE request_type = 'void' AND unique_key = ? AND po_id = ? AND `status` = 'pending'"
);
$check_stmt->bind_param("si", $unique_key, $po_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $void_log_id = $check_result->fetch_assoc()['id'];
} else {
    $insert_stmt = $conn->prepare(
        "INSERT INTO void_logs (request_type, unique_key, po_id, requested_by, `status`, created_at)
         VALUES ('void', ?, ?, ?, 'pending', NOW())"
    );
    $insert_stmt->bind_param("sis", $unique_key, $po_id, $user_id);
    $insert_stmt->execute();
    $insert_stmt->close();

    $void_log_id = $conn->insert_id;
}
$check_stmt->close();

if (isset($void_log_id) && $void_log_id) {

    // Scope strictly to this one void_logs row, not every row sharing this
    // unique_key + po_id (that was overwriting unrelated/closed requests).
    $stmt = $conn->prepare("
        UPDATE void_logs
        SET new_supplier = ?, prev_supplier = ?
        WHERE id = ?
    ");

    $stmt->bind_param("ssi", $supplier, $prev_supplier, $void_log_id);

    if ($stmt->execute()) {
        echo "success";
        exit;
    } else {
        echo "error";
        exit;
    }
} else {
    echo "currently no records on void logs";
    exit;
}
