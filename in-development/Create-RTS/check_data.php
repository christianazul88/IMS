<?php
session_start();

// Initialize count if not set
if (!isset($_SESSION['count_unique_barcode'])) {
    $_SESSION['count_unique_barcode'] = 0;
}
$old_count = $_SESSION['count_unique_barcode'];

// Retrieve and decode scanned items
$scanned_items = isset($_SESSION['scanned_return']) ? json_decode($_SESSION['scanned_return'], true) : [];

// Check if scanned_items is an array and not a string
if (!is_array($scanned_items)) {
    // Handle invalid session data or initialize it as an empty array
    $scanned_items = [];
}

$unique_barcodes = [];

// Loop through the scanned items to get unique barcodes
foreach ($scanned_items as $item) {
    // Check if the 'unique_barcode' exists in the item
    if (isset($item['unique_barcode'])) {
        $unique_barcodes[$item['unique_barcode']] = true;
    }
}

$unique_count = count($unique_barcodes);

// Compare and output the result
if ($old_count !== $unique_count) {
    echo "1"; // Trigger update
} else {
    echo "0"; // Do nothing
}

// Update the session with the new unique barcode count
$_SESSION['count_unique_barcode'] = $unique_count;

// Close the database connection (if open)
if (isset($conn)) {
    $conn->close();
}
?>
