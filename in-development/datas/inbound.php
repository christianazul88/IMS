<?php 
include "../config/database.php";

$query = "SELECT p.description, 
                 b.brand_name, 
                 c.category_name, 
                 p.parent_barcode, 
                 s.unique_barcode, 
                 sup.supplier_name,
                 sup.local_international AS supplier_type, 
                 w.warehouse_name, 
                 s.batch_code, 
                 s.capital, 
                 s.date AS received_date, 
                 u.user_fname, 
                 u.user_lname, 
                 s.unique_key,
                 s.item_status
          FROM stocks s
          LEFT JOIN product p ON p.hashed_id = s.product_id
          LEFT JOIN brand b ON b.hashed_id = p.brand
          LEFT JOIN category c ON c.hashed_id = p.category
          LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
          LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
          LEFT JOIN users u ON u.hashed_id = s.user_id
          ORDER BY w.warehouse_name";

$result = $conn->query($query);

// CSV-style header with <br> for browser display
echo "Description,Brand,Category,Parent Barcode,Unique Barcode,Supplier Name,Supplier Type,Warehouse,Batch Code,Capital,Received Date,Staff,Inbound ID,Item Status<br>";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        if($row['item_status'] == 0){
            $status = "available";
        } elseif($row['item_status'] == 1) {
            $status = "Outbounded";
        } elseif($row['item_status'] == 2) {
            $status = "Enroute";
        } elseif($row['item_status'] == 3) {
            $status = "To be Transfered";
        } else {
            $status = "unknown";
        }

        echo $row['description'] . "," .
             $row['brand_name'] . "," .
             $row['category_name'] . "," .
             $row['parent_barcode'] . "," .
             $row['unique_barcode'] . "," .
             $row['supplier_name'] . "," .
             $row['supplier_type'] . "," .
             $row['warehouse_name'] . "," .
             $row['batch_code'] . "," .
             $row['capital'] . "," .
             $row['received_date'] . "," .
             $row['user_fname'] . " " . $row['user_lname'] . "," .
             $row['unique_key'] . "," .
             $status . "<br>";
    }
}
?>
