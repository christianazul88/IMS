<?php
require_once __DIR__ . '/report_common.php';
// The source has some reused outbound references. Never silently multiply
// items or guess which customer's order owns them on an affected date.
$ambiguous = report_query("SELECT ol.id FROM outbound_logs ol
    WHERE ol.date_sent >= ? AND ol.date_sent < ?
    AND (ol.hashed_id IS NULL OR ol.hashed_id = '' OR EXISTS (
        SELECT 1 FROM outbound_logs other
        WHERE other.hashed_id = ol.hashed_id AND other.id <> ol.id
    )) LIMIT 1");
if ($ambiguous->num_rows > 0) {
    throw new RuntimeException('Outbound report has missing or duplicate order references; repair the source data before exporting this date.');
}
$output = report_output();
$rows = 0;

// CSV Header
report_csv($output, [
    'Classification',
    'Category',
    'Order No.',
    'Outbound Reference',
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
    'Outbound Status',
    'Unit Cost',
    'Selling Price',
    'Customer Return Status (as of report run)',
    'Customer Return Date',
    'Reason Given for Customer Return',
    'Reported Item Fault',
    'Customer Return Fault Type',
    'Customer Return Amount'
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
    oc.unique_barcode,
    s.batch_code,
    u.user_fname,
    u.user_lname,
    oc.status AS outbound_status,
    s.capital,
    oc.sold_price,
    customer_return.id AS customer_return_id,
    customer_return.date AS customer_return_date,
    customer_return.reason AS customer_return_reason,
    customer_return.fault AS customer_return_fault,
    customer_return.fault_type AS customer_return_type,
    customer_return.amount AS customer_return_amount
FROM outbound_content oc
LEFT JOIN stocks s
    ON s.unique_barcode = oc.unique_barcode
INNER JOIN outbound_logs ol
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
LEFT JOIN \x60returns\x60 customer_return
    ON customer_return.id = (
        /* Match the latest customer return in this outbound cycle only. */
        SELECT r.id
        FROM \x60returns\x60 r
        WHERE r.unique_barcode = oc.unique_barcode
          AND r.date >= ol.date_sent
          AND NOT EXISTS (
              SELECT 1
              FROM outbound_content oc_next
              INNER JOIN outbound_logs ol_next
                  ON ol_next.hashed_id = oc_next.hashed_id
              WHERE oc_next.unique_barcode = oc.unique_barcode
                AND (
                    ol_next.date_sent > ol.date_sent
                    OR (ol_next.date_sent = ol.date_sent AND ol_next.id > ol.id)
                )
                AND ol_next.date_sent <= r.date
          )
        ORDER BY r.date DESC, r.id DESC
        LIMIT 1
    )
WHERE
    ol.date_sent >= ? AND ol.date_sent < ? ORDER BY ol.date_sent, ol.id, oc.id
";

$result = report_query($query);

while ($row = $result->fetch_assoc()) {
    ++$rows;
    /* Clean status text */
    switch ($row['outbound_status']) {
        case 0: $status = 'Paid'; break;
        case 1: $status = 'Returned'; break;
        case 2: $status = 'Voided'; break;
        case 6: $status = 'Outbounded'; break;
        default: $status = 'Unknown';
    }

    $customer_return_status = $row['customer_return_id'] !== null
        ? 'Returned by customer'
        : 'No customer return recorded';

    report_csv($output, [
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
        $row['sold_price'],
        $customer_return_status,
        $row['customer_return_date'] ?? '',
        $row['customer_return_reason'] ?? '',
        $row['customer_return_fault'] ?? '',
        $row['customer_return_type'] ?? '',
        $row['customer_return_amount'] ?? ''
    ]);
}

report_finish($output, 'outbound', $rows);
