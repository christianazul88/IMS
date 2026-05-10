<?php
session_start();
// Retrieve and decode the scanned items from the session
$existingData = isset($_SESSION['scanned_return']) ? json_decode($_SESSION['scanned_return'], true) : [];

if (isset($_POST['barcode']) && !empty($existingData)) {
    foreach ($existingData as $index => $item) {
        if ($item['unique_barcode'] === $_POST['barcode']) {
            unset($existingData[$index]);
            $existingData = array_values($existingData); // Re-index array
            $_SESSION['scanned_return'] = json_encode($existingData); // Update session variable
            echo json_encode(['status' => 'success', 'message' => 'Item deleted.']);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'Item not found.']);
?>
