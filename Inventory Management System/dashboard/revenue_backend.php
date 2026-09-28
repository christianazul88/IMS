<?php
include "../config/database.php";
include "../config/on_session.php";

function json_response($data = null, $httpStatus = 200) {
    header_remove();
    http_response_code($httpStatus);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}


$revenue_data = [];

if (isset($_POST['rev_dateGross']) && strpos((string) $_POST['rev_dateGross'], ' to ') !== false) {
    $date_between = $_POST['rev_dateGross']; // example: 01/04/25 to 30/04/25
    list($start_date, $end_date) = explode(' to ', $date_between);

    // Convert to MySQL format Y-m-d
    $start_date_mysql = DateTime::createFromFormat('d/m/y', $start_date)->format('Y-m-d');
    $end_date_mysql = DateTime::createFromFormat('d/m/y', $end_date)->format('Y-m-d');

    // Format for display (e.g., Jan 1, 2025)
    $start_date_display = DateTime::createFromFormat('d/m/y', $start_date)->format('M j, Y');
    $end_date_display = DateTime::createFromFormat('d/m/y', $end_date)->format('M j, Y');
    $date_label = "$start_date_display to $end_date_display";
    $trend_anchor_mysql = $end_date_mysql;

    if (!empty($_GET['wh'])) {
        $warehouse_dashboard_id = $_GET['wh']; // sample: warehouse1
        $warehouse_dashboard_id = mysqli_real_escape_string($conn, $warehouse_dashboard_id); // Sanitize the input
        
        // Outbound query
        $outbound_query = "
        SELECT 
            SUM(oc.sold_price) AS total_outbound_sales,
            SUM(s.capital) AS total_good_sold
        FROM outbound_content oc
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id   
        LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
        WHERE ol.warehouse = '$warehouse_dashboard_id' AND oc.status IN (0, 6)
          AND ol.date_sent >= '$start_date_mysql 00:00:00'
          AND ol.date_sent < DATE_ADD('$end_date_mysql', INTERVAL 1 DAY)
        ";
    } else {
        // Convert into quoted format
        $warehouse_list = explode(',', $_SESSION['warehouse_ids']);
        // Sanitize each warehouse ID string to avoid SQL injection
        $warehouse_list = array_map(function($warehouse) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $warehouse) . "'";
        }, $warehouse_list);
        
        $warehouse_dashboard_id = implode(",", $warehouse_list); // sample: 'warehouse1','warehouse2','warehouse3'
    
        // Outbound query
        $outbound_query = "
        SELECT 
            SUM(oc.sold_price) AS total_outbound_sales,
            SUM(s.capital) AS total_good_sold
        FROM outbound_content oc
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id   
        LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
        WHERE ol.warehouse IN ($warehouse_dashboard_id) AND oc.status IN (0, 6)
          AND ol.date_sent >= '$start_date_mysql 00:00:00'
          AND ol.date_sent < DATE_ADD('$end_date_mysql', INTERVAL 1 DAY)
        ";
    }
    

    $outbound_result = mysqli_query($conn, $outbound_query);

    if ($outbound_result && mysqli_num_rows($outbound_result) > 0) {
        $outbound_data = mysqli_fetch_assoc($outbound_result);
        $total_outbound_sales = $outbound_data['total_outbound_sales'] ?? 0;
        $total_good_sold = $outbound_data['total_good_sold'] ?? 0;
        $total_net_income = $total_outbound_sales - $total_good_sold;
    } else {
        $total_outbound_sales = 0;
        $total_good_sold = 0;
        $total_net_income = 0;
    }




} else {
    //first day of the month
    $start_date = date("Y-m-01");
    $first_day_ofmonth = date("M j, Y", strtotime($start_date));
    // Today's date
    $today_mysql = date('Y-m-d');
    $start_date_mysql = $start_date;
    $end_date_mysql = $today_mysql;
    $today_display = date('M j, Y');
    // $date_label = $first_day_ofmonth . " to " .$today_display;

    $today_formatted = date('M j, Y'); // example: "Apr 27, 2025"
    $date_label = $first_day_ofmonth . " to " .$today_display;
    $trend_anchor_mysql = $today_mysql;


    if (!empty($_GET['wh'])) {
        $warehouse_dashboard_id = $_GET['wh']; // sample: warehouse1
        $warehouse_dashboard_id = mysqli_real_escape_string($conn, $warehouse_dashboard_id); // Sanitize the input
        
        // Outbound query
        $outbound_query = "
        SELECT 
            SUM(oc.sold_price) AS total_outbound_sales,
            SUM(s.capital) AS total_good_sold
        FROM outbound_content oc
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id   
        LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
        WHERE ol.warehouse = '$warehouse_dashboard_id' AND oc.status IN (0, 6)
          AND ol.date_sent >= '$start_date 00:00:00'
          AND ol.date_sent < DATE_ADD('$today_mysql', INTERVAL 1 DAY)
        ";
    } else {
        // Convert into quoted format
        $warehouse_list = explode(',', $_SESSION['warehouse_ids']);
        // Sanitize each warehouse ID string to avoid SQL injection
        $warehouse_list = array_map(function($warehouse) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $warehouse) . "'";
        }, $warehouse_list);
        
        $warehouse_dashboard_id = implode(",", $warehouse_list); // sample: 'warehouse1','warehouse2','warehouse3'
    
        // Outbound query
        $outbound_query = "
        SELECT 
            SUM(oc.sold_price) AS total_outbound_sales,
            SUM(s.capital) AS total_good_sold
        FROM outbound_content oc
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id   
        LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
        WHERE ol.warehouse IN ($warehouse_dashboard_id) AND oc.status IN (0, 6)
          AND ol.date_sent >= '$start_date 00:00:00'
          AND ol.date_sent < DATE_ADD('$today_mysql', INTERVAL 1 DAY)
        ";
    }


    $outbound_result = mysqli_query($conn, $outbound_query);

    if ($outbound_result && mysqli_num_rows($outbound_result) > 0) {
        $outbound_data = mysqli_fetch_assoc($outbound_result);
        $total_outbound_sales = $outbound_data['total_outbound_sales'] ?? 0;
        $total_good_sold = $outbound_data['total_good_sold'] ?? 0;
        $total_net_income = $total_outbound_sales - $total_good_sold;
    } else {
        $total_outbound_sales = 0;
        $total_good_sold = 0;
        $total_net_income = 0;
    }
}

