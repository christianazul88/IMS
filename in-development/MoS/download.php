<?php
require_once "../config/database.php";
require_once "../config/on_session.php";

if (isset($_GET['warehouse']) && isset($_GET['movement'])) {
    $warehouse_hash = $_GET['warehouse'];
    $movement_type  = $_GET['movement'];
    if($movement_type === "new"){
        $type = "MOVING";
    } else {
        $type = "NON-MOVING";
    }

    // Get warehouse name
    $warehouse_name_query = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$warehouse_hash' LIMIT 1";
    $warehouse_name_res = $conn->query($warehouse_name_query);
    $row = $warehouse_name_res->fetch_assoc();
    $selected_warehouse = $row['warehouse_name'] ?? 'Unknown';

    // Set CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="movement of stocks.csv"');

    // Start CSV output
    $output = fopen('php://output', 'w');

    // Write report title rows
    fputcsv($output, ["FOR WAREHOUSE:", $selected_warehouse]);
    fputcsv($output, ["TYPE:", $type]);
    fputcsv($output, []); // blank line

    if ($movement_type === "new") {

        // INTERNATIONAL
        $import_query = "
            SELECT COUNT(oc.unique_barcode) AS total_qty, SUM(oc.sold_price) AS total_amount
            FROM warehouse w 
            LEFT JOIN outbound_logs ol ON ol.warehouse = w.hashed_id 
            LEFT JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id 
            LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode 
            LEFT JOIN supplier sup ON sup.hashed_id = s.supplier 
            WHERE w.hashed_id = '$warehouse_hash' 
              AND sup.local_international = 'international' 
              AND oc.status IN (0,6)";
        $import_res = $conn->query($import_query);
        if ($import_res->num_rows > 0) {
            $row = $import_res->fetch_assoc();
            fputcsv($output, ['INTERNATIONAL QTY', number_format($row['total_qty'], 2)]);
            fputcsv($output, ['INTERNATIONAL AMOUNT', number_format($row['total_amount'], 2)]);
            fputcsv($output, []); 
        }

        // LOCAL
        $local_query = "
            SELECT COUNT(oc.unique_barcode) AS total_qty, SUM(oc.sold_price) AS total_amount
            FROM warehouse w 
            LEFT JOIN outbound_logs ol ON ol.warehouse = w.hashed_id 
            LEFT JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id 
            LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode 
            LEFT JOIN supplier sup ON sup.hashed_id = s.supplier 
            WHERE w.hashed_id = '$warehouse_hash' 
              AND sup.local_international = 'local' 
              AND oc.status IN (0,6)";
        $local_res = $conn->query($local_query);
        if ($local_res->num_rows > 0) {
            $row = $local_res->fetch_assoc();
            fputcsv($output, ['LOCAL QTY', number_format($row['total_qty'], 2)]);
            fputcsv($output, ['LOCAL AMOUNT', number_format($row['total_amount'], 2)]);
        }

    } elseif ($movement_type === "old") {

        // INTERNATIONAL
        $import_query = "
            SELECT COUNT(s.unique_barcode) AS total_qty, SUM(s.capital) AS total_amnt 
            FROM warehouse w 
            LEFT JOIN stocks s ON s.warehouse = w.hashed_id 
            LEFT JOIN supplier sup ON sup.hashed_id = s.supplier 
            WHERE w.hashed_id = '$warehouse_hash' 
              AND sup.local_international = 'international' 
              AND s.item_status = 0";
        $import_res = $conn->query($import_query);
        if ($import_res->num_rows > 0) {
            $row = $import_res->fetch_assoc();
            fputcsv($output, ['IMPORTS TOTAL QTY', $row['total_qty']]);
            fputcsv($output, ['IMPORTS TOTAL AMNT', number_format($row['total_amnt'], 2)]);
            fputcsv($output, []);
        }

        // LOCAL
        $local_query = "
            SELECT COUNT(s.unique_barcode) AS total_qty, SUM(s.capital) AS total_amnt 
            FROM warehouse w 
            LEFT JOIN stocks s ON s.warehouse = w.hashed_id 
            LEFT JOIN supplier sup ON sup.hashed_id = s.supplier 
            WHERE w.hashed_id = '$warehouse_hash' 
              AND sup.local_international = 'local' 
              AND s.item_status = 0";
        $local_res = $conn->query($local_query);
        if ($local_res->num_rows > 0) {
            $row = $local_res->fetch_assoc();
            fputcsv($output, ['LOCALS TOTAL QTY', $row['total_qty']]);
            fputcsv($output, ['LOCALS TOTAL AMNT', number_format($row['total_amnt'], 2)]);
        }
    }

    fclose($output);
    exit;
}

//MY PROTOTYPE =======================================================
//====================================================================
// require_once "../config/database.php";
// require_once "../config/on_session.php";

