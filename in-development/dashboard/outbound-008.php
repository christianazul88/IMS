<?php 
include "../config/database.php";
include "../config/on_session.php";

// Determine the time of day
$hour = date("H"); // Get the current hour in 24-hour format

if ($hour >= 5 && $hour < 12) {
    $time_name = "Morning";
} elseif ($hour >= 12 && $hour < 17) {
    $time_name = "Afternoon";
} elseif ($hour >= 17 && $hour < 21) {
    $time_name = "Evening";
} else {
    $time_name = "Midnight";
}


$quoted_warehouse_ids = array_map(function ($id) {
    return "'" . trim($id) . "'";
}, $user_warehouse_ids);

// Create a comma-separated string of quoted IDs
$imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);
$dashboard_wh = $_GET['wh'];
// $outbound_today = 0;
// $outbound_sales_today = 0;

// // Query to fetch outbound logs for today
// $outbound_query = "SELECT hashed_id AS ol_id FROM outbound_logs WHERE DATE(date_sent) = CURDATE() AND warehouse IN ($imploded_warehouse_ids)";
// $outbound_res = $conn->query($outbound_query);

// if ($outbound_res->num_rows > 0) {
//     while ($row = $outbound_res->fetch_assoc()) {
//         $outbound_id = $row['ol_id'];
//         $outbound_today++;

//         // Query to fetch sold price from outbound_content
//         $total_outbound_sales_query = "SELECT sold_price FROM outbound_content WHERE hashed_id = '$outbound_id'";
//         $total_outbound_sales_res = $conn->query($total_outbound_sales_query);
        
//         if ($total_outbound_sales_res->num_rows > 0) {
//             while ($row = $total_outbound_sales_res->fetch_assoc()) {
//                 $sold_price = $row['sold_price'];
//                 $outbound_sales_today += $sold_price;
//             }
//         }
//     }
// }

// // Format numbers
// $formatted_outbound_today = number_format($outbound_today);
// $formatted_outbound_sales_today = number_format($outbound_sales_today, 2);

$morethan_3_months = 0;
if(empty($dashboard_wh)){
    $morethan_3_months_query = "
        SELECT COUNT(unique_barcode) AS count 
        FROM stocks 
        WHERE DATE(`date`) <= DATE_SUB(CURDATE(), INTERVAL 3 MONTH) 
        AND warehouse IN ($imploded_warehouse_ids)
    ";
} else {
    $morethan_3_months_query = "
        SELECT COUNT(unique_barcode) AS count 
        FROM stocks 
        WHERE DATE(`date`) <= DATE_SUB(CURDATE(), INTERVAL 3 MONTH) 
        AND warehouse = '$dashboard_wh'
    ";
}

$morethan_3_months_res = $conn->query($morethan_3_months_query);

if ($morethan_3_months_res) {
    $row = $morethan_3_months_res->fetch_assoc();
    $morethan_3_months = $row['count'] ?? 0;
}


