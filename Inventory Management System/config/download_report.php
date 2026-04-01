<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '4G');

include "database.php";
include "on_session.php";

/*
|--------------------------------------------------------------------------
| Helper Function
|--------------------------------------------------------------------------
*/
function isWarehouseSelected($selected_wh, $api_warehouse_id) {
    return !empty($selected_wh) && !empty($api_warehouse_id);
}

/*
|--------------------------------------------------------------------------
| Warehouse Selection
|--------------------------------------------------------------------------
*/
$selected_wh = $_GET['select_warehouse'] ?? null;
$user_warehouse_ids = $user_warehouse_ids ?? [];

$quoted_warehouse_ids = array_map(function ($id) use ($conn) {
    return "'" . $conn->real_escape_string(trim($id)) . "'";
}, $user_warehouse_ids);

$imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);

$api_warehouse_id = null;
$filenamedefault = "ALL ACCESSIBLE WAREHOUSE";

if (!empty($selected_wh)) {

    $stmt = $conn->prepare("
        SELECT warehouse_name, hashed_id
        FROM warehouse
        WHERE hashed_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $selected_wh);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $api_warehouse_id = $row['hashed_id'];
        $filenamedefault = $row['warehouse_name'];
    }
}

/*
|--------------------------------------------------------------------------
| Warehouse Filter
|--------------------------------------------------------------------------
*/
$additional_query = ($api_warehouse_id === null)
    ? "AND s.warehouse IN ($imploded_warehouse_ids)"
    : "AND s.warehouse = '$api_warehouse_id'";

/*
|--------------------------------------------------------------------------
| Main Query
|--------------------------------------------------------------------------
*/
$query = "
SELECT
    cl.classification_name,
    c.category_name,
    sup.supplier_name,
    sup.local_international,
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
LEFT JOIN classification cl ON cl.hashed_id = c.classification_id
WHERE s.item_status = 0
$additional_query
ORDER BY
    c.category_name,
    sup.supplier_name,
    b.brand_name,
    p.description
";

$result = $conn->query($query);

/*
|--------------------------------------------------------------------------
| CSV Output
|--------------------------------------------------------------------------
*/
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filenamedefault . ' ' . date('Y m d') . '.csv"');

$output = fopen('php://output', 'w');

/*
|--------------------------------------------------------------------------
| CSV Headers
|--------------------------------------------------------------------------
*/
fputcsv($output, [
    'Classification'.
    'Category',
    'Supplier',
    'Supplier Location',
    'Brand',
    'Description',
    'Unique Barcode',
    'Capital',
    'Quantity',
    'Location',
    'Warehouse'
]);

/*
|--------------------------------------------------------------------------
| Output Rows (NO TOTALS)
|--------------------------------------------------------------------------
*/
if ($result && $result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        fputcsv($output, [
            $row['classification_name'],
            $row['category_name'],
            $row['supplier_name'],
            $row['local_international'],
            $row['brand_name'],
            $row['description'],
            $row['unique_barcode'],
            number_format($row['capital'], 2),
            1,
            $row['location_name'] ?? 'For SKU',
            $row['warehouse_name']
        ]);
    }
}

fclose($output);
$conn->close();
exit;
?>