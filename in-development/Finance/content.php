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
            $imploded_categories = "";
         } else {
            // Implode raw hashed IDs
            $imploded_category = "'" . implode("','", $categories) . "'";
            $imploded_categories = implode(",", $categories);
         }
        $supplier_selected_id = isset($_POST['supplier']) ? $_POST['supplier'] : [];
        $supplier_post_local_internation = $_POST['local_international'];
        
        $suppliers = array_map('trim', $supplier_selected_id);  // Trim each category string
    
         if(empty($suppliers)){
            $imploded_supplier = "";
            $imploded_supplierz = "";
         } else {
            // Implode raw hashed IDs
            $imploded_supplier = "'" . implode("','", $suppliers) . "'";
            $imploded_supplierz = implode(",", $suppliers);
         }


         if(empty($imploded_supplier)){
            $additional_query = "";
         } else {
            $additional_query = "AND s.supplier IN ($imploded_supplier)";
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
                <form action="../Finance/index" method="POST">
                    <div class="tab-content row">
                        <div class="col-lg-4 mb-3">
                            <label class="form-label" for="start_datepicker">Start Date</label>
                            <input class="form-control datetimepicker fs-11" name="start_date" id="start_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' value="<?php echo $startDate;?>" required/>
                        </div>
                        <div class="col-lg-4 mb-3">
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
                        <div class="col-lg-4 mb-3">
                            <label for="warehouse">Warehouse</label>
                            <select class="form-select fs-11" name="warehouse" id="warehouse">
                                <!-- <option value="">All</option> -->
                            <?php echo implode("\n", $warehouse_options2); ?>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label for="category">Supplier</label>
                            <select class="form-select selectpicker fs-11" id="supplier" multiple="multiple" size="1" name="supplier[]" data-options='{"placeholder":"Select your options"}'>
                                <option value="">Select staff...</option>
                                <?php 
                                $supplierz_sql = "SELECT * FROM supplier ORDER BY supplier_name ASC";
                                $stmt = $conn->prepare($supplierz_sql); // Use prepared statements
                                $stmt->execute();
                                $res = $stmt->get_result();
                                if ($res->num_rows > 0) {
                                    while ($row = $res->fetch_assoc()) {
                                        $supplierz_name = htmlspecialchars($row['supplier_name'], ENT_QUOTES, 'UTF-8');
                                        $supplierz_id = htmlspecialchars($row['hashed_id'], ENT_QUOTES, 'UTF-8');
                                        if(strpos($imploded_supplier, $supplierz_id)!==false){
                                            echo '<option value="' . $supplierz_id . '" selected>' . $supplierz_name . '</option>'; 
                                        } else {
                                            echo '<option value="' . $supplierz_id . '">' . $supplierz_name . '</option>'; 
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label for="">Local/ Import</label>
                            <select class="form-select fs-11" name="local_international" id="local_international" required>
                                <option value="">Select option</option>
                                <option value="All" <?php if($supplier_post_local_internation==="All"){ echo "selected";}?>>All</option>
                                <option value="Local" <?php if($supplier_post_local_internation==="Local"){ echo "selected";}?>>Local</option>
                                <option value="International" <?php if($supplier_post_local_internation==="International"){ echo "selected";}?>>International</option>
                            </select>
                        </div>
                        <div class="col-lg-12 mb-3 pt-4">
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
                        <h3>FINANCE</h3> 
                        <p class="mb-0"><?php echo $transaction_warehouse_name;?></p>
                        <small><?php echo $formattedStartDate . " - " . $formattedEndDate;?></small>
                        <h5><?php //echo $outbound_warehouse;?></h5>
                    </div>
                    <div class="col-lg-1">
                        <a href="url.php?from=<?php echo $startDate;?>&to=<?php echo $endDate;?>&category=<?php echo $imploded_categories;?>&wh=<?php echo $warehouse_transaction;?>&supplier=<?php echo $imploded_supplierz?>&sup_type=<?php echo $supplier_post_local_internation;?>" class="btn btn-primary fs-11"><span class="fas fa-download"></span></a>
                    </div>
                </div>
                <!-- ------- -->
                <div class="row">
                    <div class="col-lg-12 table-responsive">
                            <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
                            <table class="table mb-0 fs-10">
                                <thead class="bg-primary">
                                    <tr>
                                        <th class="fs-10 text-white ">#</th>
                                        <th class="fs-10 text-white " >SUPPLIER</th>
                                        <th class="fs-10 text-white " colspan="9">CATEGORY</th>
                                        <th class="fs-10 text-white text-end ">QTY</th>
                                        <th class="fs-10 text-white text-end " style="width: 500px;">SUBTOTAL UNIT COST</th>
                                        <th class="fs-10 text-white text-end " style="width: 500px;">SUBTOTAL GROSS SALES</th>
                                        <th class="fs-10 text-white text-end " style="width: 500px;">SUBTOTAL NET INCOME</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $bg_colors = ['bg-100', 'bg-200', 'bg-300', 'bg-400', 'bg-500', 'bg-600', 'table-primary', 'table-info', 'table-dark', 'table-warning', 'table-success'];
                                    
                                    if($supplier_post_local_internation === "All"){
                                        $supplier_query = "
                                        SELECT 
                                            sup.hashed_id AS supplier_head_id,
                                            sup.supplier_name AS supplier,
                                            sup.local_international AS sup_type,
                                            COUNT(oc.unique_barcode) AS sup_outbounded_qty,
                                            SUM(s.capital) AS sup_unit_cost,
                                            SUM(oc.sold_price) AS sup_gross_sale
                                        FROM supplier sup
                                        LEFT JOIN stocks s ON s.supplier = sup.hashed_id
                                        LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
                                        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                                        WHERE
                                        oc.status IN (0, 6)
                                        AND s.item_status NOT IN (4, 8)
                                        AND MONTH(ol.date_sent) = MONTH(NOW()) AND YEAR(ol.date_sent) = YEAR(NOW())
                                        AND ol.warehouse IN ($user_warehouse_id)
                                        AND ol.status IN (0, 6)
                                        $additional_query
                                        GROUP BY sup.supplier_name
                                        LIMIT 3
                                        ";
                                    } elseif($supplier_post_local_internation === "Local"){
                                        $supplier_query = "
                                        SELECT 
                                            sup.hashed_id AS supplier_head_id,
                                            sup.supplier_name AS supplier,
                                            sup.local_international AS sup_type,
                                            COUNT(oc.unique_barcode) AS sup_outbounded_qty,
                                            SUM(s.capital) AS sup_unit_cost,
                                            SUM(oc.sold_price) AS sup_gross_sale
                                        FROM supplier sup
                                        LEFT JOIN stocks s ON s.supplier = sup.hashed_id
                                        LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
                                        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                                        WHERE
                                        oc.status IN (0, 6)
                                        AND s.item_status NOT IN (4, 8)
                                        AND MONTH(ol.date_sent) = MONTH(NOW()) AND YEAR(ol.date_sent) = YEAR(NOW())
                                        AND ol.warehouse IN ($user_warehouse_id)
                                        AND ol.status IN (0, 6)
                                        AND sup.local_international = 'Local'
                                        $additional_query
                                        GROUP BY sup.supplier_name
                                        LIMIT 3
                                        ";
                                    } elseif($supplier_post_local_internation === "International"){
                                        $supplier_query = "
                                        SELECT 
                                            sup.hashed_id AS supplier_head_id,
                                            sup.supplier_name AS supplier,
                                            sup.local_international AS sup_type,
                                            COUNT(oc.unique_barcode) AS sup_outbounded_qty,
                                            SUM(s.capital) AS sup_unit_cost,
                                            SUM(oc.sold_price) AS sup_gross_sale
                                        FROM supplier sup
                                        LEFT JOIN stocks s ON s.supplier = sup.hashed_id
                                        LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
                                        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                                        WHERE
                                        oc.status IN (0, 6)
                                        AND s.item_status NOT IN (4, 8)
                                        AND MONTH(ol.date_sent) = MONTH(NOW()) AND YEAR(ol.date_sent) = YEAR(NOW())
                                        AND ol.warehouse IN ($user_warehouse_id)
                                        AND ol.status IN (0, 6)
                                        AND sup.local_international = 'International'
                                        $additional_query
                                        GROUP BY sup.supplier_name
                                        LIMIT 3
                                        ";
                                    }
                                    
                                    $supplier_res = $conn->query($supplier_query);
                                    if($supplier_res->num_rows>0){
                                        while($row=$supplier_res->fetch_assoc()){
                                            $random_bg = $bg_colors[array_rand($bg_colors)];
                                            $sup_supplier = $row['supplier'];
                                            $sup_supplierType = $row['sup_type'];
                                            $sup_outboundedQty = $row['sup_outbounded_qty'];
                                            $sup_unitCost = $row['sup_unit_cost'];
                                            $sup_grossSale = $row['sup_gross_sale'];
                                            $sup_supplierHeadId = $row['supplier_head_id'];
                                            $sup_netincome = $sup_grossSale - $sup_unitCost;

                                            echo '
                                            <tr class="' . $random_bg . '">
                                                <td class="fs-10">' . $num . '</td>
                                                <td class="fs-10">' . $sup_supplier . '</td>
                                                <td class="fs-10" colspan="9"></td>
                                                <td class="fs-10 text-end">'. $sup_outboundedQty .'</td>
                                                <td class="fs-10 text-end" style="width: 500px;">' . $sup_unitCost .'</td>
                                                <td class="fs-10 text-end" style="width: 500px;">' . $sup_grossSale . '</td>
                                                <td class="fs-10 text-end" style="width: 500px;">' . $sup_netincome . '</td>
                                            </tr>
                                            ';

                                            if($supplier_post_local_internation === "All"){
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
                                                oc.status IN (0, 6)
                                                AND s.item_status NOT IN (4, 8)
                                                AND DATE(ol.date_sent) BETWEEN '$startDate' AND '$endDate'
                                                AND ol.warehouse = '$warehouse_transaction'
                                                AND ol.status IN (0, 6)
                                                AND s.supplier = '$sup_supplierHeadId'
                                                GROUP BY c.category_name
                                                LIMIT 3
                                                ";
                                            } elseif($supplier_post_local_internation === "Local"){
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
                                                LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
                                                LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
                                                LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                                                WHERE
                                                oc.status IN (0, 6)
                                                AND s.item_status NOT IN (4, 8)
                                                AND DATE(ol.date_sent) BETWEEN '$startDate' AND '$endDate'
                                                AND ol.warehouse = '$warehouse_transaction'
                                                AND ol.status IN (0, 6)
                                                AND sup.local_international = 'Local'
                                                AND s.supplier = '$sup_supplierHeadId'
                                                GROUP BY c.category_name
                                                LIMIT 3
                                                ";
                                            } elseif($supplier_post_local_internation === "International"){
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
                                                LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
                                                LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
                                                LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                                                WHERE
                                                oc.status IN (0, 6)
                                                AND s.item_status NOT IN (4, 8)
                                                AND DATE(ol.date_sent) BETWEEN '$startDate' AND '$endDate'
                                                AND ol.warehouse = '$warehouse_transaction'
                                                AND ol.status IN (0, 6)
                                                AND sup.local_international = 'International'
                                                AND s.supplier = '$sup_supplierHeadId'
                                                GROUP BY c.category_name
                                                LIMIT 3
                                                ";
                                            }
                                            

                                            
                                            
                                            $category_result = $conn->query($category_query);
                                            if($category_result->num_rows>0){
                                                while($row=$category_result->fetch_assoc()){
                                                    // $random_bg = $bg_colors[array_rand($bg_colors)];
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
                                                                <td class="fs-10"></td>
                                                                <td class="fs-10"></td>
                                                                <td class="fs-10" colspan="9">'. $category_name .'</td>
                                                                <td class="fs-10 text-end">'. $outbound_qty .'</td>
                                                                <td class="fs-10 text-end" style="width: 500px;">' . $sub_unit_cost .'</td>
                                                                <td class="fs-10 text-end" style="width: 500px;">' . $sub_gross . '</td>
                                                                <td class="fs-10 text-end" style="width: 500px;">' . $sub_netincome . '</td>
                                                            </tr>
                                                            <tr class="' . $random_bg . '">
                                                                <td class="fs-11"><b></b></td>
                                                                <td class="fs-11"><b></b></td>
                                                                <td class="fs-11"><b>ORDER #</b></td>
                                                                <td class="fs-11"><b>ORDER LINE ID</b></td>
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

                                                            if($supplier_post_local_internation === "All"){
                                                                $item_query = "
                                                                SELECT
                                                                    oc.unique_barcode,
                                                                    oc.sold_price,
                                                                    ol.order_line_id,
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
                                                                AND oc.status IN (0, 6)
                                                                AND s.item_status NOT IN (4, 8)
                                                                AND DATE(ol.date_sent) BETWEEN '$startDate' AND '$endDate'
                                                                AND ol.warehouse = '$warehouse_transaction'
                                                                AND ol.status IN (0, 6)
                                                                AND s.supplier = '$sup_supplierHeadId'
                                                                LIMIT 3
                                                                ";
                                                            } elseif($supplier_post_local_internation === "Local"){
                                                                $item_query = "
                                                                SELECT
                                                                    oc.unique_barcode,
                                                                    oc.sold_price,
                                                                    ol.order_line_id,
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
                                                                AND oc.status IN (0, 6)
                                                                AND s.item_status NOT IN (4, 8)
                                                                AND DATE(ol.date_sent) BETWEEN '$startDate' AND '$endDate'
                                                                AND ol.warehouse = '$warehouse_transaction'
                                                                AND ol.status IN (0, 6)
                                                                AND sup.local_international = 'Local'
                                                                AND s.supplier = '$sup_supplierHeadId'
                                                                LIMIT 3
                                                                ";
                                                            } elseif($supplier_post_local_internation === "International"){
                                                                $item_query = "
                                                                SELECT
                                                                    oc.unique_barcode,
                                                                    oc.sold_price,
                                                                    ol.order_line_id,
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
                                                                AND oc.status IN (0, 6)
                                                                AND s.item_status NOT IN (4, 8)
                                                                AND DATE(ol.date_sent) BETWEEN '$startDate' AND '$endDate'
                                                                AND ol.warehouse = '$warehouse_transaction'
                                                                AND ol.status IN (0, 6)
                                                                AND sup.local_international = 'International'
                                                                AND s.supplier = '$sup_supplierHeadId'
                                                                LIMIT 3
                                                                ";
                                                            }

                                                            
                                                            $item_res = $conn->query($item_query);
                                                            if($item_res->num_rows>0){
                                                                while($row=$item_res->fetch_assoc()){
                                                                    $unique_barcode = $row['unique_barcode'];
                                                                    $sold_price = $row['sold_price'];
                                                                    $order_line_id = $row['order_line_id'];
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
                                                                        <td class="fs-11"><b></b></td>
                                                                        <td class="fs-11">' . $order_num . '</td>
                                                                        <td class="fs-11">' . $order_line_id . '</td>
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
                                                            <td class="fs-10"></td>
                                                            <td class="fs-11"></td>
                                                            <td class="fs-10" colspan="9">'. $category_name .'</td>
                                                            <td class="fs-10 text-end">'. $outbound_qty .'</td>
                                                            <td class="fs-10 text-end" style="width: 500px;">' . $sub_unit_cost .'</td>
                                                            <td class="fs-10 text-end" style="width: 500px;">' . $sub_gross . '</td>
                                                            <td class="fs-10 text-end" style="width: 500px;">' . $sub_netincome . '</td>
                                                        </tr>
                                                        <tr class="' . $random_bg . '">
                                                            <td class="fs-11"><b></b></td>
                                                            <td class="fs-11"><b></b></td>
                                                            <td class="fs-11"><b>ORDER #</b></td>
                                                            <td class="fs-11"><b>ORDER LINE ID</b></td>
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

                                                        if($supplier_post_local_internation === "All"){
                                                            $item_query = "
                                                            SELECT
                                                                oc.unique_barcode,
                                                                oc.sold_price,
                                                                ol.order_line_id,
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
                                                            AND oc.status IN (0, 6)
                                                            AND s.item_status NOT IN (4, 8)
                                                            AND DATE(ol.date_sent) BETWEEN '$startDate' AND '$endDate'
                                                            AND ol.warehouse = '$warehouse_transaction'
                                                            AND ol.status IN (0, 6)
                                                            AND s.supplier = '$sup_supplierHeadId'
                                                            LIMIT 3
                                                            ";
                                                        } elseif($supplier_post_local_internation === "Local"){
                                                            $item_query = "
                                                            SELECT
                                                                oc.unique_barcode,
                                                                oc.sold_price,
                                                                ol.order_line_id,
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
                                                            AND oc.status IN (0, 6)
                                                            AND s.item_status NOT IN (4, 8)
                                                            AND DATE(ol.date_sent) BETWEEN '$startDate' AND '$endDate'
                                                            AND ol.warehouse = '$warehouse_transaction'
                                                            AND ol.status IN (0, 6)
                                                            AND sup.local_international = 'Local'
                                                            AND s.supplier = '$sup_supplierHeadId'
                                                            LIMIT 3
                                                            ";
                                                        } elseif($supplier_post_local_internation === "International"){
                                                            $item_query = "
                                                            SELECT
                                                                oc.unique_barcode,
                                                                oc.sold_price,
                                                                ol.order_line_id,
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
                                                            AND oc.status IN (0, 6)
                                                            AND s.item_status NOT IN (4, 8)
                                                            AND DATE(ol.date_sent) BETWEEN '$startDate' AND '$endDate'
                                                            AND ol.warehouse = '$warehouse_transaction'
                                                            AND ol.status IN (0, 6)
                                                            AND sup.local_international = 'International'
                                                            AND s.supplier = '$sup_supplierHeadId'
                                                            LIMIT 3
                                                            ";
                                                        }
                                                    
                                                            $item_res = $conn->query($item_query);
                                                            if($item_res->num_rows>0){
                                                                while($row=$item_res->fetch_assoc()){
                                                                    $unique_barcode = $row['unique_barcode'];
                                                                    $sold_price = $row['sold_price'];
                                                                    $order_line_id = $row['order_line_id'];
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
                                                                        <td class="fs-11"></td>
                                                                        <td class="fs-11">' . $order_num . '</td>
                                                                        <td class="fs-11">' . $order_line_id . '</td>
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
                                                    
                                                }
                                            } else {
                                                echo '<tr><td colspan="6">No Data Available</td></tr>';
                                            }
                                        }    
                                        // $grand_total_net = $grand_total_gross - $grand_total_unit_cost;
                                        // echo '<tr>
                                        //     <td class="fs-10 text-start pe-3" colspan="11"><b><i>Total</i></b></td>
                                        //     <td class="fs-10 text-end"><b><i>' . $grand_total_qty . '</i></b></td>
                                        //     <td class="fs-10 text-end"><b><i>' . $grand_total_unit_cost . '</i></b></td>
                                        //     <td class="fs-10 text-end"><b><i>' . $grand_total_gross . '</i></b></td>
                                        //     <td class="fs-10 text-end"><b><i>' . $grand_total_net . '</i></b></td>
                                        // </tr>';
                                    } else {
                                        echo '<tr>
                                                        <td></td>
                                                        <td class="fs-10 text-end pe-3" colspan="14"><b><i>Nothing Matched</i></b></td>
                                                    </tr>';
                                    }

                                    ?>
                                </tbody>
                            </table>
                    </div>
                </div>
                <!-- ----------------- -->
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
                <form action="../Finance/index" method="POST">
                    <div class="tab-content row">
                        <div class="col-lg-4 mb-3">
                            <label class="form-label" for="start_datepicker">Start Date</label>
                            <input class="form-control datetimepicker fs-11" name="start_date" id="start_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' required/>
                        </div>
                        <div class="col-lg-4 mb-3">
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
                        <div class="col-lg-4 mb-3">
                            <label for="warehouse">Warehouse</label>
                            <select class="form-select fs-11" name="warehouse" id="warehouse">
                                <!-- <option value="">All</option> -->
                            <?php echo implode("\n", $warehouse_options2); ?>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label for="category">Supplier</label>
                            <select class="form-select selectpicker fs-11" id="supplier" multiple="multiple" size="1" name="supplier[]" data-options='{"placeholder":"Select your options"}'>
                                <option value="">Select staff...</option>
                                <?php 
                                $supplierz_sql = "SELECT * FROM supplier ORDER BY supplier_name ASC";
                                $stmt = $conn->prepare($supplierz_sql); // Use prepared statements
                                $stmt->execute();
                                $res = $stmt->get_result();
                                if ($res->num_rows > 0) {
                                    while ($row = $res->fetch_assoc()) {
                                        $supplierz_name = htmlspecialchars($row['supplier_name'], ENT_QUOTES, 'UTF-8');
                                        $supplierz_id = htmlspecialchars($row['hashed_id'], ENT_QUOTES, 'UTF-8');
                                        echo '<option value="' . $supplierz_id . '">' . $supplierz_name . '</option>'; 
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label for="">Local/ Import</label>
                            <select class="form-select fs-11" name="local_international" id="local_international" required>
                                <option value="">Select option</option>
                                <option value="All">All</option>
                                <option value="Local">Local</option>
                                <option value="International">International</option>
                            </select>
                        </div>
                        <div class="col-lg-12 text-end mb-3 pt-4">
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
                        <h3>FINANCE AS OF <?php echo $date_today;?></h3>
                        <h5>All accessible warehouse</h5>
                    </div>
                    <div class="col-lg-1">
                    <a href="url.php?from=<?php echo $startDate;?>&to=<?php echo $endDate;?>&category=&wh=&supplier=&sup_type=All" class="btn btn-primary fs-11"><span class="fas fa-download"></span></a>
                    </div>
                </div>
                <!-- --------------- -->
                <div class="row">
                    <div class="col-lg-12 table-responsive">
                            <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
                            <table class="table mb-0 fs-10">
                                <thead class="bg-primary">
                                    <tr>
                                        <th class="fs-10 text-white ">#</th>
                                        <th class="fs-10 text-white " >SUPPLIER</th>
                                        <th class="fs-10 text-white " colspan="9">CATEGORY</th>
                                        <th class="fs-10 text-white text-end ">QTY</th>
                                        <th class="fs-10 text-white text-end " style="width: 500px;">SUBTOTAL UNIT COST</th>
                                        <th class="fs-10 text-white text-end " style="width: 500px;">SUBTOTAL GROSS SALES</th>
                                        <th class="fs-10 text-white text-end " style="width: 500px;">SUBTOTAL NET INCOME</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $bg_colors = ['bg-100', 'bg-200', 'bg-300', 'bg-400', 'bg-500', 'bg-600', 'table-primary', 'table-info', 'table-dark', 'table-warning', 'table-success'];
                                    
                                    $supplier_query = "
                                    SELECT 
                                        sup.hashed_id AS supplier_head_id,
                                        sup.supplier_name AS supplier,
                                        sup.local_international AS sup_type,
                                        COUNT(oc.unique_barcode) AS sup_outbounded_qty,
                                        SUM(s.capital) AS sup_unit_cost,
                                        SUM(oc.sold_price) AS sup_gross_sale
                                    FROM supplier sup
                                    LEFT JOIN stocks s ON s.supplier = sup.hashed_id
                                    LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
                                    LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                                    WHERE
                                    oc.status IN (0, 6)
                                    AND s.item_status NOT IN (4, 8)
                                    AND MONTH(ol.date_sent) = MONTH(NOW()) AND YEAR(ol.date_sent) = YEAR(NOW())
                                    AND ol.warehouse IN ($user_warehouse_id)
                                    AND ol.status IN (0, 6)
                                    GROUP BY sup.supplier_name
                                    LIMIT 3
                                    ";
                                    $supplier_res = $conn->query($supplier_query);
                                    if($supplier_res->num_rows>0){
                                        while($row=$supplier_res->fetch_assoc()){
                                            $random_bg = $bg_colors[array_rand($bg_colors)];
                                            $sup_supplier = $row['supplier'];
                                            $sup_supplierType = $row['sup_type'];
                                            $sup_outboundedQty = $row['sup_outbounded_qty'];
                                            $sup_unitCost = $row['sup_unit_cost'];
                                            $sup_grossSale = $row['sup_gross_sale'];
                                            $sup_supplierHeadId = $row['supplier_head_id'];
                                            $sup_netincome = $sup_grossSale - $sup_unitCost;

                                            echo '
                                            <tr class="' . $random_bg . '">
                                                <td class="fs-10">' . $num . '</td>
                                                <td class="fs-10">' . $sup_supplier . '</td>
                                                <td class="fs-10" colspan="9"></td>
                                                <td class="fs-10 text-end">'. $sup_outboundedQty .'</td>
                                                <td class="fs-10 text-end" style="width: 500px;">' . $sup_unitCost .'</td>
                                                <td class="fs-10 text-end" style="width: 500px;">' . $sup_grossSale . '</td>
                                                <td class="fs-10 text-end" style="width: 500px;">' . $sup_netincome . '</td>
                                            </tr>
                                            ';

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
                                            oc.status IN (0, 6)
                                            AND s.item_status NOT IN (4, 8)
                                            AND MONTH(ol.date_sent) = MONTH(NOW()) AND YEAR(ol.date_sent) = YEAR(NOW())
                                            AND ol.warehouse IN ($user_warehouse_id)
                                            AND ol.status IN (0, 6)
                                            AND s.supplier = '$sup_supplierHeadId'
                                            GROUP BY c.category_name
                                            LIMIT 3
                                            ";

                                            
                                            
                                            $category_result = $conn->query($category_query);
                                            if($category_result->num_rows>0){
                                                while($row=$category_result->fetch_assoc()){
                                                    
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
                                                        <td class="fs-10"></td>
                                                        <td class="fs-10"></td>
                                                        <td class="fs-10" colspan="9">'. $category_name .'</td>
                                                        <td class="fs-10 text-end">'. $outbound_qty .'</td>
                                                        <td class="fs-10 text-end" style="width: 500px;">' . $sub_unit_cost .'</td>
                                                        <td class="fs-10 text-end" style="width: 500px;">' . $sub_gross . '</td>
                                                        <td class="fs-10 text-end" style="width: 500px;">' . $sub_netincome . '</td>
                                                    </tr>
                                                    <tr class="' . $random_bg . '">
                                                        <td class="fs-11"><b></b></td>
                                                        <td class="fs-11"><b></b></td>
                                                        <td class="fs-11"><b>ORDER #</b></td>
                                                        <td class="fs-11"><b>ORDER LINE ID</b></td>
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
                                                        ol.order_line_id,
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
                                                    AND oc.status IN (0, 6)
                                                    AND s.item_status NOT IN (4, 8)
                                                    AND MONTH(ol.date_sent) = MONTH(NOW()) AND YEAR(ol.date_sent) = YEAR(NOW())
                                                    AND ol.warehouse IN ($user_warehouse_id)
                                                    AND s.supplier = '$sup_supplierHeadId'
                                                    AND ol.status IN (0, 6)
                                                    LIMIT 3
                                                    ";
                                                    $item_res = $conn->query($item_query);
                                                    if($item_res->num_rows>0){
                                                        while($row=$item_res->fetch_assoc()){
                                                            $unique_barcode = $row['unique_barcode'];
                                                            $sold_price = $row['sold_price'];
                                                            $order_line_id = $row['order_line_id'];
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
                                                                <td class="fs-11"></td>
                                                                <td class="fs-11">' . $order_num . '</td>
                                                                <td class="fs-11">' . $order_line_id . '</td>
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
                                                    
                                                }
                                            } else {
                                                echo '<tr><td colspan="6">No Data Available</td></tr>';
                                            }
                                        }
                                    }

                                    // $grand_total_net = $grand_total_gross - $grand_total_unit_cost;
                                    // echo '<tr>
                                    //     <td class="fs-10 text-start pe-3" colspan="11"><b><i>Total</i></b></td>
                                    //     <td class="fs-10 text-end"><b><i>' . $grand_total_qty . '</i></b></td>
                                    //     <td class="fs-10 text-end"><b><i>' . $grand_total_unit_cost . '</i></b></td>
                                    //     <td class="fs-10 text-end"><b><i>' . $grand_total_gross . '</i></b></td>
                                    //     <td class="fs-10 text-end"><b><i>' . $grand_total_net . '</i></b></td>
                                    // </tr>';

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

<script>
document.addEventListener("DOMContentLoaded", function () {

    const startInput = document.getElementById("start_datepicker");
    const endInput = document.getElementById("end_datepicker");
    const form = document.querySelector("form");
    const generateBtn = document.querySelector("button[type='submit']");

    let alertShown = false; // prevent repeated alerts

    function getDayDifference() {
        if (!startInput.value || !endInput.value) return null;

        const startDate = new Date(startInput.value);
        const endDate = new Date(endInput.value);

        if (isNaN(startDate) || isNaN(endDate)) return null;

        const diffTime = endDate - startDate;
        return diffTime / (1000 * 60 * 60 * 24);
    }

    function validateDates(showAlert = false) {
        const diffDays = getDayDifference();

        if (diffDays === null) {
            generateBtn.disabled = true;
            return;
        }

        if (diffDays < 0) {
            generateBtn.disabled = true;

            if (showAlert) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: 'End date must be after start date.'
                });
            }
            return;
        }

        if (diffDays > 31) {
            generateBtn.disabled = true;

            if (!alertShown) {
                alertShown = true;

                Swal.fire({
                    icon: 'warning',
                    title: 'Date Range Exceeded',
                    text: 'The selected date range cannot exceed 31 days (1 month).'
                });
            }

            return;
        }

        // ✅ Valid range
        alertShown = false;
        generateBtn.disabled = false;
    }

    startInput.addEventListener("input", validateDates);
    endInput.addEventListener("input", validateDates);

    form.addEventListener("submit", function (e) {
        const diffDays = getDayDifference();

        if (diffDays === null || diffDays < 0 || diffDays > 31) {
            e.preventDefault();
            validateDates(true);
        }
    });

    generateBtn.disabled = true;
});
</script>