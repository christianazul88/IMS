<?php
include "../config/database.php";
include "../config/on_session.php";

// Get row count from activity table
$sql = "SELECT COUNT(*) AS total FROM activity";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$current_count = (int)$row['total'];

// Initialize session value if not set
if (!isset($_SESSION['activity_count'])) {
    $_SESSION['activity_count'] = 0;
}

// Compare and show result
if ($current_count > $_SESSION['activity_count']) {
    echo "New rows added since last check!";
    // Update session value
    $_SESSION['activity_count'] = $current_count;
} else {
    echo "No new activity.";
}

$conn->close();
?>