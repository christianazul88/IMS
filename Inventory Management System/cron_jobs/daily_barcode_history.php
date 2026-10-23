<?php
require_once __DIR__ . '/report_common.php';

$output = report_output();
$rows = 0;

report_csv($output, [
    'Event Date',
    'Event Type',
    'Barcode',
    'Description',
    'Brand',
    'Category',
    'Supplier',
    'Warehouse',
    'Inbound ID',
    'Outbound No.',
    'Order No.',
    'Customer',
    'Reason',
    'Fault / RTS For',
    'Fault Type',
    'Status / Outcome',
    'Event Reference',
    'Amount',
    'Recorded By'
]);

function history_emit(mysqli_result $result): void
{
    global $output, $rows;
    while ($row = $result->fetch_assoc()) {
        report_csv($output, [
            $row['event_date'] ?? '',
            $row['event_type'] ?? '',
            $row['unique_barcode'] ?? '',
            $row['description'] ?? '',
            $row['brand_name'] ?? '',
            $row['category_name'] ?? '',
            $row['supplier_name'] ?? '',
            $row['warehouse_name'] ?? '',
            $row['inbound_id'] ?? '',
            $row['outbound_num'] ?? '',
            $row['order_num'] ?? '',
            $row['customer_name'] ?? '',
            $row['reason'] ?? '',
            $row['fault_or_rts_for'] ?? '',
            $row['fault_type'] ?? '',
            $row['status_outcome'] ?? '',
            $row['event_reference'] ?? '',
            $row['amount'] ?? '',
            $row['recorded_by'] ?? ''
        ]);
        ++$rows;
    }
}

