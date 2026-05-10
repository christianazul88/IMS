<?php
include "../config/database.php";

$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$length = isset($_GET['length']) ? (int)$_GET['length'] : 100;
$search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';

$whereClause = "";
if (!empty($search)) {
    $whereClause = "WHERE product.description LIKE '%$search%' 
                    OR stocks.unique_barcode LIKE '%$search%'
                    OR category.category_name LIKE '%$search%'
                    OR brand.brand_name LIKE '%$search%'";
}

// Get total records (for pagination)
$totalQuery = "SELECT COUNT(*) as total FROM stocks 
               LEFT JOIN product ON product.hashed_id = stocks.product_id
               LEFT JOIN category ON category.hashed_id = product.category
               LEFT JOIN brand ON brand.hashed_id = product.brand
               $whereClause";
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];

// Fetch paginated data
$query = "SELECT stocks.*, product.description, category.category_name, brand.brand_name
          FROM stocks
          LEFT JOIN product ON product.hashed_id = stocks.product_id
          LEFT JOIN category ON category.hashed_id = product.category
          LEFT JOIN brand ON brand.hashed_id = product.brand
          $whereClause
          ORDER BY product.id DESC
          LIMIT $start, $length";

$result = $conn->query($query);
$data = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'unique_barcode' => $row['unique_barcode'],
            'warehouse' => $row['warehouse'],
            'description' => $row['description'],
            'brand_name' => $row['brand_name'],
            'category_name' => $row['category_name']
        ];
    }
}

// Return JSON response
$response = [
    "draw" => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecords,
    "data" => $data
];

header('Content-Type: application/json');
echo json_encode($response);
?>
