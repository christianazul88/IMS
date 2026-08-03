<div class="row">
    <div class="col-lg-12 mb-4">
      <h3><b>Void Inbound Items</b></h3>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body" style="min-height: 500px;">
                <ul class="nav nav-tabs justify-content-center" id="myTab" role="tablist">
                    <li class="nav-item"><a class="nav-link active" id="home-tab" data-bs-toggle="tab" href="#tab-home" role="tab">Single Void</a></li>
                    <li class="nav-item"><a class="nav-link" id="profile-tab" data-bs-toggle="tab" href="#tab-profile" role="tab">Batch Void</a></li>
                </ul>
                <div class="tab-content border border-top-0 p-3" id="myTabContent" style="min-height: 400px;">
                    <div class="tab-pane fade show active" id="tab-home" role="tabpanel">
                        <div id="single-void"></div>
                    </div>
                    <div class="tab-pane fade" id="tab-profile" role="tabpanel">
                        <div id="batch-void"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
      <form action="save-void.php" id="to-process" method="POST">
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
                <textarea class="form-control" name="remarks" id="remarks" rows="2" placeholder="Remarks (optional)"></textarea>
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


    // Load initial content for Single Void and Batch Void
    loadSingleVoid();
    loadBatchVoid();

    // Load preview.php content into #preview
    $("#preview").load("preview.php");

    // Check check_data.php every 3 seconds
    setInterval(checkData, 3000);

    // Handle form submission for Single Void
    $(document).on("submit", "#single", function(e){
        e.preventDefault();
        $.post($(this).attr("action"), $(this).serialize(), function(response){
            showAlert(response);
            loadSingleVoid(); // Reload form after submission
        });
    });

    // Handle form submission for Batch Void
    $(document).on("submit", "#batch", function(e){
        e.preventDefault();
        $.post($(this).attr("action"), $(this).serialize(), function(response){
            showAlert(response);
            loadBatchVoid(); // Reload form after submission
        });
    });

    // Confirm before submitting the Void form
    $("#to-process").on("submit", function(e){
        e.preventDefault(); // Prevent immediate submission
        Swal.fire({
            title: "Are you sure?",
            text: "Do you really want to proceed with this inbound items void?",
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

    // Function to load Single Void
    function loadSingleVoid(){
        $("#single-void").load("single-void.php", function(){
            $("#single-barcode").focus();
        });
    }

    // Function to load Batch Void
    function loadBatchVoid(){
        $("#batch-void").load("batch-void.php", function(){
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