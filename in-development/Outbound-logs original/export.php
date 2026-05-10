<?php 
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '4G');
ini_set('display_errors', 1); 

include "../config/database.php";
include "../config/on_session.php";
require_once '../../vendor/autoload.php'; // mPDF

use Picqer\Barcode\BarcodeGeneratorPNG;

if (isset($_GET['name'])) {
    $outbound_id = $_GET['name'];

    $stmt = $conn->prepare("SELECT u.user_fname, u.user_lname, w.warehouse_name, ol.*, lp.logistic_name, c.courier_name
                            FROM outbound_logs ol
                            LEFT JOIN users u ON u.hashed_id = ol.user_id
                            LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
                            LEFT JOIN logistic_partner lp ON lp.hashed_id = ol.platform
                            LEFT JOIN courier c ON c.hashed_id = ol.courier 
                            WHERE ol.hashed_id = ? LIMIT 1");
    $stmt->bind_param("s", $outbound_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $staff_name = $row['user_fname'] . " " . $row['user_lname'];
        $warehouse_outbound = $row['warehouse_name'];
        $customer_fullname = $row['customer_fullname'];
        $order_num = $row['order_num'];
        $order_line_id = $row['order_line_id'];
        $logistic_name = $row['logistic_name'];
        $courier = $row['courier_name'];
        $date_Sent = $row['date_sent'];

        switch ($row['status']) {
            case 0:
                $outbound_status = "Paid";
                break;
            case 1:
                $outbound_status = "Returned";
                break;
            case 6:
                $outbound_status = "Outbounded";
                break;
            default:
                $outbound_status = "Unknown Status";
        }

        $date_paid = $row['date_paid'] ?? '';

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Outbound Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 20px;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }
        .section {
            margin-bottom: 20px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        .label {
            font-weight: bold;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th, .table td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 10px;
        }
        .table th {
            background-color: #e6f2ff;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Outbound Report</h1>
</div>

<div class="section">
    <div class="row"><span class="label">Reference No:</span> <span>' . $outbound_id . '</span></div>
    <div class="row"><span class="label">Status:</span> <span>' . $outbound_status . '</span></div>
    <div class="row"><span class="label">Logistic Partner:</span> <span>' . $logistic_name . '</span></div>
    <div class="row"><span class="label">Courier:</span> <span>' . $courier . '</span></div>
    <div class="row"><span class="label">Order No:</span> <span>' . $order_num . '</span></div>
    <div class="row"><span class="label">Order Line ID:</span> <span>' . $order_line_id . '</span></div>
    <div class="row"><span class="label">Customer:</span> <span>' . $customer_fullname . '</span></div>
    <div class="row"><span class="label">Outbound Date:</span> <span>' . $date_Sent . '</span></div>
    <div class="row"><span class="label">Date Paid:</span> <span>' . $date_paid . '</span></div>
</div>

<div class="section">
    <h4>Item Details</h4>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Item Name</th>
                <th>Brand</th>
                <th>Category</th>
                <th>Parent Barcode</th>
                <th class="text-right">Qty Before</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Qty After</th>
                <th class="text-right">Sold Price</th>
            </tr>
        </thead>
        <tbody>';

        $query = "
            SELECT p.description, b.brand_name, c.category_name, s.parent_barcode, 
                   oc.quantity_before, oc.quantity_after, COUNT(s.parent_barcode) AS quantity, 
                   s.capital, oc.sold_price
            FROM outbound_content oc
            LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
            LEFT JOIN product p ON p.hashed_id = s.product_id
            LEFT JOIN brand b ON b.hashed_id = p.brand
            LEFT JOIN category c ON c.hashed_id = p.category
            WHERE oc.hashed_id = ? 
            GROUP BY s.parent_barcode";

        $stmt2 = $conn->prepare($query);
        $stmt2->bind_param("s", $outbound_id);
        $stmt2->execute();
        $res = $stmt2->get_result();

        $count = 1;
        $total = 0;
        $total_profit = 0;
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $productDescription = $row['description'];
                $brandName = $row['brand_name'];
                $categoryName = $row['category_name'];
                $parentBarcode = $row['parent_barcode'];
                $quantityBefore = $row['quantity_before'];
                $quantityAfter = $row['quantity_after'];
                $quantity = $row['quantity'];
                $productCapital = $row['capital'];
                $soldPrice = $row['sold_price'];
                $sub_Total = $quantity * $soldPrice;
                $profit = $soldPrice - $productCapital;
                $sub_profit = $profit * $quantity;

                $html .= '<tr>
                    <td>' . $count . '</td>
                    <td>' . $productDescription . '</td>
                    <td>' . $brandName . '</td>
                    <td>' . $categoryName . '</td>
                    <td>' . $parentBarcode . '</td>
                    <td class="text-right">' . $quantityBefore . '</td>
                    <td class="text-right">' . $quantity . '</td>
                    <td class="text-right">' . $quantityAfter . '</td>
                    <td class="text-right">₱ ' . number_format($soldPrice, 2) . '</td>
                </tr>';

                $count++;
                $total += $sub_Total;
                $total_profit += $sub_profit;
            }
        } else {
            $html .= "<tr><td colspan='9' class='text-center'>No items found</td></tr>";
        }

        $html .= '</tbody>
    </table>
</div>

<div class="footer">
    <div><strong>Warehouse:</strong> ' . $warehouse_outbound . '</div>
    <div><strong>Staff:</strong> ' . $staff_name . '</div>
</div>

</body>
</html>';

        $mpdf = new \Mpdf\Mpdf([
            'format' => [297, 210],
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);

        $mpdf->WriteHTML($html);
        $fileName = 'Outbound_' . $outbound_id . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $mpdf->Output($fileName, 'D');
        exit;
    }
}
?>
