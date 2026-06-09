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
$staff_name = "";







$warehouse_info_query = "SELECT w.warehouse_name, al.warehouse FROM audit_logs al
LEFT JOIN warehouse w ON al.warehouse = w.hashed_id
WHERE al.id = ? LIMIT 1";
$stmt = $conn->prepare($warehouse_info_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$warehouse_info_result = $stmt->get_result();
$warehouse_name = "";
if ($warehouse_info_result->num_rows > 0) {
    $warehouse_row = $warehouse_info_result->fetch_assoc();
    $warehouse_audit_id = $warehouse_row['warehouse'];
    $warehouse_name = $warehouse_row['warehouse_name'];
}   
$stmt->close();




// // ===============================
// // GET LOCATION NAME
// // ===============================
// $location_name = "audit";

// $stmt = $conn->prepare("
//     SELECT il.location_name
//     FROM audit_assignments aa
//     LEFT JOIN item_location il ON aa.item_location = il.id
//     WHERE aa.audit_id = ?
//     AND aa.id = ?
//     AND aa.item_location = ?
//     LIMIT 1
// ");

// $stmt->bind_param("iii", $audit_id, $audit_assignment_id, $area);
// $stmt->execute();
// $res = $stmt->get_result();

// if ($row = $res->fetch_assoc()) {
//     $location_name = $row['location_name'];
// }

// $stmt->close();

// // Clean filename
// $safe_location = preg_replace('/[^A-Za-z0-9_\-]/', '_', $location_name);

// ===============================
// LOGO (DEFINE ONCE)
// ===============================
$logoPath = '../../assets/img/logo/LPO Emblem.png';

$logoData = file_get_contents($logoPath);

$logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);

$stylesheet = "
<style>
body{
    font-family: Arial, sans-serif;
    font-size:12px;
}

.card{
    height:190mm;
    border:2px solid #000;
    padding:15px;
    box-sizing:border-box;
}

.logo{ width:80px; }

.ticket-title{
    text-align:center;
    font-size:24px;
    font-weight:bold;
    margin:10px 0 15px;
}

.details{
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
    font-weight:bold;
    margin-bottom:20px;
}

.location-title{
    font-size:42px;
    font-weight:bold;
    text-transform:uppercase;
}

.barcode-wrapper{
    text-align:center;
    margin-top:25px;
}

.barcode{ width:220px; }

.barcode-number{
    font-size:16px;
    font-weight:bold;
    margin-top:10px;
    letter-spacing:2px;
}
</style>
";


$currentDate = date('F d, Y');
$html_cards = "";
$locations = [];

$location_query = "SELECT id, location_name FROM item_location WHERE warehouse = ? ORDER BY id ASC";
$stmt = $conn->prepare($location_query);    
$stmt->bind_param("s", $warehouse_audit_id);
$stmt->execute();
$location_result = $stmt->get_result();

while ($row = $location_result->fetch_assoc()) {

    $locations[] = $row['location_name'];

    $area = $row['id'];
    $location_name = $row['location_name'];

    // ============================================
    // CREATE UNIQUE URL FOR THIS LOCATION
    // ============================================
    $url = "http://lpoims.com/Inventory%20Management%20System/finish/?area={$area}&user_id=";

    // ============================================
    // CHECK / INSERT URL
    // ============================================
    $stmtUrl = $conn->prepare("SELECT id FROM ims_urls WHERE url = ? LIMIT 1");
    $stmtUrl->bind_param("s", $url);
    $stmtUrl->execute();
    $resUrl = $stmtUrl->get_result();

    if ($urlRow = $resUrl->fetch_assoc()) {

        $barcode_id = $urlRow['id'];

    } else {

        $insert = $conn->prepare("INSERT INTO ims_urls (url) VALUES (?)");
        $insert->bind_param("s", $url);
        $insert->execute();

        $barcode_id = $insert->insert_id;

        $insert->close();
    }

    $stmtUrl->close();

    // ============================================
    // GENERATE BARCODE FOR THIS LOCATION
    // ============================================
    $barcodeData = (string)$barcode_id;

    $generator = new BarcodeGeneratorPNG();

    $barcodeImage = $generator->getBarcode(
        $barcodeData,
        $generator::TYPE_CODE_128
    );

    $barcodeBase64 = 'data:image/png;base64,' .
        base64_encode($barcodeImage);

    // ============================================
    // GET / CREATE AUDIT ASSIGNMENT
    // ============================================
    $check_audit_assignment_query = "
        SELECT id
        FROM audit_assignments
        WHERE audit_id = ?
        AND item_location = ?
        AND warehouse = ?
        LIMIT 1
    ";

    $check_stmt = $conn->prepare($check_audit_assignment_query);
    $check_stmt->bind_param(
        "iis",
        $audit_id,
        $area,
        $warehouse_audit_id
    );

    $check_stmt->execute();

    $check_res = $check_stmt->get_result();

    if ($check_res->num_rows === 0) {

        $audit_assignment_insert = $conn->prepare("
            INSERT INTO audit_assignments
            (
                audit_id,
                item_location,
                warehouse,
                `status`
            )
            VALUES
            (
                ?,
                ?,
                ?,
                'pending'
            )
        ");

        $audit_assignment_insert->bind_param(
            "iis",
            $audit_id,
            $area,
            $warehouse_audit_id
        );

        $audit_assignment_insert->execute();

        $audit_assignment_id =
            $audit_assignment_insert->insert_id;

        $audit_assignment_insert->close();

    } else {

        $existing_row = $check_res->fetch_assoc();

        $audit_assignment_id =
            $existing_row['id'];
    }

    $check_stmt->close();

    // ============================================
    // SAFE FILE NAME
    // ============================================
    $safe_location = preg_replace(
        '/[^A-Za-z0-9_\-]/',
        '_',
        $location_name
    );

    // ============================================
    // APPEND PAGE HTML
    // ============================================
    $html_cards .= "

    

    <div class='card'>

        <img class='logo' src='{$logoBase64}'>

        <div class='ticket-title'>
            INVENTORY CONTROL TICKET
        </div>

        <div class='details'>
            <strong>WAREHOUSE:</strong> {$warehouse_name}<br>
            <strong>DATE:</strong> {$currentDate}<br>
            <strong>AUDITOR:</strong> {$staff_name}
        </div>

        <div class='location-box'>

            <div class='location-label'>
                Box No. / Area Location
            </div>

            <div class='location-title'>
                {$location_name}
            </div>

        </div>

        <div class='barcode-wrapper'>

            <img
                class='barcode'
                src='{$barcodeBase64}'
            >

            <div class='barcode-number'>
                {$barcodeData}
            </div>

        </div>

        <br><br>

        <strong>Quantity:</strong>
        ______________________

        <br><br><br>

        <strong>Approved by:</strong>
        ______________________

    </div>

    <pagebreak />
    ";
}

$html = "
<html>
    <head>
        {$stylesheet}
    </head>
    <body>
        {$html_cards}
    </body>
</html>
";

// ===============================
// PDF GENERATION
// ===============================
$mpdf = new \Mpdf\Mpdf([
    'format' => [140, 216],
    'margin_left' => 5,
    'margin_right' => 5,
    'margin_top' => 5,
    'margin_bottom' => 5,
]);

$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
$chunks = str_split($html, 50000);

foreach ($chunks as $chunk) {
    $mpdf->WriteHTML($chunk, \Mpdf\HTMLParserMode::HTML_BODY);
}
// ===============================
// FILE NAME = LOCATION NAME
// ===============================
$fileName = "Item Locations.pdf";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');

$mpdf->Output($fileName, 'D');
exit;