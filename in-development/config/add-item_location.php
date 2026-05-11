<?php
include "database.php";
include "on_session.php";

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Sanitize the item_location name
    $item_locationName = filter_input(INPUT_POST, 'item_location_name', FILTER_SANITIZE_STRING);
    $warehouse_loc_id = filter_input(INPUT_POST, 'warehouse', FILTER_SANITIZE_STRING);

    // Check if item_location name is empty
    if (empty($item_locationName)) {
        echo "<script>alert('item_location name cannot be empty.'); window.history.back();</script>";
        exit;
    }


    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO item_location (location_name, `date`, user_id, warehouse) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $item_locationName, $currentDateTime, $user_id, $warehouse_loc_id);

    // Execute the query
    if ($stmt->execute()) {
        header("Location: ../item-destination/?success=true");
    } else {
        header("Location: ../item-destination/?success=false");
    }

    // Close connections
    $stmt->close();
    $conn->close();

} else {
    // Redirect back if accessed without POST
    header("Location: ../item-destination.php");
    exit;
}
?>
