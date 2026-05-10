<?php 
$_SESSION['selected_warehouse_id'] = $_GET['ware'];
$_SESSION['selected_warehouse_name'] = $_GET['waren'];
$selected_warehouse_id = $_SESSION['selected_warehouse_id'];
$selected_warehouse_name = $_SESSION['selected_warehouse_name'];


?>

<div class="card">
    <div class="card-body overflow-hidden py-6 px-2">
      <h5>SELECT PRODUCTS</h5>
    <div class="card shadow-none">
  <div class="card-body p-0 pb-3"  data-list='{"valueNames":["desc","barcode","brand","cat","qty","trans"]}'>
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
            <th class="text-black dark__text-white align-middle sort" data-sort="desc">Parent Barcode</th>
            <th class="text-black dark__text-white align-middle sort" data-sort="barcode">Brand </th>
            <th class="text-black dark__text-white align-middle sort" data-sort="cat">Category</th>
            <th class="text-black dark__text-white align-middle white-space-nowrap pe-3 sort" data-sort="qty">Quantity</th>
          </tr>
        </thead>
        <tbody id="bulk-select-body" class="list">
          <?php 
          $product_list_query = "
            SELECT 
                p.hashed_id,
                s.parent_barcode,
                p.description AS description,
                b.brand_name AS brand_name,
                c.category_name AS category_name,
                p.product_img AS product_img,
                SUM(CASE WHEN s.item_status IN (0, 2, 3) THEN 1 ELSE 0 END) AS quantity,
                p.safety AS safety,
                s.warehouse AS warehouse,
                w.warehouse_name AS warehouse_name
            FROM stocks s
            LEFT JOIN product p ON p.hashed_id = s.product_id
            LEFT JOIN brand b ON b.hashed_id = p.brand
            LEFT JOIN category c ON c.hashed_id = p.category
            LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
            WHERE s.warehouse = '$selected_warehouse_id'
            GROUP BY s.product_id, s.warehouse, p.description, b.brand_name, c.category_name, p.product_img, p.safety, w.warehouse_name
          ";
          $product_list_res = $conn->query($product_list_query);
          if($product_list_res->num_rows > 0) {
            while($row = $product_list_res->fetch_assoc()) {
              $product_id = $row['hashed_id'];
              $product_img = $row['product_img'];
              $product_category = $row['category_name'];
              $product_brand = $row['brand_name'];
              $product_des = $row['description'];
              $product_pbarcode = $row['parent_barcode'];
              $current_stock = $row['quantity'];

              $current_total_transaction_daily = rand(1, 100);
              $current_total_transaction_monthly = rand(1, 500);
              $current_total_transaction_yearly = rand(1, 1000);
              

          ?>
          <tr>
            <td class="align-middle white-space-nowrap">
              <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" id="checkbox-1" data-bulk-select-row="{<input type='checkbox' name='product_id[]' value='<?php echo $product_id; ?>' checked=''><input type='checkbox' name='product_image[]' value='<?php echo basename($product_img)?>' checked=''><input type='checkbox' name='product_desc[]' value='<?php echo $product_des;?>' checked=''><input type='checkbox' name='parent_barcode[]' value='<?php echo $product_pbarcode; ?>' checked=''><input type='checkbox' name='brand[]' value='<?php echo $product_brand; ?>' checked=''><input type='checkbox' name='category[]' value='<?php echo $product_category; ?>' checked=''><input type='checkbox' name='qty[]' value='<?php echo $current_stock; ?>' checked=''><input type='checkbox' name='trans_day[]' value='<?php echo $current_total_transaction_daily; ?>' checked=''><input type='checkbox' name='trans_month[]' value='<?php echo $current_total_transaction_monthly; ?>' checked=''><input type='checkbox' name='trans_year[]' value='<?php echo $current_total_transaction_yearly;?>' checked=''>}" />
              </div>
            </td>
            <td>
              <img class="img img-fluid m-0" src="../../assets/img/<?php echo basename($product_img); ?>" alt="" >
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
              <td colspan="8" class="py-6 px-6 text-center cat brand"><h2>No Data</h2></td>
            </tr>
          <?php
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