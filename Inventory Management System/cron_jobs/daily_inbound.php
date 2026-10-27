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
    END AS is_additional,
    rts.rts_id AS rts_reference,
    rts.status AS rts_item_status,
    rts.returned_date AS rts_item_completed_date,
    rts_log.status AS rts_log_status,
    rts_log.reason AS rts_reason,
    rts_log.date AS rts_requested_date,
    rts_log.returned_date AS rts_log_completed_date,
    rts_log.\x60for\x60 AS rts_type
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
LEFT JOIN rts_content rts
    ON rts.id = (
        SELECT rc.id
        FROM rts_content rc
        INNER JOIN rts_logs rl ON rl.id = rc.rts_id
        WHERE rc.unique_barcode = s.unique_barcode
          AND rl.date >= s.date
        ORDER BY rl.date DESC, rl.id DESC, rc.id DESC
        LIMIT 1
    )
LEFT JOIN rts_logs rts_log
    ON rts_log.id = rts.rts_id
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
    'Unit Cost',
    'Warehouse',
    'Date Acquired',
    'Inbound Reference',
    'Received By',
    'Purchase Order',
    'Receipt Type',
    'Supplier Return (RTS) Status (as of report run)',
    'Supplier Return Reference',
    'Reason for Supplier Return',
    'Supplier Return Requested On',
    'Supplier Return Completed On',
    'Supplier Return Requested Outcome'
]);

while($row = $res->fetch_assoc()){
    ++$rows;

    $full_name  = $row['user_fname'] . ' ' . $row['user_lname'];
    $unique_key = $row['unique_key'];

    // PO reference, or fallback label when no PO can be reached
    $po_ref = !empty($row['po_id']) ? $row['po_id'] : 'No linked PO';
    $item_type = (int) ($row['is_additional'] ?? 0) === 1 ? 'Additional Item' : '';
    $rts_status = 'No supplier return recorded';
    if ($row['rts_reference'] !== null) {
        switch ((int) $row['rts_item_status']) {
            case 0: $rts_status = 'Sent back to supplier'; break;
            case 1: $rts_status = 'Supplier refund recorded'; break;
            case 2: $rts_status = 'Supplier replacement recorded'; break;
            default: $rts_status = 'Supplier return recorded; outcome not specified';
        }
        if (
            (int) $row['rts_item_status'] === 0
            && (int) $row['rts_log_status'] === 0
        ) {
            $rts_status .= ' — waiting for supplier outcome';
        }
    }

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
        $item_type,
        $rts_status,
        $row['rts_reference'] ?? '',
        $row['rts_reason'] ?? '',
        $row['rts_requested_date'] ?? '',
        $row['rts_item_completed_date'] ?? $row['rts_log_completed_date'] ?? '',
        $row['rts_type'] ?? ''
    ]);
}

report_finish($output, 'inbound', $rows);
