<?php
// Unset session variable if set
if (isset($_SESSION['inbound_id'])) {
    unset($_SESSION['inbound_id']);
}

$total_unit_cost = 0;

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
            $type_additional = "";
            break;
        case 'localCost':
            $type_additional = "AND sup.local_international = 'Local'";
            break;
        case 'importCost':
            $type_additional = "AND sup.local_international = 'International'";
            break;
        default:
            $type_additional = "";
            break;
    }

  // Fetch inbound log records with necessary joins
  $inbound_sql = "SELECT 
                    il.*, 
                    u.user_fname, 
                    u.user_lname, 
                    w.warehouse_name, 
                    sup.supplier_name, 
                    sup.local_international AS supplier_type,
                    il.user_id, 
                    s.capital, 
                    p.description, 
                    b.brand_name, 
                    c.category_name
                  FROM stocks s
                  LEFT JOIN inbound_logs il ON il.unique_key = s.unique_key
                  LEFT JOIN users u ON u.hashed_id = il.user_id
                  LEFT JOIN warehouse w ON w.hashed_id = il.warehouse
                  LEFT JOIN supplier sup ON sup.hashed_id = il.supplier
                  LEFT JOIN product p ON p.hashed_id = s.product_id
                  LEFT JOIN brand b ON b.hashed_id = p.brand
                  LEFT JOIN category c ON c.hashed_id = p.category
                  WHERE $warehouse_query
                  $additional_date_query
                  $type_additional
                  ORDER BY il.id DESC";

} else {
  // Fetch inbound log records with necessary joins
  $inbound_sql = "SELECT 
                    il.*, 
                    u.user_fname, 
                    u.user_lname, 
                    w.warehouse_name, 
                    sup.supplier_name, 
                    sup.local_international AS supplier_type,
                    il.user_id, 
                    s.capital, 
                    p.description, 
                    b.brand_name, 
                    c.category_name
                  FROM stocks s
                  LEFT JOIN inbound_logs il ON il.unique_key = s.unique_key
                  LEFT JOIN users u ON u.hashed_id = il.user_id
                  LEFT JOIN warehouse w ON w.hashed_id = il.warehouse
                  LEFT JOIN supplier sup ON sup.hashed_id = il.supplier
                  LEFT JOIN product p ON p.hashed_id = s.product_id
                  LEFT JOIN brand b ON b.hashed_id = p.brand
                  LEFT JOIN category c ON c.hashed_id = p.category
                  WHERE il.warehouse IN ($imploded_warehouse_ids)
                  ORDER BY il.id DESC";
}

$inbound_res = $conn->query($inbound_sql);
?>

<div class="card">
    <div class="card-header">
      <h2>Inbound Logs <?php if(isset($get_date)){ echo $get_date;}?></h2>
      <?php if(isset($type)){ echo $type;}?>
    </div>
    <div class="card-body overflow-hidden py-6 px-0">
        <div class="row justify-content-between gx-3 gy-0 px-3">
            <div id="tableExample3" data-list='{"valueNames":["inbound_no","po_no","warehouse","supplier","date","receiver"],"page":5,"pagination":true}'>
                <div class="row justify-content-end g-0">
                    <?php 
                    //if(strpos($access, "new_inbound")!==false || $user_position_name === "Administrator"){
                    ?>
                    <!-- <div class="col-12 mb-3 text-end">
                        <button class="btn btn-primary py-0 me-auto" type="button" data-bs-toggle="modal" data-bs-target="#error-modal">Create</button>
                        <button class="btn btn-warning py-0 me-auto" type="button" data-bs-toggle="modal" data-bs-target="#csv-modal"><span class="fas fa-file-csv"></span> Upload CSV</button>
                        <button class="btn btn-warning py-0 me-auto" type="button" data-bs-toggle="modal" data-bs-target="#csv-modal-unique"><span class="fas fa-file-csv"></span> Upload CSV(Unique Barcodes)</button>
                    </div> -->
                    <?php 
                    //}

                    if(empty($get_warehouse_id)){
                    ?>
                    <div class="col-sm-auto">
                        <select class="form-select form-select-sm mb-3  " data-list-filter="warehouse">
                            <option selected value="">Select warehouse</option>
                            <?php echo implode("\n", $warehouse_options); ?>
                        </select>
                    </div>
                    <?php 
                    }
                    ?>
                    <div class="col-auto col-sm-5 mb-3 ms-1">
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
                    <table class="table table-bordered table-striped fs-11 mb-0">
                        <thead class="bg-200">
                            <tr>
                                <th>Description</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th class="text-end">Unit Cost</th>
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
                                    <td><?php echo $row['description'];?></td>
                                    <td><?php echo $row['brand_name'];?></td>
                                    <td><?php echo $row['category_name'];?></td>
                                    <td class="text-end"><?php echo number_format((float) $row['capital'], 2); ?></td>
                                    <td class="inbound_no">
                                            <strong><?php echo $row['unique_key']; ?></strong>
                                    </td>
                                    <td class="po_no">PO#<?php echo $row['po_id']; ?></td>
                                    <td class="warehouse"><?php echo $row['warehouse_name']; ?></td>
                                    <td class="supplier"><?php echo $row['supplier_name']; ?></td>
                                    <td class="date">
                                      <?php echo date("M j, Y gA", strtotime($row['date_received'])); ?>
                                    </td>

                                    <td class="receiver"><?php echo $row['user_fname'] . " " . $row['user_lname']; ?></td>
                                </tr>
                            <?php 
                            $total_unit_cost += (float) $row['capital'];
                            } 
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-start fw-bold">Total Unit Cost:</td>
                                <td class="fw-bold text-end">
                                    ₱<?php echo number_format($total_unit_cost, 2); ?>
                                </td>
                                <td colspan="6"></td>
                            </tr>
                        </tfoot>

                    </table>
                </div>
                
                <!-- Pagination Controls -->
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

