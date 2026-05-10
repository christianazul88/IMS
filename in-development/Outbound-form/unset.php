<?php
include "../config/database.php"; // Ensure this includes a $conn object for MySQLi
include "../config/on_session.php";

if (isset($_SESSION['outbound_id'])) {
    $filePath = $_SESSION['outbound_id'] . ".json"; // Adjust the path accordingly

    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            // File deleted successfully, now unset the session variables
            unset($_SESSION['warehouse_outbound']);
            unset($_SESSION['outbound_id']);
            header("Location: ../Outbound-form/");
        } else {
            echo "Error deleting the file.";
        }
    } else {
        // File does not exist, still unset the session variables
        unset($_SESSION['warehouse_outbound']);
        unset($_SESSION['outbound_id']);
    }
    header("Location: ../Outbound-form/");
} else {
    header("Location: ../Outbound-form/");
}

?>
