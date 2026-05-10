<?php 
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '4G');
ini_set('display_errors', 1); 

include "../config/database.php";
include "../config/on_session.php";
require_once '../../vendor/autoload.php'; // mPDF

use Picqer\Barcode\BarcodeGeneratorPNG;

$html = [];

if (isset($_SESSION['forecast_data']) && !empty($_SESSION['forecast_data'])) {
    $forecast_data = $_SESSION['forecast_data'];
    $start_date = $_SESSION['forecast_start_date'];
    $end_date = $_SESSION['forecast_end_date'];
    $lead_time_local = $_SESSION['lead_time_local'];
    $lead_time_import = $_SESSION['lead_time_import'];
    $prepared_by = $user_fullname;

    // Inline CSS and HTML wrapper
    $html[] = '
    <!DOCTYPE html>
    <html>
    <head>
    <style>
        .forecast-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .forecast-table th, .forecast-table td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
        }
        .forecast-table th {
            background-color: #f2f2f2;
        }
        .forecast-table tr:nth-child(even) {
            background-color: #fafafa;
        }
        .forecast-table .reorder-row {
            background-color: #ffe5e5;
        }
        .forecast-header {
            background-color: purple;
            padding: 10px;
            color: white;
        }
        .forecast-meta {
            padding: 10px;
            background-color: #f8f9fa;
            font-size: 13px;
            border-bottom: 1px solid #ccc;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            color: white;
            font-size: 11px;
        }
        .bg-danger {
            background-color: #dc3545;
        }
        .bg-success {
            background-color: #28a745;
        }
    </style>
    </head>
    <body>';

    $html[] = '
    <div class="card">
        <div class="forecast-header">
            <h3>Forecasted Data Report</h3>
        </div>
        <div class="forecast-meta">
            <strong>Date Range:</strong> ' . $start_date . ' to ' . $end_date . '<br>
            <strong>Prepared By:</strong> ' . htmlspecialchars($prepared_by) . '<br>
            <strong>Average Lead Time:</strong> Local - ' . $lead_time_local . ' days | International - ' . $lead_time_import . ' days
        </div>
        <div class="card-body">
            <table class="forecast-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Parent Barcode</th>
                        <th>Supplier</th>
                        <th>Supplier Origin</th>
                        <th>Lead Time (Days)</th>
                        <th>Safety</th>
                        <th>Warehouse</th>
                        <th>Stocks</th>
                        <th>Avg Daily Sales</th>
                        <th>Incoming Stocks</th>
                        <th>Forecast (30 Days)</th>
                        <th>Est. Stockout (Days)</th>
                        <th>Reorder Point (Local)</th>
                        <th>Reorder Point (Intl)</th>
                        <th>Reorder?</th>
                        <th>Reorder Quantity</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($forecast_data as $row) {
        $row_class = $row['needs_reorder'] === 'Yes' ? 'reorder-row' : '';
        $badge_class = $row['needs_reorder'] === 'Yes' ? 'bg-danger' : 'bg-success';

        $html[] =  "<tr class='{$row_class}'>
            <td>{$row['description']}</td>
            <td>{$row['category']}</td>
            <td>{$row['brand']}</td>
            <td>{$row['parent_barcode']}</td>
            <td>{$row['supplier']}</td>
            <td>{$row['supplier_origin']}</td>
            <td>{$row['lead_time']}</td>
            <td>{$row['safety']}</td>
            <td>{$row['warehouse']}</td>
            <td>{$row['stocks']}</td>
            <td>{$row['avg_daily_sales']}</td>
            <td>{$row['incoming_stocks']}</td>
            <td>{$row['forecast_30_days']}</td>
            <td>{$row['est_stockout_days']}</td>
            <td>{$row['reorder_point_local']}</td>
            <td>{$row['reorder_point_intl']}</td>
            <td><span class='badge {$badge_class}'>{$row['needs_reorder']}</span></td>
            <td>{$row['reorder_quantity']}</td>
        </tr>";
    }

    $html[] = '</tbody></table></div></div></body></html>';

} else {
    $html[] = "<div style='padding: 10px; background-color: #ffeeba; color: #856404; border: 1px solid #ffeeba;'>No forecast data in session.</div>";
}

$mpdf = new \Mpdf\Mpdf([
    'format' => [297, 210], // A4 Landscape
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0,
]);

$mpdf->WriteHTML(implode('', $html));
$fileName = 'forecast.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
$mpdf->Output($fileName, 'D');
exit;
?>
