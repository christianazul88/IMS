<?php
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '4G');
ini_set('display_errors', 1);
ini_set('pcre.backtrack_limit', '10000000');

include "../config/database.php";
include "../config/on_session.php";

// Prevent timeout for large exports
set_time_limit(0);

// Disable output buffering
while (ob_get_level()) {
    ob_end_clean();
}

$startDate = $_GET['startdate'];
$endDate = $_GET['enddate'];
$additional_query = $_SESSION['transaction_overview_additional'];

$filename = "Transaction Overview Report as of " . date("Y-m-d_H-i-s") . ".csv";

// Download headers
header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// CSV Header
fputcsv($output, [
    'Classification',
    'Category',
    'Order No.',
    'Outbound No.',
    'Customer',
    'Date Sent',
    'Supplier',
    'Local/International',
    'Warehouse',
    'Description',
    'Brand',
    'Barcode',
    'Batch Code',
    'Prepared By',
    'Status',
    'Capital',
    'Sold Price',
    'RTS Ref.',
    'RTS Outcome',
    'RTS Reason',
    'RTS Requested Date',
    'RTS Completed Date',
    'RTS Type'
]);

$query = "
SELECT
    class.classification_name,
    c.category_name,
    ol.order_num,
    ol.hashed_id AS outbound_num,
    ol.customer_fullname,
    ol.date_sent,
    sup.supplier_name,
    sup.local_international,
    w.warehouse_name,
    p.description,
    b.brand_name,
    s.unique_barcode,
    s.batch_code,
    u.user_fname,
    u.user_lname,
    oc.status AS outbound_status,
    s.capital,
    oc.sold_price,
    rts.rts_id AS rts_reference,
    rts.status AS rts_status,
    rts_log.reason AS rts_reason,
    rts_log.date AS rts_requested_date,
    COALESCE(rts.returned_date, rts_log.returned_date) AS rts_completed_date,
    rts_log.`for` AS rts_type
FROM outbound_content oc
INNER JOIN stocks s
    ON s.unique_barcode = oc.unique_barcode
LEFT JOIN outbound_logs ol
    ON ol.hashed_id = oc.hashed_id
LEFT JOIN product p
    ON p.hashed_id = s.product_id
LEFT JOIN brand b
    ON b.hashed_id = p.brand
LEFT JOIN category c
    ON c.hashed_id = p.category
LEFT JOIN classification class
    ON class.hashed_id = c.classification_id
LEFT JOIN users u
    ON u.hashed_id = ol.user_id
LEFT JOIN warehouse w
    ON w.hashed_id = ol.warehouse
LEFT JOIN supplier sup
    ON sup.hashed_id = s.supplier
LEFT JOIN (
    /* One most-recent RTS reference per barcode avoids multiplying CSV rows. */
    SELECT
        rc.unique_barcode,
        MAX(rl.id) AS latest_rts_id
    FROM rts_content rc
    INNER JOIN rts_logs rl
        ON rl.id = rc.rts_id
    GROUP BY rc.unique_barcode
) latest_rts
    ON latest_rts.unique_barcode = oc.unique_barcode
LEFT JOIN rts_content rts
    ON rts.unique_barcode = latest_rts.unique_barcode
    AND rts.rts_id = latest_rts.latest_rts_id
LEFT JOIN rts_logs rts_log
    ON rts_log.id = rts.rts_id
WHERE
    ol.date_sent BETWEEN '$startDate' AND '$endDate'
    $additional_query
    ORDER BY ol.warehouse DESC
";

$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    /* Clean status text */
    switch ($row['outbound_status']) {
        case 0: $status = 'Paid'; break;
        case 1: $status = 'Returned'; break;
        case 2: $status = 'Voided'; break;
        case 6: $status = 'Outbounded'; break;
        default: $status = 'Unknown';
    }

    /* Return-to-supplier details stay blank when this barcode has no RTS row. */
    $rts_outcome = '';
    if ($row['rts_status'] !== null) {
        switch ((int) $row['rts_status']) {
            case 0: $rts_outcome = 'Returned'; break;
            case 1: $rts_outcome = 'Returned and Refunded'; break;
            case 2: $rts_outcome = 'Returned and Replaced'; break;
            default: $rts_outcome = 'RTS status ' . $row['rts_status'];
        }
    }

    fputcsv($output, [
        $row['classification_name'],
        $row['category_name'],
        '"' . $row['order_num'] . '"',
        '"' . $row['outbound_num'] . '"',
        $row['customer_fullname'],
        $row['date_sent'],
        $row['supplier_name'],
        $row['local_international'],
        $row['warehouse_name'],
        $row['description'],
        $row['brand_name'],
        '"' . $row['unique_barcode'] . '"',
        '"' . $row['batch_code'] . '"',
        $row['user_fname'] . ' ' . $row['user_lname'],
        $status,
        $row['capital'],
        $row['sold_price'],
        $row['rts_reference'] ?? '',
        $rts_outcome,
        $row['rts_reason'] ?? '',
        $row['rts_requested_date'] ?? '',
        $row['rts_completed_date'] ?? '',
        $row['rts_type'] ?? ''
    ]);
}

fclose($output);
exit;