$morethan_3_months_display = '
    <li class="list-group-item mb-0 rounded-0 py-3 px-x1 list-group-item-' . ($morethan_3_months > 0 ? 'warning' : 'success') . ' border-x-0 border-top-0">
        <div class="row flex-between-center">
            <div class="col">
                <div class="d-flex">
                    <div class="fas fa-circle mt-1 fs-11"></div>
                    <p class="fs-10 ps-2 mb-0"><strong>' . 
                        ($morethan_3_months > 0 ? $morethan_3_months . ' products' : 'No products') . 
                    '</strong> has been staying in your inventory for more than 3 months.</p>
                </div>
            </div>
            <div class="col-auto d-flex align-items-center">
                '
                . ($morethan_3_months > 0 ? '<a class="fs-10 fw-medium text-warning-emphasis" href="../Extended-Shelf-Items/">View products
                    <i class="fas fa-chevron-right ms-1 fs-11"></i>
                </a>' : '') .
                '
                
            </div>
        </div>
    </li>';

$under_safety = 0;
$under_safety_query = "SELECT `safety`, hashed_id AS product_id FROM product";
$under_safety_res = $conn->query($under_safety_query);
if($under_safety_res->num_rows>0){
    while($row = $under_safety_res->fetch_assoc()){
        $product_safety = $row['safety'];
        $product_id = $row['product_id'];
        $product_qty = 0;
        if(empty($dashboard_wh)){
            $stock_query = "SELECT item_status FROM stocks WHERE product_id = '$product_id' AND warehouse IN ($imploded_warehouse_ids)";
        } else {
            $stock_query = "SELECT item_status FROM stocks WHERE product_id = '$product_id' AND warehouse = '$dashboard_wh'";
        }
        $stock_res = $conn->query($stock_query);
        if($stock_res->num_rows>0){
            while($row = $stock_res->fetch_assoc()){
                $item_status = $row['item_status'];
                if($item_status == 0){
                    $product_qty ++;
                }
            }
            if($product_qty<$product_safety){
                $under_safety++;
            }
        }
    }
}
$formatted_under_safety = number_format($under_safety);
if($under_safety>0){
    $under_safety_display = '
            <li class="list-group-item mb-0 rounded-0 py-3 px-x1 list-group-item-danger greetings-item text-700 border-x-0 border-top-0">
                <div class="row flex-between-center">
                    <div class="col">
                        <div class="d-flex">
                            <div class="fas fa-circle mt-1 fs-11 text-primary"></div>
                            <p class="fs-10 ps-2 mb-0"><strong>' . $formatted_under_safety . ' products</strong> are under safety level.</p>
                        </div>
                    </div>
                    <div class="col-auto d-flex align-items-center">
                        <a class="fs-10 fw-medium" href="../Inventory-stock/">View products
                            <i class="fas fa-chevron-right ms-1 fs-11"></i>
                        </a>
                    </div>
                </div>
            </li>
    ';
} else {
    $under_safety_display = '
            <li class="list-group-item mb-0 rounded-0 py-3 px-x1 list-group-item-success greetings-item text-700 border-x-0 border-top-0">
                <div class="row flex-between-center">
                    <div class="col">
                        <div class="d-flex">
                            <div class="fas fa-circle mt-1 fs-11 text-primary"></div>
                            <p class="fs-10 ps-2 mb-0"><strong>' . $formatted_under_safety . ' products</strong> are under safety level.</p>
                        </div>
                    </div>
                    <div class="col-auto d-flex align-items-center">
                        
                    </div>
                </div>
            </li>
    ';
}
if(empty($dashboard_wh)){
    $available_stocks_query = "SELECT count(unique_barcode) AS available_stocks FROM stocks WHERE item_status = 0 AND warehouse IN ($imploded_warehouse_ids)";
} else {
    $available_stocks_query = "SELECT count(unique_barcode) AS available_stocks FROM stocks WHERE item_status = 0 AND warehouse = '$dashboard_wh'";
}
$available_stocks_res = $conn->query($available_stocks_query);
if($available_stocks_res->num_rows>0){
    $row=$available_stocks_res->fetch_assoc();
    $available_stocks = $row['available_stocks'];
}

if($available_stocks>1){
    $available_stocks_display = number_format($available_stocks) . " products</strong> are available on your warehouse/s"; 
} elseif($available_stocks<1) {
    $available_stocks_display = number_format($available_stocks) . " product</strong> is available on your warehouse/s"; 
} else {
    $available_stocks_display = "there are no products yet on your designated warehouse/s";
}


?>
<div class="card bg-transparent-50 overflow-hidden">
    <div class="card-header position-relative">
        <div class="bg-holder d-none d-md-block bg-card z-1" 
            style="background-image: url(../assets/img/illustrations/ecommerce-bg.png); 
                   background-size: 230px; 
                   background-position: right bottom; 
                   z-index: -1;"></div>
        <!--/.bg-holder-->
        <div class="position-relative z-2">
            <div>
                <h3 class="text-primary mb-1">Good <?php echo $time_name . ", " . $user_fname; ?>!</h3>
                <p>Here’s what happening with your Inventory today</p>
            </div>
            <!-- <div class="d-flex py-3">
                <div class="pe-3">
                    <p class="text-600 fs-10 fw-medium">Today's outbound</p>
                    <h4 class="text-800 mb-0"><?php // echo $formatted_outbound_today;?></h4>
                </div>
                <div class="ps-3">
                    <p class="text-600 fs-10">Today’s outbound sales</p>
                    <h4 class="text-800 mb-0">₱<?php // echo $formatted_outbound_sales_today;?></h4>
                </div>
            </div> -->
        </div>
    </div>
    <div class="card-body p-0">
        <ul class="mb-0 list-unstyled list-group font-sans-serif">
            <?php echo $morethan_3_months_display . $under_safety_display;?>
            
            <li class="list-group-item mb-0 rounded-0 py-3 px-x1 greetings-item text-700 border-0">
                <div class="row flex-between-center">
                    <div class="col">
                        <div class="d-flex">
                            <div class="fas fa-circle mt-1 fs-11 text-primary"></div>
                            <p class="fs-10 ps-2 mb-0"><strong><?php echo $available_stocks_display; ?></p>
                        </div>
                    </div>
                    <div class="col-auto d-flex align-items-center">
                        <a class="fs-10 fw-medium" href="#">View orders
                            <i class="fas fa-chevron-right ms-1 fs-11"></i>
                        </a>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</div>
