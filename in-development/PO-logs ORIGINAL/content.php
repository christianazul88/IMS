<?php 
if(isset($_SESSION['po_list'])){
  unset($_SESSION['po_list']);
}

?>
<!-- <div class="card">
<div class="card-body overflow-hidden p-lg-6">
    <div class="row align-items-center">
    <div class="col-lg-6"><img class="img-fluid" src="../assets/img/icons/spot-illustrations/21.png" alt="" /></div>
    <div class="col-lg-6 ps-lg-4 my-5 text-center text-lg-start">
        <h3 class="text-primary">Edit me!</h3>
        <p class="lead">Create Something Beautiful.</p><a class="btn btn-falcon-primary" href="../documentation/getting-started.html">Getting started</a>
    </div>
    </div>
</div>
</div> -->
<div class="card">
  <div class="card-header bg-warning">
    <h2 class="text-white">Purchased Order Logs</h2>
  </div>
  <div class="card-body overflow-hidden py-6 px-0">
<div>
  <div class="row justify-content-end justify-content-end gx-3 gy-0 px-3">
    <div class="col-auto mb-3">
      <!-- <button class="btn btn-primary py-0 me-auto">Create</button> -->
      <button class="btn btn-primary py-0 me-auto" type="button" data-bs-toggle="modal" data-bs-target="#error-modal">Create</button>
    </div>
  </div>
  <div class="table-responsive scrollbar">
    <table class="table mb-0 table-sm data-table fs-10" data-datatables="data-datatables">
      <thead class="bg-200">
        <tr>
          <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="name">P.O no.</th>
          <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="warehouse" >Warehouse</th>
          <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="supplier">Supplier</th>
          <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="country">Date</th>
          <th class="text-900 sort pe-1 align-middle white-space-nowrap" data-sort="email">Created by</th>
          <th class="text-900 sort align-middle white-space-nowrap text-end pe-4" data-sort="payment">Status</th>
          
        </tr>
      </thead>
      <tbody class="list" id="table-purchase-body">
        <?php 
        // Quote each ID in the array
        $quoted_warehouse_ids = array_map(function ($id) {
          return "'" . trim($id) . "'";
        }, $user_warehouse_ids);

        // Create a comma-separated string of quoted IDs
        $imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);
        // Create the unique query to fetch orders for the specified warehouses
        $purchased_order_query = "
        SELECT po.*, u.user_fname, u.user_lname, s.supplier_name, wh.warehouse_name, s.local_international AS supplier_type
        FROM purchased_order po
        LEFT JOIN users u ON u.hashed_id = po.user_id
        LEFT JOIN supplier s ON s.hashed_id = po.supplier
        LEFT JOIN warehouse wh ON wh.hashed_id = po.warehouse
        WHERE po.warehouse IN ($imploded_warehouse_ids)
        ORDER BY po.id DESC";
        $purchased_order_res = $conn->query($purchased_order_query);
        if($purchased_order_res->num_rows>0){
          while($row=$purchased_order_res->fetch_assoc()){
            $po_id = $row['id'];
            $po_supplier = $row['supplier_name'];
            $date_created = $row['date_order'];
            $by = $row['user_fname'] . " " . $row['user_lname'];
            $from_warehouse = $row['warehouse_name'];
            $supplier_type = !empty($row['supplier_type']) ? $row['supplier_type'] : '';
            if($row['status'] == 0){
              $status = '<span class="badge badge rounded-pill badge-subtle-warning">Drafted  <div class="spinner-border" role="status" style="height:10px; width: 10px;"><span class="visually-hidden">Loading...</span></div></span>';
            } elseif($row['status'] == 1){
              $status = '<span class="badge badge rounded-pill badge-subtle-info">Sent to Supplier<span class="ms-1 fas fa-check" data-fa-transform="shrink-2"></span></span>';
            } elseif($row['status'] == 2) {
              $status = '<span class="badge badge rounded-pill badge-subtle-secondary">Confirmed by Supplier<span class="ms-1 fas fa-check" data-fa-transform="shrink-2"></span></span>';
            } elseif($row['status'] == 3){
              $status = '<span class="badge badge rounded-pill badge-subtle-primary">In Transit/ Shipped<span class="ms-1 fas fa-check" data-fa-transform="shrink-2"></span></span>';
            } else {
              $status = '<span class="badge badge rounded-pill badge-subtle-success">Received<span class="ms-1 fas fa-check" data-fa-transform="shrink-2"></span></span>';
            }
        ?>
        <tr class="btn-reveal-trigger">
          <th class="align-middle white-space-nowrap name">
            <?php 
            if($supplier_type !== "Local"){
            if($row['status'] == 0){
            ?>
            <a href="update-session.php?blue=<?php echo $po_id;?>" class="btn fs-11 mx-0"><span class="far fa-edit mx-0"></span></a>
            <?php
            }
            }
            ?>
            
            <a href="#" class="view-po" data-bs-toggle="modal" data-bs-target="#view-pdf-modal" target-id="<?php echo $po_id;?>">
              PO-<?php echo $po_id;?>
            </a>

          </th>
          <td class="align-middle white-space-nowrap warehouse" ><span class="badge bg-warning"><?php echo $from_warehouse;?></span></td>
          <td class="align-middle white-space-nowrap supplier"><?php echo $po_supplier;?></td>
          <td class="align-middle white-space-nowrap country"><?php echo $date_created;?></td>
          <td class="align-middle white-space-nowrap email"><?php echo $by;?></td>
          <td class="align-middle text-end fs-9 white-space-nowrap payment"><?php if($supplier_type !== "Local"){echo $status;} else { echo '<span class="badge badge rounded-pill badge-subtle-success">Received<span class="ms-1 fas fa-check" data-fa-transform="shrink-2"></span></span>';}?></td>
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

<div class="modal fade" id="view-pdf-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-5">
        <div class="row">
          <div class="col-12 text-center">
            <img src="../../assets/img/logo/LPO Logo.png" class="img" height="50" alt="">
          </div>
          <div class="col-12 text-center mb-0">
            <h2 class="mb-0">Purchased Order</h2>
          </div>
        </div>
        <div id="view-modal"></div>
      </div>
      <div class="modal-footer" id="modal-buttons">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>



<!-- create po modal -->
<div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <form action="set_session.php" method="POST">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
          <h4 class="mb-1" id="modalExampleDemoLabel">Select Warehouse </h4>
        </div>
        <div class="p-4 pb-0 mb-3">
          <div class="row">
            <div class="col-12">
              <label for="">Warehouse</label>
              <select name="warehouse" id="warehouse" class="form-select">
                <option value=""></option>
                <?php 
                // Loop through each ID and display differently for the first item
                foreach ($user_warehouse_ids as $id) {
                  // Trim any extra whitespace
                  $id = trim($id);
                  $warehouse_info_query = "SELECT * FROM warehouse WHERE hashed_id = '$id'";
                  $warehouse_info_result = $conn->query($warehouse_info_query);
                  if($warehouse_info_result->num_rows>0){
                      // Check if it's the first item
                      $row=$warehouse_info_result->fetch_assoc();
                      $tab_warehouse_name = $row['warehouse_name'];
                      echo '<option value="' . $id . '">' . $tab_warehouse_name . '</option>';
                  }
                }
                ?>
              </select>
            </div>
            <div class="col-12 mb-3">
              <label class="form-label" for="datepicker">Date Ordered</label>
              <input class="form-control datetimepicker" name="date_order" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' required/>
            </div>
          </div>
          
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" id="btn-submit-modal" type="submit">Next </button>
      </div>
    </div>
  </div>
  </form>
</div>



<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).on('click', '.view-po', function(e) {
  e.preventDefault();
  
  var targetId = $(this).attr('target-id');

  // Optional: Show a loading spinner or message
  $('#view-modal').html('<div class="text-center p-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

  // Load the PDF content into the modal
  $('#view-modal').load('pdf.php?target-id=' + targetId);
});
</script>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const submitBtn = document.getElementById('btn-submit-modal');
    const warehouseSelect = document.getElementById('warehouse');
    const dateInput = document.querySelector('input[name="date_order"]');

    // Hide submit button initially
    submitBtn.style.display = 'none';

    function toggleSubmitButton() {
      const warehouseFilled = warehouseSelect.value.trim() !== '';
      const dateFilled = dateInput.value.trim() !== '';
      submitBtn.style.display = (warehouseFilled && dateFilled) ? 'inline-block' : 'none';
    }

    warehouseSelect.addEventListener('change', toggleSubmitButton);
    dateInput.addEventListener('input', toggleSubmitButton);

    // If using a date picker like flatpickr, wait for its change
    dateInput.addEventListener('change', toggleSubmitButton);
  });
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(event) {
        const link = event.target.closest('a.requires-confirmation');

        if (link) {
            event.preventDefault();
            const href = link.getAttribute('href');

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to proceed?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, proceed!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        }
    });
});
</script>

