<?php
include "../../config/database.php";

// Initialize variables
$capital_data = [];
$sales_data = [];
$profit_data = [];
$months = [];

// Get the last 7 months' data
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i month"));
    $months[] = $month;

    $capital_this_month = 0.00;
    $sales_this_month = 0.00;

    // Fetch Capital data for this month
    $sql = "SELECT unique_key FROM inbound_logs WHERE DATE_FORMAT(date_received, '%Y-%m') = '$month'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $unique_key = $row['unique_key'];
            $stock_query = "SELECT capital FROM stocks WHERE unique_key = '$unique_key'";
            $stock_result = $conn->query($stock_query);
            if ($stock_result->num_rows > 0) {
                while ($stock_row = $stock_result->fetch_assoc()) {
                    $capital_this_month += $stock_row['capital'];
                }
            }
        }
    }

    // Fetch Sales data for this month
    $sales_query = "SELECT hashed_id FROM outbound_logs WHERE DATE_FORMAT(date_sent, '%Y-%m') = '$month'";
    $sales_result = $conn->query($sales_query);
    if ($sales_result->num_rows > 0) {
        while ($row = $sales_result->fetch_assoc()) {
            $outbound_id = $row['hashed_id'];
            $outbound_query = "SELECT sold_price FROM outbound_content WHERE hashed_id = '$outbound_id'";
            $outbound_result = $conn->query($outbound_query);
            if ($outbound_result->num_rows > 0) {
                while ($outbound_row = $outbound_result->fetch_assoc()) {
                    $sales_this_month += $outbound_row['sold_price'];
                }
            }
        }
    }

    // Calculate Profit for this month
    $profit_this_month = $sales_this_month - $capital_this_month;

    // Store the data for this month
    $capital_data[] = $capital_this_month;
    $sales_data[] = $sales_this_month;
    $profit_data[] = $profit_this_month;
}

// Prepare the data to be sent as JSON
$response = [
    'months' => $months,
    'capital' => $capital_data,
    'sales' => $sales_data,
    'profit' => $profit_data
];

// Send the response as JSON
echo json_encode($response);
?>
