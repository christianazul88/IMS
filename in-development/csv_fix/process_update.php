<?php
include "../config/database.php";
include "../config/on_session.php";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // These fields are arrays (from inputs named with []), so handle them accordingly
    $outbound_ids     = $_POST['outbound_id'] ?? [];
    $warehouse_names  = $_POST['warehouse_name'] ?? [];
    $ids              = $_POST['id'] ?? [];
    $hashed_ids       = $_POST['hashed_id'] ?? [];
    $customers        = $_POST['customer'] ?? [];
    $order_nos        = $_POST['order_no'] ?? [];
    $order_line_ids   = $_POST['order_line_id'] ?? [];
    $platforms        = $_POST['platform'] ?? [];
    $couriers         = $_POST['courier'] ?? [];
    $barcodes         = $_POST['barcode'] ?? [];
    $sold_amounts     = $_POST['sold_amount'] ?? [];

    // Example loop to process the submitted data
    for ($i = 0; $i < count($outbound_ids); $i++) {
        $outbound_id     = $outbound_ids[$i] ?? '';
        $warehouse_name  = $warehouse_names[$i] ?? '';
        $id              = $ids[$i] ?? '';
        $hashed_id       = $hashed_ids[$i] ?? '';
        $customer        = $customers[$i] ?? '';
        $order_no        = $order_nos[$i] ?? '';
        $order_line_id   = $order_line_ids[$i] ?? '';
        $platform        = $platforms[$i] ?? '';
        $courier         = $couriers[$i] ?? '';
        $barcode         = $barcodes[$i] ?? '';
        $sold_amount     = $sold_amounts[$i] ?? '';

        $get_last_hashed_id = "SELECT hashed_id FROM outbound_logs ORDER BY hashed_id DESC LIMIT 1";
        $result_last = $conn->query($get_last_hashed_id);
        if($result_last->num_rows>0){
            $row=$result_last->fetch_assoc();
            $new_outbound_id = $row['hashed_id'] + 1;
        }
        
        // Example: echo data for debugging
        // echo "Row $i: $outbound_id | $warehouse_name | $id | $hashed_id | $customer | $order_no | $order_line_id | $platform | $courier | $barcode | $sold_amount<br>";
    }
} else {
    echo "Invalid request method.";
}
?>
