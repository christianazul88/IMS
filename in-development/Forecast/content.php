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
// If user submitted new filter, unset old forecast sessions first
if (isset($_SESSION['forecast_start_date'])) {
    unset($_SESSION['forecast_data']);
    unset($_SESSION['forecast_start_date']);
    unset($_SESSION['forecast_end_date']);
    unset($_SESSION['lead_time_local']);
    unset($_SESSION['lead_time_import']);
    unset($_SESSION['forecast_selected_warehouse']);
}

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

if(!isset($_SESSION['forecast_start_date']) && !isset($_SESSION['forecast_end_date']) && !isset($_SESSION['forecast_selected_warehouse'])){
    $_SESSION['forecast_start_date'] = $start_date;
    $_SESSION['forecast_end_date'] = $end_date;
    $_SESSION['forecast_selected_warehouse'] = $selected_warehouse;
}


if(isset($_SESSION['forecast_selected_warehouse']) && !empty($_SESSION['forecast_selected_warehouse'])){
?>
    <div class="text-end">
    <?php
    echo '<a href="url.php" class="btn btn-warning">export</a>';
    ?>
    </div>
<?php
}
if (empty($selected_warehouse)) {
    echo "<div class='alert alert-warning'>Please select a warehouse to generate forecast.</div>";
    return;
}

function getLeadTimes($conn) {
    $lead_times = ['local' => 0, 'international' => 0];

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

    $res_import = $conn->query($import_query);
    $res_local = $conn->query($local_query);

    if ($res_import->num_rows > 0) {
        $lead_times['international'] = round($res_import->fetch_assoc()['avg_import_days']);
    }
    if ($res_local->num_rows > 0) {
        $lead_times['local'] = round($res_local->fetch_assoc()['avg_local_days']);
    }

    return $lead_times;
}

$lead_times = getLeadTimes($conn);

function getDailyAverageSales($conn, $start_date, $end_date) {
    $query = "
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
    ";
    $res = $conn->query($query);
    $result = [];

    if ($res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $key = $row['product_id'] . '|' . $row['warehouse'];
            $result[$key] = $row['avg_sales_per_day'];
        }
    }

    return $result;
}

$daily_avg_sales = getDailyAverageSales($conn, $start_date, $end_date);

$product_query = "
    SELECT 
        p.hashed_id AS product_id, 
        p.description, 
        p.parent_barcode, 
        b.brand_name, 
        c.category_name, 
        s.safety, 
        s.warehouse, 
        COUNT(CASE WHEN s.item_status = 0 AND s.warehouse = '$selected_warehouse' THEN 1 END) AS current_available_qty,
        w.warehouse_name,
        s2.local_international AS supplier_origin,
        s2.supplier_name
    FROM product p 
    LEFT JOIN brand b ON b.hashed_id = p.brand 
    LEFT JOIN category c ON c.hashed_id = p.category 
    LEFT JOIN stocks s ON s.product_id = p.hashed_id 
    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
    LEFT JOIN purchased_order_content poc ON poc.product_id = p.hashed_id
    LEFT JOIN purchased_order po ON po.id = poc.po_id AND po.warehouse = s.warehouse
    LEFT JOIN supplier s2 ON s2.hashed_id = po.supplier
    WHERE s.warehouse = '$selected_warehouse'
    GROUP BY p.hashed_id, b.brand_name, c.category_name, s.safety, s.warehouse, w.warehouse_name, s2.local_international, s2.supplier_name
";

$product_res = $conn->query($product_query);

