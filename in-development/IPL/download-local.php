<?php 
require_once "../config/database.php";
require_once "../config/on_session.php";

// Set headers so browser downloads file as CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Inventory Per Location ' . $date_for_file .'.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Write the column headers
fputcsv($output, ['#', 'WAREHOUSE', 'QTY', 'TOTAL AMOUNT', 'ORIGIN']);

// Get list of warehouses
$warehouseQuery = "SELECT hashed_id, warehouse_name FROM warehouse ORDER BY warehouse_name";
$warehouses = $conn->query($warehouseQuery);

$counter = 1;

if($warehouses->num_rows > 0){
    while($w = $warehouses->fetch_assoc()){
        $warehouse_id = $w['hashed_id'];
        $warehouse_name = $w['warehouse_name'];

        // --- 1. Get warehouse totals ---
        $totalQuery = "
            SELECT 
                COUNT(s.unique_barcode) AS total_qty,
                SUM(s.capital) AS total_amount
            FROM stocks s
            WHERE s.warehouse = ? AND s.item_status = 0
        ";
        $stmtTotal = $conn->prepare($totalQuery);
        $stmtTotal->bind_param("s", $warehouse_id);
        $stmtTotal->execute();
        $resultTotal = $stmtTotal->get_result();
        $totalRow = $resultTotal->fetch_assoc();

        $totalQty = $totalRow['total_qty'] ?? 0;
        $totalAmount = number_format($totalRow['total_amount'] ?? 0, 2);

        // Write warehouse total row (Origin = "All")
        fputcsv($output, [$counter, $warehouse_name, $totalQty, $totalAmount, 'All']);
        $counter++;

        // --- 2. Get breakdown by origin ---
        $breakdownQuery = "
            SELECT 
                COALESCE(NULLIF(sup.local_international, ''), 'Unknown') AS origin, 
                COUNT(s.unique_barcode) AS qty,
                SUM(s.capital) AS total_amount
            FROM stocks s
            LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
            WHERE s.warehouse = ? AND s.item_status = 0
            GROUP BY origin
        ";

        $stmt = $conn->prepare($breakdownQuery);
        $stmt->bind_param("s", $warehouse_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while($row = $result->fetch_assoc()){
            $origin = $row['origin'];
            $qty = $row['qty'];
            $amount = number_format($row['total_amount'], 2);

            fputcsv($output, [$counter, $warehouse_name, $qty, $amount, $origin]);
            $counter++;
        }
    }
}

fclose($output);
exit;



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
