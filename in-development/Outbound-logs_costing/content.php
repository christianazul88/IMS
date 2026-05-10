<?php

// --- 1. Initialization and Session Handling ---

// Unset the 'outbound_id' session variable if it exists.
if (isset($_SESSION['outbound_id'])) {
    unset($_SESSION['outbound_id']);
}


// --- total variables initialization
$total_unit_cost = 0;
$total_sold_price = 0;

// --- 2. Handling URL Parameters (Filtering and Validation) ---

// Initialize variables for GET parameters.
$get_date = null;
$get_type = null;
$get_warehouse_id = null;

// Check if the 'date_range', 'type', and 'wh' GET parameters are set.
if (isset($_GET['date_range'], $_GET['type'], $_GET['wh'])) {
    // Sanitize and validate the input using filter_input.
    $get_date = filter_input(INPUT_GET, 'date_range', FILTER_SANITIZE_STRING);
    $get_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
    $get_warehouse_id = filter_input(INPUT_GET, 'wh', FILTER_SANITIZE_STRING);
}


// --- 4. Preparing Warehouse Filtering ---

// Quote each warehouse ID in the user's warehouse IDs array and trim whitespace.
$quoted_warehouse_ids = array_map(function ($id) {
    return "'" . trim($id) . "'";
}, $user_warehouse_ids);

// Create a comma-separated string of the quoted warehouse IDs for the SQL query.
$imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);

// --- 5. Building the Main SQL Query for Outbound Logs ---

// Initialize the base SQL query.
$outbound_sql = "SELECT 
                      oc.unique_barcode AS barcode, 
                      oc.sold_price,
                      s.capital,
                      ol.*, 
                      u.user_fname, 
                      u.user_lname, 
                      w.warehouse_name, 
                      ol.status, 
                      ol.order_line_id, 
                      ol.order_num, 
                      sup.supplier_name, 
                      sup.local_international AS supplier_type, 
                      p.description, 
                      b.brand_name, 
                      c.category_name
                FROM outbound_content oc
                LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                LEFT JOIN users u ON u.hashed_id = ol.user_id
                LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
                LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
                LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
                LEFT JOIN product p ON p.hashed_id = s.product_id
                LEFT JOIN brand b ON b.hashed_id = p.brand
                LEFT JOIN category c ON c.hashed_id = p.category
                WHERE ol.warehouse IN ($imploded_warehouse_ids)
                ORDER BY ol.id DESC";

// Add conditions to the SQL query based on the presence of date and type filters.
if (isset($get_date) && isset($get_type)) {
    $additional_date_query = '';

    // Check if the date range contains "to".
    if (strpos($get_date, "to") !== false) {
        // Split the date range into start and end dates.
        list($start, $end) = explode(" to ", $get_date);

        // Convert the start and end dates to datetime format.
        $start_date = date("Y-m-d H:i:s", strtotime(trim($start) . " 00:00:01"));
        $end_date = date("Y-m-d H:i:s", strtotime(trim($end) . " 23:59:59"));

        // Add the date range condition to the query.
        $additional_date_query = "AND ol.date_sent BETWEEN '$start_date' AND '$end_date'";
    } else {
        // Handle single date format.
        $single_date = trim($get_date);

        // Convert the single date to the start and end of that day.
        $start_date = date("Y-m-d H:i:s", strtotime($single_date . " 00:00:00"));
        $end_date = date("Y-m-d H:i:s", strtotime($single_date . " 23:59:59"));

        // Add the single date condition to the query.
        $additional_date_query = "AND ol.date_sent BETWEEN '$start_date' AND '$end_date'";
    }

    // Add warehouse filtering if a specific warehouse ID is provided, otherwise use the user's allowed warehouses.
    if (!empty($get_warehouse_id)) {
        $warehouse_query = "ol.warehouse = '$get_warehouse_id'";
    } else {
        $warehouse_query = "ol.warehouse IN ($imploded_warehouse_ids)";
    }

    // Combine the warehouse and date conditions into the main SQL query.
    $outbound_sql = "SELECT 
                      oc.unique_barcode AS barcode, 
                      oc.sold_price,
                      s.capital,
                      ol.*, 
                      u.user_fname, 
                      u.user_lname, 
                      w.warehouse_name, 
                      ol.status, 
                      ol.order_line_id, 
                      ol.order_num, 
                      sup.supplier_name, 
                      sup.local_international AS supplier_type, 
                      p.description, 
                      b.brand_name, 
                      c.category_name
                    FROM outbound_content oc
                    LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
                    LEFT JOIN users u ON u.hashed_id = ol.user_id
                    LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
                    LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
                    LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
                    LEFT JOIN product p ON p.hashed_id = s.product_id
                    LEFT JOIN brand b ON b.hashed_id = p.brand
                    LEFT JOIN category c ON c.hashed_id = p.category
                    WHERE $warehouse_query
                    $additional_date_query
                    ORDER BY ol.id DESC"; // Added LIMIT and OFFSET for pagination
}

// Execute the outbound logs query.
$outbound_res = $conn->query($outbound_sql);

?>

