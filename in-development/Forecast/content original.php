<!-- Date Range + Warehouse Form -->
<form method="GET" class="mb-3 row g-3 align-items-end">
    <div class="col-md-4">
        <label class="form-label" for="timepicker2">Select Date Range</label>
        <input class="form-control datetimepicker" id="timepicker2" name="date_range" type="text"
            placeholder="dd/mm/yy to dd/mm/yy"
            data-options='{"mode":"range","dateFormat":"d/m/y","disableMobile":true}' />
    </div>

    <div class="col-md-4">
        <label class="form-label" for="warehouse_select">Select Warehouse</label>
        <select class="form-select" name="warehouse_id" id="warehouse_select" required>
            <option value="">-- Choose Warehouse --</option>
            <?php
            // Fetch and populate all warehouses
            $warehouse_query = "SELECT hashed_id, warehouse_name FROM warehouse ORDER BY warehouse_name ASC";
            $warehouse_res = $conn->query($warehouse_query);
            if ($warehouse_res->num_rows > 0) {
                while ($wh = $warehouse_res->fetch_assoc()) {
                    $selected = ($_GET['warehouse_id'] ?? '') == $wh['hashed_id'] ? 'selected' : '';
                    echo "<option value='{$wh['hashed_id']}' $selected>{$wh['warehouse_name']}</option>";
                }
            }
            ?>
        </select>
    </div>

    <div class="col-md-4">
        <button type="submit" class="btn btn-primary">Filter</button>
    </div>
</form>

<?php
function convertToMySQLDate($dateStr) {
    $parts = explode('/', trim($dateStr));
    if (count($parts) === 3) {
        $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
        $year = strlen($parts[2]) === 2 ? '20' . $parts[2] : $parts[2];
        return "$year-$month-$day";
    }
    return null;
}

// Defaults
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');
$selected_warehouse = $_GET['warehouse_id'] ?? '';

if (isset($_GET['date_range'])) {
    $dates = explode('to', $_GET['date_range']);
    if (count($dates) === 2) {
        $start_date = convertToMySQLDate($dates[0]);
        $end_date = convertToMySQLDate($dates[1]);
    }
}

if (empty($selected_warehouse)) {
    echo "<div class='alert alert-warning'>Please select a warehouse to generate forecast.</div>";
    return;
}

// Lead times
$import_lead_time_query = "
    SELECT IFNULL(AVG(TIMESTAMPDIFF(DAY, po.date_order, po.date_received)), 0) AS avg_import_days
    FROM purchased_order po
    LEFT JOIN supplier s ON s.hashed_id = po.supplier
    WHERE s.local_international = 'International' AND po.date_received IS NOT NULL
";
$import_lead_time_res = $conn->query($import_lead_time_query);
$import_lead_time = ($import_lead_time_res->num_rows > 0) ? round($import_lead_time_res->fetch_assoc()['avg_import_days']) : 0;

$local_lead_time_query = "
    SELECT IFNULL(AVG(TIMESTAMPDIFF(DAY, po.date_order, po.date_received)), 0) AS avg_local_days
    FROM purchased_order po
    LEFT JOIN supplier s ON s.hashed_id = po.supplier
    WHERE s.local_international = 'Local' AND po.date_received IS NOT NULL
";
$local_lead_time_res = $conn->query($local_lead_time_query);
$local_lead_time = ($local_lead_time_res->num_rows > 0) ? round($local_lead_time_res->fetch_assoc()['avg_local_days']) : 0;

// echo "<div><strong>Date Range:</strong> $start_date to $end_date</div>";

$product_query = "
    SELECT 
        p.hashed_id AS product_id, 
        p.description, 
        p.product_img, 
        p.parent_barcode, 
        b.brand_name, 
        c.category_name, 
        s.safety, 
        s.warehouse, 
        COUNT(CASE WHEN s.item_status = 0 AND s.warehouse = '$selected_warehouse' THEN 1 END) AS current_available_qty,
        daily_avg.avg_sales_per_day AS average_sales_count_per_day,
        w.warehouse_name,
        s2.local_international AS supplier_origin
    FROM product p 
    LEFT JOIN brand b ON b.hashed_id = p.brand 
    LEFT JOIN category c ON c.hashed_id = p.category 
    LEFT JOIN stocks s ON s.product_id = p.hashed_id 
    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
    LEFT JOIN (
        SELECT 
            s.product_id,
            ol.warehouse,
            COUNT(oc.unique_barcode) / DATEDIFF('$end_date', '$start_date') AS avg_sales_per_day
        FROM outbound_logs ol
        JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id
        JOIN stocks s ON s.unique_barcode = oc.unique_barcode
        WHERE oc.status = 0
        AND DATE(ol.date_sent) BETWEEN '$start_date' AND '$end_date'
        GROUP BY s.product_id, ol.warehouse
    ) AS daily_avg 
        ON daily_avg.product_id = p.hashed_id 
        AND daily_avg.warehouse = '$selected_warehouse'
    LEFT JOIN purchased_order_content poc ON poc.product_id = p.hashed_id
    LEFT JOIN purchased_order po ON po.id = poc.po_id AND po.warehouse = s.warehouse
    LEFT JOIN supplier s2 ON s2.hashed_id = po.supplier
    GROUP BY p.hashed_id, b.brand_name, c.category_name, s.safety, s.warehouse, daily_avg.avg_sales_per_day, s2.local_international
