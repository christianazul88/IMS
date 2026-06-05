<?php
include "database.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hashedId = $_POST['user'] ?? '';
    $activate = $_POST['activate'] ?? '';

    if ($hashedId !== '' && $activate !== '') {
        $status = $activate === 'true' ? 1 : 0;

        // Database connection (assuming $conn is your PDO or MySQLi instance)
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE hashed_id = ?");
        if ($stmt->execute([$status, $hashedId])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database update failed']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
    }
}
?>
