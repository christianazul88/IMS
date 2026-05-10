<?php 
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '4G');
ini_set('display_errors', 1); 

include "../config/database.php";
include "../config/on_session.php";

$from = htmlspecialchars($_GET['from'] ?? '');
$to = htmlspecialchars($_GET['to'] ?? '');
$warehouse_transaction = htmlspecialchars($_GET['wh'] ?? '');
$sup_type = htmlspecialchars($_GET['sup_type'] ?? '');
$display_sup = ($sup_type === "All") ? "Local/ Import" : $sup_type;

$fromFormatted = date("M j Y", strtotime($from));
$toFormatted   = date("M j Y", strtotime($to));

$raw_category = !empty($_GET['category']) ? implode(', ', array_map(fn($c) => "'" . trim($c) . "'", explode(',', $_GET['category']))) : "";
$get_supplier = !empty($_GET['supplier']) ? implode(', ', array_map(fn($s) => "'" . trim($s) . "'", explode(',', htmlspecialchars($_GET['supplier'])))) : "";

$category_additional_query = $item_additional_query = $supplier_warehouse_additional = "AND ol.warehouse IN ($user_warehouse_id)";
if ($raw_category && $warehouse_transaction) {
    $category_additional_query = "AND c.hashed_id IN ($raw_category) AND ol.warehouse = '$warehouse_transaction'";
    $item_additional_query = $supplier_warehouse_additional = "AND p.category IN ($raw_category) AND ol.warehouse = '$warehouse_transaction'";
} elseif ($warehouse_transaction) {
    $category_additional_query = $item_additional_query = $supplier_warehouse_additional = "AND ol.warehouse = '$warehouse_transaction'";
}

$additional_supplier_query = "";
if ($get_supplier) {
    $additional_supplier_query = "AND s.supplier IN ($get_supplier)";
    if ($sup_type !== "All") {
        $additional_supplier_query .= " AND sup.local_international = '$sup_type'";
    }
} elseif ($sup_type !== "All") {
    $additional_supplier_query = "AND sup.local_international = '$sup_type'";
}

$ware_treans = $imploded_warehouse_names;
if ($warehouse_transaction) {
    $res = $conn->query("SELECT warehouse_name FROM warehouse WHERE hashed_id = '$warehouse_transaction' LIMIT 1");
    $ware_treans = ($res && $res->num_rows > 0) ? $res->fetch_assoc()['warehouse_name'] : "N/A";
}

$csv_rows = [];
$grand_total_qty = $grand_total_unit_cost = $grand_total_gross = $grand_total_net = 0;
$num = 1;

