<?php
include "../config/database.php";
include "../config/on_session.php";

$outbound_id = $_SESSION['outbound_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['barcode'])) {
    $barcode = $_POST['barcode'];

    // Path to the JSON file
    $filePath = $outbound_id . '.json';

    // Check if the file exists
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'File not found']);
        exit;
    }

    // Get the current data from the JSON file
    $jsonData = file_get_contents($filePath);
    $products = json_decode($jsonData, true);

    if ($products === null) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to decode JSON data']);
        exit;
    }

    // Find and remove the product with the given barcode
    $productFound = false;
    foreach ($products as $key => $product) {
        if (isset($product['barcode']) && $product['barcode'] == $barcode) {
            unset($products[$key]);
            $productFound = true;
            break;
        }
    }

    if (!$productFound) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Product not found']);
        exit;
    }

    // Re-index the array and save the updated data back to the JSON file
    $products = array_values($products);
    $newJsonData = json_encode($products, JSON_PRETTY_PRINT);

    if (file_put_contents($filePath, $newJsonData) === false) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to save updated data']);
        exit;
    }

    // Respond with success
    echo json_encode(['status' => 'success', 'message' => 'Product deleted successfully']);
} else {
    // Invalid request
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
