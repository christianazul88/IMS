
<div class="card">
    <div class="row pt-5 ps-5 pb-1"><h3>Items that were received more than 3 months ago</h3></div>
    <div class="card-body overflow-hidden p-lg-6">
        <div class="table-responsive scrollbar overflow-auto" data-list='{"valueNames":["description","brand","category","barcode","batch","date","warehouse","supplier","staff"],"page":5,"pagination":true}'>
            <div class="row justify-content-end g-0">
                <div class="col-auto col-sm-5 mb-3">
                <form>
                    <div class="input-group"><input class="form-control form-control-sm shadow-none search" type="search" placeholder="Search..." aria-label="search" />
                    <div class="input-group-text bg-transparent"><span class="fa fa-search fs-10 text-600"></span></div>
                    </div>
                </form>
                </div>
            </div>
            <div class="table-responsive scrollbar">
                <table class="table table-bordered table-striped fs-10 mb-0">
                <thead class="bg-200">
                    <tr>
                    <th class="text-900 sort" data-sort="description">DESCRIPTION</th>
                    <th class="text-900 sort" data-sort="brand">BRAND</th>
                    <th class="text-900 sort" data-sort="category">CATEGORY</th>
                    <th class="text-900 sort" data-sort="barcode">BARCODE</th>
                    <th class="text-900 sort" data-sort="batch">BATCH</th>
                    <th class="text-900 sort" data-sort="date">INBOUND DATE</th>
                    <th class="text-900 sort" data-sort="warehouse">WAREHOUSE</th>
                    <th class="text-900 sort" data-sort="supplier">SUPPLIER</th>
                    <th class="text-900 sort" data-sort="staff">STAFF</th>
                    </tr>
                </thead>
                <tbody class="list">
                    <?php
                    // Calculate the threshold for "more than 1 month ago"
                    $dateThreshold = date('Y-m-d H:i:s', strtotime('-3 month'));

                    // SQL query for items older than 1 month
                    $sql = "
                        SELECT p.description, b.brand_name, c.category_name, s.unique_barcode, s.batch_code, s.date, w.warehouse_name, sp.supplier_name, sp.local_international, u.user_fname, u.user_lname
                        FROM stocks s
                        LEFT JOIN product p ON p.hashed_id = s.product_id
                        LEFT JOIN brand b ON b.hashed_id = p.brand
                        LEFT JOIN category c ON c.hashed_id = p.category
                        LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
                        LEFT JOIN supplier sp ON sp.hashed_id = s.supplier
                        LEFT JOIN users u ON u.hashed_id = s.user_id
                        WHERE s.date < '$dateThreshold' 
                        AND s.item_status IN (0, 2, 3) 
                        AND s.warehouse IN ($user_warehouse_id)
                    ";

                    // Execute query and fetch the result
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while($row=$result->fetch_assoc()){
                            $description = $row['description'];
                            $brandName = $row['brand_name'];
                            $categoryName = $row['category_name'];
                            $uniqueBarcode = $row['unique_barcode'];
                            $batchCode = $row['batch_code'];
                            $date_inbound = $row['date'];
                            $warehouseName = $row['warehouse_name'];
                            $supplierName = $row['supplier_name'];
                            $localInternational = $row['local_international'];
                            $userFullName = $row['user_fname'] . " " . $row['user_lname'];
                            if($localInternational === "Local"){
                                $supplier_type = '<span class="badge rounded-pill badge-subtle-info">Local</span>';
                            } elseif($localInternational === "International") {
                                $supplier_type = '<span class="badge rounded-pill badge-subtle-warning">International</span>';
                            } else {
                                $supplier_type = '<span class="badge rounded-pill badge-subtle-danger">Unset</span>';
                            }
                    ?>
                    <tr>
                        <td class="description"><?php echo $description;?></td>
                        <td class="brand"><?php echo $brandName;?></td>
                        <td class="category"><?php echo $categoryName;?></td>
                        <td class="barcode"><?php echo $uniqueBarcode;?></td>
                        <td class="batch"><?php echo $batchCode;?></td>
                        <td class="date"><?php echo $date_inbound;?></td>
                        <td class="warehouse"><?php echo $warehouseName;?></td>
                        <td class="supplier"><?php echo $supplierName . " " . $supplier_type; ?></td>
                        <td class="staff"><?php echo $userFullName;?></td>
                    </tr>
                    <?php
                        }
                    } else {
                    ?>
                    <tr>
                        <td class="p-5 text-center fs-3" colspan="9"><b>No Prolong Items yet.</b></td>
                    </tr>
                    <?php
                    }
                    ?>
                </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3"><button class="btn btn-sm btn-falcon-default me-1" type="button" title="Previous" data-list-pagination="prev"><span class="fas fa-chevron-left"></span></button>
                <ul class="pagination mb-0"></ul><button class="btn btn-sm btn-falcon-default ms-1" type="button" title="Next" data-list-pagination="next"><span class="fas fa-chevron-right"> </span></button>
            </div>
        </div>
    </div>
</div>