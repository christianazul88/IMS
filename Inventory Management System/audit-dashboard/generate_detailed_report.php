<?php

/*
|--------------------------------------------------------------------------
| SAFETY SETTINGS FOR LARGE EXPORTS
|--------------------------------------------------------------------------
| - set_time_limit(0): don't let PHP kill this script on large exports
|   (default max_execution_time is often only 30s)
| - mysqli_report(...): make DB failures throw instead of failing silently,
|   so a dropped connection mid-stream doesn't just look like "no more rows"
*/

set_time_limit(0);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

// Kill any inherited output buffering (from session/config includes, output
// compression, etc.) so fputcsv()/flush() actually stream to the browser
// instead of being held in memory until the script ends.
while (ob_get_level() > 0) {
    ob_end_flush();
}

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

// Use unbuffered execution so PHP starts streaming rows as they come in
// from MySQL rather than pulling the entire result set into memory first.
// This also makes it easier to catch a connection that dies mid-export,
// since execute()/fetch() will throw via mysqli_report() above.
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

$row_count = 0;

while ($stmt->fetch()) {

    // 🔥 MODIFY VALUES HERE (best place)
    if ($audit_status === "pending") {
        $audit_status = "MISSING";
    }

    if ($system_location === "" || $system_location == 0) {
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

    $row_count++;

    // Periodically flush so the client receives data steadily instead of
    // in one big burst at the end (helps avoid client/proxy read timeouts
    // on large exports, and lets you SEE progress if watching network tab).
    if ($row_count % 500 === 0) {
        flush();
    }
}

// If the loop ended because of a DB error rather than because it legitimately
// ran out of rows, log it instead of silently producing a truncated CSV.
if ($stmt->error) {
    error_log(
        "generate_detailed_report.php: export failed for audit_id={$audit_id} " .
        "after {$row_count} rows: {$stmt->error}"
    );
}

error_log("generate_detailed_report.php: audit_id={$audit_id} exported {$row_count} rows.");

$stmt->close();
fclose($output);

exit;
