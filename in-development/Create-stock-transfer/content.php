<?php 
if(!isset($_SESSION['warehouse_for_transfer'])){
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
                            <h4 class="mb-1" id="modalExampleDemoLabel">Select item from</h4>
                        </div>
                        <div class="p-4 pb-0">
                            <select class="form-select" id="warehouse" name="warehouse" required>
                                <option value="">Select from...</option>
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
    $warehouse_for_transfer = $conn->real_escape_string($_SESSION['warehouse_for_transfer']);
    
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
      <h3><b>TRANSFER STOCKS</b></h3>
    </div>
    <div class="col-lg-6 mb-3">
      <a href="return.php" id="return-btn" class="btn btn-primary"><span class="far fa-arrow-alt-circle-left"></span> Change stock transfer warehouse</a>
    </div>
    <div class="col-lg-6 mb-3 text-end">
        <button class="btn btn-warning" type="button" data-bs-toggle="modal" data-bs-target="#error-modal">View Summary</button>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
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
      <form action="../config/process-transfer.php" id="to-process" method="POST">
        <div class="card" style="height: 100px;">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <label for="">From:</label>
                <input type="text" class="form-control" value="<?php echo $_SESSION['warehouse_for_transfer']; ?>" disabled>
              </div>
              <div class="col-md-6">
                <label for="">To:</label>
                <select class="form-select" name="to_wh" id="to_wh" required>
                  <option value="" selected>Select Warehouse</option>
                  <?php 
                  // Quote each ID in the array
                  $quoted_warehouse_ids = array_map(function ($id) {
                    return "'" . trim($id) . "'";
                  }, $user_warehouse_ids);

                  // Create a comma-separated string of quoted IDs
                  $imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);
                  $to_query = "SELECT hashed_id, warehouse_name FROM warehouse ORDER BY warehouse_name ASC";
                  $to_res = $conn->query($to_query);
                  if($to_res->num_rows>0){
                    while($row=$to_res->fetch_assoc()){
                      if($warehouse_for_transfer !== $row['hashed_id']){
                        echo '<option value="' . $row['hashed_id'] . '">' . $row['warehouse_name'] . '</option>';
                      }
                    }
                  }
                  ?>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="card my-3">
            <div class="card-body" style="height: 300px; overflow-y: auto;">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-striped fs-10 mb-0 overflow-auto">
                        <thead class="table-info">
                            <tr>
                                <th></th>
                                <th>Barcode</th>
                                <th>Description</th>
                                <th>Brand</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody id="preview">
                            
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
        <div class="card">
          <div class="card-body" style="height: 100px;">
            <div class="row">
              <div class="col-7">
                <textarea name="remarks" class="form-control" id="" placeholder="Enter reason here....."></textarea>
              </div>
              <div class="col-5 pt-3">
                <button class="btn btn-primary mt-1 w-100" id="to-process-btn">Submit</button>
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


<div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
          <h4 class="mb-1" id="modalExampleDemoLabel">Summary of Transfer </h4>
        </div>
        <div class="p-4 pb-0">
          <div id="summary"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function(){
    // Load initial content for Single Transfer and Batch Transfer
    loadSingleTransfer();
    loadBatchTransfer();

    // Load preview.php content into #preview
    $("#preview").load("preview.php");

    loadSummary();

    function loadSummary() {
        $.ajax({
            url: 'summary.php',
            method: 'GET',
            success: function(response) {
                $('#summary').html(response);
            },
            error: function(xhr, status, error) {
                console.error('Error loading summary:', error);
            }
        });
    }

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
                loadSummary();
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
                loadSummary();
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
