<?php
include "../config/database.php";
include "../config/on_session.php";

$dateThreshold = date('Y-m-d H:i:s', strtotime('-3 month'));

// Force download
$filename = "prolong_items_" . date("Ymd_His") . ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Optional: UTF-8 BOM so Excel opens it correctly
fwrite($output, "\xEF\xBB\xBF");

// CSV Header
fputcsv($output, [
    "DESCRIPTION",
    "BRAND",
    "CATEGORY",
    "BARCODE",
    "BATCH",
    "INBOUND DATE",
    "WAREHOUSE",
    "SUPPLIER",
    "SUPPLIER TYPE",
    "STAFF"
]);

$sql = "
SELECT
    p.description,
    b.brand_name,
    c.category_name,
    s.unique_barcode,
    s.batch_code,
    s.date,
    w.warehouse_name,
    sp.supplier_name,
    sp.local_international,
    u.user_fname,
    u.user_lname
FROM stocks s
LEFT JOIN product p ON p.hashed_id = s.product_id
LEFT JOIN brand b ON b.hashed_id = p.brand
LEFT JOIN category c ON c.hashed_id = p.category
LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
LEFT JOIN supplier sp ON sp.hashed_id = s.supplier
LEFT JOIN users u ON u.hashed_id = s.user_id
WHERE
    s.date < '$dateThreshold'
    AND s.item_status IN (0,2,3)
    AND s.warehouse IN ($user_warehouse_id)
";

// Stream rows directly from MySQL
$result = $conn->query($sql, MYSQLI_USE_RESULT);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['description'],
        $row['brand_name'],
        $row['category_name'],
        $row['unique_barcode'],
        $row['batch_code'],
        $row['date'],
        $row['warehouse_name'],
        $row['supplier_name'],
        $row['local_international'],
        trim($row['user_fname'] . ' ' . $row['user_lname'])
    ]);
}

$result->close();
fclose($output);
exit;