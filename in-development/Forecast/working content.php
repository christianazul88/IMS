<table class="table table-success table-striped">
<thead>
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
    </tr>
</thead>
<tbody>
<?php
$import_lead_time_query = "
    SELECT 
        IFNULL(AVG(TIMESTAMPDIFF(DAY, po.date_order, po.date_received)), 0) AS avg_import_days
    FROM purchased_order po
    LEFT JOIN supplier s ON s.hashed_id = po.supplier
    WHERE s.local_international = 'International' AND po.date_received IS NOT NULL
";
$import_lead_time_res = $conn->query($import_lead_time_query);
$import_lead_time = ($import_lead_time_res->num_rows > 0) ? round($import_lead_time_res->fetch_assoc()['avg_import_days']) : 0;

$local_lead_time_query = "
    SELECT 
        IFNULL(AVG(TIMESTAMPDIFF(DAY, po.date_order, po.date_received)), 0) AS avg_local_days
    FROM purchased_order po
    LEFT JOIN supplier s ON s.hashed_id = po.supplier
    WHERE s.local_international = 'Local' AND po.date_received IS NOT NULL
";
$local_lead_time_res = $conn->query($local_lead_time_query);
$local_lead_time = ($local_lead_time_res->num_rows > 0) ? round($local_lead_time_res->fetch_assoc()['avg_local_days']) : 0;

foreach($warehouse_ids_array as $forecast_warehouse){
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
            COUNT(CASE WHEN s.item_status = 0 AND s.warehouse = '$forecast_warehouse' THEN 1 END) AS current_available_qty,
            daily_avg.avg_sales_per_day AS average_sales_count_per_day,
            SUM(CASE WHEN po.status NOT IN (0, 4) THEN poc.qty ELSE 0 END) AS incoming_stocks,
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
                COUNT(DISTINCT oc.unique_barcode) / COUNT(DISTINCT DATE(ol.date_sent)) AS avg_sales_per_day
            FROM outbound_logs ol
            JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id
            JOIN stocks s ON s.unique_barcode = oc.unique_barcode
            WHERE oc.status = 0
            GROUP BY s.product_id, ol.warehouse
        ) AS daily_avg 
            ON daily_avg.product_id = p.hashed_id 
            AND daily_avg.warehouse = '$forecast_warehouse'
        LEFT JOIN purchased_order_content poc ON poc.product_id = p.hashed_id
        LEFT JOIN purchased_order po ON po.id = poc.po_id
        LEFT JOIN supplier s2 ON s2.hashed_id = po.supplier
        GROUP BY p.hashed_id, p.description, p.product_img, b.brand_name, c.category_name, s.safety, s.warehouse, daily_avg.avg_sales_per_day, s2.local_international
    ";

    $product_res = $conn->query($product_query);
    if($product_res->num_rows > 0){
        while($row = $product_res->fetch_assoc()) {
            $product_id = $row['product_id'];
            $description = $row['description'];
            $product_img = $row['product_img'] ?? 'def_img.png';
            $parent_barcode = $row['parent_barcode'];
            $brand_name = $row['brand_name'];
            $category_name = $row['category_name'];
            $safety = $row['safety'];
            $warehouse = $row['warehouse'];
            $available_qty = $row['current_available_qty'];
            $average_daily_outbound_qty = $row['average_sales_count_per_day'] ?: 0;
            $incoming_stocks = $row['incoming_stocks'];
            $warehouse_name = $row['warehouse_name'];
            $supplier_origin = $row['supplier_origin'];

            $sales_query = "
                SELECT DATE(ol.date_sent) AS sale_date, COUNT(*) AS qty_sold
                FROM outbound_logs ol
                JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id
                JOIN stocks s ON s.unique_barcode = oc.unique_barcode
                WHERE oc.status = 0 
                AND s.product_id = '$product_id'
                AND s.warehouse = '$warehouse'
                AND DATE(ol.date_sent) >= CURDATE() - INTERVAL 30 DAY
                GROUP BY DATE(ol.date_sent)
                ORDER BY sale_date ASC
            ";
        
            $sales_res = $conn->query($sales_query);
            $sales_data = [];

            for ($i = 30; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $sales_data[$date] = 0;
            }
        
            if ($sales_res->num_rows > 0) {
                while ($sale_row = $sales_res->fetch_assoc()) {
                    $sales_data[$sale_row['sale_date']] = $sale_row['qty_sold'];
                }
            }

            $last_7_days_sales = array_slice($sales_data, -7);
            $total_7_days = array_sum($last_7_days_sales);
            $moving_avg = $total_7_days / 7;

            $forecast_30_days = round($moving_avg * 30);
            $days_to_stockout = $moving_avg > 0 ? round($available_qty / $moving_avg) : '∞';

            // Determine lead time
            $lead_time = ($supplier_origin == 'Local') ? $local_lead_time : $import_lead_time;

            // Reorder Point Calculation
            $reorder_point = ($moving_avg * $lead_time) + $safety;
            $total_future_stock = $available_qty + $incoming_stocks;

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
                <td>' . number_format($moving_avg, 2) . '</td>
                <td>' . $incoming_stocks . '</td>
                <td>' . $forecast_30_days . '</td>
                <td>' . $days_to_stockout . '</td>
                <td><span class="badge bg-' . ($needs_reorder == 'Yes' ? 'danger' : 'success') . '">' . $needs_reorder . '</span></td>
            </tr>';
        }
    }
}
?>
</tbody>
</table>
