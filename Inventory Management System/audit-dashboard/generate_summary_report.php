<?php

include "../config/database.php";
include "../config/on_session.php";

$audit_id = $_GET['audit_id'] ?? 0;

if (!$audit_id) die("Invalid audit ID");

/*
|--------------------------------------------------------------------------
| AUDIT INFO
|--------------------------------------------------------------------------
*/

$audit_query = "
    SELECT al.*, w.warehouse_name
    FROM audit_logs al
    LEFT JOIN warehouse w ON al.warehouse = w.hashed_id
    WHERE al.id = ?
";

$stmt = $conn->prepare($audit_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$audit = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$audit) die("Audit not found");

/*
|--------------------------------------------------------------------------
| CSV SETUP
|--------------------------------------------------------------------------
*/

$filename = "audit_parent_report_" . $audit['audit_num'] . "_" . date("Ymd_His") . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen("php://output", "w");

/*
|--------------------------------------------------------------------------
| GLOBAL METRICS
|--------------------------------------------------------------------------
*/

$total_items = 0;
$scanned_items = 0;

$total_capital = 0;

$unexpected_warehouse = 0;
$unexpected_location = 0;

$status_summary = [];
$parent_groups = [];

/*
|--------------------------------------------------------------------------
| QUERY
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        ita.unique_barcode,
        p.description,
        c.category_name,
        b.brand_name,

        w_system.warehouse_name AS system_warehouse,
        w_scan.warehouse_name AS scanned_warehouse,

        il_system.location_name AS system_location,
        il_scan.location_name AS scanned_location,

        ita.audit_status,
        ita.outbounded,
        s.capital,
        ita.scanned_date

    FROM items_to_audit ita
    INNER JOIN stocks s ON s.unique_barcode = ita.unique_barcode

    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN brand b ON b.hashed_id = p.brand

    LEFT JOIN warehouse w_system ON w_system.hashed_id = s.warehouse
    LEFT JOIN warehouse w_scan ON w_scan.hashed_id = ita.warehouse_onscanned

    LEFT JOIN item_location il_system ON il_system.id = s.item_location
    LEFT JOIN item_location il_scan ON il_scan.id = ita.item_location_onscanned

    WHERE ita.audit_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();

$stmt->bind_result(
    $barcode,
    $description,
    $category,
    $brand,
    $system_warehouse,
    $scanned_warehouse,
    $system_location,
    $scanned_location,
    $audit_status,
    $outbounded,
    $capital,
    $scanned_date
);

/*
|--------------------------------------------------------------------------
| PROCESS ROWS
|--------------------------------------------------------------------------
*/

while ($stmt->fetch()) {

    if ($audit_status === "pending") {
        $audit_status = "MISSING";
    }

    /*
    |--------------------------------------------------------------------------
    | PARENT BARCODE
    |--------------------------------------------------------------------------
    */
    $parent = explode("-", $barcode)[0];

    /*
    |--------------------------------------------------------------------------
    | GLOBAL COUNTERS
    |--------------------------------------------------------------------------
    */

    $total_items++;
    $total_capital += (float)$capital;

    if ($audit_status === "scanned") {
        $scanned_items++;
    }

    if ($system_warehouse !== $scanned_warehouse) {
        $unexpected_warehouse++;
    }

    if ($system_location !== $scanned_location) {
        $unexpected_location++;
    }

    if (!isset($status_summary[$audit_status])) {
        $status_summary[$audit_status] = 0;
    }
    $status_summary[$audit_status]++;

    /*
    |--------------------------------------------------------------------------
    | PARENT GROUP INIT
    |--------------------------------------------------------------------------
    */

    if (!isset($parent_groups[$parent])) {
        $parent_groups[$parent] = [
            'expected_qty' => 0,
            'scanned_qty' => 0,
            'expected_value' => 0,
            'scanned_value' => 0,
            'warehouse_mismatch' => 0,
            'location_mismatch' => 0,
            'outbounded' => 0
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PARENT CALCULATIONS
    |--------------------------------------------------------------------------
    */

    $parent_groups[$parent]['expected_qty']++;
    $parent_groups[$parent]['expected_value'] += (float)$capital;

    if ($audit_status === "scanned" ||$audit_status === "approved" ) {
        $parent_groups[$parent]['scanned_qty']++;
        $parent_groups[$parent]['scanned_value'] += (float)$capital;
    }

    if ($system_warehouse !== $scanned_warehouse) {
        $parent_groups[$parent]['warehouse_mismatch']++;
    }

    if ($system_location !== $scanned_location) {
        $parent_groups[$parent]['location_mismatch']++;
    }

    if ($outbounded == 1) {
        $parent_groups[$parent]['outbounded']++;
    }
}

$stmt->close();

/*
|--------------------------------------------------------------------------
| CSV HEADER
|--------------------------------------------------------------------------
*/

fputcsv($output, ["AUDIT PARENT BARCODE REPORT"]);
fputcsv($output, ["Audit Number", $audit['audit_num']]);
fputcsv($output, ["Warehouse", $audit['warehouse_name']]);
fputcsv($output, ["Schedule Date", $audit['schedule_date']]);

fputcsv($output, []);

/*
|--------------------------------------------------------------------------
| GLOBAL SUMMARY
|--------------------------------------------------------------------------
*/

$variance_qty = $total_items - $scanned_items;

fputcsv($output, ["================ GLOBAL SUMMARY ================"]);
fputcsv($output, ["Total Expected Items", $total_items]);
fputcsv($output, ["Total Scanned Items", $scanned_items]);
fputcsv($output, ["Quantity Variance", $variance_qty]);
fputcsv($output, ["Total Capital", $total_capital]);

fputcsv($output, []);
fputcsv($output, ["Unexpected Warehouse Count", $unexpected_warehouse]);
fputcsv($output, ["Unexpected Location Count", $unexpected_location]);

fputcsv($output, []);
fputcsv($output, ["------ STATUS BREAKDOWN ------"]);
fputcsv($output, ["Status", "Count"]);

foreach ($status_summary as $status => $count) {
    fputcsv($output, [$status, $count]);
}

/*
|--------------------------------------------------------------------------
| PARENT SUMMARY
|--------------------------------------------------------------------------
*/

fputcsv($output, []);
fputcsv($output, ["================ PARENT BARCODE SUMMARY ================"]);

fputcsv($output, [
    "Parent Barcode",
    "Expected Qty",
    "Scanned Qty",
    "Variance",
    "Expected Value",
    "Scanned Value",
    "Warehouse Mismatch",
    "Location Mismatch",
    "Outbounded"
]);

foreach ($parent_groups as $parent => $data) {

    $variance = $data['expected_qty'] - $data['scanned_qty'];

    fputcsv($output, [
        $parent,
        $data['expected_qty'],
        $data['scanned_qty'],
        $variance,
        $data['expected_value'],
        $data['scanned_value'],
        $data['warehouse_mismatch'],
        $data['location_mismatch'],
        $data['outbounded']
    ]);
}

fclose($output);
exit;