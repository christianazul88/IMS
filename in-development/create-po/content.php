<?php 
$selected_warehouse_id = $_SESSION['selected_warehouse_id'];
$selected_warehouse_name = $_SESSION['selected_warehouse_name'];
?>

<div class="card">
    <div class="card-header bg-warning">
      <h2 class="text-white">SELECT PRODUCTS TO ORDER</h2>
    </div>
    <div class="card-body overflow-hidden py-6 px-2">
    <div class="card shadow-none">
  <div id="tableExample" class="card-body p-0 pb-3"  data-list='{"valueNames":["desc","barcode","brand","cat","qty"],"page":5,"pagination":true}'>
  <div class="row justify-content-end g-0">
    <div class="col-auto col-sm-5 mb-3">
      <form>
        <div class="input-group"><input class="form-control form-control-sm shadow-none search" type="search" placeholder="Search..." aria-label="search" />
          <div class="input-group-text bg-transparent"><span class="fa fa-search fs-10 text-600"></span></div>
        </div>
      </form>
    </div>
  </div>
    <div class="d-flex align-items-center justify-content-end my-3">
      <div id="bulk-select-replace-element"><button class="btn btn-falcon-success btn-sm" type="button"><span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span><span class="ms-1">New</span></button></div>
      <div class="d-none ms-3" id="bulk-select-actions">
        <div class="d-flex">
          <form action="../Supplier-selection/index.php" method="POST">
            <pre id="selectedRows" hidden></pre>
            <button class="btn btn-falcon-danger btn-sm ms-2" type="submit">Next</button>
          </form>
        </div>
      </div>
    </div>
    <div class="table-responsive scrollbar">
      <table class="table table-bordered table-striped fs-10 mb-0">
        <thead class="bg-200">
          <tr>
            <th class="align-middle white-space-nowrap">
              <div class="form-check mb-0"><input class="form-check-input" id="bulk-select-example" type="checkbox" data-bulk-select='{"body":"bulk-select-body","actions":"bulk-select-actions","replacedElement":"bulk-select-replace-element"}' /></div>
            </th>
            <th class="text-black dark__text-white align-middle sort" data-sort="desc">Description</th>
            <th class="text-black dark__text-white align-middle sort" data-sort="desc">Parent Barcode</th>
            <th class="text-black dark__text-white align-middle sort" data-sort="barcode">Brand </th>
            <th class="text-black dark__text-white align-middle sort" data-sort="cat">Category</th>
            <th class="text-black dark__text-white align-middle white-space-nowrap pe-3 sort" data-sort="qty">Quantity</th>
          </tr>
        </thead>
        <tbody id="bulk-select-body" class="list">
          <?php 
          $product_list_query = "
            SELECT product.*, users.user_fname, users.user_lname, category.category_name, brand.brand_name
            FROM product 
            LEFT JOIN users ON users.hashed_id = product.user_id 
            LEFT JOIN category ON category.hashed_id = product.category
            LEFT JOIN brand ON brand.hashed_id = product.brand
            ORDER BY product.id DESC
          ";
          $product_list_res = $conn->query($product_list_query);
          if($product_list_res->num_rows > 0) {
            while($row = $product_list_res->fetch_assoc()) {
              $product_id = $row['hashed_id'];
              $product_category = $row['category_name'];
              $product_brand = $row['brand_name'];
              $product_des = $row['description'];
              $product_pbarcode = $row['parent_barcode'];
              $product_date = $row['date'];
              $product_publisher = $row['user_fname'] . " " . $row['user_lname'];
              
              $stock_query = "SELECT IFNULL(COUNT(product_id), 0) AS total_quantity FROM stocks WHERE warehouse = '$selected_warehouse_id' AND product_id = '$product_id'";
              $stock_res = $conn->query($stock_query);
              $stock_row = $stock_res->fetch_assoc();
              $current_stock = $stock_row['total_quantity'];
          ?>
          <tr>
            <td class="align-middle white-space-nowrap">
              <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="checkbox-1" data-bulk-select-row="{<input type='checkbox' name='product_id[]' value='<?php echo $product_id; ?>' checked=''><input type='checkbox' name='product_image[]' value='' checked=''><input type='checkbox' name='product_desc[]' value='<?php echo $product_des;?>' checked=''><input type='checkbox' name='parent_barcode[]' value='<?php echo $product_pbarcode; ?>' checked=''><input type='checkbox' name='brand[]' value='<?php echo $product_brand; ?>' checked=''><input type='checkbox' name='category[]' value='<?php echo $product_category; ?>' checked=''><input type='checkbox' name='qty[]' value='<?php echo $current_stock; ?>' checked=''>}" />
              </div>
            </td>
            <th class="align-middle desc"><?php echo $product_des; ?></th>
            <th class="align-middle barcode"><?php echo $product_pbarcode; ?></th>
            <td class="align-middle brand"><?php echo $product_brand; ?></td>
            <td class="align-middle cat"><?php echo $product_category; ?></td>
            <td class="align-middle white-space-nowrap text-end pe-3 qty"><?php echo $current_stock;?></td>
          </tr>
          <?php 
            }
          } else {
          ?>
            <tr>
              <td colspan="6" class="py-6 px-6 text-center cat brand"><h2>No Data</h2></td>
            </tr>
          <?php
          }
          ?>
        </tbody>
      </table>

      <button id="btn_to_trigger" class="btn btn-warning" data-selected-rows="data-selected-rows" hidden>Get Selected Rows</button>
    </div>

    <div class="row align-items-center mt-3">
      <div class="pagination d-none"></div>
      <div class="col">
        <p class="mb-0 fs-10">
          <span class="d-none d-sm-inline-block" data-list-info="data-list-info"></span>
          <span class="d-none d-sm-inline-block"> &mdash;</span>
          <a class="fw-semi-bold" href="#!" data-list-view="*">View all<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a><a class="fw-semi-bold d-none" href="#!" data-list-view="less">View Less<span class="fas fa-angle-right ms-1" data-fa-transform="down-1"></span></a>
        </p>
      </div>
      <div class="col-auto d-flex"><button class="btn btn-sm btn-primary" type="button" data-list-pagination="prev"><span>Previous</span></button><button class="btn btn-sm btn-primary px-4 ms-2" type="button" data-list-pagination="next"><span>Next</span></button></div>
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

    setInterval(function() {
        btnToTrigger.click();
    }, 1000);
  });
</script>
