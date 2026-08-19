<?php
header('Content-Type: application/json; charset=utf-8');

include "../config/database.php";
include "../config/on_session.php";

$location_name = trim($_GET['location_name'] ?? '');

if ($location_name === '') {
    http_response_code(422);
    echo json_encode(['available' => false, 'message' => 'Location name is required.']);
    exit;
}

$stmt = $conn->prepare('SELECT id FROM item_location WHERE location_name = ? LIMIT 1');
$stmt->bind_param('s', $location_name);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode([
    'available' => !$exists,
    'message' => $exists ? 'This location name is already assigned.' : ''
]);