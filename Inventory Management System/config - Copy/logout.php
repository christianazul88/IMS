<?php
include "database.php";
include "on_session.php";

$action = $user_fullname . ' Logged out.';
// Prepare the SQL statement with placeholders
$stmt = $conn->prepare("INSERT INTO logs (title, `action`, `date`, user_id) VALUES (?, ?, ?, ?)");

// Bind the parameters to the placeholders
$title = 'LOGGED OUT';
$stmt->bind_param("ssss", $title, $action, $currentDateTime, $user_id);

// Execute the prepared statement
if ($stmt->execute()) {
    
} else {
    echo json_encode(['success' => false, 'message' => 'Log entry failed: ' . $stmt->error]);
}
// Unset all session variables
$_SESSION = array();

// If there's a session cookie, delete it as well
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to the parent directory
header("Location: ../");
exit;
?>
