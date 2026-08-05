<?php
require_once '../config/database.php';
require_once '../config/on_session.php';

$unique_key = $_GET['unique_key'] ?? '';
$po_id = $_GET['po_id'] ?? '';
$supplier = $_POST['select_supplier'] ?? '';
$prev_supplier = $_GET['supplier'];

if ($supplier == '') {
    exit('error');
}

$check_stmt = $conn->prepare("SELECT * FROM void_logs WHERE unique_key = ? AND po_id = ?");
$check_stmt->bind_param("si", $unique_key, $po_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows == 0) {
    $insert_stmt = $conn->prepare("INSERT INTO void_logs (unique_key, po_id, requested_by, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $insert_stmt->bind_param("sis", $unique_key, $po_id, $user_id);
    $insert_stmt->execute();

    $insert_stmt->close();
    
    $void_log_id = $conn->insert_id;
}

if(isset($void_log_id)){

    // Update your database
    $stmt = $conn->prepare("
        UPDATE void_logs
        SET new_supplier = ?, prev_supplier = ?
        WHERE unique_key = ? AND po_id = ?
    ");

    $stmt->bind_param("sssi", $supplier, $prev_supplier, $unique_key, $po_id);

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