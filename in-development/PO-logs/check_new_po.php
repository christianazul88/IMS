<?php 
session_start();
if(isset($_SESSION['new_po'])){
    if($_SESSION['new_po'] === "true"){
        include "../config/generate-po.php";
        unset($_SESSION['new_po']);
    } else {
        
    }
}
?>