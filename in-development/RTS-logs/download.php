<?php 
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '4G');
ini_set('display_errors', 1); 

include "../config/database.php";
include "../config/on_session.php";
require_once '../../vendor/autoload.php'; // mPDF

use Picqer\Barcode\BarcodeGeneratorPNG;

header('Content-Type: image/png');
if (isset($_GET['id'])) {
    $rts_id = $_GET['id'];

    $for_query = "SELECT rts.for, rts.status, rts.proof, rts.front, rts.back, rts.warranty, rts.reason, w.warehouse_name FROM rts_logs rts LEFT JOIN warehouse w ON w.hashed_id = rts.warehouse WHERE rts.id = '$rts_id' LIMIT 1";
    $for_res = $conn->query($for_query);
    $for = "";
    if ($for_res->num_rows > 0) {
        $row = $for_res->fetch_assoc();
        $for = $row['for'];
        $rts_log_status = $row['status'];
        $reason = $row['reason'];
        $rts_proof = $row['proof'];
        $rts_front = $row['front']; 
        $rts_back = $row['back'];
        $rts_warranty = $row['warranty'];
        $rts_warehouse_name = $row['warehouse_name'];
    }

    $table_rows = [];

    $sql = "SELECT DISTINCT p.description, b.brand_name, c.category_name, rts.unique_barcode, rts.status, rts.returned_date
            FROM rts_content rts
            LEFT JOIN stocks s ON s.unique_barcode = rts.unique_barcode
            LEFT JOIN product p ON p.hashed_id = s.product_id
            LEFT JOIN brand b ON b.hashed_id = p.brand
            LEFT JOIN category c ON c.hashed_id = p.category
            WHERE rts.rts_id = '$rts_id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $number = 0;
        while ($row = $result->fetch_assoc()) {
            $number++;
            $product_description = $row['description'];
            $brand_name = $row['brand_name'];
            $category_name = $row['category_name'];
            $barcode = $row['unique_barcode'];
            $returned_date = $row['returned_date'];

            switch ($row['status']) {
                case 0:
                    $rts_status = '<span class="badge bg-primary">Returned</span>';
                    break;
                case 1:
                    $rts_status = '<span class="badge bg-success">Returned and Refunded</span>';
                    break;
                default:
                    $rts_status = '<span class="badge bg-warning">Returned and Replaced</span>';
            }

            $date_display = ($row['status'] == 0) ? '-' : $returned_date;

            $table_rows[] = "
            <tr>
                <td>$number</td>
                <td>$product_description</td>
                <td>$brand_name</td>
                <td>$category_name</td>
                <td>$rts_status</td>
                <td>$barcode</td>
                <td>$date_display</td>
            </tr>";
        }
    }

    $proof_section = '
    <h3>Return Proof Images</h3>
    <table class="image-table">
        <tr>
            <th>PROOF</th>
            <th>FRONT</th>
        </tr>
        <tr>
            <td><img src="../../assets/img_rts/' . $rts_id . '/' . trim($rts_proof) . '" style="height: 200px;" alt="Proof"/></td>
            <td><img src="../../assets/img_rts/' . $rts_id . '/' . trim($rts_front) . '" style="height: 200px;" alt="Front"/></td>
        </tr>
        <tr>
            <th>BACK</th>
            <th>WARRANTY</th>
        </tr>
        <tr>
            <td><img src="../../assets/img_rts/' . $rts_id . '/' . trim($rts_back) . '" style="height: 200px;" alt="Back"/></td>
            <td><img src="../../assets/img_rts/' . $rts_id . '/' . trim($rts_warranty) . '" style="height: 200px;" alt="Warranty"/></td>
        </tr>
    </table>';
}


$html = '
<!DOCTYPE html>
<html>
<head>
    <title>Return to Supplier Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: \'Poppins\', sans-serif;
            padding: 40px;
            background-color: #f9f9f9;
            color: #333;
        }

        h2 {
            font-size: 28px;
            color: #222;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }

        h3, h4 {
            color: #007bff;
            margin-top: 40px;
        }

        .info-section {
            text-align: right;
            margin-top: -100px;
            margin-bottom: 40px;
            font-size: 14px;
        }

        .info-section p {
            margin: 4px 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            background-color: #fff;
        }

        th {
            background-color: #007bff;
            color: #fff;
            font-weight: 600;
            text-align: center;
        }

        td, th {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        tr:hover {
            background-color: #f1f9ff;
        }

        .badge {
            padding: 6px 10px;
            border-radius: 12px;
            font-size: 12px;
            color: #fff;
        }

        .bg-primary { background-color: #007bff; }
        .bg-success { background-color: #28a745; }
        .bg-warning { background-color: #ffc107; color: #000; }

        .image-table th {
            background-color: #343a40;
            color: #fff;
        }

        .image-table td {
            padding: 10px;
        }

        .image-table img {
            width: auto;
            height: 150px; /* Fixed height */
            border-radius: 8px;
            display: block;
            margin: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .details {
            margin-top: 20px;
            font-size: 16px;
        }

        .details strong {
            width: 120px;
            display: inline-block;
        }
    </style>
</head>
<body>

<h2 style="margin-top: 30px; margin-left: 30px;">Return to Supplier Report</h2>

<div class="info-section" style="padding: 20px;">
    <p><strong>Warehouse:</strong> ' . htmlspecialchars($rts_warehouse_name) . '</p>
    <p><strong>Processed by:</strong> ' . htmlspecialchars($user_fname) . '</p>
    <p><strong>Date:</strong> ' . htmlspecialchars($date_today) . '</p>
</div>

<div class="details" style="padding: 20px;">
    <p><strong>RTS ID:</strong> ' . $rts_id . '</p>
    <p><strong>For:</strong> ' . htmlspecialchars($for) . '</p>
    <p><strong>Status:</strong> ' . ($rts_log_status == 0 ? 'Pending' : ($rts_log_status == 1 ? 'Approved' : 'Replaced')) . '</p>
    <p><strong>Reason:</strong> ' . htmlspecialchars($reason) . '</p>
</div>

<h3>Returned Items</h3>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Description</th>
            <th>Brand</th>
            <th>Category</th>
            <th>Status</th>
            <th>Barcode</th>
            <th>Returned Date</th>
        </tr>
    </thead>
    <tbody>
        ' . implode('', $table_rows) . '
    </tbody>
</table>

' . $proof_section . '


</body>
</html>';


$mpdf = new \Mpdf\Mpdf([
    'format' => [215, 279],
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0,
]);

$mpdf->WriteHTML($html);
$fileName = 'RTS#'. $rts_id . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
$mpdf->Output($fileName, 'D');
exit;

echo json_encode($response);
exit;