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

$inbound_outbound_data = [];

if (isset($_POST['date_between'])) {
    $date_between = $_POST['date_between']; // example: 01/04/25 to 30/04/25
    if(strpos($date_between, "to")!==false){
        list($start_date, $end_date) = explode(' to ', $date_between);
        // Convert to MySQL format Y-m-d
        $start_date_mysql = DateTime::createFromFormat('d/m/y', $start_date)->format('Y-m-d') . " 00:00:00";
        $end_date_mysql = DateTime::createFromFormat('d/m/y', $end_date)->format('Y-m-d') . " 11:59:59";

        // Format for display
        $start_date_display = DateTime::createFromFormat('d/m/y', $start_date)->format('M j, Y');
        $end_date_display = DateTime::createFromFormat('d/m/y', $end_date)->format('M j, Y');
        $date_label = "$start_date_display to $end_date_display";

        if (!empty($_GET['wh'])) {
            $warehouse_dashboard_id = mysqli_real_escape_string($conn, $_GET['wh']);
            
            $where_warehouse = " = '$warehouse_dashboard_id'";
        } else {
            $warehouse_list = explode(',', $_SESSION['warehouse_ids']);
            $warehouse_list = array_map(function($warehouse) use ($conn) {
                return "'" . mysqli_real_escape_string($conn, $warehouse) . "'";
            }, $warehouse_list);
            $warehouse_dashboard_id = implode(",", $warehouse_list);

            $where_warehouse = " IN ($warehouse_dashboard_id)";
        }

        $outbound_query = "
            SELECT SUM(oc.sold_price) AS total_outbound_sales, COUNT(oc.unique_barcode) AS outbound_qty
            FROM outbound_content oc
            LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
            WHERE ol.warehouse $where_warehouse AND oc.status IN (0, 6) AND DATE(ol.date_sent) BETWEEN '$start_date_mysql' AND '$end_date_mysql'
        ";

        $inbound_query = "
            SELECT SUM(capital) AS total_unit_cost, COUNT(unique_barcode) AS inbound_qty
            FROM stocks
            WHERE warehouse $where_warehouse AND DATE(date) BETWEEN '$start_date_mysql' AND '$end_date_mysql'
        ";

        $local_query = "
            SELECT SUM(s.capital) AS local_unit_cost, COUNT(s.unique_barcode) AS local_qty
            FROM stocks s
            LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
            WHERE sup.local_international = 'Local' AND s.warehouse $where_warehouse AND DATE(s.date) BETWEEN '$start_date_mysql' AND '$end_date_mysql'
        ";

        $import_query = "
            SELECT SUM(s.capital) AS import_unit_cost, COUNT(s.unique_barcode) AS import_qty
            FROM stocks s
            LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
            WHERE sup.local_international = 'International' AND s.warehouse $where_warehouse AND DATE(s.date) BETWEEN '$start_date_mysql' AND '$end_date_mysql'
        ";
    } else {
        $today_mysql = DateTime::createFromFormat('d/m/y', $date_between)->format('Y-m-d');
        $date_display = DateTime::createFromFormat('d/m/y', $date_between)->format('M j, Y');
        $date_label = $date_display;
        if (!empty($_GET['wh'])) {
            $warehouse_dashboard_id = mysqli_real_escape_string($conn, $_GET['wh']);
            
            $where_warehouse = " = '$warehouse_dashboard_id'";
        } else {
            $warehouse_list = explode(',', $_SESSION['warehouse_ids']);
            $warehouse_list = array_map(function($warehouse) use ($conn) {
                return "'" . mysqli_real_escape_string($conn, $warehouse) . "'";
            }, $warehouse_list);
            $warehouse_dashboard_id = implode(",", $warehouse_list);

            $where_warehouse = " IN ($warehouse_dashboard_id)";
        }

        $outbound_query = "
            SELECT SUM(oc.sold_price) AS total_outbound_sales, COUNT(oc.unique_barcode) AS outbound_qty
            FROM outbound_content oc
            LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
            WHERE ol.warehouse $where_warehouse AND oc.status IN (0, 6) AND DATE(ol.date_sent) = '$today_mysql'
        ";

        $inbound_query = "
            SELECT SUM(capital) AS total_unit_cost, COUNT(unique_barcode) AS inbound_qty
            FROM stocks
            WHERE warehouse $where_warehouse AND DATE(date) = '$today_mysql'
        ";

        $local_query = "
            SELECT SUM(s.capital) AS local_unit_cost, COUNT(s.unique_barcode) AS local_qty
            FROM stocks s
            LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
            WHERE sup.local_international = 'Local' AND s.warehouse $where_warehouse AND DATE(s.date) = '$today_mysql'
        ";

        $import_query = "
            SELECT SUM(s.capital) AS import_unit_cost, COUNT(s.unique_barcode) AS import_qty
            FROM stocks s
            LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
            WHERE sup.local_international = 'International' AND s.warehouse $where_warehouse AND DATE(s.date) = '$today_mysql'
        ";
    }
    
} else {
    // Today's date
    $today_mysql = date('Y-m-d');
    $today_display = date('M j, Y');
    $date_label = $today_display;

    if (!empty($_GET['wh'])) {
        $warehouse_dashboard_id = mysqli_real_escape_string($conn, $_GET['wh']);
        $where_warehouse = " = '$warehouse_dashboard_id'";
    } else {
        $warehouse_list = explode(',', $_SESSION['warehouse_ids']);
        $warehouse_list = array_map(function($warehouse) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $warehouse) . "'";
        }, $warehouse_list);
        $warehouse_dashboard_id = implode(",", $warehouse_list);

        $where_warehouse = " IN ($warehouse_dashboard_id)";
    }

    $outbound_query = "
        SELECT SUM(oc.sold_price) AS total_outbound_sales, COUNT(oc.unique_barcode) AS outbound_qty
        FROM outbound_content oc
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
        WHERE ol.warehouse $where_warehouse AND oc.status IN (0, 6) AND DATE(ol.date_sent) = '$today_mysql'
    ";

    $inbound_query = "
        SELECT SUM(capital) AS total_unit_cost, COUNT(unique_barcode) AS inbound_qty
        FROM stocks
        WHERE warehouse $where_warehouse AND DATE(date) = '$today_mysql'
    ";

    $local_query = "
        SELECT SUM(s.capital) AS local_unit_cost, COUNT(s.unique_barcode) AS local_qty
        FROM stocks s
        LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
        WHERE sup.local_international = 'Local' AND s.warehouse $where_warehouse AND DATE(s.date) = '$today_mysql'
    ";

    $import_query = "
        SELECT SUM(s.capital) AS import_unit_cost, COUNT(s.unique_barcode) AS import_qty
        FROM stocks s
        LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
        WHERE sup.local_international = 'International' AND s.warehouse $where_warehouse AND DATE(s.date) = '$today_mysql'
    ";
}

