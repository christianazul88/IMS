<?php 

include "../config/database.php";
include "../config/on_session.php";
// Token check
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (!isset($_SESSION['auth_token']) || $token !== $_SESSION['auth_token']) {
    http_response_code(403);
    echo "Invalid token.";
    exit;
}

// Debug: Check payload
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo "Bad JSON or not an array.";
    exit;
}
if (!isset($data['active']) || !$data['active']) {
    http_response_code(400);
    echo "Missing or false 'active' flag.";
    exit;
}


$stmt = $conn->prepare("INSERT INTO activity (user_id, warehouse, date) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $user_id, $warehouse, $currentDateTime);
$stmt->execute();
$stmt->close();
$conn->close();

echo "Activity logged.";
?>