<div class="card">
    <div class="card-header text-center bg-primary text-white">
        <h2 class="mb-0">Product Quality</h2>
    </div>
    <div class="card-body">
        <p class="mb-2 fs-11">
            Generates a downloadable summary of returned products within a user-defined date range. The report has two sections:
        </p>
        <p class="mb-1 fs-11">
            <span class="fw-semibold">Defective Returns (Local)</span> – includes product description, brand, category, supplier, quantity, and total value.
        </p>
        <p class="mb-3 fs-11">
            <span class="fw-semibold">Delivery Failed Returns (International)</span> – includes product description, brand, category, logistic platform, quantity, and total value.
        </p>
        <p class="mb-4 fs-11">
            It helps track product quality issues, monitor supplier performance, and identify return patterns for better inventory and operational decisions.
        </p>

        <form action="../Product-Quality/index" method="POST">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="timepicker2" class="form-label">Select Date Range</label>
                    <input 
                        type="text" 
                        id="timepicker2" 
                        name="date_between" 
                        class="form-control datetimepicker" 
                        placeholder="dd/mm/yy to dd/mm/yy" 
                        data-options='{"mode":"range","dateFormat":"d/m/y","disableMobile":true}' 
                    />
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>

        <?php 
        if(isset($_POST['date_between'])){
            $date_between = $_POST['date_between'];
            list($startStr, $endStr) = explode(' to ', $date_between);
            $startDate = DateTime::createFromFormat('d/m/y', $startStr);
            $endDate = DateTime::createFromFormat('d/m/y', $endStr);
            $startDate->setTime(0,0,0);
            $endDate->setTime(23,59,59);
            $startDateFormatted = $startDate->format('Y-m-d H:i:s');
            $endDateFormatted = $endDate->format('Y-m-d H:i:s');
        ?>
        <div class="text-center mt-4">
            <a 
                href="download-local.php?start=<?php echo $startDateFormatted;?>&&end=<?php echo $endDateFormatted;?>" 
                class="btn btn-outline-primary d-inline-flex align-items-center"
            >
                <i class="fas fa-file-alt me-2"></i> Download CSV
            </a>
        </div>
        <?php } ?>
    </div>
</div>