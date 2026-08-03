<?php
session_start(); // Start the session

// Unset and destroy the specific session variable
if(isset($_GET['type'])){
    $type = $_GET['type'];
    if($type==="wh"){
        unset($_SESSION['warehouse_rack_transfer']);
        unset($_SESSION['scanned_transfer']);
    } else {
        unset($_SESSION['rack']);
    }
}
// Redirect to the desired page
header("Location: ../Rack-transfer/");
exit(); // Ensure script stops executing after redirection
?>
