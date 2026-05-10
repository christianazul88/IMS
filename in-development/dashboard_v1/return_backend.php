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

$return_data = [];

if (isset($_POST['module_date_range'])) {
    $date_between = $_POST['module_date_range']; // example: 01/04/25 to 30/04/25
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
        
        $return_query = "
        SELECT
            COUNT(CASE WHEN r.fault_type = 'DEFECTIVE' THEN 1 END) AS total_defective,
            COUNT(CASE WHEN r.fault_type = 'DELIVERY FAILED' THEN 1 END) as total_delivery_failed,
            COUNT(CASE WHEN r.fault = 'SELLER FAULT' THEN 1 END) AS total_seller_fault,
            COUNT(CASE WHEN r.fault = 'CLIENT FAULT' THEN 1 END) AS total_client_fault,
            COUNT(CASE WHEN r.supplier_type = 'Local' AND r.fault = 'DEFECTIVE' THEN 1 END) AS total_local,
            COUNT(CASE WHEN r.supplier_type = 'International' AND r.fault = 'DEFECTIVE' THEN 1 END) AS total_import
        FROM returns r
        LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
        WHERE s.warehouse = '$warehouse_dashboard_id'
        AND DATE(r.date) BETWEEN '$start_date_mysql' AND '$end_date_mysql'
        ";

        $return_type_query = "
        SELECT 
            COUNT(r.unique_barcode) AS total_local
        FROM returns r 
        LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
        WHERE s.warehouse = '$warehouse_dashboard_id'
        AND DATE(r.date) BETWEEN '$start_date_mysql' AND '$end_date_mysql'
        AND r.supplier_type = 'Local'
        ";

        $return_type_query2 = "
        SELECT 
            COUNT(r.unique_barcode) AS total_import
        FROM returns r 
        LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
        WHERE s.warehouse = '$warehouse_dashboard_id'
        AND DATE(r.date) BETWEEN '$start_date_mysql' AND '$end_date_mysql'
        AND r.supplier_type = 'International'
        ";
    } else {
        // Convert into quoted format
        $warehouse_list = explode(',', $_SESSION['warehouse_ids']);
        // Sanitize each warehouse ID string to avoid SQL injection
        $warehouse_list = array_map(function($warehouse) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $warehouse) . "'";
        }, $warehouse_list);
        
        $warehouse_dashboard_id = implode(",", $warehouse_list); // sample: 'warehouse1','warehouse2','warehouse3'
    
        $return_query = "
        SELECT
            COUNT(CASE WHEN r.fault_type = 'DEFECTIVE' THEN 1 END) AS total_defective,
            COUNT(CASE WHEN r.fault_type = 'DELIVERY FAILED' THEN 1 END) as total_delivery_failed,
            COUNT(CASE WHEN r.fault = 'SELLER FAULT' THEN 1 END) AS total_seller_fault,
            COUNT(CASE WHEN r.fault = 'CLIENT FAULT' THEN 1 END) AS total_client_fault, 
            COUNT(CASE WHEN r.supplier_type = 'Local' AND r.fault = 'DEFECTIVE' THEN 1 END) AS total_local,
            COUNT(CASE WHEN r.supplier_type = 'International' AND r.fault = 'DEFECTIVE' THEN 1 END) AS total_import
        FROM returns r
        LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
        WHERE s.warehouse IN ($warehouse_dashboard_id)
        AND DATE(r.date) BETWEEN '$start_date_mysql' AND '$end_date_mysql'
        ";

        $return_type_query = "
        SELECT 
            COUNT(r.unique_barcode) AS total_local
        FROM returns r 
        LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
        WHERE s.warehouse IN ($warehouse_dashboard_id)
        AND DATE(r.date) BETWEEN '$start_date_mysql' AND '$end_date_mysql'
        AND r.supplier_type = 'Local'
        ";

        $return_type_query2 = "
        SELECT 
            COUNT(r.unique_barcode) AS total_import
        FROM returns r 
        LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
        WHERE s.warehouse IN ($warehouse_dashboard_id)
        AND DATE(r.date) BETWEEN '$start_date_mysql' AND '$end_date_mysql'
        AND r.supplier_type = 'International'
        ";
    }

    $return_result = mysqli_query($conn, $return_query);

    if ($return_result && mysqli_num_rows($return_result) > 0) {
        $row = mysqli_fetch_assoc($return_result);
        $total_defective = $row['total_defective'];
        $total_delivery_failed = $row['total_delivery_failed'];
        $total_seller_fault = $row['total_seller_fault'];
        $total_client_fault = $row['total_client_fault'];
        // $total_local = $row['total_local'];
        // $total_import = $row['total_import'];
    } else {
        $total_defective = 0;
        $total_delivery_failed = 0;
        $total_seller_fault = 0;
        $total_client_fault = 0;
        // $total_local = 0;
        // $total_import = 0;
    }
    $return_type_result = mysqli_query($conn, $return_type_query);

    if($return_type_result && mysqli_num_rows($return_type_result) > 0 ){
        $row = mysqli_fetch_assoc($return_type_result);
        $total_local = $row['total_local'];
    } else {
        $total_local = 0;
    }

    $return_type_result2 = mysqli_query($conn, $return_type_query2);

    if($return_type_result2 && mysqli_num_rows($return_type_result2) > 0 ){
        $row = mysqli_fetch_assoc($return_type_result2);
        $total_import = $row['total_import'];
    } else {
        $total_import = 0;
    }
} else {
    // Today's date
    $today_mysql = date('Y-m-d');
    $today_display = date('M j, Y');
    $date_label = $today_display;

    if (!empty($_GET['wh'])) {
        $warehouse_dashboard_id = $_GET['wh']; // sample: warehouse1
        $warehouse_dashboard_id = mysqli_real_escape_string($conn, $warehouse_dashboard_id); // Sanitize the input
        
        $return_query = "
        SELECT
            COUNT(CASE WHEN r.fault_type = 'DEFECTIVE' THEN 1 END) AS total_defective,
            COUNT(CASE WHEN r.fault_type = 'DELIVERY FAILED' THEN 1 END) as total_delivery_failed,
            COUNT(CASE WHEN r.fault = 'SELLER FAULT' THEN 1 END) AS total_seller_fault,
            COUNT(CASE WHEN r.fault = 'CLIENT FAULT' THEN 1 END) AS total_client_fault,
            COUNT(CASE WHEN r.supplier_type = 'Local' AND r.fault = 'DEFECTIVE' THEN 1 END) AS total_local,
            COUNT(CASE WHEN r.supplier_type = 'International' AND r.fault = 'DEFECTIVE' THEN 1 END) AS total_import
        FROM returns r
        LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
        WHERE s.warehouse = '$warehouse_dashboard_id'
        AND DATE(r.date) = '$today_mysql'
        ";

        $return_type_query = "
        SELECT 
            COUNT(r.unique_barcode) AS total_local
        FROM returns r 
        LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
        WHERE s.warehouse = '$warehouse_dashboard_id'
        AND DATE(r.date) = '$today_mysql'
        AND r.supplier_type = 'Local'
        ";

        $return_type_query2 = "
        SELECT 
            COUNT(r.unique_barcode) AS total_import
        FROM returns r 
        LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
        WHERE s.warehouse = '$warehouse_dashboard_id'
        AND DATE(r.date) = '$today_mysql'
        AND r.supplier_type = 'International'
        ";
    } else {
        // Convert into quoted format
        $warehouse_list = explode(',', $_SESSION['warehouse_ids']);
        // Sanitize each warehouse ID string to avoid SQL injection
        $warehouse_list = array_map(function($warehouse) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $warehouse) . "'";
        }, $warehouse_list);
        
        $warehouse_dashboard_id = implode(",", $warehouse_list); // sample: 'warehouse1','warehouse2','warehouse3'
    
        $return_query = "
        SELECT
            COUNT(CASE WHEN r.fault_type = 'DEFECTIVE' THEN 1 END) AS total_defective,
            COUNT(CASE WHEN r.fault_type = 'DELIVERY FAILED' THEN 1 END) as total_delivery_failed,
            COUNT(CASE WHEN r.fault = 'SELLER FAULT' THEN 1 END) AS total_seller_fault,
            COUNT(CASE WHEN r.fault = 'CLIENT FAULT' THEN 1 END) AS total_client_fault,
            COUNT(CASE WHEN r.supplier_type = 'Local' AND r.fault = 'DEFECTIVE' THEN 1 END) AS total_local,
            COUNT(CASE WHEN r.supplier_type = 'International' AND r.fault = 'DEFECTIVE' THEN 1 END) AS total_import
        FROM returns r
        LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
        WHERE s.warehouse IN ($warehouse_dashboard_id)
        AND DATE(r.date) = '$today_mysql'
        ";

        $return_type_query = "
        SELECT 
            COUNT(r.unique_barcode) AS total_local
        FROM returns r 
        LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
        WHERE s.warehouse IN ($warehouse_dashboard_id)
        AND DATE(r.date) = '$today_mysql'
        AND r.supplier_type = 'Local'
        ";

        $return_type_query2 = "
        SELECT 
            COUNT(r.unique_barcode) AS total_import
        FROM returns r 
        LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode
        WHERE s.warehouse IN ($warehouse_dashboard_id)
        AND DATE(r.date) = '$today_mysql'
        AND r.supplier_type = 'International'
        ";
    }

    $return_result = mysqli_query($conn, $return_query);

    if ($return_result && mysqli_num_rows($return_result) > 0) {
        $row = mysqli_fetch_assoc($return_result);
        $total_defective = $row['total_defective'];
        $total_delivery_failed = $row['total_delivery_failed'];
        $total_seller_fault = $row['total_seller_fault'];
        $total_client_fault = $row['total_client_fault'];
        // $total_local = $row['total_local'];
        // $total_import = $row['total_import'];
    } else {
        $total_defective = 0;
        $total_delivery_failed = 0;
        $total_seller_fault = 0;
        $total_client_fault = 0;
        // $total_local = 0;
        // $total_import = 0;
    }
    $return_type_result = mysqli_query($conn, $return_type_query);

    if($return_type_result && mysqli_num_rows($return_type_result) > 0 ){
        $row = mysqli_fetch_assoc($return_type_result);
        $total_local = $row['total_local'];
    } else {
        $total_local = 0;
    }

    $return_type_result2 = mysqli_query($conn, $return_type_query2);

    if($return_type_result2 && mysqli_num_rows($return_type_result2) > 0 ){
        $row = mysqli_fetch_assoc($return_type_result2);
        $total_import = $row['total_import'];
    } else {
        $total_import = 0;
    }
}

// Final response array
$return_data = [
    'date_selected' => $date_label,
    'seller_fault' => (int)$total_seller_fault,
    'client_fault' => (int)$total_client_fault,
    'total_delivery_failed' => (int)$total_delivery_failed,
    'local' => (int)$total_local,
    'import' => (int)$total_import,
    'total_defective' => (int)$total_defective
];

// Output JSON
json_response($return_data);
?>
