<?php
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '4G');
ini_set('display_errors', 1);

include "database.php";
include "on_session.php";

// Helper function
function isWarehouseSelected($selected_wh, $api_warehouse_id) {
    return isset($selected_wh, $api_warehouse_id) && !empty($selected_wh) && !empty($api_warehouse_id);
}

$selected_wh = $_GET['select_warehouse'] ?? null;
$user_warehouse_ids = $user_warehouse_ids ?? [];

$quoted_warehouse_ids = array_map(function ($id) use ($conn) {
    return "'" . $conn->real_escape_string(trim($id)) . "'";
}, $user_warehouse_ids);
$imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);

$api_warehouse_id = null;
if (!empty($selected_wh)) {
    $stmt = $conn->prepare("SELECT warehouse_name, hashed_id FROM warehouse WHERE hashed_id = ? LIMIT 1");
    $stmt->bind_param("s", $selected_wh);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $api_warehouse_id = $row['hashed_id'];
    }
}

$additional_query = $api_warehouse_id === null
    ? "AND s.warehouse IN ($imploded_warehouse_ids)"
    : "AND s.warehouse = '$api_warehouse_id'";

$query = "SELECT 
            c.category_name, 
            sup.supplier_name, 
            b.brand_name, 
            p.description, 
            s.unique_barcode, 
            s.capital, 
            il.location_name, 
            w.warehouse_name
          FROM stocks s 
          LEFT JOIN product p ON p.hashed_id = s.product_id
          LEFT JOIN brand b ON b.hashed_id = p.brand
          LEFT JOIN category c ON c.hashed_id = p.category
          LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
          LEFT JOIN item_location il ON il.id = s.item_location
          LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
          WHERE s.item_status = 0
          $additional_query
          ORDER BY c.category_name, sup.supplier_name, b.brand_name, p.description";

$result = $conn->query($query);

// Setup CSV output
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="inventory_report_' . date('Y_m_d_Hi') . '.csv"');

$output = fopen('php://output', 'w');

// Output headers
fputcsv($output, ['Category', 'Supplier', 'Brand', 'Description', 'Unique Barcode', 'Capital', 'Quantity', 'Location', 'Warehouse']);

$sub_qty = 0;
$subtotal = 0;
$total = 0;
$total_qty = 0;

$prev_category = null;
$prev_supplier = null;

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    while (true) {
        $next_row = $result->fetch_assoc(); // lookahead
        $category_name  = $row['category_name'];
        $supplier_name  = $row['supplier_name'];
        $brand_name     = $row['brand_name'];
        $description    = $row['description'];
        $unique_barcode = $row['unique_barcode'];
        $capital        = $row['capital'];
        $location_name  = $row['location_name'] ?? 'For SKU';
        $warehouse_name = $row['warehouse_name'];

        if ($prev_category !== null && $prev_supplier !== null &&
            ($prev_category !== $category_name || $prev_supplier !== $supplier_name)) {
            // Output subtotal row
            fputcsv($output, [
                "TOTAL FOR $prev_category WHICH SUPPLIER IS $prev_supplier",
                '', '', '', '',
                number_format($subtotal, 2),
                $sub_qty, '', ''
            ]);
            $subtotal = 0;
            $sub_qty = 0;
        }

        // Output current row
        fputcsv($output, [
            $category_name,
            $supplier_name,
            $brand_name,
            $description,
            $unique_barcode,
            number_format($capital, 2),
            1,
            $location_name,
            $warehouse_name
        ]);

        $subtotal += $capital;
        $sub_qty++;
        $total += $capital;
        $total_qty++;

        $prev_category = $category_name;
        $prev_supplier = $supplier_name;

        if (!$next_row) {
            // Output final subtotal
            fputcsv($output, [
                "TOTAL FOR $category_name WHICH SUPPLIER IS $supplier_name",
                '', '', '', '',
                number_format($subtotal, 2),
                $sub_qty, '', ''
            ]);
            break;
        }

        $row = $next_row; // advance
    }

    // Output grand total
    fputcsv($output, [
        "Grand Total", '', '', '', '', number_format($total, 2), $total_qty, '', ''
    ]);
}

fclose($output);
$conn->close();
exit;
?>
