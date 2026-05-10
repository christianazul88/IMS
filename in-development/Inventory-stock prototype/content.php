<div class="row g-3 mb-3">
    <!-- Main Container -->
    <div class="col-xxl-4 col-xl-4 col-4">
        <div class="card h-md-100 ecommerce-card-min-width">
            <div class="card-header pb-0">
                <h6 class="mb-0 mt-2 d-flex align-items-center">
                    Inventory Access
                    <span class="ms-1 text-400" data-bs-toggle="tooltip" data-bs-placement="top" title="all accessible warehouse/ Inventory">
                    <span class="far fa-question-circle" data-fa-transform="shrink-1"></span>
                    </span>
                </h6>
            </div>
            <a href="../Inventory-stock/">
                <div class="card-body d-flex flex-column justify-content-end">
                    <div class="row">
                        <div class="col-lg-12 mb-4 mb-lg-0">
                            <div class="row">
                                <div class="col">
                                    <p class="font-sans-serif lh-1 mb-4 fs-5 text-dark">
                                        <?php 
                                        // Split the string into an array using the comma as a delimiter
                                        $unique_warehouse_ids_array = explode(",", $user_warehouse_id);

                                        // Trim whitespace from each element in the array (optional, in case of spaces)
                                        $unique_warehouse_ids_array = array_map('trim', $unique_warehouse_ids_array);

                                        // Count the number of elements in the array
                                        $unique_count = count($unique_warehouse_ids_array);

                                        echo $unique_count;
                                        ?>
                                    </p>
                                    <span></span>
                                </div>
                            </div>   
                        </div>
                    </div>
                    <!-- -------------------- -->
                </div>
            </a>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-4 col-4">
        
                <?php include "belowsafety.php";?>
        
    </div>

    <div class="col-xxl-4 col-xl-4 col-4">
                <?php include "prolongitems.php";?>
            
    </div>


    <div class="col-xxl-12 text-end mb-3">
        <div class="dropdown font-sans-serif mb-2">
            <a class="btn btn-falcon-default dropdown-toggle" id="dropdownMenuLink" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Select Warehouse</a>
            <?php 
            // Quote each ID in the array
            $quoted_warehouse_ids = array_map(function ($id) {
                return "'" . trim($id) . "'";
            }, $user_warehouse_ids);
    
            // Create a comma-separated string of quoted IDs
            $imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);
            $imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);

            // Get the first warehouse ID
            $warehouse_ids_array = explode(",", $imploded_warehouse_ids);
            $first_warehouse_id = $warehouse_ids_array[0];
            ?>
            <div class="dropdown-menu dropdown-menu-end py-0" aria-labelledby="dropdownMenuLink">
                <?php 
                $warehouse_queries = "SELECT hashed_id, warehouse_name FROM warehouse WHERE hashed_id IN ($imploded_warehouse_ids)";
                $warehouse_queries_res = $conn->query($warehouse_queries);
                if($warehouse_queries_res->num_rows>0){
                    while($row=$warehouse_queries_res->fetch_assoc()){
                        ?>
                        <a class="dropdown-item" href="../Inventory-stock/?whps=<?php echo $row['hashed_id'] . "&name=" . $row['warehouse_name'] ;?>"><?php echo $row['warehouse_name'];?></a>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>
    <div class="col-xxl-14 col-xl-12 col-12">
        <div class="card">
            <div class="card-body">
                <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
                    <thead class="bg-200">
                        <tr>
                            <th></th>
                            <th class="text-900 sort text-nowrap">Description</th>
                            <th class="text-900 sort text-nowrap">Brand</th>
                            <th class="text-900 sort text-nowrap">Category</th>
                            <th class="text-900 sort text-nowrap">Quantity</th>
                            <th class="text-900 sort text-nowrap">Warehouse</th>
                            <th class="text-900 sort text-nowrap text-end">Parent Barcode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if(isset($_GET['whps'])){
                            $warehouse_id = $_GET['whps'];
                            $warehouse_name = $_GET['name'];
                        } else {
                            $warehouse_id = $first_warehouse_id;
                            $warehouse_sample_query = "SELECT warehouse_name FROM warehouse WHERE hashed_id = $first_warehouse_id";
                            $warehouse_sample_res = $conn->query($warehouse_sample_query);
                            if($warehouse_sample_res->num_rows>0){
                                $row =$warehouse_sample_res->fetch_assoc();
                                $warehouse_name = $row['warehouse_name'];
                            }
                        }
                        $product_query = "SELECT p.hashed_id AS product_id, p.description, p.product_img, 
                                     b.brand_name, c.category_name, p.parent_barcode
                              FROM product p
                              LEFT JOIN brand b ON p.hashed_id = b.hashed_id
                              LEFT JOIN category c ON p.hashed_id = c.hashed_id";
                        $product_res = $conn->query($product_query);
                        if($product_res->num_rows>0){
                            while($row = $product_res->fetch_assoc()){
                                $description = $row['description'];
                                $brand = $row['brand_name'];
                                $category = $row['category_name'];
                                $product_id = $row['product_id'];
                                $stock_query = "SELECT COUNT(product_id) AS quantity FROM stocks WHERE item_status = 0 AND warehouse = $warehouse_id AND product_id = '$product_id'";
                                $stock_res = $conn->query($stock_query);
                                if($stock_res->num_rows>0){
                                    $row=$stock_res->fetch_assoc();
                                    $quantity = $row['quantity'];
                                } else {
                                    $quantity = 0;
                                }
                                ?>
                                <tr>
                                    <td><img src="../../assets/img/'" class="img img-fluid" style="height: 30px;"></td>
                                    <td><?php echo $description; ?></td>
                                    <td><?php echo $brand ; ?></td>
                                    <td><?php echo $brand ; ?></td>
                                    <td><?php echo $category ; ?></td>
                                    <td><?php echo $quantity ; ?></td>
                                    <td><?php echo $warehouse_name; ?></td>
                                    <td class="text-end"><?php echo $product_row['parent_barcode'] ; ?></td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="firstModal" aria-hidden="true" aria-labelledby="exampleModalToggleLabel" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalToggleLabel">Batch Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Content will be loaded dynamically here -->
        <div id="modal-1-display" class="text-center">
          <!-- Initially Empty (Progress Bar will appear) -->
        </div>
      </div>
    </div>
  </div>
</div>

<?php 

$conn->close();
?>