<?php 
require_once "../config/database.php";
require_once "../config/on_session.php";

/**
 * ================================
 *  INBOUND IMPORTS (International & Local)
 * ================================
 * Exports data into CSV instead of echoing HTML
 */

$warehouse_id = null;
$warehouse_name = null;

if (isset($_GET['warehouse_id'])) {
    $warehouse_id = $_GET['warehouse_id'];
    
    $get_wh_name = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$warehouse_id' LIMIT 1";
    $get_wh_res = $conn->query($get_wh_name);
    if ($get_wh_res->num_rows > 0) {
        $row = $get_wh_res->fetch_assoc();
        $warehouse_name = $row['warehouse_name'];
    }
}

// Set headers so browser downloads it as a CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="inbound_summary - ' . $warehouse_name . ' ' . $date_for_file .'.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Section 1: Imports (International)
fputcsv($output, ['1', 'IMPORTS', 'QTY', 'TOTAL AMOUNT']);

$inbound_import_query = "
    SELECT 
        sup.supplier_name, 
        COUNT(s.unique_barcode) AS total_imports, 
        SUM(s.capital) AS total_imports_amount
    FROM supplier sup
    LEFT JOIN stocks s ON s.supplier = sup.hashed_id
    WHERE sup.local_international = 'International' AND s.warehouse = '$warehouse_id'
      AND s.date BETWEEN LAST_DAY(CURDATE() - INTERVAL 2 MONTH) + INTERVAL 1 DAY 
                      AND NOW()
    GROUP BY sup.supplier_name
";

$inbound_import_res = $conn->query($inbound_import_query);
if ($inbound_import_res->num_rows > 0) {
    while ($row = $inbound_import_res->fetch_assoc()) {
        fputcsv($output, ["",$row['supplier_name'], $row['total_imports'], $row['total_imports_amount']]);
    }
}

// Section 2: Locals
fputcsv($output, ['2', 'LOCALS', 'QTY', 'TOTAL AMOUNT']);

$inbound_local_query = "
    SELECT 
        sup.supplier_name, 
        COUNT(s.unique_barcode) AS total_locals, 
        SUM(s.capital) AS total_local_amount
    FROM supplier sup
    LEFT JOIN stocks s ON s.supplier = sup.hashed_id
    WHERE sup.local_international = 'Local' AND s.warehouse = '$warehouse_id'
      AND s.date BETWEEN LAST_DAY(CURDATE() - INTERVAL 2 MONTH) + INTERVAL 1 DAY 
                      AND NOW()
    GROUP BY sup.supplier_name
";


$inbound_local_res = $conn->query($inbound_local_query);
if ($inbound_local_res->num_rows > 0) {
    while ($row = $inbound_local_res->fetch_assoc()) {
        fputcsv($output, ["",$row['supplier_name'], $row['total_locals'], $row['total_local_amount']]);
    }
}

// Section 3: Hakot
fputcsv($output, ['3', 'HAKOT', 'QTY', 'TOTAL AMOUNT']);

$inbound_hakot_query = "
    SELECT 
        sup.supplier_name, 
        COUNT(s.unique_barcode) AS total_hakot, 
        SUM(s.capital) AS total_hakot_amount
    FROM supplier sup
    LEFT JOIN stocks s ON s.supplier = sup.hashed_id
    WHERE sup.local_international = 'Hakot' AND s.warehouse = '$warehouse_id'
      AND s.date BETWEEN LAST_DAY(CURDATE() - INTERVAL 2 MONTH) + INTERVAL 1 DAY 
                      AND NOW()
    GROUP BY sup.supplier_name
";

$inbound_hakot_res = $conn->query($inbound_hakot_query);
if ($inbound_hakot_res->num_rows > 0) {
    while ($row = $inbound_hakot_res->fetch_assoc()) {
        fputcsv($output, ["",$row['supplier_name'], $row['total_hakot'], $row['total_hakot_amount']]);
    }
}

// Close file
fclose($output);
exit;
?>
