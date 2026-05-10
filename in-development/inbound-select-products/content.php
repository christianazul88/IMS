<?php 
// Retrieve session variables
$inbound_supplier = $_SESSION['inbound_supplier'];
$inbound_po = $_SESSION['inbound_po_id'];
$inbound_date = $_SESSION['inbound_received_date'];
$inbound_warehouse = $_SESSION['inbound_warehouse'];
$inbound_driver = $_SESSION['inbound_driver'];
$inbound_plate = $_SESSION['inbound_plate_num'];
?>

<div class="row">
    <div class="col-lg-12">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="home-tab" data-bs-toggle="tab" href="#tab-home" role="tab" aria-controls="tab-home" aria-selected="true">Barcode</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="profile-tab" data-bs-toggle="tab" href="#tab-profile" role="tab" aria-controls="tab-profile" aria-selected="false">Product Selection</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="upload-tab" data-bs-toggle="tab" href="#tab-upload" role="tab" aria-controls="tab-upload" aria-selected="false">Upload CSV</a>
            </li>
        </ul>

        <!-- Tab Contents -->
        <div class="tab-content border border-top-0 p-0" id="myTabContent">
            <div class="tab-pane fade show active" id="tab-home" role="tabpanel" aria-labelledby="home-tab">
                <div class="card">
                    <div class="card-body" id="form-load">
                        <!-- Form content will be loaded here -->
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-profile" role="tabpanel" aria-labelledby="profile-tab">
               <div class="card">
                    <div class="card-body" id="form-load2">

                    </div>
               </div>
            </div>
            <div class="tab-pane fade" id="tab-upload" role="tabpanel" aria-labelledby="upload-tab">
               <div class="card">
                    <div class="card-body" id="form-upload3">

                    </div>
               </div>
            </div>
        </div>
    </div>

    <div class="col-lg-12 mt-3">
        <div class="card">
            <div class="card-body" id="inbound-preview">
                    
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Function to load the form in #form-load and set focus
    function loadForm() {
        $("#form-load").load("form-load.php", function() {
            $("#parent_barcode").focus(); // Set focus after loading
        });
    }

    // Function to load the form in #form-load2
    function loadForm2() {
        $("#form-load2").load("form-load2.php");
    }

    function loadUploadForm() {
        $("#form-upload3").load("form-upload3.php");
    }

    function inboundPreview() {
        $("#inbound-preview").load("inbound-preview.php");
    }

    // Initial load of forms and event delegation
    $(document).ready(function() {
        // Load both forms initially
        loadForm();
        loadForm2();
        loadUploadForm();
        inboundPreview();

        // Handle form submission for #form-reload inside #form-load
        $(document).on("submit", "#form-reload", function(event) {
            event.preventDefault(); // Prevent default form submission

            // Serialize form data and send via AJAX
            $.ajax({
                url: "../config/add_product_inbound.php",
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    // console.log(response);
                    // Reload #form-load after successful submission
                    loadForm(); 
                    inboundPreview();
                },
                error: function(xhr, status, error) {
                    // console.error("Form submission failed: " + xhr.status + " " + error);
                }
            });
        });

        // Handle form submission for another form if needed in #form-load2
        $(document).on("submit", "#form-reload2", function(event) {
            event.preventDefault();

            $.ajax({
                url: "../config/add_product_inbound.php",
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    // console.log(response);
                    loadForm2();  // Reload #form-load2 after successful submission
                    inboundPreview();
                },
                error: function(xhr, status, error) {
                    // console.error("Form submission failed: " + xhr.status + " " + error);
                }
            });
        });

        // Handle form submission for another form if needed in #form-load2
        $(document).on("submit", "#form-reload3", function(event) {
            event.preventDefault();

            $.ajax({
                url: "../config/add_product_inbound-csv.php",
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    // console.log(response);
                    loadUploadForm();  // Reload #form-load2 after successful submission
                    inboundPreview();
                },
                error: function(xhr, status, error) {
                    // console.error("Form submission failed: " + xhr.status + " " + error);
                }
            });
        });

        
    });
</script>
