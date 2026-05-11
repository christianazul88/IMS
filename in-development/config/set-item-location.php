<?php
// Include the necessary files
include 'database.php';  // Include the database connection
include 'on_session.php';  // Include session handling (if required)

$unique_key = $_SESSION['unique_key']; // Retrieve unique session key
// Set the JSON file path
$jsonFilePath = '../jsons/' . $unique_key . '.json';

// Get the data from the POST request
$warehouse = isset($_POST['warehouse']) ? $_POST['warehouse'] : '';
$barcode = isset($_POST['barcode']) ? $_POST['barcode'] : '';

// Validation: Check if both fields are provided
if (empty($warehouse) || empty($barcode)) {
    // Return error if fields are missing
    echo json_encode(['message' => 'Both warehouse and barcode are required.', 'status' => 'error']);
    exit;
}

// Create the data array to be inserted into the JSON file
$newData = [
    'warehouse' => $warehouse,
    'barcode' => $barcode,
    'timestamp' => date('Y-m-d H:i:s'),  // Add a timestamp for when the entry is added
];

// Check if the JSON file exists
if (file_exists($jsonFilePath)) {
    // If the file exists, load its content
    $jsonData = file_get_contents($jsonFilePath);
    $dataArray = json_decode($jsonData, true);  // Decode the JSON into an array

    // If the file is empty or doesn't contain any data, initialize as an empty array
    if (!$dataArray) {
        $dataArray = [];
    }
} else {
    // If the file doesn't exist, initialize an empty array
    $dataArray = [];
}

// Add the new data to the array
$dataArray[] = $newData;

// Encode the array back to JSON format
$jsonContent = json_encode($dataArray, JSON_PRETTY_PRINT);

// Try to write the updated JSON data back to the file
if (file_put_contents($jsonFilePath, $jsonContent)) {
    // If data is successfully written, return a success message with details
    echo json_encode([
        'message' => 'Item location successfully added for Warehouse ' . $warehouse . ' and Barcode ' . $barcode . '.',
        'status' => 'success'
    ]);
} else {
    // If there was an error writing the file, return an error message
    echo json_encode([
        'message' => 'There was an error saving the data for Warehouse ' . $warehouse . ' and Barcode ' . $barcode . '. Please try again.',
        'status' => 'error'
    ]);
}
?>
