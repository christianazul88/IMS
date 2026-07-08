<div class="row">

    <?php
    
        
        

        if(isset($_POST['start_date'])){
            $startDate = $_POST['start_date'];
        } else {
            $startDate = date('Y-m-01 00:00:00');
        }

        if(isset($_POST['end_date'])){
            $endDate = $_POST['end_date'];
        } else {
            $endDate = date('Y-m-d H:i:s');
        }

    
    ?>
    <div class="col-xxl-12">
        <div class="card">
            <div class="card-body bg-body-tertiary overflow-hidden ">
                <form action="../Finance/index" method="POST">
                    <div class="tab-content row">
                        <div class="col-lg-4 mb-3">
                            <label class="form-label" for="start_datepicker">Start Date</label>
                            <input class="form-control datetimepicker fs-11" name="start_date" id="start_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' <?php if(isset($_POST['start_date'])){ echo 'value="' . $startDate . '"';}?> required/>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <label class="form-label" for="end_datepicker">End Date</label>
                            <input class="form-control datetimepicker fs-11" name="end_date" id="end_datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' <?php if(isset($_POST['end_date'])){ echo 'value="' . $endDate . '"';}?> required/>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select class="form-select selectpicker fs-11" id="category" multiple="multiple" size="1" name="category[]" data-options='{"placeholder":"Select your options"}'>
                                    <option value="">Select staff...</option>
                                    <?php 
                                    $category_sql = "SELECT * FROM category ORDER BY category_name ASC";
                                    $stmt = $conn->prepare($category_sql); // Use prepared statements
                                    $stmt->execute();
                                    $res = $stmt->get_result();
                                    if ($res->num_rows > 0) {
                                        while ($row = $res->fetch_assoc()) {
                                            $category_name = htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8');
                                            $category_id = htmlspecialchars($row['hashed_id'], ENT_QUOTES, 'UTF-8');
                                            echo '<option value="' . $category_id . '">' . $category_name . '</option>'; 
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <label for="warehouse">Warehouse</label>
                            <select class="form-select fs-11" name="warehouse" id="warehouse">
                                <!-- <option value="">All</option> -->
                            <?php echo implode("\n", $warehouse_options2); ?>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label for="category">Supplier</label>
                            <select class="form-select selectpicker fs-11" id="supplier" multiple="multiple" size="1" name="supplier[]" data-options='{"placeholder":"Select your options"}'>
                                <option value="">Select staff...</option>
                                <?php 
                                $supplierz_sql = "SELECT * FROM supplier ORDER BY supplier_name ASC";
                                $stmt = $conn->prepare($supplierz_sql); // Use prepared statements
                                $stmt->execute();
                                $res = $stmt->get_result();
                                if ($res->num_rows > 0) {
                                    while ($row = $res->fetch_assoc()) {
                                        $supplierz_name = htmlspecialchars($row['supplier_name'], ENT_QUOTES, 'UTF-8');
                                        $supplierz_id = htmlspecialchars($row['hashed_id'], ENT_QUOTES, 'UTF-8');
                                        echo '<option value="' . $supplierz_id . '">' . $supplierz_name . '</option>'; 
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label for="">Local/ Import</label>
                            <select class="form-select fs-11" name="local_international" id="local_international" required>
                                <option value="">Select option</option>
                                <option value="All">All</option>
                                <option value="Local">Local</option>
                                <option value="International">International</option>
                            </select>
                        </div>
                        <div class="col-lg-12 text-end mb-3 pt-4">
                            <button type="submit" class="btn btn-primary mt-1 fs-11">Generate</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
    

<script>
document.addEventListener("DOMContentLoaded", function () {

    const startInput = document.getElementById("start_datepicker");
    const endInput = document.getElementById("end_datepicker");
    const form = document.querySelector("form");
    const generateBtn = document.querySelector("button[type='submit']");

    let alertShown = false; // prevent repeated alerts

    function getDayDifference() {
        if (!startInput.value || !endInput.value) return null;

        const startDate = new Date(startInput.value);
        const endDate = new Date(endInput.value);

        if (isNaN(startDate) || isNaN(endDate)) return null;

        const diffTime = endDate - startDate;
        return diffTime / (1000 * 60 * 60 * 24);
    }

    function validateDates(showAlert = false) {
        const diffDays = getDayDifference();

        if (diffDays === null) {
            generateBtn.disabled = true;
            return;
        }

        if (diffDays < 0) {
            generateBtn.disabled = true;

            if (showAlert) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: 'End date must be after start date.'
                });
            }
            return;
        }

        if (diffDays > 31) {
            generateBtn.disabled = true;

            if (!alertShown) {
                alertShown = true;

                Swal.fire({
                    icon: 'warning',
                    title: 'Date Range Exceeded',
                    text: 'The selected date range cannot exceed 31 days (1 month).'
                });
            }

            return;
        }

        // ✅ Valid range
        alertShown = false;
        generateBtn.disabled = false;
    }

    startInput.addEventListener("input", validateDates);
    endInput.addEventListener("input", validateDates);

    form.addEventListener("submit", function (e) {
        const diffDays = getDayDifference();

        if (diffDays === null || diffDays < 0 || diffDays > 31) {
            e.preventDefault();
            validateDates(true);
        }
    });

    generateBtn.disabled = true;
});
</script>