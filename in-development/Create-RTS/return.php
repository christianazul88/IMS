<?php
session_start(); // Start the session

// Unset and destroy the specific session variable
unset($_SESSION['warehouse_for_return']);
unset($_SESSION['scanned_return']);

// Redirect to the desired page
header("Location: ../Create-RTS/");
exit(); // Ensure script stops executing after redirection
?>
