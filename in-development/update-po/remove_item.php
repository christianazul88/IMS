<?php
session_start();

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    if (isset($_SESSION['po_list']) && !empty($_SESSION['po_list'])) {
        foreach ($_SESSION['po_list'] as $index => $item) {
            if ($item['id'] == $id) {
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