// if (isset($_GET['warehouse']) && isset($_GET['movement'])) {
//     $warehouse_hash = $_GET['warehouse'];
//     $movement_type  = $_GET['movement'];

//     $warehouse_name_query = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$warehouse_hash' LIMIT 1";
//     $warehouse_name_res = $conn->query($warehouse_name_query);
//     $row=$warehouse_name_res->fetch_assoc();
//     $selected_warehouse = $row['warehouse_name'];

//     echo "'FOR WAREHOUSE: " . $selected_warehouse. "'<br>'TYPE: " . $movement_type . "'<br>";

//     if($movement_type === "new"){
//         $import_query = "SELECT COUNT(oc.unique_barcode) AS total_qty, SUM(oc.sold_price) AS total_amount FROM warehouse w LEFT JOIN outbound_logs ol ON ol.warehouse = w.hashed_id LEFT JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode LEFT JOIN supplier sup ON sup.hashed_id = s.supplier WHERE w.hashed_id = '$warehouse_hash' AND sup.local_international = 'international' AND oc.status IN (0,6)";
//         $import_res = $conn->query($import_query);
//         if($import_res->num_rows>0){
//             $row=$import_res->fetch_assoc();
//             echo "'" . number_format($row['total_qty'], 2) . "','" . number_format($row['total_amount'], 2) . "'<br><br>";
//         }

//         $local_query = "SELECT COUNT(oc.unique_barcode) AS total_qty, SUM(oc.sold_price) AS total_amount FROM warehouse w LEFT JOIN outbound_logs ol ON ol.warehouse = w.hashed_id LEFT JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode LEFT JOIN supplier sup ON sup.hashed_id = s.supplier WHERE w.hashed_id = '$warehouse_hash' AND sup.local_international = 'local' AND oc.status IN (0,6)";
//         $local_res = $conn->query($local_query);
//         if($local_res->num_rows>0){
//             $row=$local_res->fetch_assoc();
//             echo "'" . number_format($row['total_qty'], 2) . "','" . number_format($row['total_amount'], 2) . "'<br><br>";
//         }

//         // $import_detailed_query = "SELECT p.description, b.brand_name, c.category_name, ol.order_num ";
//     }
    

//     if ($movement_type === "old") {

//         $import_query = "
//             SELECT 
//                 COUNT(s.unique_barcode) AS total_qty, 
//                 SUM(s.capital) AS total_amnt 
//             FROM warehouse w 
//             LEFT JOIN stocks s ON s.warehouse = w.hashed_id 
//             LEFT JOIN supplier sup ON sup.hashed_id = s.supplier 
//             WHERE 
//                 w.hashed_id = '$warehouse_hash' 
//                 AND sup.local_international = 'international' 
//                 AND s.item_status = 0
//         ";

//         $import_res = $conn->query($import_query);

//         if ($import_res->num_rows > 0) {
//             while ($row = $import_res->fetch_assoc()) {
//                 echo "<br>'IMPORTS TOTAL QTY: " . $row['total_qty'] . "','IMPORTS TOTAL AMNT: " . number_format($row['total_amnt'] , 2) . "'";
//             }
//         }


//         $local_query = "
//             SELECT 
//                 COUNT(s.unique_barcode) AS total_qty, 
//                 SUM(s.capital) AS total_amnt 
//             FROM warehouse w 
//             LEFT JOIN stocks s ON s.warehouse = w.hashed_id 
//             LEFT JOIN supplier sup ON sup.hashed_id = s.supplier 
//             WHERE 
//                 w.hashed_id = '$warehouse_hash' 
//                 AND sup.local_international = 'local' 
//                 AND s.item_status = 0
//         ";

//         $local_res = $conn->query($local_query);

//         if ($local_res->num_rows > 0) {
//             while ($row = $local_res->fetch_assoc()) {
//                 echo "<br>'LOCALS TOTAL QTY: " . $row['total_qty'] . "','LOCALS TOTAL AMNT: ". number_format($row['total_amnt'] , 2) . "'";
//             }
//         }

        

//         // echo "<br><br>DETAILED LOCALS<br>";

//         // $detaied_local_query = "
//         //     SELECT 
//         //         p.description, 
//         //         b.brand_name, 
//         //         c.category_name, 
//         //         COUNT(s.unique_barcode) AS qty_per_product, 
//         //         SUM(s.capital) AS sum_per_product, 
//         //         sup.local_international, 
//         //         w.warehouse_name 
//         //     FROM product p 
//         //     LEFT JOIN brand b ON b.hashed_id = p.brand 
//         //     LEFT JOIN category c ON c.hashed_id = p.category 
//         //     LEFT JOIN stocks s ON s.product_id = p.hashed_id 
//         //     LEFT JOIN supplier sup ON sup.hashed_id = s.supplier 
//         //     LEFT JOIN warehouse w ON w.hashed_id = s.warehouse 
//         //     WHERE 
//         //         s.warehouse = '$warehouse_hash' 
//         //         AND sup.local_international = 'local' 
//         //         AND s.item_status = 0
//         // ";