// Additional inventory context for the selected period. Inbound cost is the
// capital value of every item received during the date range; it is kept
// separate from COGS, which only includes items that were outbounded/sold.
$warehouse_request = trim((string) ($_GET['wh'] ?? ''));
if ($warehouse_request !== '') {
    $warehouse_request_escaped = mysqli_real_escape_string($conn, $warehouse_request);
    $inbound_warehouse_clause = "il.warehouse = '$warehouse_request_escaped'";
    $stock_warehouse_clause = "s.warehouse = '$warehouse_request_escaped'";
} else {
    $inbound_warehouse_clause = "il.warehouse IN ($warehouse_dashboard_id)";
    $stock_warehouse_clause = "s.warehouse IN ($warehouse_dashboard_id)";
}

$inbound_cost_query = "
    SELECT COALESCE(SUM(s.capital), 0) AS total_inbound_cost
    FROM inbound_logs il
    INNER JOIN stocks s ON s.inbound_id = il.id
    WHERE $inbound_warehouse_clause
      AND il.status = 0
      AND il.date_received >= '$start_date_mysql 00:00:00'
      AND il.date_received < DATE_ADD('$end_date_mysql', INTERVAL 1 DAY)
";
$inbound_cost_result = mysqli_query($conn, $inbound_cost_query);
$total_inbound_cost = 0;
if ($inbound_cost_result) {
    $inbound_cost_data = mysqli_fetch_assoc($inbound_cost_result);
    $total_inbound_cost = (float) ($inbound_cost_data['total_inbound_cost'] ?? 0);
}

$remaining_inventory_query = "
    SELECT COALESCE(SUM(s.capital), 0) AS remaining_inventory_value
    FROM stocks s
    WHERE $stock_warehouse_clause
      AND s.item_status IN (0, 3)
