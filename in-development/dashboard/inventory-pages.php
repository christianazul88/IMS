<?php
include "../config/database.php";

$sql = "SELECT COUNT(p.description) AS product_count 
        FROM product p 
        LEFT JOIN stocks s ON s.product_id = p.hashed_id 
        WHERE s.warehouse = 'd4735e3a265e16eee03f59718b9b5d03019c07d8b6c51f90da3a666eec13ab35' 
        GROUP BY p.description";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $pages = ceil($row['product_count'] / 3); // round up
    echo $pages;
}
