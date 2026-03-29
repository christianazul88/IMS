<?php
// Unset session variable if set
if (isset($_SESSION['inbound_id'])) {
    unset($_SESSION['inbound_id']);
}

if(isset($_SESSION['po_list'])){
  unset($_SESSION['po_list']);
}

if (isset($_GET['date_range'], $_GET['type'], $_GET['wh'])) {
    // Sanitize and validate input
    $get_date = filter_input(INPUT_GET, 'date_range', FILTER_SANITIZE_STRING);
    $get_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
    $get_warehouse_id = filter_input(INPUT_GET, 'wh', FILTER_SANITIZE_STRING);

    switch ($get_type) {
        case 'inboundQty':
            $type = "All local and imports.";
            break;

        case 'localQty':
            $type = "All local inbounds.";
            break;
        
        case 'importQty':
            $type = "All import inbounds.";
            break;
        case 'inboundCost':
            $type = "All inbound cost.";
            break;
        case 'localCost':
            $type = "All local inbound cost.";
            break;
        case 'importCost':
            $type = "All import inbound cost.";
            break;
        default:
            $type = "";
            break;
    }
}


// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM inbound_logs";
$count_res = $conn->query($count_sql);
$total_rows = $count_res->fetch_assoc()['total'];
$limit = 10; // Number of records per page
$total_pages = ceil($total_rows / $limit);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;


// Quote each ID in the array
$quoted_warehouse_ids = array_map(function ($id) {
  return "'" . trim($id) . "'";
}, $user_warehouse_ids);

// Create a comma-separated string of quoted IDs
$imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);



if(isset($get_date) && isset($get_type)){
  if(strpos($get_date, "to")!==false){
  // Split the date range
  list($start, $end) = explode(" to ", $get_date);

  // Convert to datetime format
  $start_date = date("Y-m-d H:i:s", strtotime(trim($start) . " 00:00:01"));
  $end_date = date("Y-m-d H:i:s", strtotime(trim($end) . " 23:59:59"));

  $additional_date_query = "AND il.date_received BETWEEN '$start_date' AND '$end_date'";
  } else {
  // Single date format like "May 17, 2025"
  $single_date = trim($get_date);

  // Convert to start and end of that day
  $start_date = date("Y-m-d H:i:s", strtotime($single_date . " 00:00:00"));
  $end_date = date("Y-m-d H:i:s", strtotime($single_date . " 23:59:59"));

  $additional_date_query = "AND il.date_received BETWEEN '$start_date' AND '$end_date'";
  }
  
  if(!empty($get_warehouse_id)){
      $warehouse_query = "il.warehouse = '$get_warehouse_id'";
  } else {
      $warehouse_query = "il.warehouse IN ($imploded_warehouse_ids)";
  }

  switch ($get_type) {
        case 'inboundQty':
            $type_additional = "";
            break;

        case 'localQty':
            $type_additional = "AND s.local_international = 'Local'";
            break;
        
        case 'importQty':
            $type_additional = "AND s.local_international = 'International'";
            break;
        case 'inboundCost':
            $type_additional = "All inbound cost.";
            break;
        case 'localCost':
            $type_additional = "All local inbound cost.";
            break;
        case 'importCost':
            $type_additional = "All import inbound cost.";
            break;
        default:
            $type_additional = "";
            break;
    }

  // Fetch inbound log records with necessary joins
  $inbound_sql = "SELECT il.*, u.user_fname, u.user_lname, w.warehouse_name, s.supplier_name, il.user_id
                  FROM inbound_logs il
                  LEFT JOIN users u ON u.hashed_id = il.user_id
                  LEFT JOIN warehouse w ON w.hashed_id = il.warehouse
                  LEFT JOIN supplier s ON s.hashed_id = il.supplier
                  WHERE $warehouse_query
                  $additional_date_query
                  $type_additional
                  ORDER BY il.id DESC";

} else {
  // Fetch inbound log records with necessary joins
  $inbound_sql = "SELECT il.*, u.user_fname, u.user_lname, w.warehouse_name, s.supplier_name, il.user_id
                  FROM inbound_logs il
                  LEFT JOIN users u ON u.hashed_id = il.user_id
                  LEFT JOIN warehouse w ON w.hashed_id = il.warehouse
                  LEFT JOIN supplier s ON s.hashed_id = il.supplier
                  WHERE il.warehouse IN ($imploded_warehouse_ids)
                  ORDER BY il.id DESC";
}

