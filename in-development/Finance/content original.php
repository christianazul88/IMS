<div class="row">

    <?php
    $grand_total_qty = 0;
    $grand_total_unit_cost = 0;
    $grand_total_gross = 0;
    $grand_total_net = 0;
    $num = 1;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Collect inputs safely
        $startDate = isset($_POST['start_date']) ? $_POST['start_date'] . " 23:59:59" : null;
        $endDate = isset($_POST['end_date']) ? $_POST['end_date'] . " 23:59:59" : null;
        $warehouse_transaction = isset($_POST['warehouse']) ? trim($_POST['warehouse']) : '';
        $categories = isset($_POST['category']) ? $_POST['category'] : [];

        $dateFormat = "M j, Y";  // Date format like "Jan 1, 2000"

        $formattedStartDate = isset($_POST['start_date']) ? date($dateFormat, strtotime($_POST['start_date'])) : null;
        $formattedEndDate = isset($_POST['end_date']) ? date($dateFormat, strtotime($_POST['end_date'])) : null;

        // Sanitize and validate categories array (ensure it's an array of strings)
        $categories = array_map('trim', $categories);  // Trim each category string
    
         if(empty($categories)){
            $imploded_category = "";
         } else {
            // Implode raw hashed IDs
            $imploded_category = "'" . implode("','", $categories) . "'";
         }
         
         
         
         $transaction_warehouse_name = "";
         $transaction_warehouse_query = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$warehouse_transaction' LIMIT 1";
         $transaction_warehouse_res = $conn->query($transaction_warehouse_query);
         if($transaction_warehouse_res->num_rows>0){
            $row=$transaction_warehouse_res->fetch_assoc();
            $transaction_warehouse_name = $row['warehouse_name'];
         }
         
       
    ?>
    <div class="col-xxl-12">
        <div class="card">
            <div class="card-body bg-body-tertiary overflow-hidden ">
                <form action="../Transaction-overview/index" method="POST">
                    <div class="tab-content row">
                        <div class="col-lg-2 mb-3">
                            <label class="form-label" for="start_datepicker">Start Date</label>
                            <input class="form-control datetimepicker fs-11" name="start_date" id="start_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' value="<?php echo $startDate;?>" required/>
                        </div>
                        <div class="col-lg-2 mb-3">
                            <label class="form-label" for="end_datepicker">End Date</label>
                            <input class="form-control datetimepicker fs-11" name="end_date" id="end_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' value="<?php echo $endDate;?>" required/>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select class="form-select selectpicker fs-11" id="category" multiple="multiple" size="1" name="category[]" data-options='{"placeholder":"Select your options"}'>
                                    <option value="">Select staff...</option>
                                    <?php 
                                    $category_sql = "SELECT * FROM category ORDER BY category_name ASC";
                                    $stmt = $conn->prepare($category_sql); // Use prepared statements
                                    $stmt->execute();
                                    $res = $stmt->get_result();
                                    if ($res->num_rows > 0) {
                                        while ($row = $res->fetch_assoc()) {
                                            $category_name = htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8');
                                            $category_id = htmlspecialchars($row['hashed_id'], ENT_QUOTES, 'UTF-8');
                                            if(strpos($imploded_category, $category_id)!==false){
                                                echo '<option value="' . $category_id . '" selected>' . $category_name . '</option>';  
                                            } else {
                                                echo '<option value="' . $category_id . '">' . $category_name . '</option>'; 
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 mb-3">
                            <label for="warehouse">Warehouse</label>
                            <select class="form-select fs-11" name="warehouse" id="warehouse">
                            <?php echo implode("\n", $warehouse_options2); ?>
                            </select>
                        </div>
                        <div class="col-lg-1 mb-3 pt-4">
                            <button type="submit" class="btn btn-primary mt-1 fs-11">Generate</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xxl-12">
        <div class="card mt-3">
            <div class="card-body bg-body-tertiary">
                <div class="row">
                    <div class="col-lg-11 text-center mb-3">
                        <h3>TRANSACTION OVERVIEW</h3> 
                        <p class="mb-0"><?php echo $transaction_warehouse_name;?></p>
                        <small><?php echo $formattedStartDate . " - " . $formattedEndDate;?></small>
                        <h5><?php //echo $outbound_warehouse;?></h5>
                    </div>
                    <div class="col-lg-1">
                        <a href="url.php?from=<?php echo $startDate;?>&to=<?php echo $endDate;?>&category=<?php echo $imploded_category;?>&wh=<?php echo $warehouse_transaction;?>" class="btn btn-primary fs-11"><span class="fas fa-download"></span></a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 table-responsive">
                            <!-- <table class="table mb-0 data-table fs-10" data-datatables="data-datatables"> -->
                            <table class="table mb-0 fs-10">
                                <thead class="bg-primary">
                                    <tr>
                                        <th class="fs-10 text-white ">#</th>
                                        <th class="fs-10 text-white " colspan="9">CATEGORY</th>
                                        <th class="fs-10 text-white text-end ">QTY</th>
                                        <th class="fs-10 text-white text-end " style="width: 500px;">SUBTOTAL UNIT COST</th>
                                        <th class="fs-10 text-white text-end " style="width: 500px;">SUBTOTAL GROSS SALES</th>
                                        <th class="fs-10 text-white text-end " style="width: 500px;">SUBTOTAL NET INCOME</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $category_query = "
                                    SELECT 
                                        c.hashed_id AS category_id,
                                        c.category_name,
                                        COUNT(oc.unique_barcode) AS outbounded_qty,
                                        SUM(s.capital) AS unit_cost,
                                        SUM(oc.sold_price) AS gross_sale
                                    FROM category c
                                    LEFT JOIN product p ON p.category = c.hashed_id
                                    LEFT JOIN stocks s ON s.product_id = p.hashed_id
                                    LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
                                    LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                                    WHERE
                                    oc.status = 0
                                    AND s.item_status NOT IN (4, 8)
                                    AND DATE(ol.date_sent) BETWEEN '$startDate' AND '$endDate'
                                    AND ol.warehouse = '$warehouse_transaction'
                                    GROUP BY c.category_name
                                    ";

                                    $bg_colors = ['bg-100', 'bg-200', 'bg-300', 'bg-400', 'bg-500', 'bg-600', 'table-primary', 'table-info', 'table-dark', 'table-warning', 'table-success'];
                                    
                                    
                                    
                                    $category_result = $conn->query($category_query);
                                    if($category_result->num_rows>0){
                                        while($row=$category_result->fetch_assoc()){
                                            $random_bg = $bg_colors[array_rand($bg_colors)];
                                            $category_id = $row['category_id'];
                                            $category_name = $row['category_name'];
                                            $outbound_qty = $row['outbounded_qty'];
                                            $sub_unit_cost = $row['unit_cost'];
                                            $sub_gross = $row['gross_sale'];
                                            $sub_netincome = $sub_gross - $sub_unit_cost;
                                            
                                            if(!empty($imploded_category) && $imploded_category !== "''"){
                                                if(strpos($imploded_category, $category_id)!==false){
                                                    $grand_total_qty += $outbound_qty;
                                                    $grand_total_unit_cost += $sub_unit_cost;
                                                    $grand_total_gross += $sub_gross;
                                                    echo '
                                                    <tr class="' . $random_bg . '">
                                                        <td class="fs-10">' . $num . '</td>
                                                        <td class="fs-10" colspan="9">'. $category_name .'</td>
                                                        <td class="fs-10 text-end">'. $outbound_qty .'</td>
                                                        <td class="fs-10 text-end" style="width: 500px;">' . $sub_unit_cost .'</td>
                                                        <td class="fs-10 text-end" style="width: 500px;">' . $sub_gross . '</td>
                                                        <td class="fs-10 text-end" style="width: 500px;">' . $sub_netincome . '</td>
                                                    </tr>
                                                    <tr class="' . $random_bg . '">
                                                        <td class="fs-11"><b></b></td>
                                                        <td class="fs-11"><b>ORDER #</b></td>
                                                        <td class="fs-11"><b>OUTBOUND #</b></td>
                                                        <td class="fs-11"><b>CUSTOMER</b></td>
                                                        <td class="fs-11"><b>OUTBOUND DATE</b></td>
                                                        <td class="fs-11"><b>SUPPLIER</b></td>
                                                        <td class="fs-11"><b>LOCAL/ IMPORT</b></td>
                                                        <td class="fs-11"><b>DESCRIPTION</b></td>
                                                        <td class="fs-11"><b>BRAND</b></td>
                                                        <td class="fs-11"><b>BARCODE</b></td>
                                                        <td class="fs-11"><b>BATCH</b></td>
                                                        <td class="fs-11 text-end"><b>UNIT COST</b></td>
                                                        <td class="fs-11 text-end"><b>GROSS SALE</b></td>
                                                        <td class="fs-11 text-end"><b>NET INCOME</b></td>
                                                    </tr>
                                                    ';

                                                    $item_query = "
                                                    SELECT
                                                        oc.unique_barcode,
                                                        oc.sold_price,
                                                        ol.order_num,
                                                        oc.hashed_id AS outbound_num,
                                                        ol.customer_fullname,
                                                        ol.date_sent,
                                                        sup.supplier_name,
                                                        sup.local_international,
                                                        p.description,
                                                        b.brand_name,
                                                        s.batch_code,
                                                        s.capital
                                                    FROM outbound_content oc
                                                    LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                                                    LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
                                                    LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
                                                    LEFT JOIN product p ON p.hashed_id = s.product_id
                                                    LEFT JOIN brand b ON b.hashed_id = p.brand
                                                    WHERE p.category = '$category_id'
                                                    AND oc.status = 0
                                                    AND s.item_status NOT IN (4, 8)
                                                    AND DATE(ol.date_sent) BETWEEN '$startDate' AND '$endDate'
                                                    AND ol.warehouse = '$warehouse_transaction'
                                                    ";
                                                    $item_res = $conn->query($item_query);
                                                    if($item_res->num_rows>0){
                                                        while($row=$item_res->fetch_assoc()){
                                                            $unique_barcode = $row['unique_barcode'];
                                                            $sold_price = $row['sold_price'];
                                                            $order_num = $row['order_num'];
                                                            $outbound_num = $row['outbound_num'];
                                                            $customer_fullname = $row['customer_fullname'];
                                                            $supplier_name = $row['supplier_name'];
                                                            $local_international = $row['local_international'];
                                                            $description = $row['description'];
                                                            $brand_name = $row['brand_name'];
                                                            $batch_code = $row['batch_code'];
                                                            $capital = $row['capital'];
                                                            $date_sent = $row['date_sent'];
                                                            $net_income = $sold_price - $capital;
                                                            
                                                            echo '
                                                            <tr class="' . $random_bg . '">
                                                                <td class="fs-11"></td>
                                                                <td class="fs-11">' . $order_num . '</td>
                                                                <td class="fs-11">' . $outbound_num . '</td>
                                                                <td class="fs-11">' . $customer_fullname . '</td>
                                                                <td class="fs-11">' . $date_sent . '</td>
                                                                <td class="fs-11">' . $supplier_name . '</td>
                                                                <td class="fs-11">' . $local_international . '</td>
                                                                <td class="fs-11">' . $description . '</td>
                                                                <td class="fs-11">' . $brand_name . '</td>
                                                                <td class="fs-11">' . $unique_barcode . '</td>
                                                                <td class="fs-11">' . $batch_code . '</td>
                                                                <td class="fs-11 text-end">' . $capital . '</td>
                                                                <td class="fs-11 text-end">' . $sold_price . '</td>
                                                                <td class="fs-11 text-end">' . $net_income . '</td>
                                                            </tr>';
                                                        }
                                                    }
                                                }
                                            } else {
                                                $grand_total_qty += $outbound_qty;
                                                $grand_total_unit_cost += $sub_unit_cost;
                                                $grand_total_gross += $sub_gross;
                                                echo '
                                                <tr class="' . $random_bg . '">
                                                    <td class="fs-10">' . $num . '</td>
                                                    <td class="fs-10" colspan="9">'. $category_name .'</td>
                                                    <td class="fs-10 text-end">'. $outbound_qty .'</td>
                                                    <td class="fs-10 text-end" style="width: 500px;">' . $sub_unit_cost .'</td>
                                                    <td class="fs-10 text-end" style="width: 500px;">' . $sub_gross . '</td>
                                                    <td class="fs-10 text-end" style="width: 500px;">' . $sub_netincome . '</td>
                                                </tr>
                                                <tr class="' . $random_bg . '">
                                                    <td class="fs-11"><b></b></td>
                                                    <td class="fs-11"><b>ORDER #</b></td>
                                                    <td class="fs-11"><b>OUTBOUND #</b></td>
                                                    <td class="fs-11"><b>CUSTOMER</b></td>
                                                    <td class="fs-11"><b>OUTBOUND DATE</b></td>
                                                    <td class="fs-11"><b>SUPPLIER</b></td>
                                                    <td class="fs-11"><b>LOCAL/ IMPORT</b></td>
                                                    <td class="fs-11"><b>DESCRIPTION</b></td>
                                                    <td class="fs-11"><b>BRAND</b></td>
                                                    <td class="fs-11"><b>BARCODE</b></td>
                                                    <td class="fs-11"><b>BATCH</b></td>
                                                    <td class="fs-11 text-end"><b>UNIT COST</b></td>
                                                    <td class="fs-11 text-end"><b>GROSS SALE</b></td>
                                                    <td class="fs-11 text-end"><b>NET INCOME</b></td>
                                                </tr>
                                                ';
                                                $item_query = "
                                                    SELECT
                                                        oc.unique_barcode,
                                                        oc.sold_price,
                                                        ol.order_num,
                                                        oc.hashed_id AS outbound_num,
                                                        ol.customer_fullname,
                                                        ol.date_sent,
                                                        sup.supplier_name,
                                                        sup.local_international,
                                                        p.description,
                                                        b.brand_name,
                                                        s.batch_code,
                                                        s.capital
                                                    FROM outbound_content oc
                                                    LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                                                    LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
                                                    LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
                                                    LEFT JOIN product p ON p.hashed_id = s.product_id
                                                    LEFT JOIN brand b ON b.hashed_id = p.brand
                                                    WHERE p.category = '$category_id'
                                                    AND oc.status = 0
                                                    AND s.item_status NOT IN (4, 8)
                                                    AND DATE(ol.date_sent) BETWEEN '$startDate' AND '$endDate'
                                                    AND ol.warehouse = '$warehouse_transaction'
                                                    ";
                                                    $item_res = $conn->query($item_query);
                                                    if($item_res->num_rows>0){
                                                        while($row=$item_res->fetch_assoc()){
                                                            $unique_barcode = $row['unique_barcode'];
                                                            $sold_price = $row['sold_price'];
                                                            $order_num = $row['order_num'];
                                                            $outbound_num = $row['outbound_num'];
                                                            $customer_fullname = $row['customer_fullname'];
                                                            $supplier_name = $row['supplier_name'];
                                                            $local_international = $row['local_international'];
                                                            $description = $row['description'];
                                                            $brand_name = $row['brand_name'];
                                                            $batch_code = $row['batch_code'];
                                                            $capital = $row['capital'];
                                                            $date_sent = $row['date_sent'];
                                                            $net_income = $sold_price - $capital;
                                                            
                                                            echo '
                                                            <tr class="' . $random_bg . '">
                                                                <td class="fs-11"></td>
                                                                <td class="fs-11">' . $order_num . '</td>
                                                                <td class="fs-11">' . $outbound_num . '</td>
                                                                <td class="fs-11">' . $customer_fullname . '</td>
                                                                <td class="fs-11">' . $date_sent . '</td>
                                                                <td class="fs-11">' . $supplier_name . '</td>
                                                                <td class="fs-11">' . $local_international . '</td>
                                                                <td class="fs-11">' . $description . '</td>
                                                                <td class="fs-11">' . $brand_name . '</td>
                                                                <td class="fs-11">' . $unique_barcode . '</td>
                                                                <td class="fs-11">' . $batch_code . '</td>
                                                                <td class="fs-11 text-end">' . $capital . '</td>
                                                                <td class="fs-11 text-end">' . $sold_price . '</td>
                                                                <td class="fs-11 text-end">' . $net_income . '</td>
                                                            </tr>';
                                                        }
                                                    }
                                            }
                                            $num++;
                                            $grand_total_net = $grand_total_gross - $grand_total_unit_cost;
                                            echo '<tr>
                                                <td></td>
                                                <td class="fs-10 text-end pe-3" colspan="9"><b><i>Total</i></b></td>
                                                <td class="fs-10 text-end"><b><i>' . $grand_total_qty . '</i></b></td>
                                                <td class="fs-10 text-end"><b><i>' . $grand_total_unit_cost . '</i></b></td>
                                                <td class="fs-10 text-end"><b><i>' . $grand_total_gross . '</i></b></td>
                                                <td class="fs-10 text-end"><b><i>' . $grand_total_net . '</i></b></td>
                                            </tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="6">No Data Available</td></tr>';
                                    }

                                    ?>
                                </tbody>
                            </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php 
    } else {
        $startDate = date('Y-m-01 00:00:00');
        $endDate = date('Y-m-d H:i:s');

    
    ?>
    <div class="col-xxl-12">
        <div class="card">
            <div class="card-body bg-body-tertiary overflow-hidden ">
                <form action="../Transaction-overview/index" method="POST">
                    <div class="tab-content row">
                        <div class="col-lg-2 mb-3">
                            <label class="form-label" for="start_datepicker">Start Date</label>
                            <input class="form-control datetimepicker fs-11" name="start_date" id="start_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' required/>
                        </div>
                        <div class="col-lg-2 mb-3">
                            <label class="form-label" for="end_datepicker">End Date</label>
                            <input class="form-control datetimepicker fs-11" name="end_date" id="end_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' required/>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select class="form-select selectpicker fs-11" id="category" multiple="multiple" size="1" name="category[]" data-options='{"placeholder":"Select your options"}'>
                                    <option value="">Select staff...</option>
                                    <?php 
                                    $category_sql = "SELECT * FROM category ORDER BY category_name ASC";
                                    $stmt = $conn->prepare($category_sql); // Use prepared statements
                                    $stmt->execute();
                                    $res = $stmt->get_result();
                                    if ($res->num_rows > 0) {
                                        while ($row = $res->fetch_assoc()) {
                                            $category_name = htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8');
                                            $category_id = htmlspecialchars($row['hashed_id'], ENT_QUOTES, 'UTF-8');
                                            echo '<option value="' . $category_id . '">' . $category_name . '</option>'; 
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 mb-3">
                            <label for="warehouse">Warehouse</label>
                            <select class="form-select fs-11" name="warehouse" id="warehouse">
                            <?php echo implode("\n", $warehouse_options2); ?>
                            </select>
                        </div>
                        <div class="col-lg-1 mb-3 pt-4">
                            <button type="submit" class="btn btn-primary mt-1 fs-11">Generate</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xxl-12">
        <div class="card mt-3">
            <div class="card-body bg-body-tertiary">
                <div class="row">
                    <div class="col-lg-11 text-center mb-3">
                        <h3>TRANSACTION OVERVIEW AS OF <?php echo $date_today;?></h3>
                        <h5>All accessible warehouse</h5>
                    </div>
                    <div class="col-lg-1">
                    <a href="url.php?from=<?php echo $startDate;?>&to=<?php echo $endDate;?>&category=>&wh=" class="btn btn-primary fs-11"><span class="fas fa-download"></span></a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 table-responsive">
                            <!-- <table class="table mb-0 data-table fs-10" data-datatables="data-datatables"> -->
                            <table class="table mb-0 fs-10">
                                <thead class="bg-primary">
                                    <tr>
                                        <th class="fs-10 text-white ">#</th>
                                        <th class="fs-10 text-white " colspan="9">CATEGORY</th>
                                        <th class="fs-10 text-white text-end ">QTY</th>
                                        <th class="fs-10 text-white text-end " style="width: 500px;">SUBTOTAL UNIT COST</th>
                                        <th class="fs-10 text-white text-end " style="width: 500px;">SUBTOTAL GROSS SALES</th>
                                        <th class="fs-10 text-white text-end " style="width: 500px;">SUBTOTAL NET INCOME</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $category_query = "
                                    SELECT 
                                        c.hashed_id AS category_id,
                                        c.category_name,
                                        COUNT(oc.unique_barcode) AS outbounded_qty,
                                        SUM(s.capital) AS unit_cost,
                                        SUM(oc.sold_price) AS gross_sale
                                    FROM category c
                                    LEFT JOIN product p ON p.category = c.hashed_id
                                    LEFT JOIN stocks s ON s.product_id = p.hashed_id
                                    LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
                                    LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                                    WHERE
                                    oc.status = 0
                                    AND s.item_status NOT IN (4, 8)
                                    AND MONTH(ol.date_sent) = MONTH(NOW()) AND YEAR(ol.date_sent) = YEAR(NOW())
                                    AND ol.warehouse IN ($user_warehouse_id)
                                    GROUP BY c.category_name
                                    ";

                                    $bg_colors = ['bg-100', 'bg-200', 'bg-300', 'bg-400', 'bg-500', 'bg-600', 'table-primary', 'table-info', 'table-dark', 'table-warning', 'table-success'];
                                    
                                    
                                    
                                    $category_result = $conn->query($category_query);
                                    if($category_result->num_rows>0){
                                        while($row=$category_result->fetch_assoc()){
                                            $random_bg = $bg_colors[array_rand($bg_colors)];
                                            $category_id = $row['category_id'];
                                            $category_name = $row['category_name'];
                                            $outbound_qty = $row['outbounded_qty'];
                                            $sub_unit_cost = $row['unit_cost'];
                                            $sub_gross = $row['gross_sale'];
                                            $sub_netincome = $sub_gross - $sub_unit_cost;
                                            $grand_total_qty += $outbound_qty;
                                            $grand_total_unit_cost += $sub_unit_cost;
                                            $grand_total_gross += $sub_gross;
                                            
                                            echo '
                                            <tr class="' . $random_bg . '">
                                                <td class="fs-10">' . $num . '</td>
                                                <td class="fs-10" colspan="9">'. $category_name .'</td>
                                                <td class="fs-10 text-end">'. $outbound_qty .'</td>
                                                <td class="fs-10 text-end" style="width: 500px;">' . $sub_unit_cost .'</td>
                                                <td class="fs-10 text-end" style="width: 500px;">' . $sub_gross . '</td>
                                                <td class="fs-10 text-end" style="width: 500px;">' . $sub_netincome . '</td>
                                            </tr>
                                            <tr class="' . $random_bg . '">
                                                <td class="fs-11"><b></b></td>
                                                <td class="fs-11"><b>ORDER #</b></td>
                                                <td class="fs-11"><b>OUTBOUND #</b></td>
                                                <td class="fs-11"><b>CUSTOMER</b></td>
                                                <td class="fs-11"><b>OUTBOUND DATE</b></td>
                                                <td class="fs-11"><b>SUPPLIER</b></td>
                                                <td class="fs-11"><b>LOCAL/ IMPORT</b></td>
                                                <td class="fs-11"><b>DESCRIPTION</b></td>
                                                <td class="fs-11"><b>BRAND</b></td>
                                                <td class="fs-11"><b>BARCODE</b></td>
                                                <td class="fs-11"><b>BATCH</b></td>
                                                <td class="fs-11 text-end"><b>UNIT COST</b></td>
                                                <td class="fs-11 text-end"><b>GROSS SALE</b></td>
                                                <td class="fs-11 text-end"><b>NET INCOME</b></td>
                                            </tr>
                                            ';
                                            $item_query = "
                                            SELECT
                                                oc.unique_barcode,
                                                oc.sold_price,
                                                ol.order_num,
                                                oc.hashed_id AS outbound_num,
                                                ol.customer_fullname,
                                                ol.date_sent,
                                                sup.supplier_name,
                                                sup.local_international,
                                                p.description,
                                                b.brand_name,
                                                s.batch_code,
                                                s.capital
                                            FROM outbound_content oc
                                            LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                                            LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
                                            LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
                                            LEFT JOIN product p ON p.hashed_id = s.product_id
                                            LEFT JOIN brand b ON b.hashed_id = p.brand
                                            WHERE p.category = '$category_id'
                                            AND oc.status = 0
                                            AND s.item_status NOT IN (4, 8)
                                            AND MONTH(ol.date_sent) = MONTH(NOW()) AND YEAR(ol.date_sent) = YEAR(NOW())
                                            AND ol.warehouse IN ($user_warehouse_id)
                                            ";
                                            $item_res = $conn->query($item_query);
                                            if($item_res->num_rows>0){
                                                while($row=$item_res->fetch_assoc()){
                                                    $unique_barcode = $row['unique_barcode'];
                                                    $sold_price = $row['sold_price'];
                                                    $order_num = $row['order_num'];
                                                    $outbound_num = $row['outbound_num'];
                                                    $customer_fullname = $row['customer_fullname'];
                                                    $supplier_name = $row['supplier_name'];
                                                    $local_international = $row['local_international'];
                                                    $description = $row['description'];
                                                    $brand_name = $row['brand_name'];
                                                    $batch_code = $row['batch_code'];
                                                    $capital = $row['capital'];
                                                    $date_sent = $row['date_sent'];
                                                    $net_income = $sold_price - $capital;
                                                    
                                                    echo '
                                                    <tr class="' . $random_bg . '">
                                                        <td class="fs-11"></td>
                                                        <td class="fs-11">' . $order_num . '</td>
                                                        <td class="fs-11">' . $outbound_num . '</td>
                                                        <td class="fs-11">' . $customer_fullname . '</td>
                                                        <td class="fs-11">' . $date_sent . '</td>
                                                        <td class="fs-11">' . $supplier_name . '</td>
                                                        <td class="fs-11">' . $local_international . '</td>
                                                        <td class="fs-11">' . $description . '</td>
                                                        <td class="fs-11">' . $brand_name . '</td>
                                                        <td class="fs-11">' . $unique_barcode . '</td>
                                                        <td class="fs-11">' . $batch_code . '</td>
                                                        <td class="fs-11 text-end">' . $capital . '</td>
                                                        <td class="fs-11 text-end">' . $sold_price . '</td>
                                                        <td class="fs-11 text-end">' . $net_income . '</td>
                                                    </tr>';
                                                }
                                            }
                                            $num++;
                                            $grand_total_net = $grand_total_gross - $grand_total_unit_cost;
                                            echo '<tr>
                                                <td></td>
                                                <td class="fs-10 text-end pe-3" colspan="9"><b><i>Total</i></b></td>
                                                <td class="fs-10 text-end"><b><i>' . $grand_total_qty . '</i></b></td>
                                                <td class="fs-10 text-end"><b><i>' . $grand_total_unit_cost . '</i></b></td>
                                                <td class="fs-10 text-end"><b><i>' . $grand_total_gross . '</i></b></td>
                                                <td class="fs-10 text-end"><b><i>' . $grand_total_net . '</i></b></td>
                                            </tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="6">No Data Available</td></tr>';
                                    }

                                    ?>
                                </tbody>
                            </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php 
    }
    ?>
</div>