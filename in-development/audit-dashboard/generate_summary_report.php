<?php

include "../config/database.php";
include "../config/on_session.php";

$audit_id = $_GET['audit_id'] ?? 0;

if (!$audit_id) {
    die("Invalid audit ID.");
}

/*
|--------------------------------------------------------------------------
| GET AUDIT INFO
|--------------------------------------------------------------------------
*/

$audit_query = "
    SELECT al.*, w.warehouse_name
    FROM audit_logs al
    LEFT JOIN warehouse w
        ON al.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci
    WHERE al.id = ?
";

$stmt = $conn->prepare($audit_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();

$audit = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$audit) {
    die("Audit not found.");
}

/*
|--------------------------------------------------------------------------
| CSV HEADERS
|--------------------------------------------------------------------------
*/

$filename =
    "summary_audit_report_" .
    $audit['audit_num'] .
    "_" .
    date("Ymd_His") .
    ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen("php://output", "w");

/*
|--------------------------------------------------------------------------
| TITLE
|--------------------------------------------------------------------------
*/

fputcsv($output, [
    "AUDIT SUMMARY REPORT"
]);

fputcsv($output, [
    "Audit Number",
    $audit['audit_num']
]);

fputcsv($output, [
    "Warehouse",
    $audit['warehouse_name']
]);

fputcsv($output, [
    "Schedule Date",
    $audit['schedule_date']
]);

fputcsv($output, []);

/*
|--------------------------------------------------------------------------
| COLUMN HEADERS
|--------------------------------------------------------------------------
*/

fputcsv($output, [
    "Parent Barcode",
    "Description",
    "Category",
    "Brand",
    "Expected Qty",
    "Scanned Qty",
    "Variance Qty",
    "Outbounded Qty",
    "Other Warehouse Qty",
    "Other Location Qty",
    "Variance Value",
    "Scanned Value"
]);

/*
|--------------------------------------------------------------------------
| GET SUMMARY DATA
|--------------------------------------------------------------------------
*/

$query = "
    SELECT

        ai.parent_barcode,

        p.description,
        c.category_name,
        b.brand_name,

        ai.expected_qty,
        ai.scanned_qty,
        ai.variance_qty,

        ai.scanned_outbounded_qty,
        ai.scanned_belong_to_other_wh,
        ai.scanned_belong_to_other_location,

        ai.variance_value,
        ai.scanned_value

    FROM audit_items ai

    LEFT JOIN stocks s
        ON ai.parent_barcode COLLATE utf8mb4_unicode_ci =
           SUBSTRING_INDEX(
                s.unique_barcode,
                '-',
                1
           ) COLLATE utf8mb4_unicode_ci

    LEFT JOIN product p
        ON s.product_id COLLATE utf8mb4_unicode_ci =
           p.hashed_id COLLATE utf8mb4_unicode_ci

    LEFT JOIN category c
        ON p.category COLLATE utf8mb4_unicode_ci =
           c.hashed_id COLLATE utf8mb4_unicode_ci

    LEFT JOIN brand b
        ON p.brand COLLATE utf8mb4_unicode_ci =
           b.hashed_id COLLATE utf8mb4_unicode_ci

    WHERE ai.audit_id = ?

    GROUP BY ai.parent_barcode

    ORDER BY ai.parent_barcode ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();

$result = $stmt->get_result();

/*
|--------------------------------------------------------------------------
| ROWS
|--------------------------------------------------------------------------
*/

while ($row = $result->fetch_assoc()) {

    fputcsv($output, [

        $row['parent_barcode'],

        $row['description'],
        $row['category_name'],
        $row['brand_name'],

        $row['expected_qty'],
        $row['scanned_qty'],
        $row['variance_qty'],

        $row['scanned_outbounded_qty'],
        $row['scanned_belong_to_other_wh'],
        $row['scanned_belong_to_other_location'],

        $row['variance_value'],
        $row['scanned_value']

    ]);
}

$stmt->close();

fclose($output);

exit;
?>