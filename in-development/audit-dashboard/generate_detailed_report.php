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
        ON CAST(al.warehouse AS CHAR) = CAST(w.hashed_id AS CHAR)
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

fputcsv($output, ["AUDIT DETAILED REPORT"]);
fputcsv($output, ["Audit Number", $audit['audit_num']]);
fputcsv($output, ["Warehouse", $audit['warehouse_name']]);
fputcsv($output, ["Schedule Date", $audit['schedule_date']]);
fputcsv($output, []);

/*
|--------------------------------------------------------------------------
| COLUMN HEADERS (FIXED)
|--------------------------------------------------------------------------
*/

fputcsv($output, [
    "Unique Barcode",
    "Description",
    "Category",
    "Brand",
    "System Warehouse",
    "Scanned Warehouse",
    "System Location",
    "Scanned Location",
    "Audit Status",
    "Outbounded",
    "Belong To System Stocks",
    "Scanned Date"
]);

/*
|--------------------------------------------------------------------------
| MAIN QUERY (FIXED COLLATION SAFE)
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        ita.unique_barcode,

        p.description,
        c.category_name,
        b.brand_name,

        w1.warehouse_name AS system_warehouse,
        w2.warehouse_name AS scanned_warehouse,

        il1.location_name AS system_location,
        il2.location_name AS scanned_location,

        ita.audit_status,
        ita.outbounded,
        ita.belong_to_system_stocks,
        ita.scanned_date

    FROM items_to_audit ita

    LEFT JOIN stocks s
        ON CAST(ita.unique_barcode AS CHAR) = CAST(s.unique_barcode AS CHAR)

    LEFT JOIN product p
        ON CAST(s.product_id AS CHAR) = CAST(p.hashed_id AS CHAR)

    LEFT JOIN category c
        ON CAST(p.category AS CHAR) = CAST(c.hashed_id AS CHAR)

    LEFT JOIN brand b
        ON CAST(p.brand AS CHAR) = CAST(b.hashed_id AS CHAR)

    LEFT JOIN warehouse w1
        ON CAST(s.warehouse AS CHAR) = CAST(w1.hashed_id AS CHAR)

    LEFT JOIN warehouse w2
        ON CAST(ita.scanned_wh AS CHAR) = CAST(w2.hashed_id AS CHAR)

    LEFT JOIN item_location il1
        ON CAST(s.item_location AS CHAR) = CAST(il1.id AS CHAR)

    LEFT JOIN item_location il2
        ON CAST(ita.scanned_location AS CHAR) = CAST(il2.id AS CHAR)

    WHERE ita.audit_id = ?

    ORDER BY ita.scanned_date ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();

$result = $stmt->get_result();

/*
|--------------------------------------------------------------------------
| ROW OUTPUT
|--------------------------------------------------------------------------
*/

while ($row = $result->fetch_assoc()) {

    fputcsv($output, [
        $row['unique_barcode'],
        $row['description'] ?? '',
        $row['category_name'] ?? '',
        $row['brand_name'] ?? '',

        $row['system_warehouse'] ?? '',
        $row['scanned_warehouse'] ?? '',

        $row['system_location'] ?? '',
        $row['scanned_location'] ?? '',

        $row['audit_status'] ?? '',
        $row['outbounded'] ?? '',
        $row['belong_to_system_stocks'] ?? '',

        $row['scanned_date'] ?? ''
    ]);
}

$stmt->close();
fclose($output);

exit;