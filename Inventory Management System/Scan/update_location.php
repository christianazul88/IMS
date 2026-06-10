<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../config/database.php";
include "../config/on_session.php";

$location_id = $_POST['location_id'] ?? '';
$location_name = trim($_POST['location_name'] ?? '');

if (empty($location_id)) {
    http_response_code(400);
    exit("Location ID is required.");
}

if (empty($location_name)) {
    http_response_code(400);
    exit("Location name is required.");
}

/*
|--------------------------------------------------------------------------
| Check if location name already exists
|--------------------------------------------------------------------------
*/
$check_query = "
    SELECT id
    FROM item_location
    WHERE location_name = ?
      AND id != ?
    LIMIT 1
";

$stmt = $conn->prepare($check_query);
$stmt->bind_param("si", $location_name, $location_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409);
    exit("Location name already exists.");
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| Update location
|--------------------------------------------------------------------------
*/
$update_query = "
    UPDATE item_location
    SET location_name = ?
    WHERE id = ?
";

$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $location_name, $location_id);

if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {
        echo "Location updated successfully.";
    } else {
        echo "No changes were made.";
    }

} else {

    http_response_code(500);
    echo "Database error: " . $stmt->error;

}

$stmt->close();
$conn->close();