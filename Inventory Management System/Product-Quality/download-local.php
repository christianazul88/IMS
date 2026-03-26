<?php
require_once "../config/database.php";
require_once "../config/on_session.php";

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Product Quality Report -  ' . $date_for_file .'.csv');

$start = $_GET['start'];
$end = $_GET['end'];
// Open output stream
$output = fopen('php://output', 'w');

// ---- LOCAL DEFECTIVE RETURNS ----
fputcsv($output, ["RETURNED AS DEFECTIVE BETWEEN $start AND $end"]);
fputcsv($output, ["DESCRIPTION", "BRAND", "CATEGORY", "SUPPLIER", "LOCAL / IMPORT", "QTY", "TOTAL_AMOUNT"]);

$returned_def_query = "
    SELECT
        p.id,
        p.description,
        b.brand_name,
        c.category_name,
        sup.supplier_name,
        sup.local_international,
        s.capital,
        COUNT(s.product_id) AS total_qty_of_item,
        SUM(s.capital) AS total_amount,
        w.warehouse_name
    FROM `returns` r 
    LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    WHERE r.supplier_type = 'Local' AND r.fault_type = 'DEFECTIVE'
    AND r.date BETWEEN '$start' AND '$end'
    GROUP BY p.id, s.product_id, p.description
    ORDER BY sup.local_international, w.warehouse_name ASC
";
$returned_def_res = $conn->query($returned_def_query);

if ($returned_def_res && $returned_def_res->num_rows > 0) {
    while ($row = $returned_def_res->fetch_assoc()) {
        fputcsv($output, [
            $row['description'],
            $row['brand_name'],
            $row['category_name'],
            $row['supplier_name'],
            $row['local_international'],
            $row['total_qty_of_item'],
            $row['total_amount']
        ]);
    }
}

// ---- INTERNATIONAL DELIVERY FAILED RETURNS ----
fputcsv($output, []); // empty row for separation
fputcsv($output, ["DELIVERY FAILED BETWEEN $start AND $end"]);
fputcsv($output, ["DESCRIPTION", "BRAND", "CATEGORY", "PLATFORM", "QTY", "TOTAL_AMOUNT"]);

$returned_dF_query = "
    SELECT
        p.id,
        p.description,
        b.brand_name,
        c.category_name,
        sup.supplier_name,
        sup.local_international,
        s.capital,
        COUNT(s.product_id) AS total_qty_of_item,
        SUM(s.capital) AS total_amount,
        w.warehouse_name,
        lp.logistic_name
    FROM `returns` r 
    LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN outbound_content oc ON oc.unique_barcode = r.unique_barcode
    LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
    LEFT JOIN logistic_partner lp ON lp.hashed_id = ol.platform
    WHERE r.supplier_type = 'International' AND r.fault_type = 'DELIVERY FAILED'
    AND r.date BETWEEN '$start' AND '$end'
    GROUP BY p.id, s.product_id, p.description, lp.logistic_name
    ORDER BY sup.local_international, w.warehouse_name ASC
";
$returned_dF_res = $conn->query($returned_dF_query);

if ($returned_dF_res && $returned_dF_res->num_rows > 0) {
    while ($row = $returned_dF_res->fetch_assoc()) {
        fputcsv($output, [
            $row['description'],
            $row['brand_name'],
            $row['category_name'],
            $row['logistic_name'],
            $row['total_qty_of_item'],
            $row['total_amount']
        ]);
    }
}

// Close file output
fclose($output);
exit;
