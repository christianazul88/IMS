<?php 
require_once "../config/database.php";
require_once "../config/on_session.php";

// Set CSV headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="Outbound Monthly Report - ' . $date_for_file . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Main headers
fputcsv($output, [
    'WAREHOUSE',
    'ORDER NUMBER',
    'ORDER LINE ID',
    'CUSTOMER',
    'UNIQUE BARCODE',
    'DESCRIPTION',
    'BRAND',
    'CATEGORY',
    'SUPPLIER',
    'SUPPLIER CLASSIFICATION',
    'OUTBOUND REF #',
    'PLATFORM',
    'SOLD AMOUNT'
]);

$grand_total = 0;

// Safer input
$start_date = $_GET['start'] ?? '';
$end_date = $_GET['end'] ?? '';

// Prepared statement
$stmt = $conn->prepare("
    SELECT 
        oc.unique_barcode,
        p.description,
        b.brand_name,
        c.category_name,
        s.outbound_id,
        lp.logistic_name,
        ol.order_num,
        ol.order_line_id,
        ol.customer_fullname,
        oc.sold_price,
        w.warehouse_name,
        sup.supplier_name,
        sup.local_international
    FROM outbound_content oc
    LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode 
    LEFT JOIN outbound_logs ol 
        ON ol.hashed_id = oc.hashed_id 
        AND s.outbound_id = ol.hashed_id
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
    LEFT JOIN logistic_partner lp ON lp.hashed_id = ol.platform
    LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
    WHERE oc.status != 1
    AND ol.date_sent BETWEEN ? AND ?
    ORDER BY w.warehouse_name
");

$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$res = $stmt->get_result();

// Breakdown array
$breakdown = [];

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {

        $soldPrice = $row['sold_price'] ?? 0;
        $grand_total += $soldPrice;

        // Output main CSV row
        fputcsv($output, [
            $row['warehouse_name'],
            '"' . $row['order_num'] . '"',
            '"' . $row['order_line_id'] . '"',
            $row['customer_fullname'],
            $row['unique_barcode'],
            $row['description'],
            $row['brand_name'],
            $row['category_name'],
            $row['supplier_name'],
            $row['local_international'],
            $row['outbound_id'],
            $row['logistic_name'],
            $soldPrice
        ]);

        // Build breakdown
        $warehouse = $row['warehouse_name'] ?? 'N/A';
        $origin = $row['local_international'] ?? 'Unknown';

        if (!isset($breakdown[$warehouse])) {
            $breakdown[$warehouse] = [];
        }

        if (!isset($breakdown[$warehouse][$origin])) {
            $breakdown[$warehouse][$origin] = [
                'qty' => 0,
                'total' => 0
            ];
        }

        $breakdown[$warehouse][$origin]['qty'] += 1;
        $breakdown[$warehouse][$origin]['total'] += $soldPrice;
    }

    // Grand total row
    fputcsv($output, [
        "GRAND TOTAL","","","","","","","","","",
        $grand_total
    ]);

    // Add empty row for spacing
    fputcsv($output, []);
    fputcsv($output, ['BREAKDOWN']);
    fputcsv($output, ['WAREHOUSE', 'QTY', 'TOTAL AMOUNT', 'ORIGIN']);

    foreach ($breakdown as $warehouse => $origins) {
        foreach ($origins as $origin => $data) {
            fputcsv($output, [
                $warehouse,
                $data['qty'],
                number_format($data['total'], 2),
                $origin
            ]);
        }
    }
}

// Close output
fclose($output);
exit;

// require_once "../config/database.php";
// require_once "../config/on_session.php";

// // Set CSV headers for download
// header('Content-Type: text/csv');
// header('Content-Disposition: attachment;filename="warehouse_report.csv"');

// // Open output stream
// $output = fopen('php://output', 'w');

// // ✅ FIXED: Match columns correctly (added UNIQUE BARCODE)
// fputcsv($output, [
//     'WAREHOUSE',
//     'ORDER NUMBER',
//     'ORDER LINE ID',
//     'CUSTOMER',
//     'UNIQUE BARCODE',
//     'DESCRIPTION',
//     'BRAND',
//     'CATEGORY',
//     'SUPPLIER',
//     'SUPPLIER CLASSIFICATION',
//     'OUTBOUND REF #',
//     'PLATFORM',
//     'SOLD AMOUNT'
// ]);

// $grand_total = 0;

// // ✅ SAFER INPUT
// $start_date = $_GET['start'] ?? '';
// $end_date = $_GET['end'] ?? '';

// // ✅ PREPARED STATEMENT (IMPORTANT)
// $stmt = $conn->prepare("
//     SELECT 
//         oc.unique_barcode,
//         p.description,
//         b.brand_name,
//         c.category_name,
//         s.outbound_id,
//         lp.logistic_name,
//         ol.order_num,
//         ol.order_line_id,
//         ol.customer_fullname,
//         oc.sold_price,
//         w.warehouse_name,
//         sup.supplier_name,
//         sup.local_international
//     FROM outbound_content oc
//     LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode 
//     LEFT JOIN outbound_logs ol 
//         ON ol.hashed_id = oc.hashed_id 
//         AND s.outbound_id = ol.hashed_id
//     LEFT JOIN product p ON p.hashed_id = s.product_id
//     LEFT JOIN brand b ON b.hashed_id = p.brand
//     LEFT JOIN category c ON c.hashed_id = p.category
//     LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
//     LEFT JOIN logistic_partner lp ON lp.hashed_id = ol.platform
//     LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
//     WHERE oc.status != 1
//     AND ol.date_sent BETWEEN ? AND ?
//     ORDER BY w.warehouse_name
// ");

// $stmt->bind_param("ss", $start_date, $end_date);
// $stmt->execute();
// $res = $stmt->get_result();

// if ($res->num_rows > 0) {
//     while ($row = $res->fetch_assoc()) {

//         $soldPrice = $row['sold_price'] ?? 0;
//         $grand_total += $soldPrice;

//         // ✅ FIXED ORDER matches header
//         fputcsv($output, [
//             $row['warehouse_name'],
//             $row['order_num'],
//             $row['order_line_id'],
//             $row['customer_fullname'],
//             $row['unique_barcode'],
//             $row['description'],
//             $row['brand_name'],
//             $row['category_name'],
//             $row['supplier_name'],
//             $row['local_international'],
//             $row['outbound_id'],
//             $row['logistic_name'],
//             $soldPrice
//         ]);
//     }

//     // Grand total row
//     fputcsv($output, [
//         "GRAND TOTAL","","","","","","","","","",
//         $grand_total
//     ]);
// }

// // Close output
// fclose($output);
// exit;



// I also want to add breakdown below for example :
//           WAREHOUSE	                              QTY(qty of items or you can based it on count of unique_barcode)	TOTAL AMOUNT(total amount)	ORIGIN(this will be based on local_international)
// Boss Ryan's Storage JP Rizal	                   322	    76,376.57	             Locals
// Boss Ryan's Storage JP Rizal	                   322	    76,376.57	         International
// Boss Ryan's Storage JP Rizal	                   322	    76,376.57	         Hakot