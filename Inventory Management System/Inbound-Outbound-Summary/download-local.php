
<?php 
require_once "../config/database.php";
require_once "../config/on_session.php";

$warehouse_id = null;
$warehouse_name = null;

if(isset($_GET['warehouse_id'])){
    $warehouse_id= $_GET['warehouse_id'];
    
    $get_wh_name = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$warehouse_id' LIMIT 1";
    $get_wh_res = $conn->query($get_wh_name);
    if($get_wh_res->num_rows>0){
        $row=$get_wh_res->fetch_assoc();
        $warehouse_name = $row['warehouse_name'];
    }
}

/**
 * Helper function to fetch inbound and outbound data by supplier type
 */
function getSupplierData($conn, $warehouse_id, $supplier_type, $is_outbound = false) {
    $outbound_condition = $is_outbound ? "AND s.outbound_id != ''" : "";
    
    $query = "
        SELECT COUNT(s.unique_barcode) AS total_qty, 
               SUM(s.capital) AS total_amount
        FROM stocks s
        LEFT JOIN supplier sup ON sup.hashed_Id = s.supplier
        WHERE sup.local_international = '$supplier_type'
          AND s.warehouse = '$warehouse_id'
          $outbound_condition
          AND s.date BETWEEN LAST_DAY(CURDATE() - INTERVAL 2 MONTH) + INTERVAL 1 DAY 
                          AND NOW()
    ";
    
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Define supplier types
$supplier_types = ['International', 'Local', 'Hakot'];

// Prepare arrays to store results
$inbound_totals = [];
$outbound_totals = [];

foreach($supplier_types as $type) {
    $inbound_totals[$type] = getSupplierData($conn, $warehouse_id, $type, false);
    $outbound_totals[$type] = getSupplierData($conn, $warehouse_id, $type, true);
}

// =================== CALCULATIONS ===================
$total_stocks_qty = 0;
$ending_inventory_qty = 0;

foreach($supplier_types as $type){
    $total_stocks_qty += $inbound_totals[$type]['total_qty'] ?? 0;
    $ending_inventory_qty += ($inbound_totals[$type]['total_qty'] ?? 0) - ($outbound_totals[$type]['total_qty'] ?? 0);
}

// =================== FORMAT AS MONEY ===================
function moneyFormat($number) {
    return number_format((float)$number, 2, '.', ',');
}

// =================== SET CSV HEADERS ===================
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Inbound Outbound - ' . $warehouse_name . '.csv');

// Open output stream
$output = fopen('php://output', 'w');

// =================== WRITE DATA ===================
fputcsv($output, ["BEGINNING LAST MONTH END OF DAY", "TOTAL AMOUNT"]);

foreach($supplier_types as $type){
    fputcsv($output, ["INBOUND $type", moneyFormat($inbound_totals[$type]['total_amount'] ?? 0)]);
}

fputcsv($output, []); // blank line

fputcsv($output, ["TOTAL STOCKS", $total_stocks_qty]);

fputcsv($output, []); // blank line

foreach($supplier_types as $type){
    fputcsv($output, ["OUTBOUND $type", moneyFormat($outbound_totals[$type]['total_amount'] ?? 0)]);
}

fputcsv($output, []); // blank line

fputcsv($output, ["ENDING INVENTORY UP TO DATE", $ending_inventory_qty]);

fclose($output);
exit;


// require_once "../config/database.php";
// require_once "../config/on_session.php";

// /**
//  * ================================
//  *  INBOUND IMPORTS (International)
//  * ================================
//  * Count and sum all items from INTERNATIONAL suppliers
//  * between the beginning of last month up to today.
//  */
// $warehouse_id = null;
// $warehouse_name = null;
// // $start_time = null;
// // $end_time = null;

// // if(isset($_GET['start']) && isset($_GET['end'])){
// //     $start_time = $_GET['start'];
// //     $end_time = $_GET['end'];
// // } 

// if(isset($_GET['warehouse_id'])){
//     $warehouse_id= $_GET['warehouse_id'];
    
//     $get_wh_name = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$warehouse_id' LIMIT 1";
//     $get_wh_res = $conn->query($get_wh_name);
//     if($get_wh_res->num_rows>0){
//         $row=$get_wh_res->fetch_assoc();
//         $warehouse_name = $row['warehouse_name'];
//     }
// }
// $inbound_import_query = "
//     SELECT COUNT(s.unique_barcode) AS total_imports, 
//            SUM(s.capital) AS total_imports_amount
//     FROM stocks s
//     LEFT JOIN supplier sup ON sup.hashed_Id = s.supplier
//     WHERE sup.local_international = 'International' AND s.warehouse = '$warehouse_id'
//       AND s.date BETWEEN LAST_DAY(CURDATE() - INTERVAL 2 MONTH) + INTERVAL 1 DAY 
//                       AND NOW()
// ";
// $import_result = mysqli_query($conn, $inbound_import_query);
// $import_data = mysqli_fetch_assoc($import_result);

// /**
//  * ================================
//  *  INBOUND LOCALS (Local Suppliers)
//  * ================================
//  * Count and sum all items from LOCAL suppliers
//  * between the beginning of last month up to today.
//  */
// $inbound_locals_query = "
//     SELECT COUNT(s.unique_barcode) AS total_locals, 
//            SUM(s.capital) AS total_locals_amount
//     FROM stocks s
//     LEFT JOIN supplier sup ON sup.hashed_Id = s.supplier
//     WHERE sup.local_international = 'Local' AND s.warehouse = '$warehouse_id'
//       AND s.date BETWEEN LAST_DAY(CURDATE() - INTERVAL 2 MONTH) + INTERVAL 1 DAY 
//                       AND NOW()
// ";
// $local_result = mysqli_query($conn, $inbound_locals_query);
// $local_data = mysqli_fetch_assoc($local_result);

// /**
//  * ================================
//  *  OUTBOUND IMPORTS (International)
//  * ================================
//  * Count and sum INTERNATIONAL supplier items
//  * that already have an outbound_id (means released/sold).
//  */
// $outbounded_import_query = "
//     SELECT COUNT(s.unique_barcode) AS total_outbound_imports, 
//            SUM(s.capital) AS total_outbound_imports_amount
//     FROM stocks s
//     LEFT JOIN supplier sup ON sup.hashed_Id = s.supplier
//     WHERE sup.local_international = 'International' AND s.warehouse = '$warehouse_id'
//       AND s.outbound_id != ''
//       AND s.date BETWEEN LAST_DAY(CURDATE() - INTERVAL 2 MONTH) + INTERVAL 1 DAY 
//                       AND NOW()
// ";
// $out_import_result = mysqli_query($conn, $outbounded_import_query);
// $out_import_data = mysqli_fetch_assoc($out_import_result);

// /**
//  * ================================
//  *  OUTBOUND LOCALS (Local Suppliers)
//  * ================================
//  * Count and sum LOCAL supplier items
//  * that already have an outbound_id (means released/sold).
//  */
// $outbounded_local_query = "
//     SELECT COUNT(s.unique_barcode) AS total_outbound_locals, 
//            SUM(s.capital) AS total_outbound_locals_amount
//     FROM stocks s
//     LEFT JOIN supplier sup ON sup.hashed_Id = s.supplier
//     WHERE sup.local_international = 'Local' AND s.warehouse = '$warehouse_id'
//       AND s.outbound_id != ''
//       AND s.date BETWEEN LAST_DAY(CURDATE() - INTERVAL 2 MONTH) + INTERVAL 1 DAY 
//                       AND NOW()
// ";
// $out_local_result = mysqli_query($conn, $outbounded_local_query);
// $out_local_data = mysqli_fetch_assoc($out_local_result);

// // =================== CALCULATIONS ===================
// $total_imports_qty       = $import_data['total_imports'] ?? 0;
// $total_imports_amount    = $import_data['total_imports_amount'] ?? 0;

// $total_locals_qty        = $local_data['total_locals'] ?? 0;
// $total_locals_amount     = $local_data['total_locals_amount'] ?? 0;

// $total_out_imports_qty   = $out_import_data['total_outbound_imports'] ?? 0;
// $total_out_imports_amount= $out_import_data['total_outbound_imports_amount'] ?? 0;

// $total_out_locals_qty    = $out_local_data['total_outbound_locals'] ?? 0;
// $total_out_locals_amount = $out_local_data['total_outbound_locals_amount'] ?? 0;

// // Compute total stocks (inbound qty)
// $total_stocks_qty = $total_imports_qty + $total_locals_qty;

// // Compute ending inventory = inbound - outbound
// $ending_inventory_qty = $total_stocks_qty - ($total_out_imports_qty + $total_out_locals_qty);

// // =================== FORMAT AS MONEY ===================
// function moneyFormat($number) {
//     return number_format((float)$number, 2, '.', ',');
// }

// // =================== SET CSV HEADERS ===================
// header('Content-Type: text/csv; charset=utf-8');
// header('Content-Disposition: attachment; filename=Inbound OUtbound - ' . $warehouse_name . ' ' . $date_for_file .'.csv');

// // Open output stream
// $output = fopen('php://output', 'w');

// // =================== WRITE DATA ===================
// fputcsv($output, ["BEGINNING LAST MONTH END OF DAY", "TOTAL AMOUNT"]);

// fputcsv($output, ["INBOUND IMPORTS", moneyFormat($total_imports_amount)]);
// fputcsv($output, ["INBOUND LOCALS", moneyFormat($total_locals_amount)]);

// fputcsv($output, []); // blank line

// fputcsv($output, ["TOTAL STOCKS", $total_stocks_qty]);

// fputcsv($output, []); // blank line

// fputcsv($output, ["OUTBOUND IMPORTS", moneyFormat($total_out_imports_amount)]);
// fputcsv($output, ["OUTBOUND LOCALS", moneyFormat($total_out_locals_amount)]);

// fputcsv($output, []); // blank line

// fputcsv($output, ["ENDING INVENTORY UP TO DATE", $ending_inventory_qty]);

// fclose($output);
// exit;
