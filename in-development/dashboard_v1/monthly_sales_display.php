<div class="card py-3 mb-3">
    <div class="card-body py-3">
        <div class="row g-0">
            <div class="col-6 col-md-4 border-200 border-bottom border-end pb-4">
                <?php
                $dashboard_warehouses = "SELECT COUNT(hashed_id) AS warehouse_qty FROM warehouse";
                $dashboard_warehouses_res = $conn->query($dashboard_warehouses);
                if($dashboard_warehouses_res->num_rows>0){
                    $row=$dashboard_warehouses_res->fetch_assoc();
                    $warehouse_qty = $row['warehouse_qty'];
                }
                ?>
                <h6 class="pb-1 text-700">Warehouses</h6>
                <p class="font-sans-serif lh-1 mb-1 fs-7"><?php echo number_format($warehouse_qty);?></p>
                
            </div>

            <div class="col-6 col-md-4 border-200 border-bottom border-end-md-0 pb-4 ps-md-3">
            <?php 
                // Single query to get both active and disabled user counts
                $dashboard_employees = "
                    SELECT 
                        COUNT(CASE WHEN `status` != 0 THEN 1 END) AS users_qty_active, 
                        COUNT(CASE WHEN `status` = 0 THEN 1 END) AS users_qty_disabled
                    FROM users
                ";
                
                $dashboard_employees_res = $conn->query($dashboard_employees);
                if ($dashboard_employees_res) {
                    $row = $dashboard_employees_res->fetch_assoc();
                    $users_qty= $row['users_qty_active'];
                    $users_qty_disabled = $row['users_qty_disabled'];
                }

                if ($users_qty != 0) {
                    $users_percentage = ($users_qty_disabled / $users_qty) * 100;
                } else {
                    $users_percentage = 0;
                }
                

                ?>
                <h6 class="pb-1 text-700">Users</h6>
                <p class="font-sans-serif lh-1 mb-1 fs-7"><?php echo $users_qty;?></p>
                <div class="d-flex align-items-center">
                    <h6 class="fs-10 text-500 mb-0"><?php echo $users_qty_disabled;?></h6>
                    <h6 class="fs-11 ps-3 mb-0 text-danger" data-bs-toggle="tooltip" data-bs-placement="bottom" title="are disabled users">
                        <span class="fas fa-caret-left"></span> <?php echo $users_percentage;?>%
                    </h6>
                </div>
            </div>

            <div class="col-6 col-md-4 border-200 border-bottom border-end-md-0 pb-4">
            <?php 
                $customers_query = "
                    SELECT 
                        COUNT(DISTINCT CASE WHEN MONTH(date_sent) = MONTH(CURDATE()) AND YEAR(date_sent) = YEAR(CURDATE()) THEN customer_fullname END) AS customer_qty,
                        COUNT(DISTINCT CASE WHEN MONTH(date_sent) = MONTH(CURDATE()) - 1 AND YEAR(date_sent) = YEAR(CURDATE()) THEN customer_fullname END) AS customer_qty_prev
                    FROM outbound_logs
                    WHERE 
                        (MONTH(date_sent) = MONTH(CURDATE()) AND YEAR(date_sent) = YEAR(CURDATE())) 
                        OR 
                        (MONTH(date_sent) = MONTH(CURDATE()) - 1 AND YEAR(date_sent) = YEAR(CURDATE()) )
                ";
                
                $customers_res = $conn->query($customers_query);
                if ($customers_res->num_rows > 0) {
                    $row = $customers_res->fetch_assoc();
                    $customer_qty = $row['customer_qty'];
                    $customer_qty_prev = $row['customer_qty_prev'];
                } else {
                    $customer_qty = 0;
                    $customer_qty_prev = 0;
                }
            
                
                if ($customer_qty_prev != 0) {
                    $customer_percentage = ($customer_qty / $customer_qty_prev) * 100;
                } else {
                    // Handle the case where the previous quantity is zero
                    $customer_percentage = 0; // or set a default value or message
                }
                
                if($customer_qty > $customer_qty_prev){
                    $customer_display = '<h6 class="fs-11 ps-3 mb-0 text-success" data-bs-toggle="tooltip" data-bs-placement="bottom" title="number of customers previous month">
                                            <span class="me-1 fas fa-caret-up"></span>' . $customer_percentage . '%
                                        </h6>';
                } elseif($customer_qty < $customer_qty_prev) {
                    $customer_display = '<h6 class="fs-11 ps-3 mb-0 text-danger" data-bs-toggle="tooltip" data-bs-placement="bottom" title="number of customers previous month">
                                            <span class="me-1 fas fa-caret-down"></span>' . $customer_percentage . '%
                                        </h6>';
                } else {
                    $customer_display = '<h6 class="fs-11 ps-3 mb-0 text-warning" data-bs-toggle="tooltip" data-bs-placement="bottom" title="number of customers previous month">
                                            <span class="me-1 fas fa-caret-right"></span> nothing yet
                                        </h6>';
                }
                

                ?>
                <h6 class="pb-1 text-700">Customers</h6>
                <p class="font-sans-serif lh-1 mb-1 fs-7" data-bs-toggle="tooltip" data-bs-placement="left" title="number of customers this month"><?php echo $customer_qty;?></p>
                <div class="d-flex align-items-center">
                    <h6 class="fs-10 text-500 mb-0"><?php echo $customer_qty_prev;?></h6>
                    <?php echo $customer_display;?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Get the current and previous year
