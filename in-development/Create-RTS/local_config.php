<?php
session_start(); // Ensure session is started

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['warehouse'])) {
    $warehouse = trim($_POST['warehouse']); // Ensure no unnecessary whitespace
    $_SESSION['warehouse_for_return'] = $warehouse;

    if (!empty($_SESSION['warehouse_for_return'])) {
        header("Location: ../Create-RTS/");
        exit; // Prevent further script execution
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['change_warehouse'])) {
    unset($_SESSION['warehouse_for_return']);
    header("Location: ../Create-RTS/");
    exit; // Prevent further script execution
}

?>
