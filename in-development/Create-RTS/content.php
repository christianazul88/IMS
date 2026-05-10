<?php 
if(!isset($_SESSION['warehouse_for_return'])){
    if(isset($_SESSION['return_supplier'])){
        unset($_SESSION['return_supplier']);
    }
?>
    <button class="btn btn-primary d-none" id="tobetriggered" type="button" data-bs-toggle="modal" data-bs-target="#error-modal">Launch demo modal</button>
    <div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
            <div class="modal-content position-relative">
                <form action="local_config.php" method="POST">
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
} else {
    $warehouse_for_transfer = $_SESSION['warehouse_for_return'];
    
    $warehouse_sql = "SELECT hashed_id FROM warehouse WHERE warehouse_name = '$warehouse_for_transfer' LIMIT 1";
    $res = $conn->query($warehouse_sql);
    if($res->num_rows > 0){
        $row = $res->fetch_assoc();
        $warehouse_for_transfer = $row['hashed_id'];
        $_SESSION['hashed_warehouse'] = $warehouse_for_transfer;
    }

?>
<div class="row">
    <div class="col-lg-12 mb-4">
      <h3><b>RETURN ITEMS TO SUPPLIER</b></h3>
    </div>
    <div class="col-lg-12 mb-3">
      <a href="return.php" id="return-btn" class="btn btn-primary"><span class="far fa-arrow-alt-circle-left"></span> Change returnee warehouse</a>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body" style="min-height: 500px;">
                <ul class="nav nav-tabs justify-content-center" id="myTab" role="tablist">
                    <li class="nav-item"><a class="nav-link active" id="home-tab" data-bs-toggle="tab" href="#tab-home" role="tab">Single Return</a></li>
                    <li class="nav-item"><a class="nav-link" id="profile-tab" data-bs-toggle="tab" href="#tab-profile" role="tab">Batch Returns</a></li>
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
      <form action="../config/process-return.php" id="to-process" method="POST" enctype="multipart/form-data">
        <div class="card">
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <label for="">From:</label>
                <input type="text" class="form-control" value="<?php echo $_SESSION['warehouse_for_return']; ?>" disabled>
              </div>
              <div class="col-md-4">
                <label for="">Supplier:</label>
                <div id="to_supplier"></div>
              </div>
              <div class="col-md-4">
                <label for="">To:</label>
                <select class="form-select" name="to_status" required>
                    <option value="return and refund">return & refund</option>
                    <option value="return and replace">return & replace</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="fs-11" for="">Proof</label>
                <input type="file" class="form-control" name="image_proof" id="imageInput" accept="image/*">
              </div>
              <div class="col-md-6">
                <label class="fs-11" for="">Front Picture</label>
                <input type="file" class="form-control" name="image_front" id="imageInput" accept="image/*">
              </div>
              <div class="col-md-6">
                <label class="fs-11" for="">Back Picture</label>
                <input type="file" class="form-control" name="image_back" id="imageInput" accept="image/*">
              </div>
              <div class="col-md-6">
                <label class="fs-11" for="">Warranty</label>
                <input type="file" class="form-control" name="image_warranty" id="imageInput" accept="image/*">
              </div>
            </div>
          </div>
        </div>
        <div class="pt-3" id="preview" style="height: 300px;"></div>
        <div class="card">
          <div class="card-body" style="height: 100px;">
            <div class="row">
              <div class="col-7">
                <textarea name="remarks" class="form-control" id="" placeholder="Enter reason here....."></textarea>
              </div>
              <div class="col-5">
                <button class="btn btn-primary mt-1 w-100" id="to-process-btn">Submit </button>
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
            // showAlert(response);
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