if ($from && $to) {
    $supplier_query = "
        SELECT sup.hashed_id AS supplier_head_id, sup.supplier_name AS supplier, sup.local_international AS sup_type,
               COUNT(oc.unique_barcode) AS sup_outbounded_qty, SUM(s.capital) AS sup_unit_cost, SUM(oc.sold_price) AS sup_gross_sale
        FROM supplier sup
        LEFT JOIN stocks s ON s.supplier = sup.hashed_id
        LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
        LEFT JOIN product p ON p.hashed_id = s.product_id
        WHERE oc.status IN (0, 6)
          AND s.item_status NOT IN (4, 8)
          AND MONTH(ol.date_sent) = MONTH(NOW()) AND YEAR(ol.date_sent) = YEAR(NOW())
          AND ol.status IN (0, 6)
          $supplier_warehouse_additional
          $additional_supplier_query
        GROUP BY sup.supplier_name";

    $supplier_res = $conn->query($supplier_query);
    if ($supplier_res->num_rows > 0) {
        while ($row = $supplier_res->fetch_assoc()) {
            // Supplier row
            // $csv_rows[] = [$num++, $row['supplier'], '', '', '', '', '', '', '', '', '', '', '', '', '', ''];

            $cat_q = "
                SELECT c.hashed_id AS category_id, c.category_name,
                       COUNT(oc.unique_barcode) AS outbounded_qty,
                       SUM(s.capital) AS unit_cost,
                       SUM(oc.sold_price) AS gross_sale
                FROM category c
                LEFT JOIN product p ON p.category = c.hashed_id
                LEFT JOIN stocks s ON s.product_id = p.hashed_id
                LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
                LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
                LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                WHERE oc.status IN (0, 6)
                  AND s.item_status NOT IN (4, 8)
                  AND DATE(ol.date_sent) BETWEEN '$from' AND '$to'
                  AND s.supplier = '{$row['supplier_head_id']}'
                  AND ol.status IN (0, 6)
                  $category_additional_query
                GROUP BY c.category_name";

            $cat_res = $conn->query($cat_q);
            if ($cat_res->num_rows > 0) {
                while ($cat = $cat_res->fetch_assoc()) {
                    $grand_total_qty += $cat['outbounded_qty'];
                    $grand_total_unit_cost += $cat['unit_cost'];
                    $grand_total_gross += $cat['gross_sale'];
                    $cat_net = $cat['gross_sale'] - $cat['unit_cost'];

                    // Category row
                    // $csv_rows[] = ['', '', $cat['category_name'], '', '', '', '', '', '', '', '', '', '', '', '', ''];

                    // Item rows
                    $item_q = "
                        SELECT oc.unique_barcode, oc.sold_price, ol.order_num, oc.hashed_id AS outbound_num,
                               ol.customer_fullname, ol.date_sent, sup.supplier_name, sup.local_international,
                               p.description, b.brand_name, s.batch_code, s.capital
                        FROM outbound_content oc
                        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                        LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
                        LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
                        LEFT JOIN product p ON p.hashed_id = s.product_id
                        LEFT JOIN brand b ON b.hashed_id = p.brand
                        WHERE p.category = '{$cat['category_id']}'
                          AND oc.status IN (0, 6)
                          AND s.item_status NOT IN (4, 8)
                          AND DATE(ol.date_sent) BETWEEN '$from' AND '$to'
                          AND s.supplier = '{$row['supplier_head_id']}'
                          AND ol.status IN (0, 6)
                          $item_additional_query";

                    $item_res = $conn->query($item_q);
                    while ($item = $item_res->fetch_assoc()) {
                        $net = $item['sold_price'] - $item['capital'];
                       $csv_rows[] = [
                                        $row['supplier'],
                                        $cat['category_name'],
                                        '"-' . $item['order_num'] . '-"',
                                        $item['outbound_num'],
                                        $item['customer_fullname'],
                                        $item['date_sent'],
                                        $item['supplier_name'],
                                        $item['local_international'],
                                        $item['description'],
                                        $item['brand_name'],
                                        $item['unique_barcode'],
                                        $item['batch_code'],
                                        1,
                                        number_format($item['capital'], 2),
                                        number_format($item['sold_price'], 2),
                                        number_format($net, 2)
                                    ];
                    }

                    // Category total row
                    // $csv_rows[] = [
                    //     $row['supplier'], 'TOTAL for ' . $cat['category_name'], '', '', '', '', '', '', '', '', '', '',
                    //     $cat['outbounded_qty'],
                    //     number_format($cat['unit_cost'], 2),
                    //     number_format($cat['gross_sale'], 2),
                    //     number_format($cat_net, 2)
                    // ];
                }
            }
        }

        $grand_total_net = $grand_total_gross - $grand_total_unit_cost;
        $csv_rows[] = [
            'GRAND TOTAL', '', '', '', '', '', '', '', '', '', '',
            $grand_total_qty,
            number_format($grand_total_unit_cost, 2),
            number_format($grand_total_gross, 2),
            number_format($grand_total_net, 2)
        ];
    }
}

if($warehouse_transaction === ''){
    $warehousez = "All Warehouse";
} else {
    $warehousez = $ware_treans;
}
// === Output CSV ===
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $warehousez . ' ' . $fromFormatted . '_to_' . $toFormatted . '.csv"');

$output = fopen('php://output', 'w');

// Heading Section
$heading_rows = [
    ["Generated By:", $user_fullname, '', '', '', '', '', '', '', '', '', '', '', '', ''],
    ["Warehouse:", $ware_treans, '', '', '', '', '', '', '', '', '', '', '', '', ''],
    ["Date Range:", "$from to $to", '', '', '', '', '', '', '', '', '', '', '', '', ''],
    ["Supplier Type:", $display_sup, '', '', '', '', '', '', '', '', '', '', '', '', ''],
];

if (!empty($raw_category)) {
    $cat_query = "SELECT category_name FROM category WHERE hashed_id IN ($raw_category)";
    $cat_result = $conn->query($cat_query);
    $cat_names = [];
    while ($cat = $cat_result->fetch_assoc()) {
        $cat_names[] = $cat['category_name'];
    }
    $heading_rows[] = ["Categories:", implode(', ', $cat_names), '', '', '', '', '', '', '', '', '', '', '', '', ''];
}

if (!empty($get_supplier)) {
    $sup_query = "SELECT supplier_name FROM supplier WHERE hashed_id IN ($get_supplier)";
    $sup_result = $conn->query($sup_query);
    $sup_names = [];
    while ($sup = $sup_result->fetch_assoc()) {
        $sup_names[] = $sup['supplier_name'];
    }
    $heading_rows[] = ["Suppliers:", implode(', ', $sup_names), '', '', '', '', '', '', '', '', '', '', '', '', ''];
}

// Output headings
foreach ($heading_rows as $heading) {
    fputcsv($output, array_pad($heading, 16, ''));
}
fputcsv($output, array_fill(0, 16, '')); // Blank line

// Table headers
fputcsv($output, [
    'SUPPLIER', 'CATEGORY', 'ORDER #', 'OUTBOUND #', 'CUSTOMER',
    'OUTBOUND DATE', 'SUPPLIER NAME', 'LOCAL/IMPORT', 'DESCRIPTION', 'BRAND',
    'BARCODE', 'BATCH', 'QUANTITY', 'UNIT COST', 'GROSS SALE', 'NET INCOME'
]);

// Output data
foreach ($csv_rows as $row) {
    fputcsv($output, array_pad($row, 17, ''));
}

fclose($output);
exit;