";

$product_res = $conn->query($product_query);
if ($product_res->num_rows > 0) {
    ?>
    <div class="card">
        <div class="card-heading pt-3 ps-3"><h3>Forecast for <?php echo $start_date. " to " . $end_date;?></h3></div>
        <div class="card-body">
    <?php
    echo '<table class="table table-striped">
    <thead class="table-dark">
        <tr>
            <th></th>
            <th>Description</th>
            <th>Category</th>
            <th>Brand</th>
            <th>Parent Barcode</th>
            <th>Safety</th>
            <th>Warehouse</th>
            <th>Stocks</th>
            <th>Avg Daily Sales</th>
            <th>Incoming Stocks</th>
            <th>Forecast (30 Days)</th>
            <th>Est. Stockout (Days)</th>
            <th>Reorder?</th>
            <th>Reorder Quantity</th> <!-- New Column -->
        </tr>
    </thead><tbody>';

    while ($row = $product_res->fetch_assoc()) {
        $product_id = $row['product_id'];
        $description = $row['description'];
        $product_img = $row['product_img'] ?? 'def_img.png';
        $parent_barcode = $row['parent_barcode'];
        $brand_name = $row['brand_name'];
        $category_name = $row['category_name'];
        $safety = $row['safety'];
        $warehouse = $row['warehouse'];
        $available_qty = $row['current_available_qty'];
        $moving_avg = $row['average_sales_count_per_day'] ?: 0;
        
        $warehouse_name = $row['warehouse_name'];
        $supplier_origin = $row['supplier_origin'];

        $po_query = "
            SELECT
                SUM(CASE WHEN po.status NOT IN (0, 4) THEN poc.qty ELSE 0 END) AS incoming_stocks
            FROM purchased_order_content poc 
            LEFT JOIN purchased_order po ON po.id = poc.po_id
            WHERE po.warehouse = '$selected_warehouse'
            AND poc.product_id = '$product_id'
            GROUP BY poc.product_id
        ";
        $po_result = $conn->query($po_query);
        if($po_result->num_rows>0){
            $row = $po_result->fetch_assoc();
            $incoming_stocks = $row['incoming_stocks'];
        } else {
            $incoming_stocks = 0;
        }

        $sales_query = "
            SELECT DATE(ol.date_sent) AS sale_date, COUNT(*) AS qty_sold
            FROM outbound_logs ol
            JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id
            JOIN stocks s ON s.unique_barcode = oc.unique_barcode
            WHERE oc.status = 0 
            AND s.product_id = '$product_id'
            AND s.warehouse = '$selected_warehouse'
            AND DATE(ol.date_sent) BETWEEN '$start_date' AND '$end_date'
            GROUP BY DATE(ol.date_sent)
        ";
        $sales_res = $conn->query($sales_query);
        $sales_data = [];
        $days_diff = (strtotime($end_date) - strtotime($start_date)) / 86400;
        for ($i = 0; $i <= $days_diff; $i++) {
            $date = date('Y-m-d', strtotime($start_date . " +$i days"));
            $sales_data[$date] = 0;
        }
        if ($sales_res->num_rows > 0) {
            while ($sale_row = $sales_res->fetch_assoc()) {
                $sales_data[$sale_row['sale_date']] = $sale_row['qty_sold'];
            }
        }

        $last_7_days_sales = array_slice($sales_data, -7);
        $total_7_days = array_sum($last_7_days_sales);
        $moving_avg_7 = $total_7_days / 7;
        $forecast_30_days = round($moving_avg_7 * 30);
        $days_to_stockout = $moving_avg_7 > 0 ? round($available_qty / $moving_avg_7) : '∞';

        $lead_time = ($supplier_origin == 'Local') ? $local_lead_time : $import_lead_time;
        $reorder_point = ($moving_avg_7 * $lead_time) + $safety;
        // Calculate total future stock (available + incoming)
        $total_future_stock = $available_qty + $incoming_stocks;

        // Ensure that we never fall below safety stock
        $stock_shortfall = max(0, $safety - $total_future_stock);

        // Now calculate the reorder quantity to cover forecasted sales during lead time
        $reorder_quantity = $stock_shortfall + max(0, ($moving_avg_7 * $lead_time) - $total_future_stock);

        $needs_reorder = ($total_future_stock < $reorder_point) ? 'Yes' : 'No';
        

        echo '<tr>
            <td><img class="img" src="../../assets/img/' . $product_img . '" style="height: 30px;" alt=""></td>
            <td>' . $description . '</td>
            <td>' . $category_name . '</td>
            <td>' . $brand_name . '</td>
            <td>' . $parent_barcode . '</td>
            <td>' . $safety . '</td>
            <td>' . $warehouse_name . '</td>
            <td>' . $available_qty . '</td>
            <td>' . number_format($moving_avg_7, 2) . '</td>
            <td>' . $incoming_stocks . '</td>
            <td>' . $forecast_30_days . '</td>
            <td>' . $days_to_stockout . '</td>
            <td><span class="badge bg-' . ($needs_reorder == 'Yes' ? 'danger' : 'success') . '">' . $needs_reorder . '</span></td>
            <td>' . $reorder_quantity . '</td> <!-- Display reorder quantity here -->
        </tr>';
}

echo '</tbody></table>';
?>
</div></div>
<?php } else { echo "<div class='alert alert-warning'>No product data found for the selected criteria.</div>"; } ?>
