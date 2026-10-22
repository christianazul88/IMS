<?php
require_once __DIR__ . '/report_common.php';

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
LEFT JOIN inbound_logs il ON il.id = s.inbound_id
LEFT JOIN purchased_order po ON po.id = il.po_id
LEFT JOIN (
    SELECT DISTINCT po_id, product_id
    FROM purchased_order_content
) poc ON poc.po_id = il.po_id AND poc.product_id = s.product_id
WHERE s.date >= ? AND s.date < ? ORDER BY s.date, s.id
";

$res = report_query($query);

$output = report_output();
$rows = 0;

report_csv($output, [
    'Barcode',
    'Description',
    'Brand',
    'Classification',
    'Category',
    'Supplier',
    'Supplier Info',
    'Unit Capital',
    'Warehouse',
    'Date Acquired',
    'Inbound ID',
    'Inbounded by',
    'PO Number',
    'Item Type'
]);

while($row = $res->fetch_assoc()){
    ++$rows;

    $full_name  = $row['user_fname'] . ' ' . $row['user_lname'];
    $unique_key = $row['unique_key'];

    // PO reference, or fallback label when no PO can be reached
    $po_ref = !empty($row['po_id']) ? $row['po_id'] : 'No linked PO';
    $item_type = (int) ($row['is_additional'] ?? 0) === 1 ? 'Additional Item' : '';

    report_csv($output, [
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

report_finish($output, 'inbound', $rows);
