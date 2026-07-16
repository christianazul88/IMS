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
$warehouse_id_audit = $audit['warehouse'];

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

$parent_groups = [];

$summary_query = "
    SELECT

    -- Expected Qty
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = ?
        AND warehouse_origin = ?
        AND outbounded = 'no'
    ) AS total_expected_qty,

    -- Expected Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = ?
        AND ita.warehouse_origin = ?
        AND ita.outbounded = 'no'
    ) AS total_expected_amount,

    -- Total Scanned
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = ?
        AND audit_status IN ('scanned','approved')
    ) AS total_scanned,

    -- Total Scanned Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = ?
        AND ita.audit_status IN ('scanned','approved')
    ) AS total_scanned_amount,

    -- Total Expected Scanned QTY
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = ?
        AND audit_status IN ('scanned','approved')
        AND warehouse_origin = ?
        AND outbounded = 'no'
    ) AS total_expected_scanned_qty,

    -- Total Expected Scanned Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = ?
        AND ita.warehouse_origin = ?
        AND ita.audit_status IN ('scanned','approved')
        AND ita.outbounded = 'no'
    ) AS total_expected_scanned_amount,


    -- Missing Qty
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = ?
        AND audit_status = 'pending'
        AND warehouse_origin = ?
        AND outbounded = 'no'
    ) AS total_missing_qty,

    -- Missing Expected amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = ?
        AND ita.audit_status = 'pending'
        AND ita.warehouse_origin = ?
        AND ita.outbounded = 'no'
    ) AS total_missing_amount,


    -- Positive Variance Outbounded
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = ?
        AND audit_status IN ('scanned','approved')
        AND warehouse_origin = ?
        AND outbounded != 'no'
    ) AS total_scanned_outbounded_as_positive_variance_qty,

    -- Positive Variance Outbounded Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = ?
        AND ita.audit_status IN ('scanned','approved')
        AND ita.warehouse_origin = ?
        AND ita.outbounded != 'no'
    ) AS total_scanned_outbounded_as_positive_variance_amount,

    -- Positive Variance Wrong Warehouse
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = ?
        AND audit_status IN ('scanned','approved')
        AND warehouse_origin != ?
    ) AS total_scanned_wrong_warehouse_as_positive_variance_qty,

    -- Positive Variace Wrong Warehouse Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = ?
        AND ita.audit_status IN ('scanned','approved')
        AND ita.warehouse_origin != ?
    ) AS total_scanned_wrong_warehouse_as_positive_variance_amount
";
$stmt_summary = $conn->prepare($summary_query);

$stmt_summary->bind_param(
    "isisiiisisisisisisisis",
    $audit_id,
    $warehouse_id_audit,
    $audit_id,
    $warehouse_id_audit,
    $audit_id,
    $audit_id,
    $audit_id,
    $warehouse_id_audit,
    $audit_id,
    $warehouse_id_audit,
    $audit_id,
    $warehouse_id_audit,
    $audit_id,
    $warehouse_id_audit,
    $audit_id,
    $warehouse_id_audit,
    $audit_id,
    $warehouse_id_audit,
    $audit_id,
    $warehouse_id_audit,
    $audit_id,
    $warehouse_id_audit
);

$stmt_summary->execute();

$summary = $stmt_summary->get_result()->fetch_assoc();

$stmt_summary->close();
// Prevent NULL values
foreach ($summary as $key => $value) {
    $summary[$key] = $value ?? 0;
}

// Assign variables
$total_expected_qty     = (int)$summary['total_expected_qty'];
$total_expected_amount  = (float)$summary['total_expected_amount'];

$total_scanned_qty      = (int)$summary['total_scanned'];
$total_scanned_amount   = (float)$summary['total_scanned_amount'];

$total_expected_scanned_qty    = (int)$summary['total_expected_scanned_qty'];
$total_expected_scanned_amount = (float)$summary['total_expected_scanned_amount'];

$total_missing_qty      = (int)$summary['total_missing_qty'];
$total_missing_amount   = (float)$summary['total_missing_amount'];

$total_other_wh_qty     = (int)$summary['total_scanned_wrong_warehouse_as_positive_variance_qty'];
$total_other_wh_amount  = (float)$summary['total_scanned_wrong_warehouse_as_positive_variance_amount'];

$total_outbounded_qty   = (int)$summary['total_scanned_outbounded_as_positive_variance_qty'];
$total_outbounded_amount= (float)$summary['total_scanned_outbounded_as_positive_variance_amount'];

// Positive Variance Totals
$total_positive_variance_qty =
    $total_other_wh_qty +
    $total_outbounded_qty;

$total_positive_variance_amount =
    $total_other_wh_amount +
    $total_outbounded_amount;

// Validation
$qty_result = 0;
$amount_result = 0;

if ($total_expected_qty > 0) {
    $qty_result = (
        $total_scanned_qty
        - $total_other_wh_qty
        - $total_outbounded_qty
        + $total_missing_qty
    ) / $total_expected_qty;
}

