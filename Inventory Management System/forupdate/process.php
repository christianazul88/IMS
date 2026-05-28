<?php
include '../config/database.php';
include '../config/on_session.php';

if (!isset($_FILES['csv_file'])) {
    die("No CSV file uploaded.");
}

$file = $_FILES['csv_file']['tmp_name'];

$total_csv_rows = 0;
$total_outbounded_rows = 0;
$updated_outbound_content_rows = 0;
$updated_outbound_logs = 0;
$not_found_rows = 0;

if (($handle = fopen($file, "r")) !== FALSE) {

    // Skip header row
    fgetcsv($handle);

    while (($row = fgetcsv($handle, 10000, ",")) !== FALSE) {

        $total_csv_rows++;

        // Prevent undefined index errors
        if (count($row) < 18) {
            echo "Skipping invalid CSV row.<br>";
            continue;
        }

        // Assign each column to variables
        $record_id       = trim($row[0]);
        $classification  = trim($row[1]);
        $category        = trim($row[2]);
        $order_number    = trim($row[3]);
        $outbound_number = trim($row[4]);
        $customer        = trim($row[5]);
        $outbound_date   = trim($row[6]);
        $supplier        = trim($row[7]);
        $local_import    = trim($row[8]);
        $description     = trim($row[9]);
        $brand           = trim($row[10]);
        $barcode         = trim($row[11]);
        $batch           = trim($row[12]);
        $staff           = trim($row[13]);
        $status          = trim($row[14]);
        $unit_cost       = trim($row[15]);
        $gross_sale      = trim($row[16]);
        $net_income      = trim($row[17]);

        // Only process outbounded rows
        if ($status === "Outbounded") {

            $total_outbounded_rows++;

            // Escape values
            $barcode_escaped = $conn->real_escape_string($barcode);
            $order_number_escaped = $conn->real_escape_string($order_number);
            $customer_escaped = $conn->real_escape_string($customer);
            $outbound_date_escaped = $conn->real_escape_string($outbound_date);
            $staff_escaped = $conn->real_escape_string($staff);
            $gross_sale_escaped = $conn->real_escape_string($gross_sale);

            $outbound_data_query = "
                SELECT 
                    ol.id,
                    ol.order_num, 
                    ol.order_line_id, 
                    ol.customer_fullname AS customer, 
                    ol.date_sent, 
                    s.capital AS unit_cost, 
                    oc.sold_price,
                    u.user_fname, 
                    u.user_lname,
                    sup.supplier_name,
                    s.unique_barcode,
                    ol.hashed_id AS outbound_identifier,
                    oc.id AS outbound_content_id
                FROM outbound_logs ol
                LEFT JOIN outbound_content oc ON ol.hashed_id = oc.hashed_id
                LEFT JOIN stocks s ON oc.unique_barcode = s.unique_barcode
                LEFT JOIN users u ON ol.user_id = u.hashed_id
                LEFT JOIN supplier sup ON s.supplier = sup.hashed_id
                WHERE oc.unique_barcode = '$barcode_escaped'
                LIMIT 1
            ";

            $outbound_data_result = $conn->query($outbound_data_query);

            if ($outbound_data_result && $outbound_data_result->num_rows > 0) {

                $outbound_data = $outbound_data_result->fetch_assoc();

                $outbound_log_id = $outbound_data['id'];
                $sold_price = $outbound_data['sold_price'];
                $outbound_content_id = $outbound_data['outbound_content_id'];

                // Update outbound_logs
                $update_outbound_info = "
                    UPDATE outbound_logs
                    SET 
                        order_num = '$order_number_escaped',
                        order_line_id = '$order_number_escaped',
                        customer_fullname = '$customer_escaped',
                        date_sent = '$outbound_date_escaped',
                        user_id = '$staff_escaped'
                    WHERE id = '$outbound_log_id'
                ";

                if ($conn->query($update_outbound_info) === TRUE) {

                    $updated_outbound_logs++;

                    echo "Outbound log updated successfully for Outbound Number: " . $outbound_number . "<br>";

                    // Update outbound_content only if sold_price changed
                    if ((float)$sold_price != (float)$gross_sale) {

                        $outbound_content_update = "
                            UPDATE outbound_content
                            SET sold_price = '$gross_sale_escaped'
                            WHERE id = '$outbound_content_id'
                        ";

                        if ($conn->query($outbound_content_update) === TRUE) {

                            $updated_outbound_content_rows++;

                            echo "Outbound content updated successfully for Outbound Number: " . $outbound_number . "<br>";

                        } else {

                            echo "Error updating outbound content: " . $conn->error . "<br>";
                        }
                    }

                } else {

                    echo "Error updating outbound log: " . $conn->error . "<br>";
                }

            } else {

                $not_found_rows++;

                echo "No matching outbound record found for barcode: " . $barcode . "<br>";
            }
        }
    }

    fclose($handle);

    echo "<hr>";
    echo "<strong>CSV Processing Summary</strong><br>";
    echo "Total CSV Rows: " . $total_csv_rows . "<br>";
    echo "Total Outbounded Rows: " . $total_outbounded_rows . "<br>";
    echo "Outbound Logs Updated: " . $updated_outbound_logs . "<br>";
    echo "Outbound Content Updated: " . $updated_outbound_content_rows . "<br>";
    echo "Rows Not Found: " . $not_found_rows . "<br>";

} else {

    echo "Unable to open CSV file.";
}
?>