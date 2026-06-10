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
$staff_name = "";

// ===============================
// BUILD URL
// ===============================
$url = "http://lpoims.com/Inventory%20Management%20System/finish/?area={$area}&user_id={$staff_id}";


if(!empty($staff_id)) {
    $staff_details_query = "SELECT user_fname, user_lname FROM users WHERE hashed_id = '$staff_id' LIMIT 1";
    $staff_details_result = $conn->query($staff_details_query);
    if ($staff_details_result->num_rows > 0) {
        $staff_row = $staff_details_result->fetch_assoc();
        $staff_name = $staff_row['user_fname'] . ' ' . $staff_row['user_lname'];
    }
}


$warehouse_info_query = "SELECT w.warehouse_name, il.location_name FROM audit_assignments aa
LEFT JOIN item_location il ON aa.item_location = il.id
LEFT JOIN audit_logs al ON al.id = aa.audit_id
LEFT JOIN warehouse w ON al.warehouse = w.id
WHERE aa.audit_id = ? AND aa.id = ? AND aa.item_location = ? LIMIT 1";
$stmt = $conn->prepare($warehouse_info_query);
$stmt->bind_param("iii", $audit_id, $audit_assignment_id, $area);
$stmt->execute();
$warehouse_info_result = $stmt->get_result();
$warehouse_name = "";
if ($warehouse_info_result->num_rows > 0) {
    $warehouse_row = $warehouse_info_result->fetch_assoc();
    $warehouse_name = $warehouse_row['warehouse_name'];
}   
$stmt->close();


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


$currentDate = date('F d, Y');

$html = "
<html>
<head>
<style>
body{
    font-family: Arial, sans-serif;
    font-size:10px;
    height:100vh;
}

.card{
    border:2px solid #000;
    padding:8px;
    min-height:100%;
}

.logo{
    width:80px;
}

.ticket-title{
    text-align:center;
    font-size:18px;
    font-weight:bold;
    margin-top:10px;
    margin-bottom:15px;
    letter-spacing:1px;
}

.details{
    font-size:12px;
    line-height:1.8;
    margin-bottom:15px;
}

.location-box{
    border:3px solid #000;
    padding:15px;
    text-align:center;
    margin-bottom:20px;
}

.location-label{
    font-size:12px;
    text-align:left;
    font-weight:bold;
    margin-bottom:20px;
}

.location-title{
    font-size:28px;
    font-weight:bold;
    text-transform:uppercase;
    letter-spacing:2px;
}

.barcode-wrapper{
    text-align:center;
    margin:20px 0;
}

.barcode{
    width:80px;
}

.barcode-number{
    font-size:12px;
    font-weight:bold;
    margin-top:10px;
    letter-spacing:2px;
}

.quantity{
    font-size:14px;
    margin-top:20px;
}

.line{
    display:inline-block;
    border-bottom:1px solid #000;
    width:250px;
    height:14px;
}

.remarks{
    margin-top:20px;
    font-size:12px;
}

.remark-line{
    border-bottom:1px solid #000;
    height:24px;
    margin-bottom:8px;
}

.approval{
    margin-top:30px;
    font-size:14px;
}
</style>
</head>

<body>

<div class='card'>

    <div class='header'>

        <img class='logo' src='{$logoBase64}'>

        <div class='header-text'>
            <div class='ticket-title'>
                INVENTORY CONTROL TICKET
            </div>

            <div class='details'>
                <strong>WAREHOUSE:</strong> {$warehouse_name}<br>
                <strong>DATE:</strong> {$currentDate}<br>
                <strong>AUDITOR:</strong> {$staff_name}
            </div>
        </div>

        <div class='clear'></div>

    </div>

    <div class='content'>

        <div class='location-box'>

            <div class='location-label'>
                Box No. / Area Location
            </div>

            <div class='location-title'>
                {$location_name}
            </div>

        </div>

        <div class='barcode-wrapper'>

            <img class='barcode' src='{$barcodeBase64}' />

            <div class='barcode-number'>
                {$barcodeData}
            </div>

        </div>

    </div>

    <div class='footer'>
        <strong>Quantity:</strong>
        <span class='qty-line'></span>
        <br><br>

        Approved by:
        <span class='approval-line'></span>
    </div>

</div>

</body>
</html>
";

// ===============================
// PDF GENERATION
// ===============================
$mpdf = new \Mpdf\Mpdf([
    'format' => [101.6, 152.4], // 4x6 inches (portrait waybill)
    'margin_left' => 5,
    'margin_right' => 5,
    'margin_top' => 5,
    'margin_bottom' => 5,
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