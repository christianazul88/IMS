<?php 
if(!isset($_SESSION['warehouse_rack_transfer']) && !isset($_SESSION['rack']) && strpos($warehouses, ',')!==false){
?>
    <button class="btn btn-primary d-none" id="tobetriggered" type="button" data-bs-toggle="modal" data-bs-target="#error-modal">Launch demo modal</button>
    <div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
            <div class="modal-content position-relative">
                <form action="local_config.php" method="POST">
                    <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                        <!-- Remove the close button to further prevent user from closing the modal -->
                        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
                            <h4 class="mb-1" id="modalExampleDemoLabel">Select Warehouse</h4>
                        </div>
                        <div class="p-4 pb-0">
                            <select class="form-select" id="warehouse" required="required" name="warehouse">
                                <option value="">Select warehouse...</option>
                                <?php echo implode("\n", $warehouse_options); ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal" disabled>Close</button>
                        <button class="btn btn-primary" type="submit">Next</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("tobetriggered").click();
        });
    </script>

<?php
} elseif(!isset($_SESSION['rack'])){
    if(strpos($warehouses, ',')===false){
        $_SESSION['warehouse_rack_transfer'] = $warehouses;
    }
?>
    <button class="btn btn-primary d-none" id="tobetriggered" type="button" data-bs-toggle="modal" data-bs-target="#error-modal">Launch demo modal</button>
    <div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
            <div class="modal-content position-relative">
                <form action="local_config.php" method="POST">
                    <div class="modal-body p-0">
                        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
                            <h4 class="mb-1" id="modalExampleDemoLabel">Select Item Location</h4>
                        </div>
                        <div class="p-4 pb-0">
                            <select class="form-select" id="rack" required="required" name="rack">
                                <option value="">Select rack...</option>
                                <?php 
                                $wh_rack_id = $_SESSION['warehouse_rack_transfer'];
                                $wh_query = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$wh_rack_id' LIMIT 1";
                                $wh_res = $conn->query($wh_query);
                                if($wh_res->num_rows>0){
                                    $row=$wh_res->fetch_assoc();
                                    $_SESSION['warehouse_rack_transfer'] = $row['warehouse_name'];
                                }
                                $wh_rack =$_SESSION['warehouse_rack_transfer'];
                                $item_loc_query = "SELECT il.id, il.location_name FROM item_location il LEFT JOIN warehouse w ON w.hashed_id = il.warehouse WHERE w.warehouse_name = '$wh_rack' ORDER BY location_name ASC";
                                $item_loc_res = $conn->query($item_loc_query);
                                if($item_loc_res->num_rows>0){
                                    while($row=$item_loc_res->fetch_assoc()){
                                        echo '<option value="' . $row['id'] . '">' . $row['location_name'] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="return.php?type=wh" id="return-btn" class="btn btn-primary"><span class="far fa-arrow-alt-circle-left"></span> Change Warehouse</a>
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal" disabled>Close</button>
                        <button class="btn btn-primary" type="submit">Next</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("tobetriggered").click();
        });
    </script>
<?php
} else {
    $warehouse_for_transfer = $_SESSION['warehouse_rack_transfer'];
    $rack = $_SESSION['rack'];
    $warehouse_sql = "SELECT hashed_id FROM warehouse WHERE warehouse_name = '$warehouse_for_transfer' LIMIT 1";
    $res = $conn->query($warehouse_sql);
    if($res->num_rows > 0){
        $row = $res->fetch_assoc();
        $warehouse_for_transfer = $row['hashed_id'];
        $_SESSION['hashed_warehouse'] = $warehouse_for_transfer;
    }

    $rack_query = "SELECT location_name FROM item_location WHERE id = '$rack' LIMIT 1";
    $rack_res = $conn->query($rack_query);
    if($row=$rack_res->fetch_assoc()){
        $rack_name = $row['location_name'];
    }


?>
<div class="row">
    <div class="col-lg-12 mb-4">
      <h3><b>Transfer Stocks to <?php echo $rack_name;?></b></h3>
    </div>
    <div class="col-lg-12 mb-3">
      <a href="return.php?type=rack" id="return-btn" class="btn btn-primary"><span class="far fa-arrow-alt-circle-left"></span> Change rack</a>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body" style="min-height: 500px;">
                <ul class="nav nav-tabs justify-content-center" id="myTab" role="tablist">
                    <li class="nav-item"><a class="nav-link active" id="home-tab" data-bs-toggle="tab" href="#tab-home" role="tab">Single Transfer</a></li>
                    <li class="nav-item"><a class="nav-link" id="profile-tab" data-bs-toggle="tab" href="#tab-profile" role="tab">Batch Transfer</a></li>
                </ul>
                <div class="tab-content border border-top-0 p-3" id="myTabContent" style="min-height: 400px;">
                    <div class="tab-pane fade show active" id="tab-home" role="tabpanel">
                        <div id="single-transfer"></div>
                    </div>
                    <div class="tab-pane fade" id="tab-profile" role="tabpanel">
                        <div id="batch-transfer"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
      <form action="../config/process-rack-transfer.php" id="to-process" method="POST">
        <div class="card mb-1" style="height: 400px;">
            <div class="card-body overflow-auto">
                <div class="row">
                    <div class="col-12">
                        <div class="pt-3" id="preview"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
          <div class="card-body" style="height: 100px;">
            <div class="row">
              <div class="col-7">

              </div>
              <div class="col-5 pt-3">
                <button class="btn btn-primary mt-1 w-100" id="to-process-btn">Save </button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
</div>

<!-- Bootstrap Toast for Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
    <div id="response-toast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toast-message"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function(){
    $("#to_supplier").load("select.php");


    // Load initial content for Single Transfer and Batch Transfer
    loadSingleTransfer();
    loadBatchTransfer();

    // Load preview.php content into #preview
    $("#preview").load("preview.php");

    // Check check_data.php every 3 seconds
    setInterval(checkData, 3000);

    // Handle form submission for Single Transfer
    $(document).on("submit", "#single", function(e){
        e.preventDefault();
        $.post($(this).attr("action"), $(this).serialize(), function(response){
            showAlert(response);
            loadSingleTransfer(); // Reload form after submission
        });
    });

    // Handle form submission for Batch Transfer
    $(document).on("submit", "#batch", function(e){
        e.preventDefault();
        $.post($(this).attr("action"), $(this).serialize(), function(response){
            showAlert(response);
            loadBatchTransfer(); // Reload form after submission
        });
    });

    // Confirm before submitting the transfer form
    $("#to-process").on("submit", function(e){
        e.preventDefault(); // Prevent immediate submission
        Swal.fire({
            title: "Are you sure?",
            text: "Do you really want to proceed with this stock transfer?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, submit it!"
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit(); // Proceed with form submission
            }
        });
    });

    // Confirm before redirecting when clicking #return-btn
    $("#return-btn").on("click", function(e){
        e.preventDefault(); // Prevent immediate redirection
        var url = $(this).attr("href"); // Get the target URL

        Swal.fire({
            title: "Are you sure?",
            text: "Do you want to change the stock transfer warehouse?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, change it!"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url; // Redirect if confirmed
            }
        });
    });

    // Function to show SweetAlert2 message
    function showAlert(message) {
        Swal.fire({
            icon: 'info',
            title: 'Notification',
            text: message,
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
    }

    // Function to load Single Transfer
    function loadSingleTransfer(){
        $("#single-transfer").load("single-transfer.php", function(){
            $("#single-barcode").focus();
        });
    }

    // Function to load Batch Transfer
    function loadBatchTransfer(){
        $("#batch-transfer").load("batch-transfer.php", function(){
            $("#parent-barcode").focus();
        });
    }

    // Function to check data status
    function checkData(){
        $.get("check_data.php", function(response){
            if(response.trim() === "1"){
                console.log(response);
                $("#preview").load("preview.php"); // Load preview.php when condition met
                $("#to_supplier").load("select.php");
            } else {
                console.log(response);
            }
        });
    }

    // Handle delete button click (event delegation)
    $(document).on("click", ".delete-session-item", function() {
        var barcode = $(this).data("barcode");

        $.ajax({
            url: "delete_session.php",
            type: "POST",
            data: { barcode: barcode },
            success: function(response) {
                $("#preview").load("preview.php"); // Reload session table after deletion
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to delete item.'
                });
            }
        });
    });
});
</script>


<?php
}
?>
