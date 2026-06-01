<?php
include "../config/database.php";

$audit_id = $_GET['audit_id'];

$q = mysqli_query($conn, "
    SELECT p.description, b.brand_name, c.category_name,
           il.location_name, w.warehouse_name, ia.outbounded
    FROM items_to_audit ia
    LEFT JOIN stocks s ON s.unique_barcode = ia.unique_barcode
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN item_location il ON il.hashed_id = s.item_location
    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
    WHERE ia.audit_id = '$audit_id'
    AND ia.audit_status = 'scanned'
    ORDER BY ia.scanned_date DESC
");

while ($r = mysqli_fetch_assoc($q)) {
    echo "<tr>
        <td>{$r['description']}</td>
        <td>{$r['brand_name']}</td>
        <td>{$r['category_name']}</td>
        <td>{$r['location_name']}</td>
        <td>{$r['warehouse_name']}</td>
        <td><span class='badge bg-primary'>{$r['outbounded']}</span></td>
    </tr>";
}