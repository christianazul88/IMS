<?php
require_once 'database.php'; // Include your database connection
require_once 'on_session.php';
$response = []; // Response array for AJAX feedback
$currentDateTime = date('Y-m-d H:i:s'); // Define current timestamp

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form values
    $unhashed_from = $_SESSION['warehouse_for_transfer'];
    $from_warehouse = $_SESSION['hashed_warehouse'] ?? null;
    $to_warehouse = $_POST['to_wh'] ?? null;
    $remarks = trim($_POST['remarks'] ?? '');
    $user_id = $_SESSION['user_id']; // Assuming a user session is available

    // Validate required fields
    if (!$from_warehouse || !$to_warehouse) {
        $response['status'] = 'error';
        $response['message'] = 'Both From and To warehouse must be selected.';
        echo json_encode($response);
        exit;
    }

    // Prevent transferring to the same warehouse
    if ($from_warehouse === $to_warehouse) {
        $response['status'] = 'error';
        $response['message'] = 'You cannot transfer stock to the same warehouse.';
        echo json_encode($response);
        exit;
    }

    // Check if the warehouses exist in the database
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM warehouse WHERE hashed_id IN (?, ?)");
    $stmt->bind_param("ss", $from_warehouse, $to_warehouse);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] < 2) {
        $response['status'] = 'error';
        $response['message'] = 'One or both selected warehouses are invalid.';
        echo json_encode($response);
        exit;
    }

    // Insert the transfer request into the database
    $stmt = $conn->prepare("INSERT INTO stock_transfer (from_warehouse, to_warehouse, remarks_sender, from_userid, date_out, `status`) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("sssss", $from_warehouse, $to_warehouse, $remarks, $user_id, $currentDateTime);

    if ($stmt->execute()) {
        $created_id = $conn->insert_id;
        
        // Retrieve and decode the scanned items from the session
        $existingData = isset($_SESSION['scanned_item']) ? json_decode($_SESSION['scanned_item'], true) : [];

        // If the data is empty, inform the user
        if (empty($existingData)) {
            $response['status'] = 'error';
            $response['message'] = 'No items scanned.';
            echo json_encode($response);
            exit;
        }

        // Process each scanned item
        foreach ($existingData as $item) {
            if (is_array($item) && isset($item['unique_barcode'])) {
                $unique_barcode = $item['unique_barcode'];

                // Insert into stock_transfer_content
                $insert = "INSERT INTO stock_transfer_content (unique_barcode, st_id) VALUES ('$unique_barcode', '$created_id')";
                if ($conn->query($insert)) {
                    // Log the transfer process
                    $stmt_logs = $conn->prepare("INSERT INTO stock_timeline (unique_barcode, title, `action`, `date`, user_id) VALUES (?, 'STOCK TRANSFER', 'About to be transferred', ?, ?)");
                    $stmt_logs->bind_param("sss", $unique_barcode, $currentDateTime, $user_id);
                    $stmt_logs->execute();
                }

                $update = "UPDATE stocks SET item_status = 3 WHERE unique_barcode = '$unique_barcode'";
                if($conn->query($update)){

                }
            }
        }

        // Insert into logs
        $stmt_logs = $conn->prepare("INSERT INTO logs (title, `action`, `date`, user_id) VALUES ('STOCK TRANSFER', ?, ?, ?)");
        $action_message = "Ref #:$created_id is pending for transfer to $unhashed_from.";
        $stmt_logs->bind_param("sss", $action_message, $currentDateTime, $user_id);
        $stmt_logs->execute();

        $response['status'] = 'success';
        $response['message'] = 'Stock transfer request submitted successfully.';
        header("Location:../Stock-transfer-logs/");
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Error processing the request. Please try again.';
    }
    unset($_SESSION['warehouse_for_transfer']);
    unset($_SESSION['scanned_item']);
    
    echo json_encode($response);
} else {
    // If accessed directly, return error
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
}
?>
