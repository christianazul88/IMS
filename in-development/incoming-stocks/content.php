<?php
$category_id = $_GET['name'] ?? null;
$wh = $_GET['wh'] ?? null;
$category_name = $_GET['name2'] ?? null;

// Quote each warehouse ID
$quoted_warehouse_ids = array_map(function ($id) {
    return "'" . trim($id) . "'";
}, $user_warehouse_ids);
$imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);

// Build the dynamic WHERE clause
$additional_query = "po.warehouse IN ($imploded_warehouse_ids)";
$additional_category_query = "";

$paragraph = 'All categories and accessible warehouses.';
if ($wh !== null) {
    if (!empty($wh)) {
        $another_wh_query = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$wh' LIMIT 1";
        $result = $conn->query($another_wh_query);
        $row=$result->fetch_assoc();
        $selected_warehouse_name = $row['warehouse_name'];
        $additional_query = "po.warehouse = '$wh'";
        $paragraph = "For " . $selected_warehouse_name;
    }
}

if ($category_id !== null && $category_name !== null) {
    $additional_category_query = "AND p.category = '$category_id'";
    if($paragraph !== "All categories and accessible warehouses."){
        $paragraph .= " And category: " . $category_name;
    } else {
        $paragraph = "For category: " . $category_name . " and all accessible warehouses.";
    }
}

// Main query
$rowz = [];
$query = "
    SELECT
        po.id AS po_id,
        p.hashed_id AS product_id,
        p.product_img,
        p.description,
        b.brand_name,
        c.category_name,
        SUM(DISTINCT poc.qty) AS incoming_stocks,
        po.status,
        w.warehouse_name,
        w.hashed_id AS warehouse_identifier,
        sup.supplier_name,
        po.date_order,
        u.user_fname,
        u.user_lname
    FROM product p
    LEFT JOIN purchased_order_content poc ON poc.product_id = p.hashed_id
    LEFT JOIN purchased_order po ON po.id = poc.po_id
    LEFT JOIN supplier sup ON sup.hashed_id = po.supplier
    LEFT JOIN warehouse w ON w.hashed_id = po.warehouse
    LEFT JOIN users u ON u.hashed_id = po.user_id
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    WHERE $additional_query 
    AND po.status NOT IN (0, 4)
    $additional_category_query
    GROUP BY p.product_img, p.description, p.category, w.warehouse_name, sup.supplier_name
    ORDER BY c.category_name, sup.supplier_name, w.warehouse_name, po.id ASC
";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $po_id = $row['po_id'];
        $product_id = $row['product_id'];
        $product_img = $row['product_img'];
        $description = $row['description'];
        $brand_name = $row['brand_name'];
        $category_name = $row['category_name'];
        $incoming_stocks = $row['incoming_stocks'];
        $stocks = $row['stocks'] ?? 0;
        $incoming_warehouse_name = $row['warehouse_name'];
        $warehouse_identifier = $row['warehouse_identifier'];
        $supplier_name = $row['supplier_name'];
        $date_order = $row['date_order'];
        $staff_fullname = $row['user_fname'] . " " . $row['user_lname'];

        if($row['status'] == 0){
            $status = '<span class="badge badge rounded-pill badge-subtle-warning">Drafted  <div class="spinner-border" role="status" style="height:10px; width: 10px;"><span class="visually-hidden">Loading...</span></div></span>';
        } elseif($row['status'] == 1){
            $status = '<span class="badge badge rounded-pill badge-subtle-info">Sent to Supplier<span class="ms-1 fas fa-check" data-fa-transform="shrink-2"></span></span>';
        } elseif($row['status'] == 2) {
            $status = '<span class="badge badge rounded-pill badge-subtle-secondary">Confirmed by Supplier<span class="ms-1 fas fa-check" data-fa-transform="shrink-2"></span></span>';
        } elseif($row['status'] == 3){
            $status = '<span class="badge badge rounded-pill badge-subtle-primary">In Transit/ Shipped<span class="ms-1 fas fa-check" data-fa-transform="shrink-2"></span></span>';
        } else {
            $status = '<span class="badge badge rounded-pill badge-subtle-success">Received<span class="ms-1 fas fa-check" data-fa-transform="shrink-2"></span></span>';
        }

        $stocks_query = "
            SELECT 
                COUNT(s.unique_barcode) AS stocks
            FROM product p
            LEFT JOIN stocks s ON s.product_id = p.hashed_id
            WHERE s.item_status NOT IN (1, 4, 8) AND s.batch_code != '-' AND p.hashed_id = '$product_id'
            AND s.warehouse = '$warehouse_identifier'
            GROUP BY p.hashed_id";
        
        $stock_result = $conn->query($stocks_query);
        if($stock_result->num_rows>0){
            $row=$stock_result->fetch_assoc();
            $stocks = $row['stocks'];
        }


        $rowz[] = '
        <tr>
            <td><img src="../../assets/img/' . $product_img . '" height="50" alt=""></td>
            <td>' . $description . '</td>
            <td>' . $brand_name . '</td>
            <td>' . $category_name . '</td>
            <td>' . $status . '</td>
            <td>' . $incoming_warehouse_name . '</td>
            <td>' . $supplier_name . '</td>
            <td>' . $date_order . '</td>
            <td>' . $staff_fullname . '</td>
            <td>' . $stocks . '</td>
            <td>' . $incoming_stocks . '</td>
        </tr>';
    }
}
?>
<div class="row">
    <div class="col-xxl-14">
        <div class="card">
            <div class="card-header bg-secondary">
                <h2 class="text-white">Incoming Stocks</h2>
                <p class="text-white"><?php echo $paragraph;?></p>
                <div class="row">
                    <div class="col-6"></div>
                    <div class="col-6">
                        <small class="text-300 text-end">This module only displays incoming products, so the current stock may appear different in the summary on the dashboard compared to this module. </small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Description</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Delivery Status</th>
                            <th>Warehouse</th>
                            <th>Supplier</th>
                            <th>Order Date</th>
                            <th>Author</th>
                            <th>Stocks</th>
                            <th>Incoming Stocks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach($rowz AS $incoming_rows){
                            echo $incoming_rows;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>