$current_year = date('Y');
$previous_year = $current_year - 1;

// Initialize variables for sales data
$last_year_sales = 0;
$current_year_sales = 0;

// Query to get the outbound sales grouped by year
$monthly_outbound_sales_query = "
    SELECT YEAR(date_sent) AS year, SUM(sold_price) AS total_outbound_sale 
    FROM outbound_logs ol 
    JOIN outbound_content oc ON ol.hashed_id = oc.hashed_id
    WHERE YEAR(date_sent) IN ($previous_year, $current_year)
    AND warehouse IN ($imploded_warehouse_ids)
    GROUP BY YEAR(date_sent)
    ORDER BY YEAR(date_sent) ASC
";

$monthly_outbound_sales_res = $conn->query($monthly_outbound_sales_query);

if ($monthly_outbound_sales_res->num_rows > 0) {
    while ($row = $monthly_outbound_sales_res->fetch_assoc()) {
        if ($row['year'] == $previous_year) {
            $last_year_sales = $row['total_outbound_sale'];
        } elseif ($row['year'] == $current_year) {
            $current_year_sales = $row['total_outbound_sale'];
        }
    }
} 
?>

<div class="card">
    <div class="card-header">
        <div class="row flex-between-center g-0">
            <div class="col-auto">
                <h6 class="mb-0">Monthly Outbound Sales</h6>
            </div>
            <div class="col-auto d-flex">
                <div class="form-check mb-0 d-flex">
                    <input class="form-check-input form-check-input-primary" id="ecommerceLastMonth" type="checkbox" checked>
                    <label class="form-check-label ps-2 fs-11 text-600 mb-0" for="ecommerceLastMonth">
                        Current Year<span class="text-1100 d-none d-md-inline">: ₱<?php echo number_format($current_year_sales, 2);;?></span>
                    </label>
                </div>
                <div class="form-check mb-0 d-flex ps-0 ps-md-3">
                    <input class="form-check-input ms-2 form-check-input-warning opacity-75" id="ecommercePrevYear" type="checkbox" checked>
                    <label class="form-check-label ps-2 fs-11 text-600 mb-0" for="ecommercePrevYear">
                        Prev Year<span class="text-1100 d-none d-md-inline">: ₱<?php echo number_format($last_year_sales, 2);?></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body pe-xxl-0">
        <div class="echart-line-total-sales-ecommerce" 
            data-echart-responsive="true" 
            data-options='{"optionOne":"ecommerceLastMonth","optionTwo":"ecommercePrevYear"}'>
        </div>
    </div>
</div>
