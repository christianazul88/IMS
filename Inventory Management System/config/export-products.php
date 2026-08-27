<?php
// TODO: point this at whatever file sets up $conn in the rest of this app
// (it's already available inside content.php because it's included from a
// parent page — this script is hit directly via the link, so it needs its
// own connection).
require_once '../config/database.php';

$product_list_query = "
  SELECT product.id, product.parent_barcode, product.unique_id,
         product.description, product.date, product.safety,
         users.user_fname, users.user_lname, category.category_name, brand.brand_name
  FROM product
  INNER JOIN users ON users.hashed_id = product.user_id
  INNER JOIN category ON category.hashed_id = product.category
  INNER JOIN brand ON brand.hashed_id = product.brand
  WHERE product.current_status = 0
  ORDER BY product.id DESC
";
$product_list_res = $conn->query($product_list_query);

$filename = 'product-list-all-' . date('Y-m-d-His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// BOM so Excel reads UTF-8 (e.g. special characters in names) correctly
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
  'Description', 'Category', 'Brand', 'Product ID',
  'Parent Barcode', 'Created by', 'Date', 'Safety'
]);

if ($product_list_res && $product_list_res->num_rows > 0) {
  while ($row = $product_list_res->fetch_assoc()) {
    fputcsv($out, [
      $row['description'],
      $row['category_name'],
      $row['brand_name'],
      $row['unique_id'],
      $row['parent_barcode'],
      trim($row['user_fname'] . ' ' . $row['user_lname']),
      $row['date'],
      $row['safety'],
    ]);
  }
}

fclose($out);
exit;
