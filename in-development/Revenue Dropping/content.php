<?php 
$revdrop_data = [];

$filter_warehouse = "";
if (!empty($_GET['wh'])) {
    $warehouse_dashboard_id = mysqli_real_escape_string($conn, $_GET['wh']);
    $filter_warehouse = "AND s.warehouse = '$warehouse_dashboard_id'";
} else {
    $warehouse_list = explode(',', $_SESSION['warehouse_ids']);
    $warehouse_list = array_map(function($warehouse) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $warehouse) . "'";
    }, $warehouse_list);
    $filter_warehouse = "AND s.warehouse IN (" . implode(",", $warehouse_list) . ")";
}

$revdrop_product_query = "
    SELECT 
        p.description,
        p.product_img,
        b.brand_name,
        c.category_name,
        
        COUNT(DISTINCT CASE WHEN ol.date_sent BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW()) THEN oc.unique_barcode END) AS this_month_outbound,
        IFNULL(SUM(CASE WHEN ol.date_sent BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW()) THEN s.capital END), 0) AS this_month_capital,
        IFNULL(SUM(CASE WHEN ol.date_sent BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW()) THEN oc.sold_price END), 0) AS this_month_sales,
        
        COUNT(DISTINCT CASE WHEN ol.date_sent BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') AND LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)) THEN oc.unique_barcode END) AS last_month_outbound,
        IFNULL(SUM(CASE WHEN ol.date_sent BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') AND LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)) THEN s.capital END), 0) AS last_month_capital,
        IFNULL(SUM(CASE WHEN ol.date_sent BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') AND LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)) THEN oc.sold_price END), 0) AS last_month_sales

    FROM product p
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN stocks s ON s.product_id = p.hashed_id
    LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
    LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
    WHERE oc.status = 0
      AND (s.batch_code IS NOT NULL AND s.batch_code != '-')
      $filter_warehouse
    GROUP BY p.hashed_id
    HAVING 
        COUNT(DISTINCT CASE WHEN ol.date_sent BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') 
        AND LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)) THEN oc.unique_barcode END) 
        >
        COUNT(DISTINCT CASE WHEN ol.date_sent BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') 
        AND LAST_DAY(NOW()) THEN oc.unique_barcode END)
    ORDER BY 
        ((COUNT(DISTINCT CASE WHEN ol.date_sent BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') 
        AND LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)) THEN oc.unique_barcode END)
        - 
        COUNT(DISTINCT CASE WHEN ol.date_sent BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') 
        AND LAST_DAY(NOW()) THEN oc.unique_barcode END)) 
        / 
        COUNT(DISTINCT CASE WHEN ol.date_sent BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') 
        AND LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)) THEN oc.unique_barcode END)
        ) DESC
";


$revdrop_product_res = $conn->query($revdrop_product_query);

if ($revdrop_product_res->num_rows > 0) {
    while($row = $revdrop_product_res->fetch_assoc()) {
        $revdrop_item = $row['description'];
        $revdrop_item_img = $row['product_img'] ?? 'def_img.png';
        $revdrop_brand = $row['brand_name'];
        $revdrop_category = $row['category_name'];

        $this_month_outbound = (int)$row['this_month_outbound'];
        $last_month_outbound = (int)$row['last_month_outbound'];

        $this_month_capital = (float)$row['this_month_capital'];
        $last_month_capital = (float)$row['last_month_capital'];

        $this_month_sales = (float)$row['this_month_sales'];
        $last_month_sales = (float)$row['last_month_sales'];

        $this_month_net = $this_month_sales - $this_month_capital;
        $last_month_net = $last_month_sales - $last_month_capital;

        $drop_percentage = ($last_month_outbound > 0) ? (($last_month_outbound - $this_month_outbound) / $last_month_outbound) * 100 : 0;

        $revdrop_data[] = '<tr>
            <td><img class="img" src="../../assets/img/' . basename($revdrop_item_img) . '" style="height: 30px; width: 30px;" alt=""></td>
            <td>' . htmlspecialchars($revdrop_item) . '</td>
            <td>' . htmlspecialchars($revdrop_brand) . '</td>
            <td>' . htmlspecialchars($revdrop_category) . '</td>
            <td class="text-end">' . number_format($this_month_outbound) . '</td>
            <td class="text-end">' . number_format($last_month_outbound) . '</td>
            <td class="text-end">' . number_format($this_month_capital, 2) . '</td>
            <td class="text-end">' . number_format($last_month_capital, 2) . '</td>
            <td class="text-end">' . number_format($this_month_sales, 2) . '</td>
            <td class="text-end">' . number_format($last_month_sales, 2) . '</td>
            <td class="text-end">' . number_format($this_month_net, 2) . '</td>
            <td class="text-end">' . number_format($last_month_net, 2) . '</td>
            <td class="text-end text-danger">' . number_format($drop_percentage, 2) . '%</td>
        </tr>';
    }
}

?>

<div class="card">
    <div class="card-header">
        <h6>Revenue Dropping <?php echo $date_today; ?></h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0 data-table fs-10" data-datatables='{"paging":false,"scrollY":"300px","scrollCollapse":true}'>
                <thead class="bg-warning">
                    <tr>
                        <th></th>
                        <th>Description</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th class="text-end">This Month Outbound</th>
                        <th class="text-end">Last Month Outbound</th>
                        <th class="text-end">This Month Unit Cost</th>
                        <th class="text-end">Last Month Unit Cost</th>
                        <th class="text-end">This Month Gross Sales</th>
                        <th class="text-end">Last Month Gross Sales</th>
                        <th class="text-end">This Month Net Income</th>
                        <th class="text-end">Last Month Net Income</th>
                        <th class="text-end">% Dropped</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach($revdrop_data as $revdrop_display){
                        echo $revdrop_display;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
