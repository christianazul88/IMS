<?php
// Include database connection
require_once 'database.php';

$response = [];

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Sanitize and validate input data
    $position_id = isset($_POST['position']) ? $conn->real_escape_string($_POST['position']) : null;
    $warehouse_access = isset($_POST['warehouse_access']) ? $_POST['warehouse_access'] : [];

    if (!$position_id) {
        throw new Exception('Position is required');
    }

    if (!is_array($warehouse_access)) {
        throw new Exception('Invalid warehouse access data');
    }

    // Example employee ID (replace with dynamic data, e.g., session or hidden field)
    $employee_id = $_POST['user_id'];

    // Update the employee's position
    $update_position_query = "UPDATE users SET user_position = ? WHERE hashed_id = ?";
    $stmt = $conn->prepare($update_position_query);
    $stmt->bind_param('ss', $position_id, $employee_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update employee position');
    }

    // Implode warehouse access selections into a comma-separated string
    $warehouse_access_imploded = implode(',', $warehouse_access);

    // Update the warehouse access as a single string in the database
    $update_access_query = "UPDATE users SET warehouse_access = ? WHERE hashed_id = ?";
    $stmt = $conn->prepare($update_access_query);
    $stmt->bind_param('ss', $warehouse_access_imploded, $employee_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update warehouse access');
    }

    // Return success response
    $response = [
        'status' => 'success',
        'message' => 'Employee information updated successfully',
    ];
} catch (Exception $e) {
    // Handle errors and return a failure response
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
    ];
}

// Set response header to JSON
header('Content-Type: application/json');
echo json_encode($response);

?>
