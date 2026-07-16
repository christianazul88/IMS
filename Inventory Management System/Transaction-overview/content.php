<?php 
$show = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $show = true;

    $additional_query = "";
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] . " 00:00:00" : null;
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] . " 23:59:59" : null;
    $warehouse_transaction = isset($_POST['warehouse']) ? trim($_POST['warehouse']) : 'ALL';
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
        $additional_query .= "AND p.category IN ($imploded_category)";
    }


    if($warehouse_transaction === "all"){
        $warehouse_transactions = $user_warehouse_id; // Use the array of warehouse IDs
    } else {
        $warehouse_transactions = "'" . $warehouse_transaction . "'"; // Use the single warehouse ID
    }

    $additional_query .= " AND ol.warehouse IN ($warehouse_transactions)";


    $_SESSION['warehouse_transaction_overview'] = $warehouse_transactions; // Store the warehouse transaction in session
    
    if($_POST['warehouse'] === 'all'){
        $transaction_warehouse_name = "All your accessible warehouse(s)";
    } else {
        $transaction_warehouse_name = "";
        $transaction_warehouse_query = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$warehouse_transaction' LIMIT 1";
        $transaction_warehouse_res = $conn->query($transaction_warehouse_query);
        if($transaction_warehouse_res->num_rows>0){
            $row=$transaction_warehouse_res->fetch_assoc();
            $transaction_warehouse_name = $row['warehouse_name'];
        }
    }

    $_SESSION['transaction_overview_additional'] = $additional_query;
    


    // for debugging purposes only
    // echo $additional_query;
}
?>
<div class="row">
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
                                <option value="all">All</option>
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


    <!-- Once form is submitted -->
    <?php 
    if($show === true){
    ?>
    <div class="col-xxl-12 mt-3">
        <div class="card">
            <div class="card-header bg-primary text-white">

                <h5 class="mb-0">
                    <i class="fas fa-file-alt me-2"></i>
                    Report Summary
                </h5>

            </div>
            <div class="card-body">
                <?php 
                $query = "
                    SELECT
                        class.classification_name,
                        c.category_name,
                        ol.order_num,
                        ol.hashed_id as outbound_num,
                        ol.customer_fullname,
                        ol.date_sent,
                        sup.supplier_name,
                        sup.local_international,
                        w.warehouse_name,
                        p.description,
                        b.brand_name,
                        s.unique_barcode,
                        s.batch_code,
                        u.user_fname,
                        u.user_lname,
                        oc.status AS outbound_status,
                        s.capital,
                        oc.sold_price
                    FROM outbound_content oc
                    INNER JOIN stocks s
                        ON s.unique_barcode = oc.unique_barcode
                    LEFT JOIN outbound_logs ol
                        ON ol.hashed_id = oc.hashed_id
                    LEFT JOIN product p
                        ON p.hashed_id = s.product_id
                    LEFT JOIN brand b
                        ON b.hashed_id = p.brand
                    LEFT JOIN category c
                        ON c.hashed_id = p.category
                    LEFT JOIN classification class
                        ON class.hashed_id = c.classification_id
                    LEFT JOIN users u
                        ON u.hashed_id = ol.user_id
                    LEFT JOIN warehouse w
                        ON w.hashed_id = ol.warehouse
                    LEFT JOIN supplier sup
                        ON sup.hashed_id = s.supplier
                    WHERE
                        ol.date_sent BETWEEN '$startDate' AND '$endDate'
                        $additional_query
                    LIMIT 1
                ";

                $result = $conn->query($query);
                if($result->num_rows>0){
                ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1">
                            <i class="fas fa-chart-bar text-primary me-2"></i>
                            Transaction Overview Report
                        </h4>
                        <p class="text-muted mb-0 fs-10">
                            Review your selected filters before downloading the generated report.
                        </p>
                    </div>
                </div>

                <div class="row g-3 mb-4">

                    <div class="col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block">Date Range</small>
                            <strong><?= $formattedStartDate ?></strong>
                            <div>to</div>
                            <strong><?= $formattedEndDate ?></strong>
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block">Warehouse</small>
                            <strong><?= htmlspecialchars($transaction_warehouse_name) ?></strong>
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block">Categories</small>
                            <strong>
                                <?= empty($categories) ? "All Categories" : count($categories) . " Selected"; ?>
                            </strong>
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block">Status</small>
                            <span class="badge bg-success">
                                Ready to Generate
                            </span>
                        </div>
                    </div>

                </div>

                <div class="text-center py-5">

                    <i class="fas fa-file-csv fa-4x text-success mb-3"></i>

                    <h4 class="mb-2">
                        Report is Ready
                    </h4>

                    <p class="text-muted">
                        Your report has matching records and is ready for download.
                    </p>

                    <a
                        href="url.php?startdate=<?= urlencode($startDate) ?>&enddate=<?= urlencode($endDate) ?>"
                        class="btn btn-success btn-lg px-5">

                        <i class="fas fa-download me-2"></i>
                        Download CSV Report

                    </a>

                </div>
                <?php
                } else {
                ?>
                <div class="text-center py-6">

                    <i class="fas fa-folder-open fa-5x text-secondary mb-4"></i>

                    <h3>No Transactions Found</h3>

                    <p class="text-muted">
                        No outbound transactions matched your selected filters.
                        <br>
                        Try selecting a different date range, warehouse, or category.
                    </p>

                </div>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
    <?php
    }
    ?>
</div>