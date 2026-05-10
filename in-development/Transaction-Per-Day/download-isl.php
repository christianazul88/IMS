<?php 
require_once "../config/database.php";
require_once "../config/on_session.php";

// Set CSV headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="warehouse_report.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV column headers
fputcsv($output, ['WAREHOUSE', 'QTY', 'TOTAL AMOUNT']);

$query = "SELECT 
            COUNT(s.unique_barcode) AS total_qty_per_loc,
            SUM(s.capital) AS total_amount_per_loc,
            w.warehouse_name
          FROM stocks s
          LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
          WHERE s.item_status = 0
          GROUP BY w.warehouse_name, s.warehouse 
          ORDER BY w.warehouse_name";

$res = $conn->query($query);

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $total_qty = $row['total_qty_per_loc'];
        $total_amount = $row['total_amount_per_loc'];
        $warehouse_name = $row['warehouse_name'];

        // Write each row to CSV
        fputcsv($output, [$warehouse_name, $total_qty, $total_amount]);
    }
}

// Close output
fclose($output);
exit;
