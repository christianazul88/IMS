<?php
header('Content-Type: application/json');

// Include database configuration
include "../config/database.php";

// Initialize variables
$total_outbound_data = [];
$unique_barcode = $_GET['prod'] ?? ''; // Get 'prod' from query string
if (empty($unique_barcode)) {
    echo json_encode(['error' => 'Missing required parameter: prod']);
    exit;
}

// Fetch the product ID based on the unique barcode
$sql = "SELECT product_id FROM stocks WHERE unique_barcode = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $unique_barcode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $product_id = $row['product_id'];

    // Loop through the last 11 months
    for ($i = 0; $i < 11; $i++) {
        $start_date = date("Y-m-01", strtotime("-$i month")); // First day of the month
        $end_date = date("Y-m-t", strtotime("-$i month")); // Last day of the month

        $outbound_query = "
            SELECT COUNT(*) AS total_outbound
            FROM stock_timeline
            WHERE unique_barcode IN (
                SELECT unique_barcode
                FROM stocks
                WHERE product_id = ?
            )
            AND title = 'Outbound'
            AND `date` BETWEEN ? AND ?
        ";

        $stmt_outbound = $conn->prepare($outbound_query);
        $stmt_outbound->bind_param("sss", $product_id, $start_date, $end_date);
        $stmt_outbound->execute();
        $outbound_result = $stmt_outbound->get_result();

        if ($outbound_result->num_rows > 0) {
            $outbound_data = $outbound_result->fetch_assoc();
            $total_outbound = $outbound_data['total_outbound'];
        } else {
            $total_outbound = 0;
        }

        // Add data to the response array
        $month_label = date("F Y", strtotime("-$i month"));
        $total_outbound_data[] = [
            'month' => $month_label,
            'total_outbound' => $total_outbound,
        ];
    }
}

// Output the result as JSON
echo json_encode($total_outbound_data, JSON_PRETTY_PRINT);
