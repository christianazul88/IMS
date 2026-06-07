<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '4G');

include "../config/database.php";
include "../config/on_session.php";

require_once '../../vendor/autoload.php';

use Picqer\Barcode\BarcodeGeneratorPNG;

$audit_id = $_GET['audit_id'] ?? '';
$area = $_GET['area'] ?? '';
$audit_assignment_id = $_GET['audit_assignment_id'] ?? '';
$staff_id = $_GET['user_id'] ?? '';

// ===============================
// BUILD URL
// ===============================
$url = "http://lpoims.com/Inventory%20Management%20System/finish/?area={$area}&user_id={$staff_id}";

// ===============================
// 1. CREATE TABLE IF NOT EXISTS
// ===============================
$conn->query("
    CREATE TABLE IF NOT EXISTS ims_urls (
        id INT AUTO_INCREMENT PRIMARY KEY,
        url VARCHAR(300) NOT NULL UNIQUE
    ) AUTO_INCREMENT=888
");

// ===============================
// 2. CHECK IF URL EXISTS
// ===============================
$stmt = $conn->prepare("SELECT id FROM ims_urls WHERE url = ? LIMIT 1");
$stmt->bind_param("s", $url);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $barcode_id = $row['id']; // already exists
} else {
    // ===============================
    // 3. INSERT NEW URL
    // ===============================
    $insert = $conn->prepare("INSERT INTO ims_urls (url) VALUES (?)");
    $insert->bind_param("s", $url);
    $insert->execute();

    $barcode_id = $insert->insert_id; // new ID (starts at 888)
    $insert->close();
}

$stmt->close();

// ===============================
// GET LOCATION NAME
// ===============================
$location_name = "audit";

$stmt = $conn->prepare("
    SELECT il.location_name
    FROM audit_assignments aa
    LEFT JOIN item_location il ON aa.item_location = il.id
    WHERE aa.audit_id = ?
    AND aa.id = ?
    AND aa.item_location = ?
    LIMIT 1
");

$stmt->bind_param("iii", $audit_id, $audit_assignment_id, $area);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $location_name = $row['location_name'];
}

$stmt->close();

// Clean filename
$safe_location = preg_replace('/[^A-Za-z0-9_\-]/', '_', $location_name);

// ===============================
// BARCODE DATA (NOW USING ID)
// ===============================
$barcodeData = (string)$barcode_id;

// Generate barcode
$generator = new BarcodeGeneratorPNG();
$barcodeImage = $generator->getBarcode($barcodeData, $generator::TYPE_CODE_128);

$barcodeBase64 = 'data:image/png;base64,' . base64_encode($barcodeImage);

// ===============================
// LOGO
// ===============================
$logoPath = '../../assets/img/logo/LPO Emblem.png';
$logoData = file_get_contents($logoPath);
$logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);

// ===============================
// HTML
// ===============================
$html = "
<html>
<head>
<style>

body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}

.card {
    width: 100%;
    height: 100%;
    padding: 6px 10px;
    box-sizing: border-box;
    text-align: center;
    border: 1px solid #ddd;
}

.logo {
    width: 45px;
    margin-bottom: 4px;
}

.title {
    font-size: 10px;
    font-weight: bold;
    letter-spacing: 1px;
    margin-bottom: 4px;
}

.barcode {
    width: 240px;
    margin: 2px 0;
}

.code {
    font-size: 10px;
    letter-spacing: 0.5px;
    margin-top: 2px;
}

.footer {
    margin-top: 10px;
    font-size: 9px;
    text-align: left;
    width: 100%;
}

.line {
    display: inline-block;
    border-bottom: 1px solid #000;
    width: 120px;
    margin-left: 5px;
}

</style>
</head>

<body>

<div class='card'>

    <img class='logo' src='{$logoBase64}' />

    <div class='title'>INVENTORY AUDIT BARCODE</div>

    <img class='barcode' src='{$barcodeBase64}' />

    <div class='code'>{$barcodeData}</div>

    <div class='footer'>
        Approved by: <span class='line'></span>
    </div>

</div>

</body>
</html>
";

// ===============================
// PDF GENERATION
// ===============================
$mpdf = new \Mpdf\Mpdf([
    'format' => [60, 40],
    'margin_left' => 3,
    'margin_right' => 3,
    'margin_top' => 3,
    'margin_bottom' => 3,
]);

$mpdf->WriteHTML($html);

// ===============================
// FILE NAME = LOCATION NAME
// ===============================
$fileName = $safe_location . ".pdf";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');

$mpdf->Output($fileName, 'D');
exit;