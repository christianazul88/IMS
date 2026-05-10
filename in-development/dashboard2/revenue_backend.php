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

if (isset($_POST['rev_dateGross'])) {
    $date_between = $_POST['rev_dateGross']; // example: 01/04/25 to 30/04/25
    list($start_date, $end_date) = explode(' to ', $date_between);

    // Convert to MySQL format Y-m-d
    $start_date_mysql = DateTime::createFromFormat('d/m/y', $start_date)->format('Y-m-d');
    $end_date_mysql = DateTime::createFromFormat('d/m/y', $end_date)->format('Y-m-d');

    // Format for display (e.g., Jan 1, 2025)
    $start_date_display = DateTime::createFromFormat('d/m/y', $start_date)->format('M j, Y');
    $end_date_display = DateTime::createFromFormat('d/m/y', $end_date)->format('M j, Y');
    $date_label = "$start_date_display to $end_date_display";

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
        WHERE ol.warehouse = '$warehouse_dashboard_id' AND oc.status IN (0, 6) AND DATE(ol.date_sent) BETWEEN '$start_date_mysql' AND '$end_date_mysql'
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
        WHERE ol.warehouse IN ($warehouse_dashboard_id) AND oc.status IN (0, 6) AND DATE(ol.date_sent) BETWEEN '$start_date_mysql' AND '$end_date_mysql'
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
    $today_display = date('M j, Y');
    // $date_label = $first_day_ofmonth . " to " .$today_display;

    $today_formatted = date('M j, Y'); // example: "Apr 27, 2025"
    $date_label = $first_day_ofmonth . " to " .$today_display;


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
        WHERE ol.warehouse = '$warehouse_dashboard_id' AND oc.status IN (0, 6) AND DATE(ol.date_sent) BETWEEN '$start_date' AND '$today_mysql'
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
        WHERE ol.warehouse IN ($warehouse_dashboard_id) AND oc.status IN (0, 6) AND DATE(ol.date_sent) BETWEEN '$start_date' AND '$today_mysql'
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

// Final response array
$revenue_data = [
    'date_selected' => $date_label,
    'total_sales' => (float)$total_outbound_sales,
    'good_sold' => (float)$total_good_sold,
    'net_income' => (int)$total_net_income
];

// Output JSON
json_response($revenue_data);
?>
