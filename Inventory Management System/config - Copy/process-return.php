<?php
require_once 'database.php'; // Include your database connection
require_once 'on_session.php';
$response = []; // Response array for AJAX feedback
$currentDateTime = date('Y-m-d H:i:s'); // Define current timestamp

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form values
    $unhashed_from = $_SESSION['warehouse_for_return'];
    $from_warehouse = $_SESSION['hashed_warehouse'] ?? null;
    $to_warehouse = $_POST['supplier'] ?? null;
    $remarks = trim($_POST['remarks'] ?? '');
    $to_status = $_POST['to_status'];


    // Insert the transfer request into the database
    $stmt = $conn->prepare("INSERT INTO rts_logs (supplier, warehouse, reason, user_id, `date`, `status`, `for`) VALUES (?, ?, ?, ?, ?, '0', ?)");
    $stmt->bind_param("ssssss", $to_warehouse, $from_warehouse, $remarks, $user_id, $currentDateTime, $to_status);

    if ($stmt->execute()) {
        $created_id = $conn->insert_id;

        $imageFilename = null;

        if (!empty($_FILES['image_proof']['name'])) {
            $folderName = $created_id;
            $uploadDir = "../../assets/img_rts/" . $folderName . "/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $originalName = basename($_FILES['image_proof']['name']);
            $tmp_name = $_FILES['image_proof']['tmp_name'];
            $destination = $uploadDir . $originalName;

            if (move_uploaded_file($tmp_name, $destination)) {
                $imageFilename = $originalName;

                // Update rts_logs with image filename
                $stmt_update_image = $conn->prepare("UPDATE rts_logs SET proof = ? WHERE id = ?");
                $stmt_update_image->bind_param("si", $imageFilename, $created_id);
                $stmt_update_image->execute();
            } else {
                error_log("Failed to move uploaded image: $originalName");
            }
        }

        if (!empty($_FILES['image_front']['name'])) {
            $folderName = $created_id;
            $uploadDir = "../../assets/img_rts/" . $folderName . "/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $originalName = basename($_FILES['image_front']['name']);
            $tmp_name = $_FILES['image_front']['tmp_name'];
            $destination = $uploadDir . $originalName;

            if (move_uploaded_file($tmp_name, $destination)) {
                $imageFilename = $originalName;

                // Update rts_logs with image filename
                $stmt_update_image = $conn->prepare("UPDATE rts_logs SET front = ? WHERE id = ?");
                $stmt_update_image->bind_param("si", $imageFilename, $created_id);
                $stmt_update_image->execute();
            } else {
                error_log("Failed to move uploaded image: $originalName");
            }
        }

        if (!empty($_FILES['image_back']['name'])) {
            $folderName = $created_id;
            $uploadDir = "../../assets/img_rts/" . $folderName . "/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $originalName = basename($_FILES['image_back']['name']);
            $tmp_name = $_FILES['image_back']['tmp_name'];
            $destination = $uploadDir . $originalName;

            if (move_uploaded_file($tmp_name, $destination)) {
                $imageFilename = $originalName;

                // Update rts_logs with image filename
                $stmt_update_image = $conn->prepare("UPDATE rts_logs SET back = ? WHERE id = ?");
                $stmt_update_image->bind_param("si", $imageFilename, $created_id);
                $stmt_update_image->execute();
            } else {
                error_log("Failed to move uploaded image: $originalName");
            }
        }

        if (!empty($_FILES['image_warranty']['name'])) {
            $folderName = $created_id;
            $uploadDir = "../../assets/img_rts/" . $folderName . "/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $originalName = basename($_FILES['image_warranty']['name']);
            $tmp_name = $_FILES['image_warranty']['tmp_name'];
            $destination = $uploadDir . $originalName;

            if (move_uploaded_file($tmp_name, $destination)) {
                $imageFilename = $originalName;

                // Update rts_logs with image filename
                $stmt_update_image = $conn->prepare("UPDATE rts_logs SET warranty = ? WHERE id = ?");
                $stmt_update_image->bind_param("si", $imageFilename, $created_id);
                $stmt_update_image->execute();
            } else {
                error_log("Failed to move uploaded image: $originalName");
            }
        }



        
        // Retrieve and decode the scanned items from the session
        $existingData = isset($_SESSION['scanned_return']) ? json_decode($_SESSION['scanned_return'], true) : [];

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
                $insert = "INSERT INTO rts_content (unique_barcode, rts_id) VALUES ('$unique_barcode', '$created_id')";
                if ($conn->query($insert)) {
                    // Log the transfer process
                    $action = "About to " . $to_status;
                    $stmt_logs = $conn->prepare("INSERT INTO stock_timeline (unique_barcode, title, `action`, `date`, user_id) VALUES (?, 'RTS', ?, ?, ?)");
                    $stmt_logs->bind_param("ssss", $unique_barcode, $action, $currentDateTime, $user_id);
                    $stmt_logs->execute();
                }


                $update = "UPDATE stocks SET item_status = 4 WHERE unique_barcode = '$unique_barcode'";
                if($conn->query($update)){

                }
            }
        }

        // Insert into logs
        $stmt_logs = $conn->prepare("INSERT INTO logs (title, `action`, `date`, user_id) VALUES ('Return to Supplier', ?, ?, ?)");
        $action_message = "Return Ref #:$created_id is pending for $to_status.";
        $stmt_logs->bind_param("sss", $action_message, $currentDateTime, $user_id);
        $stmt_logs->execute();

        $response['status'] = 'success';
        $response['message'] = 'Stock transfer request submitted successfully.';
        header("Location: ../RTS-logs/");
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Error processing the request. Please try again.';
    }
    unset($_SESSION['warehouse_for_transfer']);
    unset($_SESSION['scanned_return']);
    
    echo json_encode($response);
} else {
    // If accessed directly, return error
    $response['status'] = 'error';
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
}
?>
