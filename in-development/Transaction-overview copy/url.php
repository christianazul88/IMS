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

// Sanitize GET inputs
$from = filter_input(INPUT_GET, 'from', FILTER_SANITIZE_STRING);
$to = filter_input(INPUT_GET, 'to', FILTER_SANITIZE_STRING);
$barcode_keyword = filter_input(INPUT_GET, 'bc', FILTER_SANITIZE_STRING);
$imploded_users = filter_input(INPUT_GET, 'staffs', FILTER_SANITIZE_STRING);
$warehouse_transaction = filter_input(INPUT_GET, 'wh', FILTER_SANITIZE_STRING);

$warehouse_trans_sql = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$warehouse_transaction' LIMIT 1";
$warehouse_trans_res = $conn->query($warehouse_trans_sql);
if($warehouse_trans_res->num_rows>0){
    $row = $warehouse_trans_res->fetch_assoc();
    $ware_treans = $row['warehouse_name'];
} else {
    $ware_treans = "N/A";
}

$response = array('success' => false, 'message' => 'Something went wrong.');
$table_rows = [];

if ($from && $to && $warehouse_transaction) {
    $additional = '';
    if (!empty($barcode_keyword)) {
        $barcode_keyword = "%{$barcode_keyword}%";
        $additional = " AND oc.unique_barcode LIKE ?";
    }

    $query = "
    SELECT oc.unique_barcode, oc.sold_price, p.description, b.brand_name, c.category_name, s.capital, p.keyword, 
           ol.hashed_id AS outbound_id, ol.date_sent, w.warehouse_name, ol.customer_fullname, cr.courier_name, 
           lp.logistic_name, ol.order_num, ol.order_line_id, u.user_fname, u.user_lname, ol.user_id AS outbounder
    FROM outbound_content oc
    LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
    LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
    LEFT JOIN courier cr ON cr.hashed_id = ol.courier
    LEFT JOIN logistic_partner lp ON lp.hashed_id = ol.platform
    LEFT JOIN users u ON u.hashed_id = ol.user_id
    WHERE ol.date_sent BETWEEN ? AND ? AND ol.warehouse = ? $additional";

    $stmt = $conn->prepare($query);
    if ($additional) {
        $stmt->bind_param("sss", $from, $to, $warehouse_transaction);
        $stmt->bind_param("sss", $from, $to, $warehouse_transaction, $barcode_keyword);
    } else {
        $stmt->bind_param("sss", $from, $to, $warehouse_transaction);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $number = 1;
    while ($row = $result->fetch_assoc()) {
        $outbounder = $row['outbounder'];
        $should_include = true;

        if (!empty($imploded_users)) {
            $should_include = strpos($imploded_users, $outbounder) !== false;
        }

        if ($should_include) {
            $table_rows[] = '<tr>
                <td>' . $number++ . '</td>
                <td>' . htmlspecialchars($row['description']) . '</td>
                <td>' . htmlspecialchars($row['brand_name']) . '</td>
                <td>' . htmlspecialchars($row['category_name']) . '</td>
                <td>' . htmlspecialchars($row['unique_barcode']) . '</td>
                <td>' . htmlspecialchars($row['keyword']) . '</td>
                <td style="min-width: 200px;">' . htmlspecialchars($row['date_sent']) . '</td>
                <td>' . htmlspecialchars($row['capital']) . '</td>
                <td style="min-width: 150px;">' . htmlspecialchars($row['sold_price']) . '</td>
                <td style="min-width: 150px;">' . htmlspecialchars($row['outbound_id']) . '</td>
                <td style="min-width: 150px;">' . htmlspecialchars($row['order_num']) . '</td>
                <td style="min-width: 150px;">' . htmlspecialchars($row['order_line_id']) . '</td>
                <td>' . htmlspecialchars($row['courier_name']) . '</td>
                <td>' . htmlspecialchars($row['logistic_name']) . '</td>
                <td>' . htmlspecialchars($row['customer_fullname']) . '</td>
                <td>' . htmlspecialchars($row['user_fname'] . ' ' . $row['user_lname']) . '</td>
            </tr>';
        }
    }

    $tables = '<tr>
        <th class="label">PREPARED BY:</th><td class="value">' . htmlspecialchars($user_fullname) . '</td>
        <th class="label">FROM:</th><td class="value">' . date('F j, Y', strtotime($from)) . '</td>
        <th class="label">TO:</th><td class="value">' . date('F j, Y', strtotime($to)) . '</td>
        <th class="label">Date:</th><td class="value">' . date('F j, Y') . '</td>
        <th class="label">Warehouse:</th><td class="value">' . $ware_treans . '</td>
    </tr>';

    $html = "
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th, td {
                padding: 6px;
                border: 1px solid #ccc;
                text-align: left;
            }
            .label {
                background-color: #f0f0f0;
                font-weight: bold;
                border: none;
            }
            .value {
                background-color: #ffffff;
                border: none;
            }
            .meta-table {
                border: none;
            }
            .meta-table th, .meta-table td {
                border: none;
            }
            h1 {
                text-align: center;
                margin: 30px 0;
            }
        </style>
    </head>
    <body>
        <h1>Transaction Overview</h1>
        <table class='meta-table'>$tables</table>
        <table>
            <thead>
                <tr>
                    <th>#</th><th>DESCRIPTION</th><th>BRAND</th><th>CATEGORY</th><th>BARCODE</th>
                    <th>KEYWORD</th><th>OUTBOUND DATE</th><th>CAPITAL</th><th>SOLD AMOUNT</th>
                    <th>OUTBOUND ID</th><th>ORDER NO</th><th>ORDER LINE ID</th>
                    <th>COURIER</th><th>LOGISTIC</th><th>CUSTOMER</th><th>STAFF</th>
                </tr>
            </thead>
            <tbody>" . implode('', $table_rows) . "</tbody>
        </table>
    </body>
    </html>";

    $mpdf = new \Mpdf\Mpdf([
        'format' => [297, 210],
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_top' => 0,
        'margin_bottom' => 0,
    ]);

    $mpdf->WriteHTML($html);
    $fileName = $from . ' to ' . $to . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    $mpdf->Output($fileName, 'D');
    exit;
}

echo json_encode($response);
exit;
?>
