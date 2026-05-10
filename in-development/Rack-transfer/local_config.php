<?php
session_start(); // Ensure session is started

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['warehouse'])) {
    $warehouse = trim($_POST['warehouse']); // Ensure no unnecessary whitespace
    $_SESSION['warehouse_rack_transfer'] = $warehouse;

    if (!empty($_SESSION['warehouse_rack_transfer'])) {
        header("Location: ../Rack-transfer/");
        exit; // Prevent further script execution
    }
} elseif($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rack'])){
    $rack = trim($_POST['rack']); // Ensure no unnecessary whitespace
    $_SESSION['rack'] = $rack;

    if (!empty($_SESSION['rack'])) {
        header("Location: ../Rack-transfer/");
        exit; // Prevent further script execution
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['change_warehouse'])) {
    unset($_SESSION['warehouse_rack_transfer']);
    header("Location: ../Rack-transfer/");
    exit; // Prevent further script execution
} elseif($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['change_rack'])){

}

?>
