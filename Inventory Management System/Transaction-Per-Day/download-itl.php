<?php 
require_once "../config/database.php";
require_once "../config/on_session.php";

// Validate inputs
$start_date  = $_GET['start'] ?? '';
$end_date    = $_GET['end'] ?? '';
$selected_id = $_GET['user'] ?? '';

if (!$start_date || !$end_date || !$selected_id) {
    die("Invalid parameters.");
}

// Get staff name safely
$stmt = $conn->prepare("SELECT user_fname, user_lname FROM users WHERE hashed_id = ? LIMIT 1");
$stmt->bind_param("s", $selected_id);
$stmt->execute();
$res = $stmt->get_result();

$staff = "Unknown";
if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $staff = $row['user_fname'] . " " . $row['user_lname'];
}

// CSV headers
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="Inbound Transaction Daily - ' . $staff . '.csv"');

// Open output
$output = fopen('php://output', 'w');

// BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Column headers
fputcsv($output, [
    'WAREHOUSE', 'BARCODE', 'DESCRIPTION', 'BRAND', 'CATEGORY',
    'SUPPLIER', 'INBOUND ID', 'DATE RECEIVED', 'UNIT COST',
    'INBOUNDED BY', 'SIGNATURE'
]);

$grand_total = 0;

// Prepared query
$query = "SELECT 
            il.date_received,
            u.user_fname,
            u.user_lname,
            il.unique_key,
            p.description,
            b.brand_name,
            c.category_name,
            s.capital,
            s.unique_barcode,
            sup.supplier_name,
            w.warehouse_name
          FROM stocks s
          LEFT JOIN inbound_logs il ON il.unique_key = s.unique_key
          LEFT JOIN product p ON p.hashed_id = s.product_id
          LEFT JOIN brand b ON b.hashed_id = p.brand
          LEFT JOIN category c ON c.hashed_id = p.category
          LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
          LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
          LEFT JOIN users u ON u.hashed_id = s.user_id
          WHERE s.user_id = ?
          AND il.date_received BETWEEN ? AND ?
          ORDER BY w.warehouse_name ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $selected_id, $start_date, $end_date);
$stmt->execute();
$res = $stmt->get_result();

// Output rows
if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {

        $inbounded_by = $row['user_fname'] . " " . $row['user_lname'];

        // Sum numeric properly
        $grand_total += (float)$row['capital'];

        fputcsv($output, [
            $row['warehouse_name'],
            '"' . $row['unique_barcode'] . '"',
            $row['description'],
            $row['brand_name'],
            $row['category_name'],
            $row['supplier_name'],
            '"' . $row['unique_key'] . '"',
            $row['date_received'],
            $row['capital'],
            $inbounded_by,
            ''
        ]);
    }

    // Optional total row
    fputcsv($output, [
        "TOTAL","","","","","","","",
        $grand_total,"",""
    ]);
}

// Close
fclose($output);
exit;