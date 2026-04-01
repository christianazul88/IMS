<?php 
require_once "../config/database.php";
require_once "../config/on_session.php";

// Headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="Inbound Monthly Report - ' . $date_for_file . '.csv"');

$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, ['#','WAREHOUSE', 'BARCODE', 'DESCRIPTION', 'BRAND', 'CLASSIFICATION', 'CATEGORY', 'SUPPLIER', 'SUPPLIER CLASSIFICATION', 'INBOUNDED BY', 'INBOUND ID', 'DATE RECEIVED', 'SOLD AMOUNT']);

$grand_total = 0;
$count = 1;

// Secure inputs
$start_date = $_GET['start'];
$end_date   = $_GET['end'];

// MAIN QUERY
$stmt = $conn->prepare("
    SELECT 
        il.date_received,
        u.user_fname,
        u.user_lname,
        il.unique_key,
        p.description,
        b.brand_name,
        cl.classification_name,
        c.category_name,
        s.capital,
        s.unique_barcode,
        sup.supplier_name,
        sup.local_international,
        w.warehouse_name
    FROM stocks s
    LEFT JOIN inbound_logs il ON il.unique_key = s.unique_key
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
    LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
    LEFT JOIN users u ON u.hashed_id = s.user_id
    LEFT JOIN classification cl ON cl.hashed_id = c.classification_id
    WHERE il.date_received BETWEEN ? AND ?
    ORDER BY w.warehouse_name ASC
");

$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$res = $stmt->get_result();

// STORE breakdown data
$breakdown = [];

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {

        $warehouseName = $row['warehouse_name'];
        $origin = $row['local_international'];
        $price = $row['capital'];

        // accumulate breakdown
        if (!isset($breakdown[$warehouseName][$origin])) {
            $breakdown[$warehouseName][$origin] = [
                'qty' => 0,
                'total' => 0
            ];
        }

        $breakdown[$warehouseName][$origin]['qty']++;
        $breakdown[$warehouseName][$origin]['total'] += $price;

        $grand_total += $price;

        // CSV row
        fputcsv($output, [
            $count++,
            $warehouseName,
            $row['unique_barcode'],
            $row['description'],
            $row['brand_name'],
            $row['classification_name'],
            $row['category_name'],
            $row['supplier_name'],
            $origin,
            $row['user_fname'] . " " . $row['user_lname'],
            '"' . $row['unique_key'] . '"',
            $row['date_received'],
            $price
        ]);
    }

    // GRAND TOTAL
    fputcsv($output, []);
    fputcsv($output, ["GRAND TOTAL","","","","","","","","","",$grand_total]);

    // =========================
    // BREAKDOWN SECTION
    // =========================
    fputcsv($output, []);
    fputcsv($output, ['WAREHOUSE','QTY','TOTAL AMOUNT','ORIGIN']);

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

fclose($output);
exit;


// require_once "../config/database.php";
// require_once "../config/on_session.php";

// // Set CSV headers for download
// header('Content-Type: text/csv');
// header('Content-Disposition: attachment;filename="warehouse_report.csv"');

// // Open output stream
// $output = fopen('php://output', 'w');

// // Write CSV column headers
// fputcsv($output, ['#','WAREHOUSE', 'BARCODE', 'DESCRIPTION', 'BRAND', 'CATEGORY', 'SUPPLIER', 'SUPPLIER CLASSIFICATION', 'INBOUNDED BY', 'INBOUND ID', 'DATE RECEIVED', 'SOLD AMOUNT']);
// $grand_total = 0;
// $start_date = $_GET['start'];
// $end_date = $_GET['end'];

// $query = "SELECT 
//             il.date_received,
//             u.user_fname,
//             u.user_lname,
//             il.unique_key,
//             p.description,
//             b.brand_name,
//             c.category_name,
//             s.capital,
//             s.unique_barcode,
//             sup.supplier_name,
//             sup.local_international,
//             w.warehouse_name
//           FROM stocks s
//           LEFT JOIN inbound_logs il ON il.unique_key = s.unique_key
//           LEFT JOIN product p ON p.hashed_id = s.product_id
//           LEFT JOIN brand b ON b.hashed_id = p.brand
//           LEFT JOIN category c ON c.hashed_id = p.category
//           LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
//           LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
//           LEFT JOIN users u ON u.hashed_id = s.user_id
//           WHERE il.date_received BETWEEN '$start_date' AND '$end_date'
//           ORDER BY s.warehouse, w.warehouse_name ASC";

// $res = $conn->query($query);

// if ($res->num_rows > 0) {
//     while ($row = $res->fetch_assoc()) {
//         $uniqueBarcode     = $row['unique_barcode'];
//         $description       = $row['description'];
//         $brandName         = $row['brand_name'];
//         $categoryName      = $row['category_name'];
//         $Price         = $row['capital'];
//         $supplier = $row['supplier_name'];
//         $supplier_classification = $row['local_international'];
//         $inbounded_by = $row['user_fname'] . " " . $row['user_lname'];
//         $date_received = $row['date_received'];
//         $warehouseName     = $row['warehouse_name'];
//         $inbound_id = $row['unique_key'];

//         $grand_total += $Price;

//         // Write each row to CSV
//         fputcsv($output, [$warehouseName, $uniqueBarcode, $description, $brandName, $categoryName, $supplier, $supplier_classification, $inbounded_by, $inbound_id, $date_received, $Price]);
//     }
//     fputcsv($output, ["GRAND TOTAL","","","","","","","","","",$grand_total]);
// }

// // Close output
// fclose($output);
// exit;
