<?php
include "../config/database.php";
include "../config/on_session.php";

// Set response header to JSON
header('Content-Type: application/json');
$quoted_warehouse_ids = array_map(function ($id) {
    return "'" . trim($id) . "'";
}, $user_warehouse_ids);
// Create a comma-separated string of quoted IDs
$imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);

// Get the current and previous year
$current_year = date('Y');
$previous_year = $current_year - 1;

// Initialize arrays for sales data
$last_year_sales = [];
$current_year_sales = array_fill(1, (int)date('n'), 0); // Fill from Jan up to current month with 0


// Query to get the outbound sales grouped by year and month
$monthly_outbound_sales_query = "SELECT YEAR(date_sent) AS year, MONTH(date_sent) AS month, SUM(sold_price) AS total_outbound_sale 
                                FROM outbound_logs ol 
                                JOIN outbound_content oc ON ol.hashed_id = oc.hashed_id
                                WHERE YEAR(date_sent) IN ($previous_year, $current_year)
                                AND warehouse IN ($imploded_warehouse_ids)
                                GROUP BY YEAR(date_sent), MONTH(date_sent)
                                ORDER BY YEAR(date_sent) ASC, MONTH(date_sent) ASC";

$monthly_outbound_sales_res = $conn->query($monthly_outbound_sales_query);
if ($monthly_outbound_sales_res->num_rows > 0) {
    while ($row = $monthly_outbound_sales_res->fetch_assoc()) {
        $year = $row['year'];
        $sale = $row['total_outbound_sale'];

        // Store sales in respective arrays
        if ($year == $previous_year) {
            $last_year_sales[] = $sale;
        } else {
            $current_year_sales[(int)$row['month']] = $sale;

        }
    }
    $current_year_sales = array_values($current_year_sales);

}

// Prepare response array
$response = [
    "last_year_sales" => $last_year_sales,
    "current_year_sales" => $current_year_sales
];

// Output JSON response
echo json_encode($response);
?>
