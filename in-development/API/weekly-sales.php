<?php
include "../config/database.php";

// Set header to return JSON
header('Content-Type: application/json');

$sql = "SELECT ol.hashed_id, ol.date_sent, w.warehouse_name 
        FROM outbound_logs ol 
        LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse 
        ORDER BY ol.date_sent DESC";

$res = $conn->query($sql);

if (!$res) {
    die(json_encode(["error" => "SQL Error (outbound_logs): " . $conn->error]));
}

// Initialize weekly sales (outside the loop)
$weekly_sales = [
    "sunday" => 0,
    "monday" => 0,
    "tuesday" => 0,
    "wednesday" => 0,
    "thursday" => 0,
    "friday" => 0,
    "saturday" => 0
];

// Get start and end of current week (Sunday - Saturday)
$start_of_week = strtotime("last sunday midnight"); // Start of the week (00:00:00 Sunday)
$end_of_week = strtotime("next sunday midnight") - 1; // End of the week (23:59:59 Saturday)
$current_year = date("Y"); // Current year

while ($row = $res->fetch_assoc()) {
    $outbound_id = $row['hashed_id'];
    $outbound_out = strtotime($row['date_sent']);

    // If date_sent is before this week or not in the current year, break the loop
    if ($outbound_out < $start_of_week || date("Y", $outbound_out) != $current_year) {
        break;
    }

    $day_name = strtolower(date("l", $outbound_out)); // Get day name (e.g., "monday")

    $query = "SELECT oc.sold_price
              FROM outbound_content oc
              LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
              LEFT JOIN product p ON p.hashed_id = s.product_id
              WHERE oc.hashed_id = '$outbound_id'";

    $result = $conn->query($query);

    if (!$result) {
        die(json_encode(["error" => "SQL Error (outbound_content): " . $conn->error]));
    }

    while ($prod = $result->fetch_assoc()) {
        $weekly_sales[$day_name] += $prod['sold_price'];
    }
}

// Encode as JSON safely
echo json_encode([$weekly_sales], JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE);
?>