$inbound_res = $conn->query($inbound_sql);
?>

<div class="card">
    <div class="card-header bg-warning">
      <h2 class="text-white">Inbound Logs <?php if(isset($get_date)){ echo $get_date;}?></h2>
      <?php if(isset($type)){ echo $type;}?>
    </div>
    <div class="card-body overflow-hidden py-6 px-0">
        <div class="row justify-content-between gx-3 gy-0 px-3">
            <div id="tableExample3" data-list='{"valueNames":["inbound_no","po_no","warehouse","supplier","date","receiver"],"page":5,"pagination":true}'>
                <div class="row justify-content-end g-0">
                    <?php 
                    if(strpos($access, "new_inbound")!==false || $user_position_name === "Administrator"){
                    ?>
                    <div class="col-12 mb-3 text-end">
                        <button class="btn btn-primary py-0 me-auto" type="button" data-bs-toggle="modal" data-bs-target="#error-modal">Create</button>
                        <button class="btn btn-warning py-0 me-auto" type="button" data-bs-toggle="modal" data-bs-target="#csv-modal"><span class="fas fa-file-csv"></span> Upload CSV</button>
                        <button class="btn btn-warning py-0 me-auto" type="button" data-bs-toggle="modal" data-bs-target="#csv-modal-unique"><span class="fas fa-file-csv"></span> Upload CSV(Unique Barcodes)</button>
                    </div>
                    <?php 
                    }
                    ?>
                    <div class="col-sm-auto">
                        <select class="form-select form-select-sm mb-3  " data-list-filter="warehouse">
                            <option selected value="">Select warehouse</option>
                            <?php echo implode("\n", $warehouse_options); ?>
                        </select>
                    </div>
                    <div class="col-auto col-sm-5 mb-3">
                        <form>
                            <div class="input-group">
                                <input class="form-control form-control-sm shadow-none search" type="search" placeholder="Search..." aria-label="search" />
                                <div class="input-group-text bg-transparent"><span class="fa fa-search fs-10 text-600"></span></div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Table -->
                <div class="table-responsive scrollbar">
                    <table class="table table-bordered table-striped fs-10 mb-0">
                        <thead class="bg-200">
                            <tr>
                                <th class="p-0"></th>
                                <th class="text-900 sort" data-sort="inbound_no">Inbound no.</th>
                                <th class="text-900 sort" data-sort="po_no">P.O no.</th>
                                <th class="text-900 sort" data-sort="warehouse">Warehouse</th>
                                <th class="text-900 sort" data-sort="supplier">Supplier</th>
                                <th class="text-900 sort" data-sort="date">Date Received</th>
                                <th class="text-900 sort" data-sort="receiver">Received by</th>
                            </tr>
                        </thead>
                        <tbody class="list" id="table-body">
                            <?php while ($row = $inbound_res->fetch_assoc()) { ?>
                                <tr>
                                    <td class="p-0">
                                      <?php 
                                      if($row['user_id'] === $user_id && $row['status'] == 0){
                                      ?>
                                      <button class="btn btn-primary py-1 fs-11 undo" target-id="<?php echo $row['unique_key'];?>" type="button" data-bs-toggle="modal" data-bs-target="#void-modal"><span class="fas fa-trash-alt"></span> Void</button>
                                      <?php 
                                      } elseif($row['user_id'] !== $user_id && $row['status'] == 0){
                                      ?>
                                      <span class="badge badge-subtle-primary fs-11"><span class="fas fa-check-circle"></span> Inbounded</span>
                                      <?php
                                      }elseif (strpos($access, "approve_inbound")!==false && $row['status'] == 1 || $user_position_name === "Administrator" && $row['status'] == 1){
                                      ?>
                                      <button class="btn btn-warning fs-11 approve" inbound-id="<?php echo $row['unique_key'];?>" to-userid="<?php echo $row['user_id'];?>" type="button" data-bs-toggle="modal" data-bs-target="#approve-modal"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="width: 15px; height:15px;"></span> For review</button>
                                      <!-- <button class="btn btn-primary fs-11 approval-btn" response="approve" doc_id="<?php //echo $row['unique_key'];?>" to_userid="<?php // echo $row['user_id'];?>"><span class="fas fa-check"></span></button>
                                      <button class="btn btn-danger fs-11 approval-btn" response="decline" doc_id="<?php //echo $row['unique_key'];?>" to_userid="<?php // echo $row['user_id'];?>"><span class="far fa-window-close"></span></button> -->
                                      <?php
                                      } elseif(strpos($access, "approve_inbound")!==true && $row['status'] == 1) {
                                      ?>
                                      <span class="badge rounded-pill bg-warning fs-11" type="button" disabled="">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="width: 15px; height:15px;"></span>
                                        Pending...
                                      </span>
                                      <?php
                                      } elseif($row['status'] == 2) {
                                      ?>
                                      <span class="badge rounded-pill bg-danger fs-11"><span class="fas fa-exclamation-triangle"></span> declined</span>
                                      <?php
                                      } else {
                                      ?>
                                      <span class="badge rounded-pill bg-success fs-11"><span class="fas fa-check"></span> Approved</span>
                                      <?php
                                      }
                                      ?>
                                    </td>
                                    <td class="inbound_no">
                                        <a type="button" data-bs-toggle="modal" data-bs-target="#view-modal" target-id="<?php echo $row['unique_key']; ?>">
                                            <strong><?php echo $row['unique_key']; ?></strong>
                                        </a>
                                    </td>
                                    <td class="po_no">PO#<?php echo $row['po_id']; ?></td>
                                    <td class="warehouse"><?php echo $row['warehouse_name']; ?></td>
                                    <td class="supplier"><?php echo $row['supplier_name']; ?></td>
                                    <td class="date"><?php echo $row['date_received']; ?></td>
                                    <td class="receiver"><?php echo $row['user_fname'] . " " . $row['user_lname']; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Controls -->
                <div class="d-flex justify-content-center mt-3">
                    <button class="btn btn-sm btn-falcon-default me-1" type="button" title="Previous" data-list-pagination="prev"><span class="fas fa-chevron-left"></span></button>
                    <ul class="pagination mb-0"></ul>
                    <button class="btn btn-sm btn-falcon-default ms-1" type="button" title="Next" data-list-pagination="next"><span class="fas fa-chevron-right"> </span></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- void modal -->
<div class="modal fade" id="void-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
    <form id="void_form">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
          <h4 class="mb-1" id="modalExampleDemoLabel">Void Inbound? </h4>
        </div>
        <div class="p-4 pb-0">
            <div id="load-content"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" type="submit">Submit </button>
      </div>
    </div>
    </form>
  </div>
</div>

<!-- approved modal -->
<div class="modal fade" id="approve-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
    <form id="approve_form">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
          <h4 class="mb-1" id="modalExampleDemoLabel">Approve delete? </h4>
        </div>
        <div class="p-4 pb-0">
            <div id="load-approve"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" type="submit">Submit </button>
      </div>
    </div>
    </form>
  </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="view-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content position-relative">
            <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="target-id"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- upcoming -->
<div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
          <h4 class="mb-1" id="modalExampleDemoLabel">Select Supplier</h4>
        </div>
        <div class="p-4 pb-0">
          <form action="set_inbound_session.php" method="POST">
          <div class="row">

            <div class="col-lg-5 mb-3">
              <label for="">P.O no</label>
              <select name="po_id" class="form-select" required>
                <option value="" selected>Select Purchased Order #</option>
                <?php 
                  // Quote each ID in the array
                  $quoted_warehouse_ids = array_map(function ($id) {
                    return "'" . trim($id) . "'";
                  }, $user_warehouse_ids);

                  // Create a comma-separated string of quoted IDs
                  $imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);
                  // Ensure that $imploded_warehouse_ids is safely included in the query to avoid SQL injection
                  if (isset($imploded_warehouse_ids) && !empty($imploded_warehouse_ids)) {
                    // Assuming $imploded_warehouse_ids is a comma-separated string of integers
                    $po_query = "
SELECT 
    po.id, 
    sup.local_international AS supplier_type 
FROM 
    purchased_order po
LEFT JOIN 
    supplier sup ON sup.hashed_id = po.supplier
WHERE 
    po.warehouse IN ($imploded_warehouse_ids)
    AND (
        (po.status = 4 AND sup.local_international = 'International')
        OR (po.status = 0 AND sup.local_international = 'Local')
        OR (sup.local_international = 'Hakot')
    )
    AND po.date_received IS NULL
ORDER BY 
    po.id DESC";

                    // Execute the query
                    $res = $conn->query($po_query);

                    // Check if the query is successful and returns rows
                    if ($res) {
                      if ($res->num_rows > 0) {
                        // Output the options for PO
                        while ($row = $res->fetch_assoc()) {
                          echo '<option value="' . htmlspecialchars($row['id']) . '">PO-' . htmlspecialchars($row['id']) . '</option>';
                        }
                      } else {
                        // If no rows returned, suggest creating a PO
                        echo '<option value="">No POs found, please create one</option>';
                      }
                    } else {
                      // Handle query failure (optional)
                      echo '<option value="">Error fetching data</option>';
                    }
                  } else {
                    echo '<option value="">Invalid or empty warehouse IDs</option>';
                  }
                ?>
              </select>

            </div>

            <!-- <div class="col-lg-7 mb-3">
              <label class="form-label" for="datepicker">Received Date</label>
              <input class="form-control datetimepicker" name="received_date" id="datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' required/>
            </div> -->
            
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" type="submit">Next</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="csv-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
          <h4 class="mb-1" id="modalExampleDemoLabel">Upload CSV</h4>
        </div>
        <div class="p-4 pb-0">
          <form action="../csv-select/index" method="POST" enctype="multipart/form-data">
          <div class="row">

            <div class="col-lg-5 mb-3">
              <label class="col-form-label" for="recipient-name">Warehouse:</label>
              <select class="form-select" id="warehouse" required="required" name="warehouse" required>
                <option value="">Select warehouse...</option>
                  <?php echo implode("\n", $warehouse_options2); ?>
                <?php ?>
              </select>
              <div class="invalid-feedback">Please select one</div>
            </div>

            <!-- <div class="col-lg-4 mb-3">
              <label class="form-label" for="datepicker">Received Date</label>
              <input class="form-control datetimepicker" name="received_date" id="datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' required/>
            </div> -->
            
            <div class="col-lg-5 mb-3">
                <label for="">Upload CSV</label>
                <input type="file" name="csv_file" id="input_csv" class="form-control" accept=".csv" required>
            </div>

            

          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
        <input class="btn btn-primary" type="submit" name="submit" value="Upload CSV">
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="csv-modal-unique" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
          <h4 class="mb-1" id="modalExampleDemoLabel">Upload CSV</h4>
        </div>
        <div class="p-4 pb-0">
          <form action="../csv-select-unique/index" method="POST" enctype="multipart/form-data">
          <div class="row">

            <div class="col-lg-5 mb-3">
              <label class="col-form-label" for="recipient-name">Warehouse:</label>
              <select class="form-select" id="warehouse" required="required" name="warehouse" required>
                <option value="">Select warehouse...</option>
                  <?php echo implode("\n", $warehouse_options2); ?>
                <?php ?>
              </select>
              <div class="invalid-feedback">Please select one</div>
            </div>

            <!-- <div class="col-lg-4 mb-3">
              <label class="form-label" for="datepicker">Received Date</label>
              <input class="form-control datetimepicker" name="received_date" id="datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' required/>
            </div> -->
            
            <div class="col-lg-5 mb-3">
                <label for="">Upload CSV</label>
                <input type="file" name="csv_file" id="input_csv" class="form-control" accept=".csv" required>
            </div>

          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
        <input class="btn btn-primary" type="submit" name="submit" value="Upload CSV">
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Load modal content dynamically
$(document).on("click", "a[data-bs-toggle='modal']", function() {
    var targetId = $(this).attr("target-id"); // Get unique key
    $("#target-id").load("form-content.php?id=" + targetId); // Load content
});