";
$remaining_inventory_result = mysqli_query($conn, $remaining_inventory_query);
$remaining_inventory_value = 0;
if ($remaining_inventory_result) {
    $remaining_inventory_data = mysqli_fetch_assoc($remaining_inventory_result);
    $remaining_inventory_value = (float) ($remaining_inventory_data['remaining_inventory_value'] ?? 0);
}

// Build a compact monthly trend for the revenue chart. It follows the same
// warehouse scope and status rules as the KPI query above, then fills months
// with zeroes so the chart always has a stable six- or twelve-point timeline.
$trend_months = (int) ($_POST['trend_months'] ?? 6);
if (!in_array($trend_months, [3, 6, 12], true)) {
    $trend_months = 6;
}

$trend_anchor = DateTime::createFromFormat('!Y-m-d', $trend_anchor_mysql);
if (!$trend_anchor) {
    $trend_anchor = new DateTime('today');
}

$trend_start = (clone $trend_anchor)->modify('first day of this month')->modify('-' . ($trend_months - 1) . ' months');
$trend_start_mysql = $trend_start->format('Y-m-d');
$trend_end_exclusive = (clone $trend_anchor)->modify('+1 day')->format('Y-m-d');

if (!empty($_GET['wh'])) {
    $trend_warehouse = mysqli_real_escape_string($conn, (string) $_GET['wh']);
    $trend_warehouse_clause = "ol.warehouse = '$trend_warehouse'";
    $trend_inbound_warehouse_clause = "il.warehouse = '$trend_warehouse'";
    $trend_stock_warehouse_clause = "s.warehouse = '$trend_warehouse'";
} else {
    $trend_warehouse_list = explode(',', (string) ($_SESSION['warehouse_ids'] ?? ''));
    $trend_warehouse_list = array_map(function ($warehouse) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, trim($warehouse)) . "'";
    }, array_filter($trend_warehouse_list, static fn($warehouse) => trim($warehouse) !== ''));
    $trend_warehouse_clause = $trend_warehouse_list
        ? 'ol.warehouse IN (' . implode(',', $trend_warehouse_list) . ')'
        : '1 = 0';
    $trend_inbound_warehouse_clause = $trend_warehouse_list
        ? 'il.warehouse IN (' . implode(',', $trend_warehouse_list) . ')'
        : '1 = 0';
    $trend_stock_warehouse_clause = $trend_warehouse_list
        ? 's.warehouse IN (' . implode(',', $trend_warehouse_list) . ')'
        : '1 = 0';
}

$trend_query = "
    SELECT
        YEAR(ol.date_sent) AS trend_year,
        MONTH(ol.date_sent) AS trend_month,
        SUM(oc.sold_price) AS month_sales,
        SUM(COALESCE(s.capital, 0)) AS month_cost
    FROM outbound_logs ol
    INNER JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id
    LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
    WHERE $trend_warehouse_clause
      AND oc.status IN (0, 6)
      AND ol.date_sent >= '$trend_start_mysql 00:00:00'
      AND ol.date_sent < '$trend_end_exclusive 00:00:00'
    GROUP BY YEAR(ol.date_sent), MONTH(ol.date_sent)
    ORDER BY trend_year, trend_month
";

$trend_values = [];
$trend_result = mysqli_query($conn, $trend_query);
if ($trend_result) {
    while ($trend_row = mysqli_fetch_assoc($trend_result)) {
        $trend_key = sprintf('%04d-%02d', (int) $trend_row['trend_year'], (int) $trend_row['trend_month']);
        $trend_values[$trend_key] = [
            'sales' => (float) ($trend_row['month_sales'] ?? 0),
            'cogs' => (float) ($trend_row['month_cost'] ?? 0),
            'profit' => (float) ($trend_row['month_sales'] ?? 0) - (float) ($trend_row['month_cost'] ?? 0),
        ];
    }
}

