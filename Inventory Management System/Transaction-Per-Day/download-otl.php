<?php
require_once "../config/database.php";
require_once "../config/on_session.php";

// Validate inputs
$start_date = $_GET['start'] ?? '';
$end_date   = $_GET['end'] ?? '';
$wh_id      = $_GET['user'] ?? '';

if (!$start_date || !$end_date || !$wh_id) {
    die("Invalid parameters.");
}

// Get staff name safely
$stmt = $conn->prepare("SELECT user_fname, user_lname FROM users WHERE hashed_id = ? LIMIT 1");
$stmt->bind_param("s", $wh_id);
$stmt->execute();
$res = $stmt->get_result();

$staff = "Unknown";
if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $staff = $row['user_fname'] . " " . $row['user_lname'];
}

// Headers
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="daily transaction outbound - ' . $staff . '.csv"');

// Output stream
$output = fopen('php://output', 'w');

// BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV headers
fputcsv($output, [
    'WAREHOUSE','ORDER NUMBER','ORDER LINE ID','CUSTOMER','DESCRIPTION',
    'BRAND', 'CLASSIFICATION', 'CATEGORY','OUTBOUND REF #','PLATFORM','SOLD AMOUNT','STAFF','SIGNATURE'
]);

// Main query (prepared)
$query = "SELECT 
            oc.unique_barcode,
            p.description,
            b.brand_name,
            cl.classification_name,
            c.category_name,
            s.outbound_id,
            lp.logistic_name,
            ol.order_num,
            ol.order_line_id,
            ol.customer_fullname,
            oc.sold_price,
            w.warehouse_name,
            u.user_fname,
            u.user_lname
          FROM outbound_content oc
          LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode 
          LEFT JOIN outbound_logs ol 
            ON ol.hashed_id = oc.hashed_id
          LEFT JOIN product p ON p.hashed_id = s.product_id
          LEFT JOIN brand b ON b.hashed_id = p.brand
          LEFT JOIN category c ON c.hashed_id = p.category
          LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
          LEFT JOIN logistic_partner lp ON lp.hashed_id = ol.platform
          LEFT JOIN users u ON u.hashed_id = ol.user_id
          LEFT JOIN classification cl ON cl.hashed_id = c.classification_id
          WHERE ol.date_sent BETWEEN ? AND ?
          AND ol.user_id = ?
          ORDER BY w.warehouse_name";

$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $start_date, $end_date, $wh_id);
$stmt->execute();
$res = $stmt->get_result();

// Output rows
if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $staff_name = $row['user_fname']." ".$row['user_lname'];

        fputcsv($output, [
            $row['warehouse_name'],
            '"' . $row['order_num'] . '"',
            '"' . $row['order_line_id'] . '"',
            $row['customer_fullname'],
            $row['description'],
            $row['brand_name'],
            $row['classification_name'],
            $row['category_name'],
            $row['outbound_id'],
            $row['logistic_name'],
            $row['sold_price'],
            $staff_name,
            ''
        ]);
    }
}

fclose($output);
exit;