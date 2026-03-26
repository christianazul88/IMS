<?php 
$selected_warehouse = "";
$movement_of_Stocks = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_warehouse = $_POST['warehouse'] ?? "";
    $movement_of_Stocks = $_POST['movement'] ?? "";
} 
?>
<div class="card">
    <div class="card-header text-center mb-4">
        <h2>Movement of Stocks</h2>
        <span class="fs-11">The Movement of Stocks module provides an overview of inventory activity by tracking items that have been outbounded and those that remain in stock. It helps monitor stock movement, identify fast-moving items, and detect non-moving inventory for better decision-making.</span>
    </div>
    <hr>
    <div class="card-body">
        <div class="text-center">
            <!-- <p>
                Please note: This is a <strong>demo version</strong> of the system.  
                CSV downloads are currently <strong>Limited</strong>.  
                The complete functionality will be accessible upon client approval of the quotation.
            </p> -->
        </div>
        <form action="../MoS/index.php" method="POST">
            <div class="row">
                <!-- Warehouse Selection -->
                <div class="col-6">
                    <label for="">Select Locations</label>
                    <?php 
                    $warehouse_query = "SELECT * FROM warehouse ORDER BY warehouse_name";
                    $warehouse_res = $conn->query($warehouse_query);
                    if ($warehouse_res && $warehouse_res->num_rows > 0) {
                        while ($row = $warehouse_res->fetch_assoc()) {
                            $warehouse_check = $row['warehouse_name'];
                            $warehouse_check_id = $row['hashed_id'];
                            $checked = ($warehouse_check_id === $selected_warehouse) ? 'checked' : '';
                            echo '<div class="form-check">
                                    <input class="form-check-input" id="' . $warehouse_check_id . '" type="radio" name="warehouse" value="' . $warehouse_check_id . '" ' . $checked . ' />
                                    <label class="form-check-label" for="' . $warehouse_check_id . '">' . $warehouse_check . '</label>
                                </div>';
                        }
                    }
                    ?>
                </div>

                <!-- Movement Selection -->
                <div class="col-6">
                    <label for="">Please Select One</label>
                    <div class="form-check">
                        <input class="form-check-input" id="Moving-Stocks" type="radio" name="movement" value="new" <?php if ($movement_of_Stocks === "new") echo 'checked'; ?> />
                        <label class="form-check-label" for="Moving-Stocks">Moving Stocks <br><span class="text-info fs-11">Total number of items and their corresponding value that have been outbounded (sold or released) from the warehouse since the system was implemented.</span></label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" id="Non-Moving-Stocks" type="radio" name="movement" value="old" <?php if ($movement_of_Stocks === "old") echo 'checked'; ?> />
                        <label class="form-check-label" for="Non-Moving-Stocks">NON-Moving Stocks <br><span class="text-info fs-11">Total number of items and their corresponding value that are still in stock and have not been outbounded, indicating no movement.</span></label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="col-12 text-center">
                    <button class="btn btn-primary w-100" type="submit">Submit</button>
                </div>
            </div>
        </form>

        <!-- CSV Download Link -->
        <?php 
        if (!empty($selected_warehouse) && !empty($movement_of_Stocks)) {
        ?>
        <hr>
        <div class="text-center">
            <a class="d-inline-flex align-items-center border rounded-pill px-3 py-1 me-2 mt-2 inbox-link" href="download.php?warehouse=<?php echo $selected_warehouse;?>&&movement=<?php echo $movement_of_Stocks;?>">
                <span class="fas fa-file-alt text-primary" data-fa-transform="grow-4"></span>
                <span class="ms-2">Download CSV</span>
            </a>
        </div>
        <?php 
        }
        ?>
    </div>
</div>