$inbound_trend_query = "
    SELECT
        YEAR(il.date_received) AS trend_year,
        MONTH(il.date_received) AS trend_month,
        SUM(COALESCE(s.capital, 0)) AS month_inbound_cost
    FROM inbound_logs il
    INNER JOIN stocks s ON s.inbound_id = il.id
    WHERE $trend_inbound_warehouse_clause
      AND il.status = 0
      AND il.date_received >= '$trend_start_mysql 00:00:00'
      AND il.date_received < '$trend_end_exclusive 00:00:00'
    GROUP BY YEAR(il.date_received), MONTH(il.date_received)
    ORDER BY trend_year, trend_month
";

$inbound_trend_values = [];
$inbound_trend_result = mysqli_query($conn, $inbound_trend_query);
if ($inbound_trend_result) {
    while ($inbound_trend_row = mysqli_fetch_assoc($inbound_trend_result)) {
        $trend_key = sprintf('%04d-%02d', (int) $inbound_trend_row['trend_year'], (int) $inbound_trend_row['trend_month']);
        $inbound_trend_values[$trend_key] = (float) ($inbound_trend_row['month_inbound_cost'] ?? 0);
    }
}

// Establish the inventory-value baseline before the first displayed month so
// the remaining-inventory series represents inventory value, not only the
// receipts shown inside the selected chart window.
$baseline_inbound_query = "
    SELECT COALESCE(SUM(s.capital), 0) AS baseline_inbound_cost
    FROM inbound_logs il
    INNER JOIN stocks s ON s.inbound_id = il.id
    WHERE $trend_inbound_warehouse_clause
      AND il.status = 0
      AND il.date_received < '$trend_start_mysql 00:00:00'
";
$baseline_outbound_query = "
    SELECT COALESCE(SUM(s.capital), 0) AS baseline_outbound_cost
    FROM outbound_logs ol
    INNER JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id
    LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
    WHERE $trend_warehouse_clause
      AND oc.status IN (0, 6)
      AND ol.date_sent < '$trend_start_mysql 00:00:00'
";

$baseline_inbound_cost = 0;
$baseline_inbound_result = mysqli_query($conn, $baseline_inbound_query);
if ($baseline_inbound_result) {
    $baseline_inbound_row = mysqli_fetch_assoc($baseline_inbound_result);
    $baseline_inbound_cost = (float) ($baseline_inbound_row['baseline_inbound_cost'] ?? 0);
}

$baseline_outbound_cost = 0;
$baseline_outbound_result = mysqli_query($conn, $baseline_outbound_query);
if ($baseline_outbound_result) {
    $baseline_outbound_row = mysqli_fetch_assoc($baseline_outbound_result);
    $baseline_outbound_cost = (float) ($baseline_outbound_row['baseline_outbound_cost'] ?? 0);
}

$running_inventory_value = $baseline_inbound_cost - $baseline_outbound_cost;

$trend = [];
$trend_cursor = clone $trend_start;
$trend_last_month = (clone $trend_anchor)->modify('first day of this month');
while ($trend_cursor <= $trend_last_month) {
    $trend_key = $trend_cursor->format('Y-m');
    $trend_point = $trend_values[$trend_key] ?? ['sales' => 0, 'cogs' => 0, 'profit' => 0];
    $running_inventory_value += ($inbound_trend_values[$trend_key] ?? 0) - ($trend_point['cogs'] ?? 0);
    $trend[] = [
        'label' => $trend_cursor->format('M'),
        'period' => $trend_cursor->format('M Y'),
        'sales' => $trend_point['sales'],
        'profit' => $trend_point['profit'],
        'inbound_cost' => $inbound_trend_values[$trend_key] ?? 0,
        'remaining_inventory' => max(0, $running_inventory_value),
    ];
    $trend_cursor->modify('+1 month');
}

// Final response array
$revenue_data = [
    'date_selected' => $date_label,
    'total_sales' => (float)$total_outbound_sales,
    'good_sold' => (float)$total_good_sold,
    'net_income' => (float)$total_net_income,
    'inbound_cost' => (float)$total_inbound_cost,
    'remaining_inventory' => (float)$remaining_inventory_value,
    'trend_months' => $trend_months,
    'trend' => $trend
];

// Output JSON
json_response($revenue_data);
?>
