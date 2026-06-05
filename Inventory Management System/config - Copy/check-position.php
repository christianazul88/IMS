<?php
// Include database connection
include('database.php');  // Make sure this file contains your DB connection

// Get the position name from the AJAX request
if (isset($_POST['position-name'])) {
    $position_name = $_POST['position-name'];

    // Prepare the query to check if the position exists
    $sql = "SELECT COUNT(*) AS count FROM user_position WHERE position_name = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $position_name);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        // Return response as JSON
        if ($count > 0) {
            echo json_encode(['exists' => true]);
        } else {
            echo json_encode(['exists' => false]);
        }
    } else {
        // Error handling for SQL preparation failure
        echo json_encode(['exists' => false, 'error' => 'Database query failed']);
    }
} else {
    // If position-name is not set in the request
    echo json_encode(['exists' => false]);
}
?>
