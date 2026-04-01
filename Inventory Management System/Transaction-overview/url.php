<?php
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '4G');
ini_set('display_errors', 1);
ini_set('pcre.backtrack_limit', '10000000');

include "../config/database.php";
include "../config/on_session.php";

/* -----------------------------
   Sanitize Inputs
------------------------------ */
$from = htmlspecialchars($_GET['from'] ?? '');
$to = htmlspecialchars($_GET['to'] ?? '');
$raw_category = $_GET['category'] ?? '';
$warehouse_transaction = htmlspecialchars($_GET['wh'] ?? '');

$categories = explode(',', $_GET['category'] ?? '');
$escaped = array_map(fn($cat) => "'" . trim(htmlspecialchars($cat, ENT_QUOTES)) . "'", $categories);
$imploded_category = implode(',', $escaped);

/* -----------------------------
   Warehouse Filters
------------------------------ */
if (empty($raw_category) && empty($warehouse_transaction)) {
    $category_additional_query = "AND ol.warehouse IN ($user_warehouse_id)";
    $item_additional_query = "AND ol.warehouse IN ($user_warehouse_id)";
} elseif (!empty($raw_category) && !empty($warehouse_transaction)) {
    $category_additional_query = "AND c.hashed_id IN ($raw_category) AND ol.warehouse = '$warehouse_transaction'";
    $item_additional_query = "AND ol.warehouse = '$warehouse_transaction'";
} elseif (empty($raw_category) && !empty($warehouse_transaction)) {
    $category_additional_query = "AND ol.warehouse = '$warehouse_transaction'";
    $item_additional_query = "AND ol.warehouse = '$warehouse_transaction'";
}

/* -----------------------------
   Start CSV Export
------------------------------ */
if ($from && $to) {

    $fileName = "Transaction Overview - $from to $to.csv";

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    /* CSV Header Row */
    fputcsv($output, [
        '#',
        'Classification',
        'Category',
        'Order #',
        'Outbound #',
        'Customer',
        'Outbound Date',
        'Supplier',
        'Local/Import',
        'Description',
        'Brand',
        'Barcode',
        'Batch',
        'Staff',
        'Status',
        'Unit Cost',
        'Gross Sale',
        'Net Income'
    ]);

    $num = 1;

    /* -----------------------------
       Category Query
    ------------------------------ */
    $category_query = "
        SELECT 
            c.hashed_id AS category_id,
            c.category_name,
            cl.classification_name
        FROM category c
        LEFT JOIN product p ON p.category = c.hashed_id
        LEFT JOIN stocks s ON s.product_id = p.hashed_id
        LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
        LEFT JOIN classification cl ON cl.hashed_id = c.classification_id
        WHERE
            s.item_status != 8
            AND DATE(ol.date_sent) BETWEEN '$from' AND '$to'
            $category_additional_query
        GROUP BY c.category_name
    ";

    $category_result = $conn->query($category_query);

    if ($category_result && $category_result->num_rows > 0) {

        while ($cat = $category_result->fetch_assoc()) {

            $category_id = $cat['category_id'];
            $category_name = $cat['category_name'];
            $classification_name = $cat['classification_name'];

            /* -----------------------------
               Item Query Per Category
            ------------------------------ */
            $item_query = "
                SELECT
                    oc.unique_barcode,
                    oc.sold_price,
                    ol.order_num,
                    oc.hashed_id AS outbound_num,
                    ol.customer_fullname,
                    ol.date_sent,
                    sup.supplier_name,
                    sup.local_international,
                    p.description,
                    b.brand_name,
                    s.batch_code,
                    s.capital,
                    u.user_fname,
                    u.user_lname,
                    oc.status AS outbound_status
                FROM outbound_content oc
                LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
                LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
                LEFT JOIN product p ON p.hashed_id = s.product_id
                LEFT JOIN brand b ON b.hashed_id = p.brand
                LEFT JOIN users u ON u.hashed_id = ol.user_id
                WHERE 
                    p.category = '$category_id'
                    AND s.item_status != 8
                    AND DATE(ol.date_sent) BETWEEN '$from' AND '$to'
                    $item_additional_query
                ORDER BY u.user_fname, oc.status ASC
            ";

            $item_res = $conn->query($item_query);

            if ($item_res && $item_res->num_rows > 0) {

                while ($row = $item_res->fetch_assoc()) {

                    $net_income = $row['sold_price'] - $row['capital'];
                    $staff_fullname = $row['user_fname'] . " " . $row['user_lname'];

                    /* Clean status text */
                    switch ($row['outbound_status']) {
                        case 0: $status = 'Paid'; break;
                        case 1: $status = 'Returned'; break;
                        case 2: $status = 'Voided'; break;
                        case 6: $status = 'Outbounded'; break;
                        default: $status = 'Unknown';
                    }

                    fputcsv($output, [
                        $num,
                        $classification_name,
                        $category_name,
                        '"-' . $row['order_num'] . '-"',
                        '"-' . $row['outbound_num'] . '-"',
                        $row['customer_fullname'],
                        $row['date_sent'],
                        $row['supplier_name'],
                        $row['local_international'],
                        $row['description'],
                        $row['brand_name'],
                        $row['unique_barcode'],
                        $row['batch_code'],
                        $staff_fullname,
                        $status,
                        $row['capital'],
                        $row['sold_price'],
                        $net_income
                    ]);

                    $num++;
                }
            }
        }
    }

    fclose($output);
    exit;
}

/* If no date provided */
echo "Invalid date range.";
exit;
?>