<?php
include "../config/database.php";
include "../config/on_session.php";
    $warehouse_for_transfer = $_SESSION['warehouse_for_return'];

    $warehouse_sql = "SELECT hashed_id FROM warehouse WHERE warehouse_name = '$warehouse_for_transfer' LIMIT 1";
    $res = $conn->query($warehouse_sql);
    if($res->num_rows>0){
        $row = $res->fetch_assoc();
        $warehouse_for_transfer = $row['hashed_id'];
    }
?>

<div class="card" data-list='{"valueNames":["desc","barcode","brand","cat","qty","trans"]}'>
    <div class="card-body overflow-hidden py-6 px-2">
      <h5>SELECT PRODUCTS TO BE TRANSFERED</h5>
      <div class="row d-flex align-items-center justify-content-end my-3">
        <div class="col-auto text-end">
            <a href="local_config.php?change_warehouse=true" class="btn btn-warning">Change Warehouse</a>
        </div>
        <div class="col-auto col-sm-5">
        <form>
            <div class="input-group"><input class="form-control form-control-sm shadow-none search" type="search" placeholder="Search..." aria-label="search" />
            <div class="input-group-text bg-transparent"><span class="fa fa-search fs-10 text-600"></span></div>
            </div>
        </form>
        </div>
      </div>
    <div class="card shadow-none">
  <div class="card-body p-0 pb-3">
    <div class="d-flex align-items-center justify-content-end my-3">
      <div id="bulk-select-replace-element"><button class="btn btn-falcon-success btn-sm" type="button"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="ms-1">New</span></button></div>
      <div class="d-none ms-3" id="bulk-select-actions">
        <div class="d-flex">
          <form action="../Supplier-selection/index.php" method="POST">
            <pre id="selectedRows" hidden></pre>
            <button class="btn btn-falcon-danger btn-sm ms-2" type="submit">Next</button>
          </form>
          
          <!-- <a href="../Supplier-selection/" class="btn btn-falcon-danger btn-sm ms-2">Apply</a> -->
        </div>
        
      </div>
      
    </div>
    <div class="table-responsive scrollbar">
      <table class="table mb-0">
        <thead class="bg-200">
          <tr>
            <th class="align-middle white-space-nowrap">
              <div class="form-check mb-0"><input class="form-check-input" id="bulk-select-example" type="checkbox" data-bulk-select='{"body":"bulk-select-body","actions":"bulk-select-actions","replacedElement":"bulk-select-replace-element"}' /></div>
            </th>
            <th width="50"></th>
            <th class="text-black dark__text-white align-middle sort" data-sort="desc">Description</th>
            <th class="text-black dark__text-white align-middle sort" data-sort="batch_code">Batch Code</th>
            <th class="text-black dark__text-white align-middle sort" data-sort="desc">Parent Barcode</th>
            <th class="text-black dark__text-white align-middle sort" data-sort="barcode">Brand </th>
            <th class="text-black dark__text-white align-middle sort" data-sort="cat">Category</th>
          </tr>
        </thead>
        <tbody id="bulk-select-body" class="list">
        <?php 
        // Check if the form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Check if any checkboxes are selected
            if (isset($_POST['batch_code']) && is_array($_POST['batch_code'])) {
                // Loop through each selected checkbox
                foreach ($_POST['batch_code'] as $selectedProductId) {
                    // Retrieve data associated with the selected product id
                    $product_key = array_search($selectedProductId, $_POST['batch_code']);
                    $product_batch_code = $_POST['batch_code'][$product_key];
                    
          ?>
          <tr>
            <td class="align-middle white-space-nowrap">
              <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="checkbox-1" data-bulk-select-row="{<input type='checkbox' name='unique_barcode[]' value='<?php echo $product_pbarcode; ?>' checked=''>}" />
              </div>
            </td>
            <td>
              <img class="img img-fluid m-0" src="../../assets/img/<?php //echo basename($product_img); ?>" alt="" >
            </td>
            <th class="align-middle desc"><?php echo $product_batch_code; ?></th>
            <td class="align-middle batch_code"><?php //echo $batch_code;?></td>
            <th class="align-middle barcode"><?php //echo $product_pbarcode; ?></th>
            <td class="align-middle brand"><?php //echo $product_brand; ?></td>
            <td class="align-middle cat"><?php //echo $product_category; ?></td>
          </tr>
          <?php 
                }
              }
            }
          ?>

        </tbody>
      </table>
      <!-- <p class="mt-3 mb-2">Click the button to get selected rows</p> -->
      <button id="btn_to_trigger" class="btn btn-warning" data-selected-rows="data-selected-rows" hidden>Get Selected Rows</button>
      <!-- <pre id="selectedRows"></pre> -->
    </div>
  </div>
</div>
    </div>
</div>

<script>
      document.addEventListener("DOMContentLoaded", function() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        const btnToTrigger = document.getElementById('btn_to_trigger');

        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    btnToTrigger.click();
                }
            });
        });

        // Trigger button click every 1 second
        setInterval(function() {
            btnToTrigger.click();
        }, 1000);
    });
</script>