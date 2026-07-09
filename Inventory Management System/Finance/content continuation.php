<div class="row">

    <?php
    
        $additional_query = "";
        

        if(isset($_POST['start_date'])){
            $startDate = $_POST['start_date'];
        } else {
            $startDate = date('Y-m-01 00:00:00');
        }

        if(isset($_POST['end_date'])){
            $endDate = $_POST['end_date'];
        } else {
            $endDate = date('Y-m-d H:i:s');
        }

        $warehouse_transaction = isset($_POST['warehouse']) ? trim($_POST['warehouse']) : 'ALL';
        $categories = isset($_POST['category']) ? $_POST['category'] : [];

        $dateFormat = "M j, Y";  // Date format like "Jan 1, 2000"

        $formatted_startDate = isset($startDate) ? date($dateFormat, strtotime($startDate)) : null;
        $formatted_endDate = isset($endDate) ? date($dateFormat, strtotime($endDate)) : null;

        // Sanitize and validate categories array (ensure it's an array of strings)
        $categories = array_map('trim', $categories);  // Trim each category string

        if($warehouse_transaction === "ALL"){
            $warehouse_transactions = $user_warehouse_ids; // Use the array of warehouse IDs
        } else {
            $warehouse_transactions = $warehouse_transaction; // Use the single warehouse ID
        }
    
        if(empty($categories)){
            $imploded_category = "";
            $imploded_categories = "";
        } else {
            // Implode raw hashed IDs
            $imploded_category = "'" . implode("','", $categories) . "'";
            $imploded_categories = implode(",", $categories);
            $additional_query .= " AND p.category IN ($imploded_category)";
        }

        $supplier_selected_id = isset($_POST['supplier']) ? $_POST['supplier'] : [];
        $supplier_post_local_internation = isset($_POST['local_international']) ? trim($_POST['local_international']) : '';
        if($supplier_post_local_internation !== "All" && !empty($supplier_post_local_internation)){
            $additional_query .= " AND sup.local_international = '$supplier_post_local_internation'";
        } 

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
            $additional_query .= "";
        } else {
            $additional_query .= " AND s.supplier IN ($imploded_supplier)";
        }
        
        
        
        // ============check later==========
        // $transaction_warehouse_name = "";
        // $transaction_warehouse_query = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$warehouse_transaction' LIMIT 1";

        // $transaction_warehouse_res = $conn->query($transaction_warehouse_query);
        // if($transaction_warehouse_res->num_rows>0){
        //     $row=$transaction_warehouse_res->fetch_assoc();
        //     $transaction_warehouse_name = $row['warehouse_name'];
        // }

        // echo $imploded_category;
        // echo $imploded_supplier;


        $query = "
            SELECT
                sup.supplier_name,
                c.category_name,
                ol.order_num,
                ol.customer_fullname,
                ol.hashed_id,
                ol.date_sent,
                sup.local_international,
                p.description,
                b.brand_name,
                s.unique_barcode,
                s.batch_code,
                s.capital,
                oc.sold_price,
                w.warehouse_name
            FROM stocks s
            INNER JOIN product p 
                ON p.hashed_id = s.product_id
            INNER JOIN brand b
                ON b.hashed_id = p.brand
            INNER JOIN category c
                ON c.hashed_id = p.category
            INNER JOIN supplier sup
                ON sup.hashed_id = s.supplier
            LEFT JOIN outbound_content oc
                ON oc.unique_barcode = s.unique_barcode
            LEFT JOIN outbound_logs ol
                ON ol.hashed_id = oc.hashed_id
            LEFT JOIN warehouse w
                ON w.hashed_id = s.warehouse
            WHERE 
                ol.date_sent BETWEEN '$startDate' AND '$endDate'
                $additional_query
                ORDER BY ol.date_sent ASC
            LIMIT 20
        ";

        // echo '<div class="mt-3 mb-3">' . $query . '</div>';
        // $result = $conn->query($query);
        // if($result->num_rows>0){
        //     while($row=$result->fetch_assoc()){
        //         $supplier_name = $row['supplier_name'];
        //         $category_name = $row['category_name'];
        //         $order_num = $row['order_num'];
        //         $customer_fullname = $row['customer_fullname'];
        //         $hashed_id = $row['hashed_id'];
        //         $date_sent = date($dateFormat, strtotime($row['date_sent']));
        //         $local_international = $row['local_international'];
        //         $description = $row['description'];
        //         $brand_name = $row['brand_name'];
        //         $unique_barcode = $row['unique_barcode'];
        //         $batch_code = $row['batch_code'];
        //         $capital = number_format($row['capital'], 2);
        //         $sold_price = number_format($row['sold_price'], 2);
        //         $warehouse_name = $row['warehouse_name'];

        //         echo "<p>Supplier: $supplier_name, Category: $category_name, Order Number: $order_num, Customer: $customer_fullname, Date Sent: $date_sent, Local/International: $local_international, Description: $description, Brand: $brand_name, Unique Barcode: $unique_barcode, Batch Code: $batch_code, Capital: $capital, Sold Price: $sold_price, Warehouse: $warehouse_name</p>";

        //     }
        // }

        
    
    ?>
    <div class="col-xxl-12">
        <div class="card">
            <div class="card-body bg-body-tertiary overflow-hidden ">
                <form action="../Finance/index" method="POST">
                    <div class="tab-content row">
                        <div class="col-lg-4 mb-3">
                            <label class="form-label" for="start_datepicker">Start Date</label>
                            <input class="form-control datetimepicker fs-11" name="start_date" id="start_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' <?php if(isset($_POST['start_date'])){ echo 'value="' . $startDate . '"';} else { echo 'placeholder="' . $startDate . '"'; }?> required/>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <label class="form-label" for="end_datepicker">End Date</label>
                            <input class="form-control datetimepicker fs-11" name="end_date" id="end_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' <?php if(isset($_POST['end_date'])){ echo 'value="' . $endDate . '"';} else { echo 'placeholder="' . $endDate . '"'; }?> required/>
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
                                <option value="">All</option>
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


    <!-- display section -->
    <div class="col-12 col-xxl-12">
        <div class="card">
            <div class="card-body">
                <div class="text-center">
                    <h2>Finance Report</h2>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Category</th>
                                <th>Order Number</th>
                                <th>Customer</th>
                                <th>Date Sent</th>
                                <th>Local/International</th>
                                <th>Description</th>
                                <th>Brand</th>
                                <th>Unique Barcode</th>
                                <th>Batch Code</th>
                                <th>Capital</th>
                                <th>Sold Price</th>
                                <th>Warehouse</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $conn->query($query);
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['supplier_name'], ENT_QUOTES, 'UTF-8') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['order_num'], ENT_QUOTES, 'UTF-8') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['customer_fullname'], ENT_QUOTES, 'UTF-8') . "</td>";
                                    echo "<td>" . date($dateFormat, strtotime($row['date_sent'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['local_international'], ENT_QUOTES, 'UTF-8') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['brand_name'], ENT_QUOTES, 'UTF-8') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['unique_barcode'], ENT_QUOTES, 'UTF-8') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['batch_code'], ENT_QUOTES, 'UTF-8') . "</td>";
                                    echo "<td>" . number_format($row['capital'], 2) . "</td>";
                                    echo "<td>" . number_format($row['sold_price'], 2) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['warehouse_name'], ENT_QUOTES, 'UTF-8') . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo '<tr><td colspan="13" class="text-center">No records found for the selected criteria.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

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