<div class="card">
    <div class="card-header bg-primary bg-gradient">
        <h2 class="text-white">Outbound Logs <?php if (isset($get_date)) {
                                    echo htmlspecialchars($get_date);
                                } ?></h2>
    </div>
    <div class="card-body overflow-hidden py-6 px-0">
        <div class="row justify-content-between gx-3 gy-0 px-3">
            <div id="tableExample3" data-list='{"valueNames":["outbound_no","outbound_status","warehouse","date","receiver"],"page":10,"pagination":true}'>
                
                <div class="table-responsive scrollbar">
                    <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
                        <thead class="bg-200">
                            <tr>
                                <th>Description</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Barcode</th>
                                <th>Unit Cost</th>
                                <th>Sold For</th>
                                <th class="text-900 sort" data-sort="outbound_no">Outbound no.</th>
                                <th class="text-900 sort" data-sort="outbound_status">Fulfillment Status</th>
                                <th class="text-900 sort text-end" data-sort="outbound_status">Order #</th>
                                <th class="text-900 sort text-end" data-sort="outbound_status">Order Line ID</th>
                                <th>Supplier</th>
                                <th>Supplier Type</th>
                                <th class="text-900 sort" data-sort="warehouse">Warehouse</th>
                                <th class="text-900 sort" data-sort="date">Date</th>
                                <th class="text-900 sort" data-sort="receiver">Client</th>
                            </tr>
                        </thead>
                        <tbody class="list">
                            <?php while ($row = $outbound_res->fetch_assoc()) {
                                $outbound_id = htmlspecialchars($row['hashed_id']);
                                $outbound_barcode = htmlspecialchars($row['barcode']);
                                $outbound_warehouse = htmlspecialchars($row['warehouse_name']);
                                $outbound_date = htmlspecialchars($row['date_sent']);
                                $outbound_receiver = htmlspecialchars($row['customer_fullname']);
                                $order_no = htmlspecialchars($row['order_num']);
                                $order_line = htmlspecialchars($row['order_line_id']);
                                $outbound_supplier = htmlspecialchars($row['supplier_name']);
                                $outbound_supplier_type = htmlspecialchars($row['supplier_type']);
                                $outbound_product = htmlspecialchars($row['description']);
                                $outbound_brand = htmlspecialchars($row['brand_name']);
                                $outbound_category = htmlspecialchars($row['category_name']);
                                $outbount_unit_cost = isset($row['capital']) ? (float)$row['capital'] : 0.00;
                                $outbound_sold_amount = isset($row['sold_price']) ? (float)$row['sold_price'] : 0.00;

                                $total_unit_cost += $outbount_unit_cost;
                                $total_sold_price += $outbound_sold_amount;


                                $outbound_status = '';
                                switch ($row['status']) {
                                    case 0:
                                        $outbound_status = '<span class="badge rounded-pill badge-subtle-success">Paid</span>';
                                        break;
                                    case 1:
                                        $outbound_status = '<span class="badge rounded-pill badge-subtle-success">Paid w/ return</span><span class="badge rounded-pill badge-subtle-danger">-1</span>';
                                        break;
                                    case 2:
                                        $outbound_status = '<span class="badge rounded-pill badge-subtle-danger">Returned</span>';
                                        break;
                                    case 3:
                                        $outbound_status = '<span class="badge rounded-pill badge-subtle-danger">Void Requested</span>';
                                        break;
                                    case 4:
                                        $outbound_status = '<span class="badge rounded-pill badge-subtle-primary">Voided</span>';
                                        break;
                                    case 5:
                                        $outbound_status = '<span class="badge rounded-pill badge-subtle-danger">Void Rejected</span>';
                                        break;
                                    case 6:
                                        $outbound_status = '<span class="badge rounded-pill badge-subtle-info">Outbounded</span>';
                                        break;
                                }
                            ?>
                                <tr>
                                    <td><?php echo $outbound_product; ?></td>
                                    <td><?php echo $outbound_brand; ?></td>
                                    <td><?php echo $outbound_category; ?></td>
                                    <td><?php echo $outbound_barcode; ?></td>
                                    <td><?php echo $outbount_unit_cost;?></td>
                                    <td><?php echo $outbound_sold_amount;?></td>
                                    <td class="outbound_no">
                                        <?php echo $outbound_id; ?>
                                    </td>
                                    <td class="outbound_status text-center"><?php echo $outbound_status; ?></td>
                                    <td class="outbound_status text-end"><?php echo $order_no; ?></td>
                                    <td class="outbound_status text-end"><?php echo $order_line; ?></td>
                                    <td><?php echo $outbound_supplier; ?></td>
                                    <td><?php echo $outbound_supplier_type; ?></td>
                                    <td class="warehouse"><?php echo $outbound_warehouse; ?></td>
                                    <td class="date"><?php echo $outbound_date; ?></td>
                                    <td class="receiver"><?php echo $outbound_receiver; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-start fw-bold">Total:</td>
                                <td class="fw-bold">₱<?php echo number_format($total_unit_cost, 2); ?></td>
                                <td class="fw-bold">₱<?php echo number_format($total_sold_price, 2); ?></td>
                                <td colspan="9"></td>
                            </tr>
                        </tfoot>

                    </table>
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
                  <div class="col-auto d-flex">
                    <button class="btn btn-sm btn-primary" type="button" data-list-pagination="prev">
                      <span>Previous</span>
                    </button>
                    <button class="btn btn-sm btn-primary px-4 ms-2" type="button" data-list-pagination="next">
                      <span>Next</span>
                    </button>
                  </div>
                </div>
            </div>
        </div>
    </div>
</div>