<?php
// Function to sanitize inputs
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barcodeKeyword = isset($_POST['barcode_keyword']) ? sanitizeInput($_POST['barcode_keyword']) : null;
    $startDate = isset($_POST['start_date']) ? sanitizeInput($_POST['start_date']) : null;
    $endDate = isset($_POST['end_date']) ? sanitizeInput($_POST['end_date']) : null;
    $selectedUsers = isset($_POST['multiple_users']) ? array_map('sanitizeInput', $_POST['multiple_users']) : [];
    $warehouse_selected = isset($_POST['warehouse']) ? sanitizeInput($_POST['warehouse']) : null;

    // Validate date format
    $startDateObj = DateTime::createFromFormat('d/m/y', $startDate);
    $endDateObj = DateTime::createFromFormat('d/m/y', $endDate);
    $startDateSQL = $startDateObj ? $startDateObj->format('Y-m-d') : null;
    $endDateSQL = $endDateObj ? $endDateObj->format('Y-m-d') . ' 23:59:59' : null;

    // Start building query
    $query = "SELECT ol.hashed_id, 
    ol.date_sent, 
    w.warehouse_name, 
    ol.customer_fullname, 
    c.courier_name, 
    lp.logistic_name, 
    ol.order_num, 
    ol.order_line_id,  
    u.user_fname,
    u.user_lname
    FROM outbound_logs ol 
    LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
    LEFT JOIN courier c ON c.hashed_id = ol.courier
    LEFT JOIN logistic_partner lp ON lp.hashed_id = ol.platform
    LEFT JOIN users u ON u.hashed_id = ol.user_id
    WHERE 1=1";

    // Add date filter
    if ($startDateSQL && $endDateSQL) {
        $query .= " AND ol.date_sent BETWEEN '$startDateSQL' AND '$endDateSQL'";
    }

    // Add warehouse filter
    if ($warehouse_selected) {
        $query .= " AND ol.warehouse = '$warehouse_selected'";
    }
    
    if (!empty($barcodeKeyword)) {
        $query .= " AND ol.unique_barcode LIKE '%$barcodeKeyword%'";
    }
    

    // Add user filter
    if (!empty($selectedUsers)) {
        $selectedUsers = array_filter($selectedUsers, fn($user) => $user !== "Select staff...");
        if (!empty($selectedUsers)) {
            $userIds = "'" . implode("','", $selectedUsers) . "'";
            $query .= " AND ol.user_id IN ($userIds)";
        }
    }
    
    // Execute query
    $result = $conn->query($query);
    if ($result) {
        while($row = $result->fetch_assoc()){
            $outbound_id = $row['hashed_id'];
            $outbound_date = $row['date_sent'];
            $outbound_warehouse = $row['warehouse_name'];
            $outbound_customer = $row['customer_fullname'];
            $courier_name = $row['courier_name'];
            $platform = $row['logistic_name'];
            $order_number = $row['order_num'];
            $order_line_id = $row['order_line_id'];
            $staff_fullname = $row['user_fname'] . " " . $row['user_lname'];
    ?>
    <div class="card">
        <div class="card-body bg-body-tertiary overflow-hidden p-1">
            <form action="../Transaction-overview/index.php" method="POST">
                <div class="tab-content row">
                    <div class="col-lg-2 mb-3">
                        <label for="barcode_keyword">Filter by Barcode /Keyword</label>
                        <input type="text" name="barcode_keyword" id="barcode_keyword" class="form-control" />
                    </div>
                    <div class="col-lg-2 mb-3">
                        <label class="form-label" for="start_datepicker">Start Date</label>
                        <input class="form-control datetimepicker" name="start_date" id="start_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' />
                    </div>
                    <div class="col-lg-2 mb-3">
                        <label class="form-label" for="end_datepicker">End Date</label>
                        <input class="form-control datetimepicker" name="end_date" id="end_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' />
                    </div>
                    <div class="col-lg-2 mb-3">
                        <label for="staff_name">Staff Name</label>
                        <select class="form-select js-choice" id="staff_name" multiple="multiple" size="1" name="multiple_users[]" data-options='{"removeItemButton":true,"placeholder":true}'>
                            <option value="">Select staff...</option>
                            <?php 
                            $staff_sql = "SELECT * FROM users ORDER BY user_lname ASC";
                            $stmt = $conn->prepare($staff_sql); // Use prepared statements
                            $stmt->execute();
                            $res = $stmt->get_result();
                            if ($res->num_rows > 0) {
                                while ($row = $res->fetch_assoc()) {
                                    $staff_name = htmlspecialchars($row['user_lname'] . ", " . $row['user_fname'], ENT_QUOTES, 'UTF-8');
                                    $staff_userid = htmlspecialchars($row['hashed_id'], ENT_QUOTES, 'UTF-8');
                                    echo '<option value="' . $staff_userid . '">' . $staff_name . '</option>'; 
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-lg-2 mb-3">
                        <label for="warehouse">Warehouse</label>
                        <select class="form-select" name="warehouse" id="warehouse">
                        <?php echo implode("\n", $warehouse_options2); ?>
                        </select>
                    </div>
                    <div class="col-lg-2 mb-3 pt-4">
                        <button type="submit" class="btn btn-primary mt-1">Generate</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="row my-3">
        <div class="col-lg-12 text-center">
            <form action="" method="POST">
            <button class="btn btn-warning">Save Report as PDF</button>
            </form>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-body bg-body-tertiary overflow-hidden">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <h3>TRANSACTION OVERVIEW</h3>
                    <h5><?php echo $outbound_warehouse;?></h5>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-lg-12">
                    <table class="table table-sm">
                        <tr>
                            <th>PREPARED BY:</th>
                            <td><?php echo $user_fullname; ?></td>
                            <th>FROM:</th>
                            <td><?php echo date('F j, Y', strtotime($startDate)); ?></td>
                            <th>TO:</th>
                            <td><?php echo date('F j, Y', strtotime($endDate)); ?></td>
                        </tr>

                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="table-responsive">
                        <table class="table bordered-table table-bordered table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th></th>
                                    <th>DESCRIPTION</th>
                                    <th>BRAND</th>
                                    <th>CATEGORY</th>
                                    <th>BARCODE</th>
                                    <th>KEYWORD</th>
                                    <th>OUTBOUND DATE</th>
                                    <th>CAPITAL</th>
                                    <th>SOLD AMOUNT</th>
                                    <th>OUTBOUND ID</th>
                                    <th>ORDER NO</th>
                                    <th>ORDER LINE ID</th>
                                    <th>COURIER</th>
                                    <th>LOGISTIC</th>
                                    <th>CUSTOMER</th>
                                    <th style="min-width: 150px;">STAFF</th>
                                </tr>
                            </thead>
                            <tbody>
    <?php
            $query = "
            SELECT oc.unique_barcode, oc.sold_price, p.description, b.brand_name, c.category_name, s.capital, p.keyword
            FROM outbound_content oc
            LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
            LEFT JOIN product p ON p.hashed_id = s.product_id
            LEFT JOIN brand b ON b.hashed_id = p.brand
            LEFT JOIN category c ON c.hashed_id = p.category
            WHERE oc.hashed_id = '$outbound_id'
            ";
            $result = $conn->query($query);
            if($result->num_rows>0){
                while($row=$result->fetch_assoc()){
                    $unique_barcode = $row['unique_barcode'];
                    $sold_amount = $row['sold_price'];
                    $product_description = $row['description'];
                    $brand_name = $row['brand_name'];
                    $category_name = $row['category_name'];
                    $capital = $row['capital'];
                    $keyword = $row['keyword'];
                    if(isset($_POST['barcode_keyword'])){
                        $filter = $_POST['barcode_keyword'];
                        if(strpos($unique_barcode, $filter)!==false || strpos($keyword, $filter)!==false){
                            ?>
                            <tr>
                                <td></td>
                                <td><?php echo $product_description;?></td>
                                <td><?php echo $brand_name;?></td>
                                <td><?php echo $category_name;?></td>
                                <td><?php echo $unique_barcode;?></td>
                                <td><?php echo $keyword;?></td>
                                <td style="min-width: 200px;"><?php echo $outbound_date;?></td>
                                <td><?php echo $capital;?></td>
                                <td style="min-width: 150px;"><?php echo $sold_amount;?></td>
                                <td style="min-width: 150px;"><?php echo $outbound_id;?></td>
                                <td style="min-width: 150px;"><?php echo $order_number;?></td>
                                <td style="min-width: 150px;"><?php echo $order_line_id;?></td>
                                <td><?php echo $courier_name;?></td>
                                <td><?php echo $platform;?></td>
                                <td><?php echo $outbound_customer;?></td>
                                <td><?php echo $staff_fullname;?></td>
                            </tr>
                            <?php
                        }
                    } else {
                    ?>
                    <tr>
                        <td></td>
                        <td><?php echo $product_description;?></td>
                        <td><?php echo $brand_name;?></td>
                        <td><?php echo $category_name;?></td>
                        <td><?php echo $unique_barcode;?></td>
                        <td><?php echo $keyword;?></td>
                        <td style="min-width: 200px;"><?php echo $outbound_date;?></td>
                        <td><?php echo $capital;?></td>
                        <td style="min-width: 150px;"><?php echo $sold_amount;?></td>
                        <td style="min-width: 150px;"><?php echo $outbound_id;?></td>
                        <td style="min-width: 150px;"><?php echo $order_number;?></td>
                        <td style="min-width: 150px;"><?php echo $order_line_id;?></td>
                        <td><?php echo $courier_name;?></td>
                        <td><?php echo $platform;?></td>
                        <td><?php echo $outbound_customer;?></td>
                        <td><?php echo $staff_fullname;?></td>
                    </tr>
                    <?php
                    }
                }
            }
        }
        
    } else {
        echo "Error: " . mysqli_error($conn);
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
?>
<div class="card">
    <div class="card-body bg-body-tertiary overflow-hidden ">
        <form action="../Transaction-overview/index.php" method="POST">
            <div class="tab-content row">
                <div class="col-lg-2 mb-3">
                    <label class="fs--6" for="barcode_keyword">Filter by Barcode /Keyword</label>
                    <input type="text" name="barcode_keyword" id="barcode_keyword" class="form-control" />
                </div>
                <div class="col-lg-2 mb-3">
                    <label class="form-label" for="start_datepicker">Start Date</label>
                    <input class="form-control datetimepicker" name="start_date" id="start_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' />
                </div>
                <div class="col-lg-2 mb-3">
                    <label class="form-label" for="end_datepicker">End Date</label>
                    <input class="form-control datetimepicker" name="end_date" id="end_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' />
                </div>
                <div class="col-lg-2 mb-3">
                    <label for="staff_name">Staff Name</label>
                    <select class="form-select js-choice" id="staff_name" multiple="multiple" size="1" name="multiple_users[]" data-options='{"removeItemButton":true,"placeholder":true}'>
                        <option value="">Select staff...</option>
                        <?php 
                        $staff_sql = "SELECT * FROM users ORDER BY user_lname ASC";
                        $stmt = $conn->prepare($staff_sql); // Use prepared statements
                        $stmt->execute();
                        $res = $stmt->get_result();
                        if ($res->num_rows > 0) {
                            while ($row = $res->fetch_assoc()) {
                                $staff_name = htmlspecialchars($row['user_lname'] . ", " . $row['user_fname'], ENT_QUOTES, 'UTF-8');
                                $staff_userid = htmlspecialchars($row['hashed_id'], ENT_QUOTES, 'UTF-8');
                                echo '<option value="' . $staff_userid . '">' . $staff_name . '</option>'; 
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-lg-2 mb-3">
                    <label for="warehouse">Warehouse</label>
                    <select class="form-select" name="warehouse" id="warehouse">
                    <?php echo implode("\n", $warehouse_options2); ?>
                    </select>
                </div>
                <div class="col-lg-2 mb-3 pt-4">
                    <button type="submit" class="btn btn-primary mt-1">Generate</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card mt-3">
    <div class="card-body bg-body-tertiary overflow-hidden p-lg-6">
        <div class="row">
            <div class="col-lg-12">
                <div class="table-responsive">
                    <table class="table bordered-table table-bordered">
                        <thead>
                            <tr>
                                <th></th>
                                <th>DESCRIPTION</th>
                                <th>BRAND</th>
                                <th>CATEGORY</th>
                                <th>BARCODE</th>
                                <th>KEYWORD</th>
                                <th>OUTBOUND DATE</th>
                                <th>CAPITAL</th>
                                <th>SOLD AMOUNT</th>
                                <th>OUTBOUND ID</th>
                                <th>CUSTOMER</th>
                                <th>STAFF</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
}