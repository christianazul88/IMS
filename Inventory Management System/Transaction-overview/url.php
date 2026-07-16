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
    'Sold Price'
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
    oc.sold_price
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

    fputcsv($output, [
        $row['classification_name'],
        $row['category_name'],
        $row['order_num'],
        $row['outbound_num'],
        $row['customer_fullname'],
        $row['date_sent'],
        $row['supplier_name'],
        $row['local_international'],
        $row['warehouse_name'],
        $row['description'],
        $row['brand_name'],
        $row['unique_barcode'],
        $row['batch_code'],
        $row['user_fname'] . ' ' . $row['user_lname'],
        $status,
        $row['capital'],
        $row['sold_price']
    ]);
}

fclose($output);
exit;