if ($product_res->num_rows > 0) {
    echo "
    <div class='mb-3'>
        <strong>Average Lead Times:</strong><br>
        Local Suppliers: {$lead_times['local']} days<br>
        International Suppliers: {$lead_times['international']} days
    </div>";

    if(!isset($_SESSION['lead_time_local']) && !isset($_SESSION['lead_time_import'])){
        $_SESSION['lead_time_local'] = $lead_times['local'];
        $_SESSION['lead_time_import'] = $lead_times['international'];
    }
?>

<div class="card">
    <div class="card-heading pt-3 ps-3" style="background-color: purple;"><h3 class="text-white">Forecast for <?php echo $start_date . " to " . $end_date; ?></h3></div>
    <div class="card-body">
    <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
    <thead class="table-secondary">
        <tr>
            <th>Description</th>
            <th>Category</th>
            <th>Brand</th>
            <th>Parent Barcode</th>
            <th>Supplier</th>
            <th>Supplier Origin</th>
            <th>Lead Time (Days)</th>
            <th>Safety</th>
            <th>Warehouse</th>
            <th>Stocks</th>
            <th>Avg Daily Sales</th>
            <th>Incoming Stocks</th>
            <th>Forecast (30 Days)</th>
            <th>Est. Stockout (Days)</th>
            <th>Reorder Point (Local)</th>
            <th>Reorder Point (Intl)</th>
            <th>Reorder?</th>
            <th>Reorder Quantity</th>
        </tr>
    </thead>
    <tbody>

<?php
    while ($row = $product_res->fetch_assoc()) {
        $product_id = $row['product_id'];
        $description = $row['description'];
        $parent_barcode = $row['parent_barcode'];
        $brand_name = $row['brand_name'];
        $category_name = $row['category_name'];
        $safety = $row['safety'] ?? 0;
        $warehouse = $row['warehouse'];
        $available_qty = $row['current_available_qty'];
        $warehouse_name = $row['warehouse_name'];
        $supplier_origin = $row['supplier_origin'];
        $supplier_name = $row['supplier_name'] ?? 'N/A';

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
        $moving_avg_7 = array_sum($last_7_days_sales) / 7;

        $key = $product_id . '|' . $selected_warehouse;
        $moving_avg = $daily_avg_sales[$key] ?? $moving_avg_7;

        $incoming_stocks = 0;
        $po_query = "
            SELECT SUM(CASE WHEN po.status NOT IN (0, 4) THEN poc.qty ELSE 0 END) AS incoming_stocks
            FROM purchased_order_content poc 
            LEFT JOIN purchased_order po ON po.id = poc.po_id
            WHERE po.warehouse = '$selected_warehouse'
            AND poc.product_id = '$product_id'
            GROUP BY poc.product_id
        ";
        $po_result = $conn->query($po_query);
        if ($po_result->num_rows > 0) {
            $incoming_stocks = $po_result->fetch_assoc()['incoming_stocks'];
        }

        $forecast_30_days = round($moving_avg * 30);
        $days_to_stockout = $moving_avg > 0 ? round($available_qty / $moving_avg) : '∞';

        $lead_time = ($supplier_origin == 'Local') ? $lead_times['local'] : $lead_times['international'];
        $reorder_point_local = ($moving_avg * $lead_times['local']) + $safety;
        $reorder_point_intl = ($moving_avg * $lead_times['international']) + $safety;

        $reorder_point = $supplier_origin == 'Local' ? $reorder_point_local : $reorder_point_intl;
        $total_future_stock = $available_qty + $incoming_stocks;
        $stock_shortfall = max(0, $safety - $total_future_stock);
        $reorder_quantity = $stock_shortfall + max(0, ($moving_avg * $lead_time) - $total_future_stock);
        $needs_reorder = ($total_future_stock < $reorder_point) ? 'Yes' : 'No';

        if (!isset($_SESSION['forecast_data'])) {
            $_SESSION['forecast_data'] = [];
        }

        $_SESSION['forecast_data'][] = [
            'description' => $description,
            'category' => $category_name,
            'brand' => $brand_name,
            'parent_barcode' => $parent_barcode,
            'supplier' => $supplier_name,
            'supplier_origin' => $supplier_origin,
            'lead_time' => $lead_time,
            'safety' => $safety,
            'warehouse' => $warehouse_name,
            'stocks' => $available_qty,
            'avg_daily_sales' => number_format($moving_avg, 2),
            'incoming_stocks' => $incoming_stocks,
            'forecast_30_days' => $forecast_30_days,
            'est_stockout_days' => $days_to_stockout,
            'reorder_point_local' => $reorder_point_local,
            'reorder_point_intl' => $reorder_point_intl,
            'needs_reorder' => $needs_reorder,
            'reorder_quantity' => $reorder_quantity
        ];


        echo "<tr>
            <td>{$description}</td>
            <td>{$category_name}</td>
            <td>{$brand_name}</td>
            <td>{$parent_barcode}</td>
            <td>{$supplier_name}</td>
            <td>{$supplier_origin}</td>
            <td>{$lead_time}</td>
            <td>{$safety}</td>
            <td>{$warehouse_name}</td>
            <td>{$available_qty}</td>
            <td>" . number_format($moving_avg, 2) . "</td>
            <td>{$incoming_stocks}</td>
            <td>{$forecast_30_days}</td>
            <td>{$days_to_stockout}</td>
            <td>{$reorder_point_local}</td>
            <td>{$reorder_point_intl}</td>
            <td><span class='badge bg-" . ($needs_reorder == 'Yes' ? 'danger' : 'success') . "'>{$needs_reorder}</span></td>
            <td>{$reorder_quantity}</td>
        </tr>";
    }

    echo '</tbody></table></div></div>';
} else {
    echo "<div class='alert alert-warning'>No product data found for the selected criteria.</div>";
}



?>
