<?php
session_start(); // Ensure session is started

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['warehouse'])) {
    $warehouse = trim($_POST['warehouse']); // Ensure no unnecessary whitespace
    $_SESSION['warehouse_outbound'] = $warehouse;

    if (!empty($_SESSION['warehouse_outbound'])) {
        header("Location: ../Outbound-form/");
        exit; // Prevent further script execution
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['change_warehouse'])) {
    unset($_SESSION['warehouse_outbound']);
    header("Location: ../Outbound-form/");
    exit; // Prevent further script execution
}

?>