// Execute Queries
$outbound_result = mysqli_query($conn, $outbound_query);
$inbound_result = mysqli_query($conn, $inbound_query);
$local_result = mysqli_query($conn, $local_query);
$import_result = mysqli_query($conn, $import_query);

// Prepare Data
if ($outbound_result && mysqli_num_rows($outbound_result) > 0) {
    $outbound_data = mysqli_fetch_assoc($outbound_result);
    $total_outbound_sales = $outbound_data['total_outbound_sales'] ?? 0;
    $outbound_qty = $outbound_data['outbound_qty'] ?? 0;
} else {
    $total_outbound_sales = 0;
    $outbound_qty = 0;
}

if ($inbound_result && mysqli_num_rows($inbound_result) > 0) {
    $inbound_data = mysqli_fetch_assoc($inbound_result);
    $total_unit_cost = $inbound_data['total_unit_cost'] ?? 0;
    $inbound_qty = $inbound_data['inbound_qty'] ?? 0;
} else {
    $total_unit_cost = 0;
    $inbound_qty = 0;
}

if ($local_result && mysqli_num_rows($local_result) > 0) {
    $local_data = mysqli_fetch_assoc($local_result);
    $local_qty = $local_data['local_qty'] ?? 0;
    $local_unit_cost = $local_data['local_unit_cost'] ?? 0;
} else {
    $local_qty = 0;
    $local_unit_cost = 0;
}

if ($import_result && mysqli_num_rows($import_result) > 0) {
    $import_data = mysqli_fetch_assoc($import_result);
    $import_qty = $import_data['import_qty'] ?? 0;
    $import_unit_cost = $import_data['import_unit_cost'] ?? 0;
} else {
    $import_qty = 0;
    $import_unit_cost = 0;
}

// Final response
$inbound_outbound_data = [
    'date' => $date_label,
    'outbound_qty' => (int)$outbound_qty,
    'outbound_sales' => (float)$total_outbound_sales,
    'inbound_qty' => (int)$inbound_qty,
    'inbound_cost' => (float)$total_unit_cost,
    'local_inbound_qty' => (int)$local_qty,
    'local_inbound_amount' => (float)$local_unit_cost,
    'import_inbound_qty' => (int)$import_qty,
    'import_inbound_amount' => (float)$import_unit_cost
];

// Output JSON
json_response($inbound_outbound_data);
?>
