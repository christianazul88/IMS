<?php
// Include your database connection file and session file
include 'database.php';
include "on_session.php";

// Check if the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $positionName = $_POST['position_name'];
    $position_id = $_POST['position_id'];

    // Retrieve selected access permissions and implode them into a comma-separated string
    $accessPermissions = isset($_POST['access']) ? implode(',', $_POST['access']) : '';

    // Basic validation to ensure fields are not empty
    if (empty($positionName)) {
        echo json_encode(["success" => false, "message" => "Position name is required."]);
        exit;
    }

    // Ensure the access permissions are not empty
    if (empty($accessPermissions)) {
        echo json_encode(["success" => false, "message" => "Access permissions are required."]);
        exit;
    }

    // Ensure that the current date and time is retrieved securely
    $currentDateTime = date('Y-m-d H:i:s'); // or use a proper datetime format from your DB
    $user_id = $_SESSION['user_id']; // Assuming the user ID is stored in session after login

    // Prepare the SQL statement with placeholders
    $stmt = $conn->prepare("UPDATE user_position SET access = ? WHERE id = ?");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("ss", $accessPermissions, $position_id);

    // Execute the statement and check for success
    if ($stmt->execute()) {
        // Log action
        $action = "Access of User Position: " . $positionName . " has been updated.";
        $logs_stmt = $conn->prepare("INSERT INTO logs (title, `action`, `date`, user_id) VALUES (?, ?, ?, ?)");
        if ($logs_stmt) {
            $title = "STOCK TRANSFER";
            $logs_stmt->bind_param("ssss", $title, $action, $currentDateTime, $user_id);
            $logs_stmt->execute();
            $logs_stmt->close();
        }

        echo json_encode(["success" => true, "message" => "Access updated successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update access."]);
    }

    // Close the prepared statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
