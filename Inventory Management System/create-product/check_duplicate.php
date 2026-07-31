<?php
header('Content-Type: application/json');
require '../config/database.php'; // adjust to wherever $conn is set up

$description = trim($_POST['product_description'] ?? '');
$brand_id    = trim($_POST['brand'] ?? '');

if ($description === '' || $brand_id === '') {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $conn->prepare("
    SELECT p.hashed_id
    FROM product p
    INNER JOIN brand b ON b.hashed_id = p.brand
    WHERE p.description = ? AND b.hashed_id = ?
    LIMIT 1
");
$stmt->bind_param('ss', $description, $brand_id);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode(['exists' => $result->num_rows > 0]);
$stmt->close();