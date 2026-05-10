<?php 
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $warehouse = $_POST['wh'];
    $_SESSION['selected-warehouse-SIL'] = $warehouse;
    header("Location: ../set-item-locations/");
    exit(); // Always call exit after header redirection
}
?>
