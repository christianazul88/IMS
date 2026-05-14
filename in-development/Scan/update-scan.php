<?php

include "../config/database.php";
include "../config/on_session.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_POST['barcode'])) {
        die("No barcode provided.");
    }

    $barcode = trim($_POST['barcode']);

    if (empty($barcode)) {
        die("Barcode is empty.");
    }

    $audit_id = $_SESSION['audit_id'];
    $selected_area = $_SESSION['selected_area'];

    // =========================================================
    // GET BARCODE DETAILS FROM DATABASE
    // =========================================================
    $stmt = $conn->prepare("
        SELECT 
            item_location,
            supplier,
            item_status,
            warehouse,
            hashed_id
        FROM stocks
        WHERE unique_barcode = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $barcode);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("Barcode not found in stocks.");
    }

    $stock_data = $result->fetch_assoc();

    // =========================================================
    // JSON FILE SETUP
    // =========================================================
    $json_folder = "../audit_json/";

    // CREATE FOLDER IF NOT EXIST
    if (!is_dir($json_folder)) {
        mkdir($json_folder, 0777, true);
    }

    // JSON FILE NAME
    $json_filename = $audit_id . "-" . $selected_area . ".json";
    $json_path = $json_folder . $json_filename;

    // CREATE FILE IF NOT EXIST
    if (!file_exists($json_path)) {
        file_put_contents($json_path, json_encode([]));
    }

    // LOAD EXISTING JSON DATA
    $json_data = json_decode(file_get_contents($json_path), true);

    // SAFETY CHECK
    if (!is_array($json_data)) {
        $json_data = [];
    }

    // =========================================================
    // CHECK IF BARCODE ALREADY EXISTS
    // =========================================================
    foreach ($json_data as $item) {

        if (
            isset($item['barcode']) &&
            $item['barcode'] === $barcode
        ) {

            echo "DENIED: Barcode already scanned.";
            exit;

        }
    }

    // =========================================================
    // SAVE BARCODE + DETAILS
    // =========================================================
    $json_data[] = [
        "barcode"       => $barcode,
        "item_location" => $stock_data['item_location'],
        "supplier"      => $stock_data['supplier'],
        "item_status"   => $stock_data['item_status'],
        "warehouse"     => $stock_data['warehouse'],
        "hashed_id"     => $stock_data['hashed_id'],
        "datetime"      => date("Y-m-d H:i:s")
    ];

    // SAVE BACK TO FILE
    file_put_contents(
        $json_path,
        json_encode($json_data, JSON_PRETTY_PRINT)
    );

    echo "Scan successful for barcode: " . htmlspecialchars($barcode);

}

?>