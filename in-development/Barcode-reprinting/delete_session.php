<?php
session_start();

if (isset($_POST['barcode']) && isset($_SESSION['stored_data'])) {
    foreach ($_SESSION['stored_data'] as $index => $item) {
        if ($item['unique_barcode'] === $_POST['barcode']) {
            unset($_SESSION['stored_data'][$index]);
            $_SESSION['stored_data'] = array_values($_SESSION['stored_data']); // Re-index array
            echo json_encode(['status' => 'success', 'message' => 'Item deleted.']);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'Item not found.']);
?>
