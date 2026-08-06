<div class="card shadow-sm">
    <div class="card-header text-center bg-primary text-white">
        <h2>Inventory Per Supplier</h2>
    </div>
    <div class="card-body">

        <?php 
            $supplier_query = "SELECT * FROM supplier WHERE (local_international = '' or local_international IS NULL) AND current_status = 0";
            $supplier_res = $conn->query($supplier_query);
            if($supplier_res->num_rows>0){
        ?>
            <!-- ✅ Warning for missing classification -->
            <div class="alert alert-warning text-center">
                <strong>Action Required:</strong><br>
                One or more suppliers have not been assigned a classification.  
                Please update their records by specifying whether they are <strong>"Local"</strong>, <strong>"Hakot/Bidding"</strong> or <strong>"Import"</strong>.
            </div>
        <?php
            } else {
        ?>
            <!-- ✅ Report Description -->
            <div class="alert alert-info text-start">
                <strong>Report Description:</strong><br>
                Generates a downloadable summary of current inventory based on the selected supplier type. The report includes:
                <ul class="mb-0 mt-2">
                    <li>Local suppliers</li>
                    <li>International/Import suppliers</li>
                    <li>Hakot suppliers</li>
                </ul>
                Reports are grouped by category and show the total quantity and total amount per category for quick inventory and value analysis.
            </div>

            <!-- ✅ Download Buttons -->
            <div class="text-center mt-4">
                <a class="d-inline-flex align-items-center border rounded-pill px-4 py-2 me-2 mt-2 inbox-link text-decoration-none"
                   style="background-color:#f8f9fa; border:1px solid #0d6efd;"
                   href="download-local.php">
                    <span class="fas fa-file-alt text-primary me-2" data-fa-transform="grow-4"></span>
                    Download Inventory Per Supplier Local CSV
                </a>
                <a class="d-inline-flex align-items-center border rounded-pill px-4 py-2 me-2 mt-2 inbox-link text-decoration-none"
                   style="background-color:#f8f9fa; border:1px solid #0d6efd;"
                   href="download-Imports.php">
                    <span class="fas fa-file-alt text-primary me-2" data-fa-transform="grow-4"></span>
                    Download Inventory Per Supplier Imports CSV
                </a>
                <a class="d-inline-flex align-items-center border rounded-pill px-4 py-2 me-2 mt-2 inbox-link text-decoration-none"
                   style="background-color:#f8f9fa; border:1px solid #0d6efd;"
                   href="download-hakot.php">
                    <span class="fas fa-file-alt text-primary me-2" data-fa-transform="grow-4"></span>
                    Download Inventory Per Supplier Hakot/ Bidding CSV
                </a>
            </div>
        <?php
            }
        ?>
    </div>
</div>