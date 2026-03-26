<div class="card">
    <div class="card-header text-center">
        <h2>Monthly Report to Export</h2>
        <p class="mb-0 text-muted">Select a date range and download reports based on warehouse activity.</p>
    </div>

    <div class="card-body">
        <form action="../Monthly-Report-Export/index" method="POST">
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label" for="timepicker2">Select Date Range</label>
                    <input class="form-control datetimepicker" id="timepicker2" type="text" name="date_between" placeholder="dd/mm/yy to dd/mm/yy" data-options='{"mode":"range","dateFormat":"d/m/y","disableMobile":true}' />
                </div>
                <div class="col-12 mb-3">
                    <button class="btn btn-primary">Generate Reports</button>
                </div>
            </div>
        </form>

        <?php 
        if(isset($_POST['date_between'])){
            $date_between = $_POST['date_between'];

            list($startStr, $endStr) = explode(' to ', $date_between);

            $startDate = DateTime::createFromFormat('d/m/y', $startStr);
            $endDate = DateTime::createFromFormat('d/m/y', $endStr);

            $startDate->setTime(0, 0, 0);
            $endDate->setTime(23, 59, 59);

            $startDateFormatted = $startDate->format('Y-m-d H:i:s');
            $endDateFormatted = $endDate->format('Y-m-d H:i:s');
        ?>

        <div class="mt-4">

            <!-- Inventory Summary -->
            <div class="border rounded p-3 mb-3">
                <h5 class="mb-1">📦 Current Inventory Summary Per Location</h5>
                <p class="text-muted mb-2">
                    View the total available items and their overall value per warehouse. 
                    This gives a quick overview of your current inventory status.
                </p>
                <a class="btn btn-outline-primary btn-sm"
                   href="download-isl.php?start=<?php echo $startDateFormatted;?>&&end=<?php echo $endDateFormatted;?>">
                    Download CSV
                </a>
            </div>

            <!-- Inbound Transactions -->
            <div class="border rounded p-3 mb-3">
                <h5 class="mb-1">📥 Inbound Transactions Per Location</h5>
                <p class="text-muted mb-2">
                    Track all items received within the selected date range, including supplier details, 
                    who processed the entry, and total cost of inbound items.
                </p>
                <a class="btn btn-outline-success btn-sm"
                   href="download-itl.php?start=<?php echo $startDateFormatted;?>&&end=<?php echo $endDateFormatted;?>">
                    Download CSV
                </a>
            </div>

            <!-- Outbound Transactions -->
            <div class="border rounded p-3 mb-3">
                <h5 class="mb-1">📤 Outbound Transactions Per Location</h5>
                <p class="text-muted mb-2">
                    View all sold or dispatched items within the selected date range, including customer details, 
                    order references, and total sales amount.
                </p>
                <a class="btn btn-outline-danger btn-sm"
                   href="download-otl.php?start=<?php echo $startDateFormatted;?>&&end=<?php echo $endDateFormatted;?>">
                    Download CSV
                </a>
            </div>

        </div>

        <?php 
        }
        ?>
    </div>
</div>