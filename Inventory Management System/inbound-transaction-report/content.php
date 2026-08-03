<?php 
$categories = [];
$imploded_category = "";

if(isset($_POST['category'])){
    $categories = array_map('trim', $_POST['category']);
    if(!empty($categories)){
        $imploded_category = "'" . implode("','", $categories) . "'";
    }
}
?>

<div class="card">
    <div class="card-body overflow-hidden p-6">
        <h2 class="mb-6">INBOUND TRANSACTIONS</h2>

        <form method="POST">
            <div class="row">

                <div class="col-lg-2 mb-3">
                    <label class="form-label" for="start_datepicker">Start Date</label>
                    <input class="form-control datetimepicker fs-11" name="start_date" id="start_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' value="<?php if(isset($_POST['start_date'])){echo $_POST['start_date'];}?>" required/>
                </div>
                <div class="col-lg-2 mb-3">
                    <label class="form-label" for="end_datepicker">End Date</label>
                    <input class="form-control datetimepicker fs-11" name="end_date" id="end_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' value="<?php if(isset($_POST['end_date'])){echo $_POST['end_date'];}?>" required/>
                </div>
                <div class="col-lg-3 mb-3">
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

                <div class="col-lg-3 mb-3">
                    <label>Warehouse</label>
                    <select class="form-select" name="warehouse">
                        <option value="ALL">ALL</option>
                        <?php echo implode("\n", $warehouse_options2); ?>
                    </select>
                </div>

                <div class="col-lg-2">
                    <label>Supplier</label>
                    <select class="form-select" name="supplier_type">
                        <option value="ALL">ALL</option>
                        <option value="Local">Local</option>
                        <option value="International">Import</option>
                        <option value="Hakot">Bidding/ Hakot</option>
                    </select>
                </div>

                <div class="col-lg-2 pt-4">
                    <button type="submit" name="preview" class="btn btn-primary">Generate</button>
                </div>

            </div>
        </form>
    </div>
</div>


<?php 
if(isset($_POST['preview'])){

    $startDate = date('Y-m-d 00:00:00', strtotime($_POST['start_date']));
    $endDate   = date('Y-m-d 23:59:59', strtotime($_POST['end_date']));

    // CATEGORY FILTER
    $additional_category_query = "";
    if($imploded_category !== ""){
        $additional_category_query = "AND p.category IN ($imploded_category)";
    }

    // WAREHOUSE FILTER
    if($_POST['warehouse'] !== "ALL"){
        $warehouse = $conn->real_escape_string($_POST['warehouse']);
        $additional_warehouse_query = "AND s.warehouse = '$warehouse'";
    } else {
        $additional_warehouse_query = "AND s.warehouse IN ($user_warehouse_id)";
    }

    if($_POST['supplier_type'] !== "ALL"){
        $supplier_type = $conn->real_escape_string($_POST['supplier_type']);
        $additional_supplier_query = "AND sup.local_international = '$supplier_type'";
    } else {
        $additional_supplier_query = "";
    }

    // FINAL QUERY
    $query = "
    SELECT 
        s.unique_barcode, 
        p.description, 
        b.brand_name, 
        c.category_name, 
        sup.supplier_name, 
        sup.local_international, 
        s.capital, 
        w.warehouse_name, 
        s.date AS date_acquired, 
        s.unique_key, 
        u.user_fname, 
        u.user_lname 
    FROM stocks s 
    LEFT JOIN product p ON p.hashed_id = s.product_id 
    LEFT JOIN brand b ON b.hashed_id = p.brand 
    LEFT JOIN category c ON c.hashed_id = p.category 
    LEFT JOIN supplier sup ON sup.hashed_id = s.supplier 
    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse 
    LEFT JOIN users u ON u.hashed_id = s.user_id 
    WHERE s.date BETWEEN '$startDate' AND '$endDate'
    $additional_warehouse_query
    $additional_category_query
    $additional_supplier_query
    ";

    $res = $conn->query($query);

    if($res->num_rows > 0){
        
        // STORE QUERY IN SESSION
        $_SESSION['csv_filters'] = [
            'start' => $startDate,
            'end' => $endDate,
            'warehouse_query' => $additional_warehouse_query,
            'category_query' => $additional_category_query,
            'supplier_query' => $additional_supplier_query
        ];

        echo '
        <div class="card mt-3">
            <div class="card-body text-center">
                <h5>Inbound Transaction Report ready for download</h5>
                <p>
                    Download will start automatically in 5 seconds...<br>
                    If not, <a href="download_csv.php">click here</a>
                </p>
            </div>
        </div>

        <script>
            setTimeout(function(){
                window.location.href = "download_csv.php";
            }, 5000);
        </script>
        ';
    }
}
?>