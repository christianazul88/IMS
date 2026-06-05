<?php
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '4G');
ini_set('display_errors', 1);

include "database.php";
include "on_session.php";

// Helper function to check if warehouse is selected
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
        $api_warehouse_name = $row['warehouse_name'];
        $api_warehouse_id = $row['hashed_id'];
    }
}

$category_query = "SELECT category_name, hashed_id FROM category ORDER BY category_name ASC";
$category_res = $conn->query($category_query);

$rows = [];
$grand_total_capital = 0;
$grand_total_qty = 0;

if ($category_res && $category_res->num_rows > 0) {
    while ($row = $category_res->fetch_assoc()) {
        $c_category_name = $row['category_name'];
        $c_category_id   = $row['hashed_id'];
        $category_capital = 0;
        $category_qty = 0;

        $stmt = $conn->prepare("SELECT hashed_id FROM product WHERE category = ?");
        $stmt->bind_param("s", $c_category_id);
        $stmt->execute();
        $product_result = $stmt->get_result();

        $product_ids = [];
        while ($product = $product_result->fetch_assoc()) {
            $product_ids[] = $product['hashed_id'];
        }

        foreach ($product_ids as $product_id) {
            if (isWarehouseSelected($selected_wh, $api_warehouse_id)) {
                $stock_stmt = $conn->prepare("
                    SELECT capital 
                    FROM stocks 
                    WHERE item_status = 0 
                        AND product_id = ? 
                        AND warehouse = ?
                ");
                $stock_stmt->bind_param("ss", $product_id, $api_warehouse_id);
            } else {
                $stock_stmt = $conn->prepare("
                    SELECT capital 
                    FROM stocks 
                    WHERE item_status = 0 
                        AND product_id = ? 
                        AND warehouse IN ($imploded_warehouse_ids)
                ");
                $stock_stmt->bind_param("s", $product_id);
            }

            $stock_stmt->execute();
            $stock_result = $stock_stmt->get_result();

            while ($stock_row = $stock_result->fetch_assoc()) {
                $total_capital = floatval($stock_row['capital']);
                $category_capital += $total_capital;
                $category_qty += 1;
                $grand_total_capital += $total_capital;
                $grand_total_qty += 1;
            }
        }

        // Skip category if quantity is 0
        if ($category_qty === 0) {
            continue;
        }

        $rows[] = [$c_category_name, '', '', '', number_format($category_capital, 2), number_format($category_qty), ''];

        $supplier_query = "
            SELECT 
                s.hashed_id AS supplier_id,
                s.supplier_name, 
                SUM(st.capital) AS total_supplier_capital,
                COUNT(*) AS supplier_qty
            FROM stocks st
            JOIN product p ON p.hashed_id = st.product_id
            JOIN supplier s ON s.hashed_id = st.supplier
            WHERE p.category = ? 
                AND st.item_status = 0";

        $supplier_query .= isWarehouseSelected($selected_wh, $api_warehouse_id)
            ? " AND st.warehouse = ?"
            : " AND st.warehouse IN ($imploded_warehouse_ids)";
        $supplier_query .= " GROUP BY st.supplier";

        $supplier_stmt = $conn->prepare($supplier_query);
        isWarehouseSelected($selected_wh, $api_warehouse_id)
            ? $supplier_stmt->bind_param("ss", $c_category_id, $api_warehouse_id)
            : $supplier_stmt->bind_param("s", $c_category_id);

        $supplier_stmt->execute();
        $supplier_result = $supplier_stmt->get_result();

        while ($supplier = $supplier_result->fetch_assoc()) {
            // Skip supplier if quantity is 0
            if (intval($supplier['supplier_qty']) === 0) {
                continue;
            }

            $rows[] = ['', $supplier['supplier_name'], '', '', number_format($supplier['total_supplier_capital'], 2), number_format($supplier['supplier_qty']), ''];

            $product_details_query = "
                SELECT 
                    p.hashed_id AS product_id,
                    p.description,
                    p.parent_barcode,
                    b.brand_name,
                    SUM(st.capital) AS total_capital,
                    COUNT(*) AS product_qty,
                    il.location_name,
                    w.warehouse_name
                FROM stocks st
                JOIN product p ON p.hashed_id = st.product_id
                JOIN brand b ON b.hashed_id = p.brand
                LEFT JOIN item_location il ON il.id = st.item_location
                LEFT JOIN warehouse w ON w.hashed_id = st.warehouse
                WHERE st.supplier = ?
                    AND p.category = ?
                    AND st.item_status = 0";

            $product_details_query .= isWarehouseSelected($selected_wh, $api_warehouse_id)
                ? " AND st.warehouse = ?"
                : " AND st.warehouse IN ($imploded_warehouse_ids)";
            $product_details_query .= " GROUP BY p.hashed_id, b.hashed_id, st.item_location, st.warehouse";

            $product_details_stmt = $conn->prepare($product_details_query);
            isWarehouseSelected($selected_wh, $api_warehouse_id)
                ? $product_details_stmt->bind_param("sss", $supplier['supplier_id'], $c_category_id, $api_warehouse_id)
                : $product_details_stmt->bind_param("ss", $supplier['supplier_id'], $c_category_id);

            $product_details_stmt->execute();
            $product_details_result = $product_details_stmt->get_result();

            while ($product = $product_details_result->fetch_assoc()) {
                // Skip item if quantity is 0
                if (intval($product['product_qty']) === 0) {
                    continue;
                }

                $rows[] = [
                    '', '', 
                    $product['description'] . ' ' . $product['brand_name'], 
                    '', 
                    number_format($product['total_capital'], 2), 
                    number_format($product['product_qty']),
                    !empty($product['location_name']) ? $product['location_name'] . " - " . $product['warehouse_name'] : 'For SKU - ' . $product['warehouse_name'] 
                ];

                                // Final detail: per-sequence line (individual item line)
                $sequences_query = "
                    SELECT 
                        s.capital, 
                        s.unique_barcode, 
                        il.location_name, 
                        w.warehouse_name 
                    FROM stocks s 
                    LEFT JOIN item_location il ON il.id = s.item_location 
                    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse 
                    WHERE s.supplier = ? 
                        AND s.product_id = ? 
                        AND w.warehouse_name = ?
                        AND s.item_status = 0";

                $sequences_stmt = $conn->prepare($sequences_query);
                $sequences_stmt->bind_param("sss", $supplier['supplier_id'], $product['product_id'], $product['warehouse_name']);
                $sequences_stmt->execute();
                $sequences_result = $sequences_stmt->get_result();

                while ($sequence = $sequences_result->fetch_assoc()) {
                    $rows[] = [
                        '', '', '',
                        $sequence['unique_barcode'], 
                        number_format($sequence['capital'], 2), 
                        1,
                        !empty($sequence['location_name']) 
                            ? $sequence['location_name'] . " - " . $sequence['warehouse_name'] 
                            : 'For SKU - ' . $sequence['warehouse_name']
                    ];
                }
            }
        }
    }
}

// Grand total row
$rows[] = ['Grand Total', '', '', '', number_format($grand_total_capital, 2), number_format($grand_total_qty), ''];

// Output as CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="inventory_report_' . date('Y_m') . '.csv"');
$output = fopen('php://output', 'w');

// CSV headers
fputcsv($output, ['Category', 'Supplier', 'Item', 'Parent Barcode', 'Total Unit Cost', 'Quantity', 'Item Location']);

foreach ($rows as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
