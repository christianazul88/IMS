<?php
session_start();

// Check if session variable is set
if (!isset($_SESSION['rts_id'])) {
    http_response_code(400);
    echo "Session ID not found.";
    exit;
}

$targetDir = "../" . $_SESSION['rts_id'] . "/";

// Create the folder if it doesn't exist
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// Handle uploaded file
if (!empty($_FILES['file'])) {
    $file = $_FILES['file'];
    $targetFile = $targetDir . basename($file['name']);

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        echo "Upload successful!<br>";

        // Check if 'reason' is provided
        if (isset($_POST['reason'])) {
            $reason = $_POST['reason'];

            // Prepare the JSON file path
            $jsonFilePath = $targetDir . 'reason.json';

            // Check if the JSON file exists
            if (file_exists($jsonFilePath)) {
                // Read the existing data
                $jsonData = json_decode(file_get_contents($jsonFilePath), true);
            } else {
                // If file doesn't exist, initialize an empty array
                $jsonData = [];
            }

            // Add the reason to the data array
            $jsonData[] = ['reason' => $reason, 'file' => $file['name'], 'timestamp' => date('Y-m-d H:i:s')];

            // Save the data to the JSON file
            if (file_put_contents($jsonFilePath, json_encode($jsonData, JSON_PRETTY_PRINT))) {
                echo "Reason saved to JSON file.";
            } else {
                http_response_code(500);
                echo "Error saving reason to JSON file.";
            }
        } else {
            http_response_code(400);
            echo "No reason provided.";
        }

    } else {
        http_response_code(500);
        echo "Error moving uploaded file.";
    }
} else {
    http_response_code(400);
    echo "No file uploaded.";
}
?>