//         // $detailed_local_res = $conn->query($detaied_local_query);

//         // if ($detailed_local_res->num_rows > 0) {
//         //     while ($row = $detailed_local_res->fetch_assoc()) {
//         //         $description      = "'" . $row['description'] . "'";
//         //         $brand_name       = "'" . $row['brand_name'] . "'";
//         //         $category_name    = "'" . $row['category_name'] . "'";
//         //         $qty_per_product  = "'" . $row['qty_per_product'] . "'";
//         //         $sum_per_product  = "'" . number_format($row['sum_per_product'], 2) . "'";
//         //         $supplier_type    = "'" . $row['local_international'] . "'";
//         //         $warehouse_neym   = "'" . $row['warehouse_name'] . "'";

//         //         echo $description . "," 
//         //             . $brand_name . "," 
//         //             . $category_name . "," 
//         //             . $qty_per_product . "," 
//         //             . $sum_per_product . "," 
//         //             . $supplier_type . "," 
//         //             . $warehouse_neym . "<br>";
//         //     }
//         // }

//         // echo "<br><br>DETAILED IMPORTS<br>";
//         // $detaied_international_query = "
//         //     SELECT 
//         //         p.description, 
//         //         b.brand_name, 
//         //         c.category_name, 
//         //         COUNT(s.unique_barcode) AS qty_per_product, 
//         //         SUM(s.capital) AS sum_per_product, 
//         //         sup.local_international, 
//         //         w.warehouse_name 
//         //     FROM product p 
//         //     LEFT JOIN brand b ON b.hashed_id = p.brand 
//         //     LEFT JOIN category c ON c.hashed_id = p.category 
//         //     LEFT JOIN stocks s ON s.product_id = p.hashed_id 
//         //     LEFT JOIN supplier sup ON sup.hashed_id = s.supplier 
//         //     LEFT JOIN warehouse w ON w.hashed_id = s.warehouse 
//         //     WHERE 
//         //         s.warehouse = '$warehouse_hash' 
//         //         AND sup.local_international = 'international' 
//         //         AND s.item_status = 0
//         // ";

//         // $detailed_international_res = $conn->query($detaied_international_query);

//         // if ($detailed_international_res->num_rows > 0) {
//         //     while ($row = $detailed_international_res->fetch_assoc()) {
//         //         $description      = "'" . $row['description'] . "'";
//         //         $brand_name       = "'" . $row['brand_name'] . "'";
//         //         $category_name    = "'" . $row['category_name'] . "'";
//         //         $qty_per_product  = "'" . $row['qty_per_product'] . "'";
//         //         $sum_per_product  = "'" . number_format($row['sum_per_product'], 2) . "'";
//         //         $supplier_type    = "'" . $row['local_international'] . "'";
//         //         $warehouse_neym   = "'" . $row['warehouse_name'] . "'";

//         //         echo $description . "," 
//         //             . $brand_name . "," 
//         //             . $category_name . "," 
//         //             . $qty_per_product . "," 
//         //             . $sum_per_product . "," 
//         //             . $supplier_type . "," 
//         //             . $warehouse_neym . "<br>";
//         //     }
//         // }
//     }
// }

//===========================================================================
//===========================================================================















// require_once "../config/database.php";
// require_once "../config/on_session.php";

// // Set headers so browser downloads file as CSV
// header('Content-Type: text/csv; charset=utf-8');
// header('Content-Disposition: attachment; filename=Inventory Per Supplier - Locals.csv');

// // Open output stream
// $output = fopen('php://output', 'w');

// // Write the column headers
// fputcsv($output, ['#', 'LOCALS', 'TOTAL QTY', 'TOTAL AMOUNT']);

// $query = "SELECT 
//             c.category_name, 
//             COUNT(s.unique_barcode) AS available_qty, 
//             SUM(s.capital) as total_amount 
//         FROM category c 
//         LEFT JOIN product p ON p.category = c.hashed_id 
//         LEFT JOIN stocks s ON s.product_id = p.hashed_id 
//         LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
//         WHERE item_status = 0
//         AND sup.local_international = 'Local'
//         GROUP BY p.category
//         ORDER BY c.category_name";

// $result = $conn->query($query);
// $counter = 1;

// if($result->num_rows > 0){
//     while($row = $result->fetch_assoc()){
//         $category = $row['category_name'];
//         $available_qty = $row['available_qty'];
//         $total_amount = number_format($row['total_amount'], 2);

//         // Write row to CSV
//         fputcsv($output, [$counter, $category, $available_qty, $total_amount]);
//         $counter++;
//     }
// }

// fclose($output);
// exit;
