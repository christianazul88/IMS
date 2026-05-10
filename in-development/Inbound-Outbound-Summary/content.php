<div class="card shadow-sm">
    <div class="card-header text-center bg-primary text-white">
        <h2>Inbound / Outbound Summary</h2>
    </div>
    <div class="card-body">

        <!-- ✅ Report Description -->
        <div class="alert alert-info">
            <strong>Report Description:</strong><br>
            This report provides a summary of inbound and outbound stock for the selected warehouse.
            <ul class="mb-0 mt-2">
                <li>Includes stocks received from the beginning of last month up to today.</li>
                <li>Stocks received before the start of last month are <strong>not</strong> included.</li>
                <li>Ending Inventory = Total Inbound - Total Outbound within the date range.</li>
                <li>All amounts are based on recorded capital value.</li>
            </ul>
        </div>

        <!-- ✅ Form -->
        <form action="../Inbound-Outbound-Summary/index.php" method="POST">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="warehouseSelect" class="form-label">Select Warehouse</label>
                    <select class="form-select" name="warehouses" id="warehouseSelect" required>
                        <option value="">Select warehouse</option>
                        <?php 
                        $warehouses_query = "SELECT * FROM warehouse";
                        $warehouse_res = $conn->query($warehouses_query);
                        if($warehouse_res->num_rows>0){
                            while($row=$warehouse_res->fetch_assoc()){
                                echo '<option value="' . $row['hashed_id'] . '">' . $row['warehouse_name'] . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Optional: Date Range -->
                <!--
                <div class="col-md-4">
                    <label class="form-label" for="timepicker2">Select Date Range</label>
                    <input class="form-control datetimepicker" id="timepicker2" type="text" name="date_between" placeholder="dd/mm/yy to dd/mm/yy" data-options='{"mode":"range","dateFormat":"d/m/y","disableMobile":true}' />
                </div>
                -->

                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Generate Report</button>
                </div>
            </div>
        </form>

        <?php 
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $selected_Warehouse = $_POST['warehouses'];
        ?>
        <!-- ✅ Result Section -->
        <div class="mt-4 text-center">
            <div class="alert alert-success">
                Report generated successfully. Download the inventory CSV below.
            </div>

            <a class="d-inline-flex align-items-center border rounded-pill px-4 py-2 me-2 mt-2 inbox-link text-decoration-none"
               style="background-color:#f8f9fa; border:1px solid #0d6efd;"
               href="download-local.php?warehouse_id=<?php echo $selected_Warehouse;?>">
                <span class="fas fa-file-alt text-primary me-2" data-fa-transform="grow-4"></span>
                Download Inventory Per Location CSV
            </a>
        </div>
        <?php
        }
        ?>

    </div>
</div>