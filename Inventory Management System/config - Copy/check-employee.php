<?php
include('database.php'); // DB connection

if (isset($_POST['email'])) { // Match key with AJAX
    $email = $_POST['email'];

    $sql = "SELECT COUNT(*) AS count FROM users WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        echo json_encode(['exists' => $count > 0]);
    } else {
        echo json_encode(['exists' => false, 'error' => 'Query failed']);
    }
} else {
    echo json_encode(['exists' => false]);
}
?>
