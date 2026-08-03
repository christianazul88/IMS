<?php
include "../config/database.php";
include "../config/on_session.php";

if(!isset($_SESSION['csv_filters'])){
    die("No data to export.");
}

$filters = $_SESSION['csv_filters'];

$query = "
SELECT 
    s.unique_barcode, 
    p.description, 
    b.brand_name, 
    c.category_name, 
    cl.classification_name,
    sup.supplier_name, 
    sup.local_international, 
    s.inbound_id, 
    s.capital, 
    w.warehouse_name, 
    s.date AS date_acquired, 
    s.unique_key, 
    u.user_fname, 
    u.user_lname 
FROM stocks s 
LEFT JOIN product p ON p.hashed_id = s.product_id 
LEFT JOIN brand b ON b.hashed_id = p.brand 
LEFT JOIN category c ON c.hashed_id = p.category 
LEFT JOIN supplier sup ON sup.hashed_id = s.supplier 
LEFT JOIN warehouse w ON w.hashed_id = s.warehouse 
LEFT JOIN users u ON u.hashed_id = s.user_id 
LEFT JOIN classification cl ON cl.hashed_id = c.classification_id
WHERE s.date BETWEEN '{$filters['start']}' AND '{$filters['end']}'
{$filters['warehouse_query']}
{$filters['category_query']}
{$filters['supplier_query']}
";

$res = $conn->query($query);

// HEADERS
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=inbound_transactions '. $date_for_file . '.csv');

$output = fopen('php://output', 'w');

// ✅ CUSTOM COLUMN HEADERS
fputcsv($output, [
    'Barcode',
    'Description',
    'Brand',
    'Classification',
    'Category',
    'Supplier',
    'Supplier Info',
    'Cost of Good Sold',
    'Warehouse',
    'Date Acquired',
    'Inbound ID',
    'Inbounded by'
]);

// ✅ CUSTOM ROW FORMAT
while($row = $res->fetch_assoc()){

    // 👉 Combine name
    $full_name = $row['user_fname'] . ' ' . $row['user_lname'];

    // 👉 Force quotes around unique_key
    $unique_key = '"' . $row['unique_key'] . '"';

    fputcsv($output, [
        $row['unique_barcode'],
        $row['description'],
        $row['brand_name'],
        $row['classification_name'],
        $row['category_name'],
        $row['supplier_name'],
        $row['local_international'],
        $row['capital'],
        $row['warehouse_name'],
        $row['date_acquired'],
        $unique_key,
        $full_name
    ]);
}

fclose($output);
exit;