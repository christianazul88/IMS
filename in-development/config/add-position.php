<?php
// Include your database connection file and session file
include 'database.php';
include "on_session.php";


// Check if the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // CSRF Token validation (ensure token is valid and matches the session token)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    // Sanitize and retrieve the position name
    $positionName = isset($_POST['position-name']) ? trim($_POST['position-name']) : '';
    $positionName = htmlspecialchars($positionName, ENT_QUOTES, 'UTF-8'); // Prevent XSS

    // Retrieve selected access permissions and implode them into a comma-separated string
    $accessPermissions = isset($_POST['access']) ? implode(',', $_POST['access']) : '';
    
    // Basic validation to ensure fields are not empty
    if (empty($positionName)) {
        echo "Position name is required.";
        exit;
    }

    // Ensure the access permissions are not empty
    if (empty($accessPermissions)) {
        echo "Access permissions are required.";
        exit;
    }

    // Ensure that the current date and time is retrieved securely
    $currentDateTime = date('Y-m-d H:i:s'); // or use a proper datetime format from your DB
    $user_id = $_SESSION['user_id']; // Assuming the user ID is stored in session after login

    // Prepare the SQL statement with placeholders
    $stmt = $conn->prepare("INSERT INTO user_position (position_name, access, `date`, user_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $positionName, $accessPermissions, $currentDateTime, $user_id);

    // Execute the statement and check for success
    if ($stmt->execute()) {
        // Get the auto-incremented ID of the inserted position
        $position_unhashid = $conn->insert_id;
        
        // Secure the position ID with hash (use SHA-256 or any secure hash algorithm)
        $position_hashid = hash('sha256', $position_unhashid);
        
        // Update the position with the hashed ID
        $update_position = "UPDATE user_position SET hashed_id = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_position);
        $update_stmt->bind_param("si", $position_hashid, $position_unhashid);
        
        if ($update_stmt->execute()) {
            // Success - Redirect
            header("Location: ../Access-levels/?success=true");
        } else {
            // Error in updating the hashed ID
            header("Location: ../Access-levels/?success=false&m=Error updating hashed ID.");
        }
        $update_stmt->close();
    } else {
        // Error in inserting the position
        header("Location: ../Access-levels/?success=false&m=" . urlencode($stmt->error));
    }

    // Close the prepared statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
