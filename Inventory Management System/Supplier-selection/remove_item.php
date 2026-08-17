<?php
include "../config/on_session.php"; // must exit/redirect if user is not authenticated

header('Content-Type: application/json');

// --- CSRF check ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(["status" => "error", "message" => "Invalid session token. Please refresh the page."]);
    exit;
}

if (isset($_POST['id']) && $_POST['id'] !== '') {
    $id = $_POST['id'];

    if (isset($_SESSION['po_list']) && !empty($_SESSION['po_list'])) {
        foreach ($_SESSION['po_list'] as $index => $item) {
            if (hash_equals((string) $item['id'], (string) $id)) {
                unset($_SESSION['po_list'][$index]);
                $_SESSION['po_list'] = array_values($_SESSION['po_list']); // reindex
                echo json_encode(["status" => "success", "message" => "Item removed."]);
                exit;
            }
        }
    }
    echo json_encode(["status" => "error", "message" => "Item not found."]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
}
?>
