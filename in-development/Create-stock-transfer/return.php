<?php
session_start(); // Start the session

// Unset and destroy the specific session variable
unset($_SESSION['warehouse_for_transfer']);
unset($_SESSION['scanned_item']);

// Redirect to the desired page
header("Location: ../Create-stock-transfer/");
exit(); // Ensure script stops executing after redirection
?>
