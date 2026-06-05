<?php
ini_set('max_execution_time', 3000);

include 'database.php';

$hashed_ids = [
    '0000000000034599','0000000000039539','0000000000046673',
    '0000000009998177','0000000009998178','0000000009998180',
    '0000000009998181','0000000009998183','0000000009998184',
    '0000000009998185','0000000009998186','0000000009998187',
    '0000000009998191','0000000009998192','0000000009998194',
    '0000000009998196','0000000009998197','0000000009998200',
    '0000000009998201','0000000009998203','0000000009998204',
    '0000000009998205','0000000009998206','0000000009998207',
    '0000000009998208','0000000009998209','0000000009998210',
    '0000000009998213','0000000009998214','0000000009998216',
    '0000000009998217','0000000009998218','0000000009998220',
    '0000000009998221','0000000009998222','0000000009998223',
    '0000000009998224','0000000009998225','0000000009998228',
    '0000000009998229','0000000009998230','0000000009998231',
    '0000000009998232','0000000009998235','0000000009998236',
    '0000000009998237','0000000009998238','0000000009998239',
    '0000000009998241','0000000009998242','0000000009998245',
    '0000000009998247','0000000009998248','0000000009998249',
    '0000000009998254','0000000009998256','0000000009998258'
];

$placeholders = implode(',', array_fill(0, count($hashed_ids), '?'));
$sql = "SELECT * FROM outbound_logs WHERE hashed_id IN ($placeholders)";
$stmt = $conn->prepare($sql);

$types = str_repeat('s', count($hashed_ids));
$stmt->bind_param($types, ...$hashed_ids);

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Outbound Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Outbound Logs</h5>
        </div>

        <div class="card-body">
            <?php 
                            echo "'Result','Outbound ID','Order #','Customer','Date Sent','Barcodes','Outbound Date','Action'";
                            echo "<br>";
while ($row = $result->fetch_assoc()) {

    $outbound_id = $row['hashed_id'];
    $customer_fullname = $row['customer_fullname'];
    $outbound_Date = $row['date_sent'];
    $order_number = $row['order_num'];

    $barcode_out_date = '';
    $barcode_action = '';
    $resulta = "";

    $get_barcodes_Sql = "SELECT oc.unique_barcode, st.date, st.action 
                         FROM outbound_content oc 
                         LEFT JOIN stock_timeline st 
                         ON oc.unique_barcode = st.unique_barcode 
                         WHERE st.title = 'Outbound' 
                         AND st.date = '$outbound_Date' 
                         AND oc.hashed_id = '$outbound_id'";

    $get_barcodes_Result = $conn->query($get_barcodes_Sql);

    if ($get_barcodes_Result && $get_barcodes_Result->num_rows > 0) {
        while ($barcode_row = $get_barcodes_Result->fetch_assoc()) {
            $unique_barcode = $barcode_row['unique_barcode'];
            $barcode_out_date = $barcode_row['date'];
            $barcode_action = $barcode_row['action'];

            // Highlight condition
            if(strpos($barcode_action, $customer_fullname) !== false && $outbound_Date === $barcode_out_date) {
                $resulta = "matched";
            } else {
                $resulta = "not-matched";
            }

            echo $resulta;
            echo ",'" . $outbound_id . "'";
            echo ",'" . $order_number . "'";
            echo ",'" . $customer_fullname . "'";
            echo ",'" . $outbound_Date . "'";
            echo ",'" . $unique_barcode . "'";
            echo ",'" . $barcode_out_date . "'";
            echo ",'" . $barcode_action . "'";
            echo "<br>";
        }
    }


    
    
}
?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>