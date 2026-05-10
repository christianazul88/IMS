<?php
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
?>
<div class="row g-3 mb-3">
    <?php 
    if(strpos($access, "dashboard_outbound")!==false || $user_position_name === "Administrator"){
    ?>
    <div class="col-xxl-12">
        <div class="row g-3">
            <div class="col-5">
                <?php 
                if(isset($_GET['wh'])){
                    $dashboard_wh = $_GET['wh'];
                    $dashboard_heading = "<h3>" . $_GET['wha'] . "</h3>";
                } else {
                    $dashboard_wh = "";
                    $dashboard_heading = "<h3>All Accessible Warehouse</h3>";
                } 
                echo $dashboard_heading;
                ?>
            </div>
            <div class="col-7 my-3 text-end">
                <div class="btn-group">
                    <button class="btn dropdown-toggle mb-2 btn-secondary" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Select Warehouse</button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="../dashboard/">All</a>
                        <?php 
                        foreach ($warehouse_dropdow_dashboard as $link_dashboard) {
                            echo $link_dashboard;
                        }
                        $quoted_warehouse_ids = array_map(function ($id) {
                        return "'" . trim($id) . "'";
                        }, $user_warehouse_ids);
                        
                        // Create a comma-separated string of quoted IDs
                        $imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <?php include "revenue_tracker.php";?>
        </div>
    </div>
    <div class="col-xxl-6 col-xl-12">
        <div class="row g-3">
            <div class="col-12">
                <?php include "outbound.php"; ?>
            </div>
            <div class="col-lg-12">
                <?php include "fast_moving_product.php";?>
            </div>

            <div class="col-lg-12">
                <?php include "inbound_outbound.php";?>
            </div>

            <div class="col-lg-12">
                <?php include "fast_moving_category.php";?>
            </div>

            <div class="col-lg-12">
                <?php include "stocks_summary.php";?>
            </div>

            <div class="col-lg-12">
                <?php include "revenue_dropping.php";?>
            </div>
            
        </div>
    </div>
    
    <div class="col-xxl-6 col-xl-12">
        <div class="row">
            <div class="col-lg-12">
                <?php include "return_summary.php";?>
            </div>

            <div class="col-lg-12">
                <div class="row g-3 mb-3">
                    <?php include "weekly_sales.php"; ?>
                    <?php include "total_order.php"; ?>
                </div>
            </div>
            
            <div class="col-lg-12 mb-3">
                <?php include "monthly_sales_display.php";?>
            </div>
            <div class="col-lg-12 mb-3">
                <?php include "incoming_stocks.php";?>
            </div>
            <div class="col-lg-12 mb-3">
                <?php include "slow_moving_category.php";?>
            </div>
            <div class="col-lg-12 mb-3">
                <?php include "promotion.php";?>
            </div>

            <?php include "under_safety.php";?>
        </div>
    </div>

    <?php 
    }
    if(strpos($access, "dashboard_inventory")!==false || $user_position_name === "Administrator"){
    ?>
    <div class="col-xxl-12 col-xl-12">
        <?php include "inventory.php";?>
    </div>
    <?php 
    }
    ?>
</div>




<script>
$(document).ready(function() {
    // Function to load the warehouse preview
    function loadWarehousePreview(warehouse) {
        if (warehouse) {
            $.get('dashboard-wh-preview.php', { warehouse: warehouse }, function(data) {
                $('#dashboard-wh-preview').html(data);
            }).fail(function() {
                $('#dashboard-wh-preview').html('Failed to load preview.');
            });
        }
    }

    // Get the initial selected warehouse value
    var initialWarehouse = $('#dashboard-wh').val();

    // Load the preview for the initially selected warehouse
    loadWarehousePreview(initialWarehouse);

    // When the user changes the warehouse, update the preview
    $('#dashboard-wh').on('change', function() {
        var warehouse = $(this).val();
        loadWarehousePreview(warehouse);
    });
});
</script>