if ($total_expected_amount > 0) {
    $amount_result = (
        $total_scanned_amount
        - $total_other_wh_amount
        - $total_outbounded_amount
        + $total_missing_amount
    ) / $total_expected_amount;
}

$qty_status = (abs($qty_result - 1) < 0.0001) ? "BALANCED" : "NOT BALANCED";
$amount_status = (abs($amount_result - 1) < 0.0001) ? "BALANCED" : "NOT BALANCED";


// =======================================
// WRITE CSV
// =======================================

fputcsv($output, ["AUDIT SUMMARY"]);
fputcsv($output, ["================================================================================"]);
fputcsv($output, []);

fputcsv($output, ["AUDIT EXPECTED VALUES"]);
fputcsv($output, ["-------------------------------"]);
fputcsv($output, ["Expected Quantity", $total_expected_qty]);
fputcsv($output, ["Expected Amount", number_format($total_expected_amount, 2, '.', '')]);
fputcsv($output, []);

fputcsv($output, ["SCANNED RESULTS"]);
fputcsv($output, ["-------------------------------"]);
fputcsv($output, ["Total Scanned Quantity", $total_scanned_qty]);
fputcsv($output, ["Total Scanned Amount", number_format($total_scanned_amount, 2, '.', '')]);
fputcsv($output, []);

fputcsv($output, ["EXPECTED ITEMS SUCCESSFULLY SCANNED"]);
fputcsv($output, ["-------------------------------"]);
fputcsv($output, ["Expected Scanned Quantity", $total_expected_scanned_qty]);
fputcsv($output, ["Expected Scanned Amount", number_format($total_expected_scanned_amount, 2, '.', '')]);
fputcsv($output, []);

fputcsv($output, ["MISSING ITEMS"]);
fputcsv($output, ["-------------------------------"]);
fputcsv($output, ["Missing Quantity", $total_missing_qty]);
fputcsv($output, ["Missing Expected Amount", number_format($total_missing_amount, 2, '.', '')]);
fputcsv($output, []);

fputcsv($output, ["POSITIVE VARIANCE BREAKDOWN"]);
fputcsv($output, ["-------------------------------"]);
fputcsv($output, ["Items from Other Warehouse (Qty)", $total_other_wh_qty]);
fputcsv($output, ["Items from Other Warehouse (Amount)", number_format($total_other_wh_amount, 2, '.', '')]);
fputcsv($output, ["Outbounded Items (Qty)", $total_outbounded_qty]);
fputcsv($output, ["Outbounded Items (Amount)", number_format($total_outbounded_amount, 2, '.', '')]);
fputcsv($output, []);

fputcsv($output, ["POSITIVE VARIANCE TOTAL"]);
fputcsv($output, ["-------------------------------"]);
fputcsv($output, ["Positive Variance Quantity", $total_positive_variance_qty]);
fputcsv($output, ["Positive Variance Amount", number_format($total_positive_variance_amount, 2, '.', '')]);
fputcsv($output, []);

fputcsv($output, ["AUDIT VALIDATION"]);
fputcsv($output, ["-------------------------------"]);

$qty_formula =
    "(" .
    $total_scanned_qty .
    " - " .
    $total_other_wh_qty .
    " - " .
    $total_outbounded_qty .
    " + " .
    $total_missing_qty .
    ") / " .
    $total_expected_qty;

$amount_formula =
    "(" .
    number_format($total_scanned_amount, 2, '.', '') .
    " - " .
    number_format($total_other_wh_amount, 2, '.', '') .
    " - " .
    number_format($total_outbounded_amount, 2, '.', '') .
    " + " .
    number_format($total_missing_amount, 2, '.', '') .
    ") / " .
    number_format($total_expected_amount, 2, '.', '');

fputcsv($output, ["Quantity Check Formula", $qty_formula]);
fputcsv($output, ["Quantity Check Result", number_format($qty_result, 2, '.', '')]);
fputcsv($output, ["Quantity Status", $qty_status]);
fputcsv($output, []);

fputcsv($output, ["Amount Check Formula", $amount_formula]);
fputcsv($output, ["Amount Check Result", number_format($amount_result, 2, '.', '')]);
fputcsv($output, ["Amount Status", $amount_status]);
fputcsv($output, []);

fputcsv($output, ["================================================================================"]);
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

    LEFT JOIN warehouse w_system ON w_system.hashed_id = ita.warehouse_origin
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

    if ($system_warehouse !== $scanned_warehouse && $audit_status === "approved") {
        $parent_groups[$parent]['warehouse_mismatch']++;
    }

    if ($system_location !== $scanned_location && $audit_status === "approved") {
        $parent_groups[$parent]['location_mismatch']++;
    }

    if ($outbounded == 1 && $audit_status === "approved") {
        $parent_groups[$parent]['outbounded']++;
    }
}

$stmt->close();




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