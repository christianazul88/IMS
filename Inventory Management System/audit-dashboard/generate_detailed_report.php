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
        ON al.warehouse = w.hashed_id
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
    "detailed_audit_report_" .
    $audit['audit_num'] .
    "_" .
    date("Ymd_His") .
    ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen("php://output", "w");

/*
|--------------------------------------------------------------------------
| HEADER SECTION
|--------------------------------------------------------------------------
*/

fputcsv($output, ["AUDIT DETAILED REPORT FOR SCANNED ITEMS"]);
fputcsv($output, ["Audit Number", $audit['audit_num']]);
fputcsv($output, ["Warehouse", $audit['warehouse_name']]);
fputcsv($output, ["Schedule Date", $audit['schedule_date']]);
fputcsv($output, []);

/*
|--------------------------------------------------------------------------
| COLUMN HEADERS
|--------------------------------------------------------------------------
*/

fputcsv($output, [
    "Unique Barcode",
    "Description",
    "Category",
    "Brand",
    "Supplier",
    "System Warehouse",
    "Scanned Warehouse",
    "System Location",
    "Scanned Location",
    "Audit Status",
    "Outbounded",
    "Unit Cost",
    "Scanned Date"
]);

/*
|--------------------------------------------------------------------------
| MAIN QUERY (STREAM SAFE VERSION)
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        ita.unique_barcode,
        p.description,
        c.category_name,
        b.brand_name,
        
        sup.supplier_name,

        w_system.warehouse_name AS system_warehouse,
        w_scan.warehouse_name AS scanned_warehouse,

        il_system.location_name AS system_location,
        il_scan.location_name AS scanned_location,

        ita.audit_status,
        ita.outbounded,
        s.capital,
        ita.scanned_date

    FROM items_to_audit ita

    /* ✅ PRIMARY FILTER FIRST (very important for speed) */
    INNER JOIN stocks s
        ON s.unique_barcode = ita.unique_barcode

    LEFT JOIN supplier sup
        ON sup.hashed_id = s.supplier

    /* PRODUCT TREE (kept linear, but index-driven) */
    LEFT JOIN product p
        ON p.hashed_id = s.product_id

    LEFT JOIN category c
        ON c.hashed_id = p.category

    LEFT JOIN brand b
        ON b.hashed_id = p.brand

    /* SYSTEM WAREHOUSE (avoid double warehouse table scan patterns) */
    LEFT JOIN warehouse w_system
        ON w_system.hashed_id = ita.warehouse_origin

    LEFT JOIN warehouse w_scan
        ON w_scan.hashed_id = ita.warehouse_onscanned

    /* SYSTEM LOCATION */
    LEFT JOIN item_location il_system
        ON il_system.id = s.item_location

    LEFT JOIN item_location il_scan
        ON il_scan.id = ita.item_location_onscanned

    WHERE ita.audit_id = ?

        AND ita.audit_status = 'approved'

    ORDER BY ita.audit_status;
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();

/*
|--------------------------------------------------------------------------
| ❗ STREAM BIND RESULT (NO get_result)
|--------------------------------------------------------------------------
*/

$stmt->bind_result(
    $unique_barcode,
    $description,
    $category_name,
    $brand_name,
    $supplier_name,
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
| ROW OUTPUT (STREAMING)
|--------------------------------------------------------------------------
*/

while ($stmt->fetch()) {

    // 🔥 MODIFY VALUES HERE (best place)
    if ($audit_status === "pending") {
        $audit_status = "MISSING";
    }

    if($system_location === "" || $system_location == 0){
        $system_location = "FOR SKU";
    }

    fputcsv($output, [
        $unique_barcode,
        $description,
        $category_name,
        $brand_name,
        $supplier_name,
        $system_warehouse,
        $scanned_warehouse,
        $system_location,
        $scanned_location,
        $audit_status,
        $outbounded,
        $capital,
        $scanned_date
    ]);
}

$stmt->close();
fclose($output);

exit;

