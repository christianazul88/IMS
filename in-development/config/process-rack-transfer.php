<?php
require_once 'database.php'; // Include your database connection
require_once 'on_session.php';
$response = []; // Response array for AJAX feedback
$currentDateTime = date('Y-m-d H:i:s'); // Define current timestamp

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form values
    $unhashed_from = $_SESSION['warehouse_rack_transfer'];
    $from_warehouse = $_SESSION['hashed_warehouse'] ?? null;

        
    // Retrieve and decode the scanned items from the session
    $existingData = isset($_SESSION['scanned_transfer']) ? json_decode($_SESSION['scanned_transfer'], true) : [];

    // If the data is empty, inform the user
    if (empty($existingData)) {
        $response['status'] = 'error';
        $response['message'] = 'No items scanned.';
        echo json_encode($response);
        exit;
    }
    $count_p = 0;
    // Process each scanned item
    foreach ($existingData as $item) {
        if (is_array($item) && isset($item['unique_barcode'])) {
            $unique_barcode = $item['unique_barcode'];
            $item_location = $item['rack_id'];
            $rack_name = $item['rack_name'];
            // Insert into stock_transfer_content
            $update_stocks = "UPDATE stocks SET item_location = '$item_location' WHERE unique_barcode = '$unique_barcode'";
            if ($conn->query($update_stocks)) {
                $count_p ++;
                // Log the transfer process
                $action = "Transfered barcode:" . $unique_barcode . " to " . $rack_name  ;
                $stmt_logs = $conn->prepare("INSERT INTO stock_timeline (unique_barcode, title, `action`, `date`, user_id) VALUES (?, 'RACK TRANSFER', ?, ?, ?)");
                $stmt_logs->bind_param("ssss", $unique_barcode, $action, $currentDateTime, $user_id);
                $stmt_logs->execute();
            }

        }
    }

    // Insert into logs
    $stmt_logs = $conn->prepare("INSERT INTO logs (title, `action`, `date`, user_id) VALUES ('Rack Transfer', ?, ?, ?)");
    if($count_p > 1){
        $action_message = "Conducted multiple transfer of product to different item location.";
    } else {
        $action_message = "Conducted single transfer of product to different item location.";
    }
    $stmt_logs->bind_param("sss", $action_message, $currentDateTime, $user_id);
    $stmt_logs->execute();

    $response['status'] = 'success';
    $response['message'] = 'Stock transfer request submitted successfully.';
    header("Location: ../RTS-logs/");
    
    unset($_SESSION['warehouse_rack_transfer']);
    unset($_SESSION['scanned_transfer']);
    unset($_SESSION['rack']);
    
    echo json_encode($response);
} else {
    // If accessed directly, return error
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
}
?>
