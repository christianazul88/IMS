<?php

// Get average lead time if not already in session
if (!isset($_SESSION['lead_time_local']) && !isset($_SESSION['lead_time_import'])) {
    $import_query = "
        SELECT IFNULL(AVG(TIMESTAMPDIFF(DAY, po.date_order, po.date_received)), 0) AS avg_import_days
        FROM purchased_order po
        LEFT JOIN supplier s ON s.hashed_id = po.supplier
        WHERE s.local_international = 'International' AND po.date_received IS NOT NULL
    ";
    $local_query = "
        SELECT IFNULL(AVG(TIMESTAMPDIFF(DAY, po.date_order, po.date_received)), 0) AS avg_local_days
        FROM purchased_order po
        LEFT JOIN supplier s ON s.hashed_id = po.supplier
        WHERE s.local_international = 'Local' AND po.date_received IS NOT NULL
    ";

    $lead_times = ['international' => 0, 'local' => 0];
    $res_import = $conn->query($import_query);
    if ($res_import && $res_import->num_rows > 0) {
        $lead_times['international'] = round($res_import->fetch_assoc()['avg_import_days']);
    }
    $res_local = $conn->query($local_query);
    if ($res_local && $res_local->num_rows > 0) {
        $lead_times['local'] = round($res_local->fetch_assoc()['avg_local_days']);
    }

    $_SESSION['lead_time_import'] = $lead_times['international'];
    $_SESSION['lead_time_local'] = $lead_times['local'];
}
?>
<div class="card">
    <div class="card-header bg-primary-subtle">
        <h2>Upload CSV for forecasting</h2>
    </div>
    <div class="card-body">
        

        <form method="post" enctype="multipart/form-data" class="mb-4">
            <div class="row">
                <div class="col-sm-3">
                    <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-primary" type="submit">Upload</button>
                </div>
            </div>
            
        </form>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csv_file"])) {
            $tmpName = $_FILES["csv_file"]["tmp_name"];
            $fileType = mime_content_type($tmpName);
            $lead_time_import = $_SESSION['lead_time_import'];
            $lead_time_local = $_SESSION['lead_time_local'];

            if ($fileType === "text/plain" || $fileType === "text/csv" || pathinfo($_FILES["csv_file"]["name"], PATHINFO_EXTENSION) === "csv") {
                if (($handle = fopen($tmpName, "r")) !== false) {

                    // Display formula explanations
                    echo "
                        <div class='alert alert-info'>
                            <strong>Formulas Used:</strong><br>
                            <ul>
                                <li><b>Expected Needed</b> = Sum of monthly sales for # of months based on lead time (Local or Import)</li>
                                <li><b>Suggested Order</b> = (Expected Needed + Safety Stock) - (Available Stocks + Incoming Stocks)</li>
                                <li>Lead time is based on historical average delivery time of local and imported suppliers.</li>
                                <li><b>Highlighted Rows</b> mean stock is below required + safety level.</li>
                                <li><b><span class='badge bg-danger'>Not Matched</span></b> = Product not found in database (by description & category).</li>
                            </ul>
                        </div>
                    ";

                    echo "<h3>Forecast Table</h3>";
                    echo '<table class="table mb-0 data-table fs-10" data-datatables="data-datatables">';
                    echo "
                        <thead class='table-secondary'>
                            <tr>
                                <th class='fs-11 w-100'>Description</th>
                                <th class='fs-11'>LT(Local)</th>
                                <th class='fs-11'>LT(Import)</th>
                                <th class='fs-11'>Stocks</th>
                                <th class='fs-11'>Safety</th>
                                <th class='fs-11'>Qty Sold (3 Months)</th>
                                <th class='fs-11 w-100'>Forecast Months Used</th>
                                <th class='fs-11'>Expected Needed</th>
                                <th class='fs-11'>Incoming Stocks</th>
                                <th class='fs-11'>Total Stocks</th>
                                <th class='fs-11'>Suggested Order (Local)</th>
                                <th class='fs-11'>Suggested Order (Import)</th>
                            </tr>
                        </thead>


                    ";

                    while (($data = fgetcsv($handle)) !== false) {
                        if (count($data) > 14) continue;

                        $category = strtoupper(trim($data[0])) ?? '';

                        // Updated: Assign each month individually
                        $january      = !empty($data[1])  ? strtoupper($data[1])  : 0;
                        $february     = !empty($data[2])  ? strtoupper($data[2])  : 0;
                        $march        = !empty($data[3])  ? strtoupper($data[3])  : 0;
                        $april        = !empty($data[4])  ? strtoupper($data[4])  : 0;
                        $may          = !empty($data[5])  ? strtoupper($data[5])  : 0;
                        $june         = !empty($data[6])  ? strtoupper($data[6])  : 0;
                        $july         = !empty($data[7])  ? strtoupper($data[7])  : 0;
                        $august       = !empty($data[8])  ? strtoupper($data[8])  : 0;
                        $september    = !empty($data[9])  ? strtoupper($data[9])  : 0;
                        $october      = !empty($data[10]) ? strtoupper($data[10]) : 0;
                        $november     = !empty($data[11]) ? strtoupper($data[11]) : 0;
                        $december     = !empty($data[12]) ? strtoupper($data[12]) : 0;

                        $monthly_sales = [
                            $january, $february, $march, $april, $may, $june,
                            $july, $august, $september, $october, $november, $december
                        ];

                        $grand_total = strtoupper(trim($data[13] ?? ''));

                        if($january === "JANUARY"){

                            if(isset($_SESSION['category_forecast'])){
                                if($category !== $_SESSION['category_forecast']){
                                    $_SESSION['category_forecast'] = $category;
                                }
                            } else {
                                $_SESSION['category_forecast'] = $category;
                            }
                        }


                        // if ($category === "CATEGORY" || $grand_total === "GRAND TOTAL") continue;

                        // if (!isset($_SESSION['category_forecast'])) {
                        //     $_SESSION['category_forecast'] = $category;
                        //     continue;
                        // }

                        $category_name = $_SESSION['category_forecast'];
                        $description = $category;

                        $product_search = "
                            SELECT 
                                p.hashed_id AS product_id
                            FROM product p
                            LEFT JOIN category c ON c.hashed_id = p.category
                            WHERE UPPER(p.description) = UPPER('$description')
                            AND UPPER(c.category_name) = UPPER('$category_name')
                            LIMIT 1
                        ";

                        $search_result = $conn->query($product_search);
                        $product_id = "";
                        $qty_sold_past_3_moths = 0;
                        $matched = false;

                        if ($search_result && $search_result->num_rows > 0) {
                            $row = $search_result->fetch_assoc();
                            $product_id = $row['product_id'];
                            $matched = true;
                        }

                        $outbounds = "
                            SELECT 
                                COUNT(oc.unique_barcode) AS outbounded_qty
                            FROM product p
                            LEFT JOIN category c ON c.hashed_id = p.category
                            LEFT JOIN stocks s ON s.product_id = p.hashed_id
                            LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
                            LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                            WHERE s.product_id = '$product_id'
                            AND ol.date_sent BETWEEN DATE_SUB(NOW(), INTERVAL 3 MONTH) AND NOW()
                            LIMIT 1
                        ";
                        if(!empty($product_id)){
                            $outbounds_resut = $conn->query($outbounds);
                            if($outbounds_resut->num_rows>0){
                                $row=$outbounds_resut->fetch_assoc();
                                $qty_sold_past_3_moths = (int)$row['outbounded_qty'];
                            }
                        }

                        $available_stocks = 0;
                        $safety = 0;
                        $incoming_stocks = 0;

                        if (!empty($product_id)) {
                            $stock_query = "
                                SELECT COUNT(s.unique_barcode) AS qty, p.safety
                                FROM stocks s
                                LEFT JOIN product p ON p.hashed_id = s.product_id
                                WHERE s.product_id = '$product_id' AND s.item_status = 0
                            ";
                            $stock_result = $conn->query($stock_query);
                            if ($stock_result && $stock_result->num_rows > 0) {
                                $row = $stock_result->fetch_assoc();
                                $available_stocks = (int)$row['qty'];
                                $safety = (int)$row['safety'];
                            }

                            $po_query = "
                                SELECT SUM(CASE WHEN po.status NOT IN (0, 4) THEN poc.qty ELSE 0 END) AS incoming_stocks
                                FROM purchased_order_content poc 
                                LEFT JOIN purchased_order po ON po.id = poc.po_id
                                WHERE poc.product_id = '$product_id'
                                GROUP BY poc.product_id
                            ";
                            $po_result = $conn->query($po_query);
                            if ($po_result && $po_result->num_rows > 0) {
                                $row = $po_result->fetch_assoc();
                                $incoming_stocks = (int)$row['incoming_stocks'];
                            }
                        }

                        // Replace the original expected_needed_local/import logic and echo row with this:

                        $date_now = new DateTime();
                        $current_month_index = (int)$date_now->format('n') - 1; // 0-based

                        $lead_time_avg = round(($lead_time_local + $lead_time_import) / 2);
                        $months_needed = ceil($lead_time_avg / 30);

                        $expected_needed = 0;
                        $forecast_months = [];

                        $month_names = [
                            "January", "February", "March", "April", "May", "June",
                            "July", "August", "September", "October", "November", "December"
                        ];

                        for ($i = 0; $i < $months_needed; $i++) {
                            $month_index = ($current_month_index + 1 + $i) % 12; // +1 for next month
                            $month_name = $month_names[$month_index];
                            $month_value = (int)$monthly_sales[$month_index];
                            $expected_needed += $month_value;
                            $forecast_months[] = "$month_name ($month_value)";
                        }

                        $forecast_months_display = implode(' + ', $forecast_months);

                        $total_stocks = $available_stocks + $incoming_stocks;
                        $suggested_local = max(0, ($expected_needed + $safety) - $total_stocks);
                        $suggested_import = max(0, ($expected_needed + $safety) - $total_stocks);

                        $row_class = ($total_stocks < ($expected_needed + $safety)) 
                            ? 'bg-secondary-subtle' : '';

                        $desc_display = $description;
                        if ($matched === false) {
                            $desc_display .= " <span class='badge bg-danger'>Not Matched</span>";
                        } else {
                            $desc_display .= " <span class='badge bg-success'>Matched</span>";
                        }

                        echo "<tr class='$row_class'>
                            <td class='fs-11 w-100'>$desc_display</td>
                            <td class='fs-11'>$lead_time_local</td>
                            <td class='fs-11'>$lead_time_import</td>
                            <td class='fs-11'>$available_stocks</td>
                            <td class='fs-11'>$safety</td>
                            <td class='fs-11'>$qty_sold_past_3_moths</td>
                            <td class='fs-11 w-100'>$forecast_months_display</td>
                            <td class='fs-11'>$expected_needed</td>
                            <td class='fs-11'>$incoming_stocks</td>
                            <td class='fs-11'>$total_stocks</td>
                            <td class='fs-11'>$suggested_local</td>
                            <td class='fs-11'>$suggested_import</td>
                        </tr>";

                    }

                    echo "</table>";
                    fclose($handle);
                } else {
                    echo "<p class='text-danger'>Could not open the file.</p>";
                }
            } else {
                echo "<p class='text-danger'>Invalid file type. Please upload a CSV file.</p>";
            }
        }
        ?>
        
    </div>
</div>