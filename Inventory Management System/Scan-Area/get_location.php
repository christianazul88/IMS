<?php
include '../config/database.php'; // adjust to your config

header('Content-Type: application/json');

if (!isset($_POST['id'])) {
    echo json_encode([
        "success" => false,
        "message" => "No ID provided"
    ]);
    exit;
}

$id = trim($_POST['id']);

$stmt = $conn->prepare("SELECT url FROM ims_urls WHERE id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "success" => true,
        "url" => $row['url']
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Not found"
    ]);
}