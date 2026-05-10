<div class="card">
    <div class="card-header text-center">
        <h2>Daily Transaction Report Export</h2>
    </div>
    <div class="card-body">

        <!-- ✅ Description -->
        <div class="alert alert-info">
            <strong>Report Description:</strong><br>
            This report allows you to export transaction records based on a selected staff and date range.
            <ul class="mb-0 mt-2">
                <li><strong>Outbound Transactions CSV</strong> – Contains all outbound (released) transactions handled by the selected staff.</li>
                <li><strong>Inbound Transactions CSV</strong> – Contains all inbound (received) transactions handled by the selected staff.</li>
            </ul>
            Select a staff member and date range, then click <strong>Submit</strong> to generate download options.
        </div>

        <form action="../Transaction-Per-Day/index" method="POST">
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label" for="timepicker2">Select Date Range</label>
                    <input class="form-control datetimepicker" id="timepicker2" type="text" name="date_between" placeholder="dd/mm/yy to dd/mm/yy" data-options='{"mode":"range","dateFormat":"d/m/y","disableMobile":true}' required/>
                </div>

                <div class="col-6 mb-3">
                    <label>Select Staff</label>
                    <select name="warehouse_id" class="form-select" required>
                        <option value="" selected="">Select Staff</option>
                        <?php 
                        $wh_selection = "SELECT hashed_id, user_fname, user_lname FROM users ORDER BY user_lname ASC";
                        $wh_selection_res = $conn->query($wh_selection);
                        if($wh_selection_res->num_rows>0){
                            while($row=$wh_selection_res->fetch_assoc()){
                                echo '<option value="' . $row['hashed_id'] . '">' . $row['user_lname'] . ", " . $row['user_fname'] . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="col-12 mb-3 text-center">
                    <button class="btn btn-primary px-4">Generate Report</button>
                </div>
            </div>
        </form>

        <?php 
        if(isset($_POST['date_between'])){
            $date_between = $_POST['date_between'];
            $selected_wh = $_POST['warehouse_id'];

            if (strpos($date_between, ' to ') !== false) {
                list($startStr, $endStr) = explode(' to ', $date_between);

                $startDate = DateTime::createFromFormat('d/m/y', $startStr);
                $endDate   = DateTime::createFromFormat('d/m/y', $endStr);

                $startDate->setTime(0, 0, 0);
                $endDate->setTime(23, 59, 59);
            } else {
                $startDate = DateTime::createFromFormat('d/m/y', $date_between);
                $endDate   = clone $startDate;

                $startDate->setTime(0, 0, 0);
                $endDate->setTime((int)date('H'), (int)date('i'), (int)date('s'));
            }

            $startDateFormatted = $startDate->format('Y-m-d H:i:s');
            $endDateFormatted   = $endDate->format('Y-m-d H:i:s');
        ?>

        <!-- ✅ Result Section -->
        <div class="text-center mt-4">
            <div class="alert alert-success">
                Report generated successfully. You may now download the transaction files below.
            </div>

            <a class="d-inline-flex align-items-center border rounded-pill px-3 py-2 me-2 mt-2 inbox-link"
               href="download-otl.php?start=<?php echo $startDateFormatted;?>&&end=<?php echo $endDateFormatted;?>&&user=<?php echo $selected_wh;?>">
                <span class="fas fa-file-alt text-primary" data-fa-transform="grow-4"></span>
                <span class="ms-2">Download Outbound Transactions CSV</span>
            </a>

            <a class="d-inline-flex align-items-center border rounded-pill px-3 py-2 me-2 mt-2 inbox-link"
               href="download-itl.php?start=<?php echo $startDateFormatted;?>&&end=<?php echo $endDateFormatted;?>&&user=<?php echo $selected_wh;?>">
                <span class="fas fa-file-alt text-primary" data-fa-transform="grow-4"></span>
                <span class="ms-2">Download Inbound Transactions CSV</span>
            </a>
        </div>

        <?php 
        }
        ?>

    </div>
</div>