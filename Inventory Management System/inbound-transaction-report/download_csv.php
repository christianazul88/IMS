<?php
include "../config/database.php";
include "../config/on_session.php";

if(!isset($_SESSION['csv_filters'])){
    die("No data to export.");
}

$filters = $_SESSION['csv_filters'];

$query = "
SELECT 
    s.unique_barcode, 
    p.description, 
    b.brand_name, 
    c.category_name, 
    cl.classification_name,
    sup.supplier_name, 
    sup.local_international, 
    s.inbound_id, 
    s.capital, 
    w.warehouse_name, 
    s.date AS date_acquired, 
    s.unique_key, 
    u.user_fname, 
    u.user_lname,
    po.id AS po_id,
    CASE
        WHEN il.po_id IS NOT NULL AND poc.product_id IS NULL THEN 1
        ELSE 0
    END AS is_additional
FROM stocks s 
LEFT JOIN product p ON p.hashed_id = s.product_id 
LEFT JOIN brand b ON b.hashed_id = p.brand 
LEFT JOIN category c ON c.hashed_id = p.category 
LEFT JOIN supplier sup ON sup.hashed_id = s.supplier 
LEFT JOIN warehouse w ON w.hashed_id = s.warehouse 
LEFT JOIN users u ON u.hashed_id = s.user_id 
LEFT JOIN classification cl ON cl.hashed_id = c.classification_id
LEFT JOIN (
    SELECT unique_key, MIN(po_id) AS po_id
    FROM inbound_logs
    WHERE po_id IS NOT NULL AND po_id <> 0
    GROUP BY unique_key
) il ON il.unique_key = s.unique_key
LEFT JOIN purchased_order po ON po.id = il.po_id
LEFT JOIN (
    SELECT DISTINCT po_id, product_id
    FROM purchased_order_content
) poc ON poc.po_id = il.po_id AND poc.product_id = s.product_id
WHERE s.date BETWEEN '{$filters['start']}' AND '{$filters['end']}'
{$filters['warehouse_query']}
{$filters['category_query']}
{$filters['supplier_query']}
";

$res = $conn->query($query);

// HEADERS
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=inbound_transactions '. $date_for_file . '.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Barcode',
    'Description',
    'Brand',
    'Classification',
    'Category',
    'Supplier',
    'Supplier Info',
    'Cost of Good Sold',
    'Warehouse',
    'Date Acquired',
    'Inbound ID',
    'Inbounded by',
    'PO Number',
    'Item Type'
]);

while($row = $res->fetch_assoc()){

    $full_name  = $row['user_fname'] . ' ' . $row['user_lname'];
    $unique_key = '"' . $row['unique_key'] . '"';

    // PO reference, or fallback label when no PO can be reached
    $po_ref = !empty($row['po_id']) ? $row['po_id'] : 'Imported via CSV';
    $item_type = (int) ($row['is_additional'] ?? 0) === 1 ? 'Additional Item' : '';

    fputcsv($output, [
        $row['unique_barcode'],
        $row['description'],
        $row['brand_name'],
        $row['classification_name'],
        $row['category_name'],
        $row['supplier_name'],
        $row['local_international'],
        $row['capital'],
        $row['warehouse_name'],
        $row['date_acquired'],
        $unique_key,
        $full_name,
        $po_ref,
        $item_type
    ]);
}

fclose($output);
exit;
