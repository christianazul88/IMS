<?php
// check_barcode.php

header('Content-Type: application/json');
require_once "../config/database.php"; // this file must set $conn

$barcode = $_GET['barcode'] ?? '';

if ($barcode === '') {
    echo json_encode(['exists' => false]);
    exit;
}

// Prepare & execute query
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM product WHERE parent_barcode = ?");
$stmt->bind_param("s", $barcode);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['exists' => $row['total'] > 0]);