// Inventory receipt events.
history_emit(report_query("
    SELECT
        s.date AS event_date,
        'Inbound Receipt' AS event_type,
        s.unique_barcode,
        p.description,
        b.brand_name,
        c.category_name,
        sup.supplier_name,
        w.warehouse_name,
        s.inbound_id,
        '' AS outbound_num,
        '' AS order_num,
        '' AS customer_name,
        '' AS reason,
        '' AS fault_or_rts_for,
        '' AS fault_type,
        'Received' AS status_outcome,
        CONCAT('Inbound #', s.inbound_id) AS event_reference,
        '' AS amount,
        TRIM(CONCAT(COALESCE(u.user_fname, ''), ' ', COALESCE(u.user_lname, ''))) AS recorded_by
    FROM stocks s
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN inbound_logs il ON il.id = s.inbound_id
    LEFT JOIN supplier sup ON sup.hashed_id = il.supplier
    LEFT JOIN warehouse w ON w.hashed_id = il.warehouse
    LEFT JOIN users u ON u.hashed_id = il.user_id
    WHERE s.date >= ? AND s.date < ?
    ORDER BY s.date, s.id
"));

// Outbound events, one row for each item in the outbound transaction.
history_emit(report_query("
    SELECT
        ol.date_sent AS event_date,
        'Outbound Shipment' AS event_type,
        oc.unique_barcode,
        p.description,
        b.brand_name,
        c.category_name,
        sup.supplier_name,
        w.warehouse_name,
        s.inbound_id,
        ol.hashed_id AS outbound_num,
        ol.order_num,
        ol.customer_fullname AS customer_name,
        '' AS reason,
        '' AS fault_or_rts_for,
        '' AS fault_type,
        CASE oc.status
            WHEN 0 THEN 'Paid'
            WHEN 1 THEN 'Returned'
            WHEN 2 THEN 'Voided'
            WHEN 6 THEN 'Outbounded'
            ELSE CONCAT('Status ', oc.status)
        END AS status_outcome,
        CONCAT('Outbound ', ol.hashed_id) AS event_reference,
        oc.sold_price AS amount,
        TRIM(CONCAT(COALESCE(u.user_fname, ''), ' ', COALESCE(u.user_lname, ''))) AS recorded_by
    FROM outbound_content oc
    INNER JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
    LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
    LEFT JOIN product p ON p.hashed_id = COALESCE(s.product_id, oc.product_id)
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
    LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
    LEFT JOIN users u ON u.hashed_id = ol.user_id
    WHERE ol.date_sent >= ? AND ol.date_sent < ?
    ORDER BY ol.date_sent, ol.id, oc.id
"));

// Customer return events. Link each barcode to its most recent preceding outbound.
history_emit(report_query("
    SELECT
        r.date AS event_date,
        'Customer Return' AS event_type,
        r.unique_barcode,
        p.description,
        b.brand_name,
        c.category_name,
        sup.supplier_name,
        w.warehouse_name,
        s.inbound_id,
        ol.hashed_id AS outbound_num,
        ol.order_num,
        ol.customer_fullname AS customer_name,
        r.reason,
        r.fault AS fault_or_rts_for,
        r.fault_type,
        'Returned by customer' AS status_outcome,
        CONCAT('Return #', r.id) AS event_reference,
        r.amount,
        TRIM(CONCAT(COALESCE(u.user_fname, ''), ' ', COALESCE(u.user_lname, ''))) AS recorded_by
    FROM returns r
    LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
    LEFT JOIN warehouse w ON w.hashed_id = r.warehouse
    LEFT JOIN users u ON u.hashed_id = r.user_id
    LEFT JOIN outbound_logs ol
        ON ol.id = (
            SELECT ol2.id
            FROM outbound_content oc2
            INNER JOIN outbound_logs ol2 ON ol2.hashed_id = oc2.hashed_id
            WHERE oc2.unique_barcode = r.unique_barcode
              AND ol2.date_sent <= r.date
            ORDER BY ol2.date_sent DESC, ol2.id DESC, oc2.id DESC
            LIMIT 1
        )
    WHERE r.date >= ? AND r.date < ?
    ORDER BY r.date, r.id
"));

// Supplier RTS request events. Each item in the supplier-return batch gets its own row.
history_emit(report_query("
    SELECT
        rl.date AS event_date,
        'Supplier RTS Request' AS event_type,
        rc.unique_barcode,
        p.description,
        b.brand_name,
        c.category_name,
        sup.supplier_name,
        w.warehouse_name,
        s.inbound_id,
        ol.hashed_id AS outbound_num,
        ol.order_num,
        ol.customer_fullname AS customer_name,
        rl.reason,
        rl.\x60for\x60 AS fault_or_rts_for,
        '' AS fault_type,
        'RTS recorded for supplier return' AS status_outcome,
        CONCAT('RTS #', rl.id) AS event_reference,
        '' AS amount,
        TRIM(CONCAT(COALESCE(u.user_fname, ''), ' ', COALESCE(u.user_lname, ''))) AS recorded_by
    FROM rts_content rc
    INNER JOIN rts_logs rl ON rl.id = rc.rts_id
    LEFT JOIN stocks s ON s.unique_barcode = rc.unique_barcode
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN supplier sup ON sup.hashed_id = rl.supplier
    LEFT JOIN warehouse w ON w.hashed_id = rl.warehouse
    LEFT JOIN users u ON u.hashed_id = rl.user_id
    LEFT JOIN outbound_logs ol
        ON ol.id = (
            SELECT ol2.id
            FROM outbound_content oc2
            INNER JOIN outbound_logs ol2 ON ol2.hashed_id = oc2.hashed_id
            WHERE oc2.unique_barcode = rc.unique_barcode
              AND ol2.date_sent <= rl.date
            ORDER BY ol2.date_sent DESC, ol2.id DESC, oc2.id DESC
            LIMIT 1
        )
    WHERE rl.date >= ? AND rl.date < ?
    ORDER BY rl.date, rl.id, rc.id
"));

// A later supplier refund or replacement is a separate event from the RTS request.
history_emit(report_query("
    SELECT
        COALESCE(rc.returned_date, rl.returned_date) AS event_date,
        'Supplier RTS Resolution' AS event_type,
        rc.unique_barcode,
        p.description,
        b.brand_name,
        c.category_name,
        sup.supplier_name,
        w.warehouse_name,
        s.inbound_id,
        ol.hashed_id AS outbound_num,
        ol.order_num,
        ol.customer_fullname AS customer_name,
        rl.reason,
        rl.\x60for\x60 AS fault_or_rts_for,
        '' AS fault_type,
        CASE rc.status
            WHEN 1 THEN 'Returned and Refunded'
            WHEN 2 THEN 'Returned and Replaced'
            ELSE 'Supplier RTS resolved'
        END AS status_outcome,
        CONCAT('RTS #', rl.id) AS event_reference,
        '' AS amount,
        TRIM(CONCAT(COALESCE(u.user_fname, ''), ' ', COALESCE(u.user_lname, ''))) AS recorded_by
    FROM rts_content rc
    INNER JOIN rts_logs rl ON rl.id = rc.rts_id
    LEFT JOIN stocks s ON s.unique_barcode = rc.unique_barcode
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN supplier sup ON sup.hashed_id = rl.supplier
    LEFT JOIN warehouse w ON w.hashed_id = rl.warehouse
    LEFT JOIN users u ON u.hashed_id = rl.user_id
    LEFT JOIN outbound_logs ol
        ON ol.id = (
            SELECT ol2.id
            FROM outbound_content oc2
            INNER JOIN outbound_logs ol2 ON ol2.hashed_id = oc2.hashed_id
            WHERE oc2.unique_barcode = rc.unique_barcode
              AND ol2.date_sent <= COALESCE(rc.returned_date, rl.returned_date)
            ORDER BY ol2.date_sent DESC, ol2.id DESC, oc2.id DESC
            LIMIT 1
        )
    WHERE COALESCE(rc.returned_date, rl.returned_date) >= ?
      AND COALESCE(rc.returned_date, rl.returned_date) < ?
    ORDER BY COALESCE(rc.returned_date, rl.returned_date), rl.id, rc.id
"));

report_finish($output, 'barcode_history', $rows);
