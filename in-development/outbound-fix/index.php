<?php
ini_set('max_execution_time', 30000);
include "../config/database.php";
include "../config/on_session.php";

$file = 'forfix.csv';
if (($handle = fopen($file, "r")) !== FALSE) {

    $first = true;

    // Skip header row
    fgetcsv($handle);

    echo "<table border='1' cellpadding='5'>
            <tr>
                <th>Result</th>
                <th>Outbound ID</th>
                <th>Order #</th>
                <th>Customer</th>
                <th>Date Sent</th>
                <th>Barcodes</th>
                <th>Outbound Date</th>
                <th>Action</th>
            </tr>";

    while (($data = fgetcsv($handle)) !== FALSE) {

        // Assign each column to a readable variable
        $result         = trim($data[0], "'");
        $outbound_id    = trim($data[1], "'");
        $order_number   = trim($data[2], "'");
        $customer = trim($data[3], " \t\n\r\0\x0B\"'"); 
        $customer = $conn->real_escape_string($customer);
        $date_sent      = trim($data[4], "'");
        $barcodes       = trim($data[5], "'");
        $outbound_date  = trim($data[6], "'");
        $action         = $data[7];

        if($first===true){
            $get_last_id = "SELECT id FROM outbound_logs  ORDER BY id DESC LIMIT 1";
            $res_last_id = $conn->query($get_last_id);
            if($res_last_id->num_rows > 0){
                $row_last_id = $res_last_id->fetch_assoc();
                $last_id = $row_last_id['id'];
                $first = false;
            } else {
                $last_id = 0;
            }
            $new_id = $last_id + 1000000000000000;
        } else {
            $get_last_id = "SELECT hashed_id FROM outbound_logs ORDER BY CAST(hashed_id AS UNSIGNED) DESC LIMIT 1;";
            $res_last_id = $conn->query($get_last_id);
            if($res_last_id->num_rows > 0){
                $row_last_id = $res_last_id->fetch_assoc();
                $last_id = $row_last_id['hashed_id'];
            } else {
                $last_id = 0;
            }
            $new_id = $last_id + 1;
        }
        
        
        //update
        $update_outbound_logs = "UPDATE outbound_logs SET hashed_id='$new_id' WHERE hashed_id='$outbound_id' AND date_sent='$date_sent' AND order_num='$order_number' AND customer_fullname='$customer'";

        if ($conn->query($update_outbound_logs) === TRUE) {
            $update_outbound_content = "UPDATE outbound_content SET hashed_id='$new_id' WHERE hashed_id='$outbound_id' AND unique_barcode = '$barcodes'";
            if ($conn->query($update_outbound_content) === TRUE) {
                $message = "Record updated successfully";
            } else {
                $message = "Error updating outbound content record: " . $conn->error;
            }
        } else {
            $message = "Error updating outbound logs record: " . $conn->error;
        }

        echo "<tr>
                <td>$new_id</td>
                <td>$message</td>
                <td>$order_number</td>
                <td>$customer</td>
                <td>$date_sent</td>
                <td>$barcodes</td>
                <td>$outbound_date</td>
                <td>$action</td>
              </tr>";

        // Example usage:
        // echo $customer;
        // echo $barcodes;
    }

    echo "</table>";

    fclose($handle);
} else {
    echo "Unable to open file.";
}
?>