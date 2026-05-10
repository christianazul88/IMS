<div class="card shadow-sm">
    <div class="card-header text-center bg-primary text-white">
        <h2>Outbound Summary</h2>
    </div>
    <div class="card-body">

        <!-- ✅ Report Description -->
        <div class="alert alert-info text-start">
            <strong>Report Description:</strong><br>
            Generates a downloadable summary of outbound stocks per supplier (International and Local) for a selected warehouse.  
            The report covers transactions from the start of the previous month up to the current date, showing:
            <ul class="mb-0 mt-2">
                <li>Total quantity released per supplier</li>
                <li>Total sales amount per supplier</li>
            </ul>
        </div>

        <!-- ✅ Form -->
        <form action="../Outbound-Summary/index.php" method="POST">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
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
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success w-100">Generate Report</button>
                </div>
            </div>
        </form>

        <?php 
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $selected_Warehouse = $_POST['warehouses'];
        ?>
        <!-- ✅ Download Section -->
        <div class="text-center mt-4">
            <div class="alert alert-success">
                Report generated successfully. Download the outbound summary CSV below.
            </div>

            <a class="d-inline-flex align-items-center border rounded-pill px-4 py-2 inbox-link text-decoration-none"
               style="background-color:#f8f9fa; border:1px solid #0d6efd;"
               href="download-local.php?warehouse_id=<?php echo $selected_Warehouse;?>">
                <span class="fas fa-file-alt text-primary me-2" data-fa-transform="grow-4"></span>
                Download Outbound Summary CSV
            </a>
        </div>
        <?php
        }
        ?>

    </div>
</div>