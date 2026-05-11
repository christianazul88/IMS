<?php
include "database.php";
include "on_session.php";

if (!isset($_SESSION['unique_key'])) {
    die("Unauthorized access.");
}

$uniqueKey = $_SESSION['unique_key'];

if (isset($_SESSION['last_ext'])) {
    unset($_SESSION['last_ext']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize user inputs
    $unique_barcodes = array_map('trim', $_POST['unique_barcode']);
    $item_locations = array_map('trim', $_POST['item_location']);

    foreach ($unique_barcodes as $index => $barcode) {
        $location = htmlspecialchars($item_locations[$index]);
        if($location !== "na"){

            if (!isset($location) || empty($location)) {
                header("Location: ../set-item-locations/?missing_field=true");
                $conn->close();
                exit;
            }

            // Update item_location directly using the unique_barcode
            $sql = "UPDATE stocks SET item_location = ? WHERE unique_barcode = ? AND unique_key = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $location, $barcode, $uniqueKey);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: ../Inventory-stock/");
    exit;
}
?>