// When the void button (with class .undo) is clicked,
// load modal content into #load-content from void_modal_content.php
$(document).on("click", ".undo", function() {
    var targetId = $(this).attr("target-id");
    // Load void modal content dynamically into the #load-content container
    $("#load-content").load("void_modal_content.php?target-id=" + targetId);
});


// Override form submission for the void modal form (#void_form)
// to show SweetAlert2 confirmation and then submit the form via AJAX
$(document).on("submit", "#void_form", function(e) {
    e.preventDefault(); // Prevent the default form submission
    
    // Store form reference for further use
    var form = $(this);
    
    // Show confirmation dialog using SweetAlert2
    Swal.fire({
        title: 'Please Confirm',
        text: 'Are you sure you want to void this inbound?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, void it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Use AJAX to submit the form without reloading the page.
            $.ajax({
                url: form.attr('action') || '../config/undo_inbound.php', // use form action or default handler URL
                method: form.attr('method') || 'POST', // use form method or default to POST
                data: form.serialize(), // serialize all form data
                dataType: 'json'
            }).done(function(response) {
                // Show the server response using SweetAlert2
                Swal.fire({
                    title: response.success ? 'Success' : 'Error',
                    text: response.message,
                    icon: response.success ? 'success' : 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    if (response.success) {
                        setTimeout(function() {
                            location.reload(); // Reload the page after 2 seconds
                        }, 1000);
                    }
                });
            }).fail(function(jqXHR, textStatus, errorThrown) {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred: ' + textStatus,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        }
    });
});


// approve
$(document).on("click", ".approve", function() {
    var inboundId = $(this).attr("inbound-id");
    var toUserId = $(this).attr("to-userid");
    // Load void modal content dynamically into the #load-content container
    $("#load-approve").load(`approve_modal_content.php?inbound-id=${inboundId}&&to_userid=${toUserId}`);
});

$(document).on("submit", "#approve_form", function(e) {
    e.preventDefault(); // Prevent the default form submission
    
    // Store form reference for further use
    var form = $(this);
    
    // Show confirmation dialog using SweetAlert2
    Swal.fire({
        title: 'Please Confirm',
        text: 'Are you sure you want to void this inbound?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, void it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Use AJAX to submit the form without reloading the page.
            $.ajax({
                url: form.attr('action') || '../config/inbound_approval.php', // use form action or default handler URL
                method: form.attr('method') || 'POST', // use form method or default to POST
                data: form.serialize(), // serialize all form data
                dataType: 'json'
            }).done(function(response) {
                // Show the server response using SweetAlert2
                Swal.fire({
                    title: response.success ? 'Success' : 'Error',
                    text: response.message,
                    icon: response.success ? 'success' : 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    if (response.success) {
                        setTimeout(function() {
                            location.reload(); // Reload the page after 2 seconds
                        }, 1000);
                    }
                });
            }).fail(function(jqXHR, textStatus, errorThrown) {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred: ' + textStatus,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        }
    });
});



</script>


<?php
if (isset($_GET['error']) && $_GET['error'] === "error") {
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'warning',
                title: 'Warehouse Field Empty',
                text: 'Please fill in the warehouse field and try again.',
                confirmButtonText: 'OK'
            });
        });
    </script>
    ";
}
?>
