<script>
    const TAB_LIST_KEY = 'outbound_form_tabs';
    const thisTabId = Date.now().toString();

    function getTabs() {
      try {
        return JSON.parse(localStorage.getItem(TAB_LIST_KEY)) || [];
      } catch {
        return [];
      }
    }

    function updateTabsList(add) {
      let tabs = getTabs();
      tabs = tabs.filter(id => id !== thisTabId); // remove self just in case

      if (add) tabs.push(thisTabId);

      localStorage.setItem(TAB_LIST_KEY, JSON.stringify(tabs));
      return tabs;
    }

    function showExitAlert() {
      Swal.fire({
        icon: 'error',
        title: 'Multiple Outbound Forms Detected',
        text: 'Outbound Form cannot be open in multiple tabs. This tab will now close or redirect.',
        confirmButtonText: 'Return to Outbound Logs',
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
      }).then(() => {
        window.location.href = '/IMS/Inventory%20Management%20System/Outbound-logs/';
      });
    }

    function checkTabs() {
      const tabs = getTabs();
      if (tabs.length > 1) {
        showExitAlert();
      }
    }

    // Add self to the tab list
    updateTabsList(true);
    checkTabs();

    // Listen for tab list changes (another tab opening)
    window.addEventListener('storage', function(e) {
      if (e.key === TAB_LIST_KEY) {
        checkTabs();
      }
    });

    // Remove self on unload
    window.addEventListener('beforeunload', function() {
      updateTabsList(false);
    });
</script>
<?php 
    if(isset($_GET['warehouse'])){
        $_SESSION['warehouse_outbound'] = $_GET['warehouse'];
    } else {

    }

    if(strpos($warehouses, ',')!==true && !isset($_SESSION['warehouse_outbound'])){
        // Fetch warehouse hashed_id securely
        $warehouse_sql = "SELECT warehouse_name FROM warehouse WHERE hashed_id = ? LIMIT 1";
        $stmt = $conn->prepare($warehouse_sql);
        $stmt->bind_param("s", $warehouses);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $warehouse = $row['warehouse_name'];
            $_SESSION['warehouse_outbound'] = $warehouse;
        } else {
            echo json_encode(['status' => 'error', 'error' => 'Invalid warehouse.']);
            exit;
        }
    }
?>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <!-- Card Header -->
                <div class="card-header bg-info">
                    <h2 class="text-white">Outbound Form </h2>
                    <div class="my-3" id="barcode-form"></div>
                </div>
                <form action="../config/outbound.php" id="myform2" method="post">
                <!-- Card Body -->
                <div class="card-body overflow-hidden scrollbar border-top border-bottom">
                    <div class="table-responsive" style="height: 50vh; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 30px;"></th>
                                    <th>Barcode</th>
                                    <th>Description</th>
                                    <?php 
                                    if($user_position_name === "Administrator" || strpos($access ?? '', "view_capital")!==false){
                                    ?>
                                    <th class="text-end">Unit Cost(₱)</th>
                                    <?php 
                                    }
                                    ?>
                                    <th>Selling Price(₱)</th>
                                    <th>Batch Number</th>
                                    <th>Brand Name</th>
                                    <th>Category Name</th>
                                </tr>
                            </thead>
                            <tbody id="table-body"></tbody>

                        </table>
                    </div>    
                    <div class="row">

                        <!-- Left Column -->
                        <div class="col-8">
                            <div class="row">

                                <!-- Customer Name -->
                                <div class="col-4 mb-3">
                                    <label for="">Customer Name</label>
                                    <input class="form-control" name="customer_name" type="text" required>
                                </div>

                                <div class="col-4 mb-3">
                                    <label for="Payment Method">Payment Method</label>
                                    <select name="payment_method" class="form-select">
                                        <option value="" selected>Select Payment Method</option>
                                        <option value="Not Available">Not Available</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Gcash">Gcash</option>
                                        <option value="Credit/ Debit Card">Credit/ Debit Card</option>
                                        <option value="BDO">BDO</option>
                                        <option value="Eastwest">Eastwest</option>
                                        <option value="BPI">BPI</option>
                                    </select>
                                </div>

                                <!-- Platform -->
                                <div class="col-4 mb-3">
                                    <label for="">Platform</label>
                                    <select class="form-select" name="platform" id="" required>
                                        <?php 
                                        $sql = "SELECT * FROM logistic_partner ORDER BY logistic_name";
                                        $res = $conn->query($sql);

                                        if ($res->num_rows > 0) {
                                            while ($row = $res->fetch_assoc()) {
                                                echo "<option value='" . $row['hashed_id'] . "'>" . $row['logistic_name'] . "</option>";
                                            }
                                        } else {
                                            echo "<option value=''>No Data</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Courier -->
                                <div class="col-3 mb-3">
                                    <label for="">Courier</label>
                                    <select class="form-select" name="courier" id="" required>
                                        <?php 
                                        $sql = "SELECT * FROM courier ORDER BY courier_name ASC";
                                        $res = $conn->query($sql);

                                        if ($res->num_rows > 0) {
                                            while ($row = $res->fetch_assoc()) {
                                                echo "<option value='" . $row['hashed_id'] . "'>" . $row['courier_name'] . "</option>";
                                            }
                                        } else {
                                            echo "<option value=''>No Data</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Order Number -->
                                <div class="col-3 mb-3">
                                    <label for="">Order no.</label>
                                    <input class="form-control" name="order_no" type="text" required>
                                </div>

                                <!-- Order Line ID -->
                                <div class="col-3 mb-3">
                                    <label for="">Order Line ID</label>
                                    <input class="form-control" name="order_line_id" type="text" required>
                                </div>

                                <!-- Process By -->
                                <div class="col-3 mb-3">
                                    <label for="">Process by</label>
                                    <input class="form-control" type="text" name="processed_by" readonly value="<?php echo $user_fullname; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="col-4 border-start">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6 text-start">
                                        <p><b>Total:</b></p>
                                    </div>
                                    <div class="col-6 text-end">
                                        <p id="total" class="fw-bold">Total: 0.00</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <button class="btn btn-primary w-100">Submit</button>
                            </div>
                        </div>
                        
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content position-relative">
            <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
                <h4 class="mb-1" id="modalExampleDemoLabel">Summary </h4>
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
    <!-- Add this in the <head> or before </body> -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function () {
            let sellingInputClicked = false; // Track if the alert has been shown

            // Load barcode form initially
            loadBarcodeForm();

            // Load table content
            loadContent();

            loadSummary();

            // Function to load the barcode form
            function loadBarcodeForm() {
                $('#barcode-form').load('barcode-form.php', function () {
                    $('#barcode').focus();
                });
            }

            function loadSummary() {
                $.ajax({
                    url: 'summary.php?view=<?php echo $_SESSION['outbound_id'];?>',
                    method: 'GET',
                    success: function(response) {
                        $('#summary').html(response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading summary:', error);
                    }
                });
            }
            // Function to load table content
            function loadContent() {
                $.ajax({
                    url: 'bor.php?view=<?php echo $_SESSION['outbound_id'];?>', // URL of the PHP file
                    method: 'GET',
                    success: function (data) {
                        $('#table-body').html(data);

                        // Rebind events after loading content
                        bindSellingInputEvent();
                        calculateTotal();
                    },
                    error: function (xhr, status, error) {
                        console.error('Error loading content:', error);
                    }
                });
            }

            // Event delegation for calculateTotal
            $(document).on('input', '.selling-input', function () {
                calculateTotal();
            });

            // Calculate total
            function calculateTotal() {
                let total = 0;
                $('.selling-input').each(function () {
                    const value = parseFloat($(this).val());
                    if (!isNaN(value)) {
                        total += value;
                    }
                });
                $('#total').text(`Total: ${total.toFixed(2)}`);
            }

            // Show warning on first click of selling input
            function bindSellingInputEvent() {
                $('.selling-input').one('focus', function () {
                    if (!sellingInputClicked) {
                        sellingInputClicked = true; // Set to true after first alert
                        Swal.fire({
                            icon: 'warning',
                            title: 'Finalize Items First!',
                            text: 'If you input any values here, they will be cleared if you delete any barcode.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }

            // Event delegation for action buttons
            $(document).on('click', '.action-button', function () {
                const barcode = $(this).data('barcode');

                // Send AJAX request
                $.ajax({
                    url: 'action.php',
                    method: 'POST',
                    data: { barcode: barcode },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: response.message,
                                confirmButtonText: 'OK'
                            });

                            // Reload table content
                            loadContent();
                            loadSummary();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'An unexpected error occurred. Please try again later.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            
            // Include SweetAlert2 (make sure it's loaded in your HTML)
            $(document).on('submit', '#myForm', function (e) {
                e.preventDefault();

                $.ajax({
                    type: $(this).attr('method'),
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function (response) {
                        if (response.error) {
                            // Display SweetAlert2 error message
                            Swal.fire({
                                icon: 'warning',
                                title: response.error,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                loadBarcodeForm();
                            });
                        } else {
                            // Reload data
                            loadBarcodeForm();
                            loadContent();
                            loadSummary();
                        }
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'An unexpected error occurred. Please try again later.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            // Submit the form with id="myform2" via AJAX and show loading state
            $(document).on('submit', '#myform2', function (e) {
                e.preventDefault(); // Prevent default form submission

                // Disable submit button and show loading state
                let submitButton = $(this).find('button[type="submit"]');
                submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');

                // Submit the form via AJAX
                $.ajax({
                    type: $(this).attr('method'),
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function (response) {
                        // Re-enable submit button after response
                        submitButton.prop('disabled', false).html('Submit');

                        // Show SweetAlert2 based on response
                        Swal.fire({
                            icon: response.status === 'success' ? 'success' : 'error',
                            title: response.status === 'success' ? 'Success' : 'Error',
                            text: response.message,
                            confirmButtonText: 'OK'
                        }).then(function () {
                            if (response.status === 'success') {
                                // After successful response, ask user if they want to make another outbound transaction
                                Swal.fire({
                                    icon: 'question',
                                    title: 'Would you like to make another outbound transaction?',
                                    showCancelButton: true,
                                    confirmButtonText: 'Yes',
                                    cancelButtonText: 'No'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Redirect to unset.php for a new transaction
                                        window.location.href = 'unset.php';
                                    } else {
                                        // Redirect to outbound logs
                                        window.location.href = '../Outbound-logs/';
                                    }
                                });
                            }
                        });
                    },
                    error: function () {
                        // Re-enable submit button if there's an error
                        submitButton.prop('disabled', false).html('Submit');

                        // Show error message if AJAX fails
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'An unexpected error occurred. Please try again later.',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            });

            // $(document).on('submit', '#myform2', function (e) {
            //     e.preventDefault();

            //     let hasValidItem = false;
            //     let hasInvalidItem = false;

            //     $('#table-body tr').each(function () {
            //         // let unitCost = parseFloat($(this).find('.unit-cost').text()) || 0;
            //         let unitCost = parseFloat($(this).find('.unit-cost').data('cost')) || 0;
            //         let sellingPrice = parseFloat($(this).find('.selling-input').val()) || 0;

            //         // Condition 1: Valid profitable item
            //         if (sellingPrice > unitCost) {
            //             hasValidItem = true;
            //         }

            //         // Condition 2: Invalid (loss but not freebie)
            //         if (sellingPrice > 0 && sellingPrice < unitCost) {
            //             hasInvalidItem = true;
            //         }
            //     });

            //     // 🚫 If no profitable item at all → block
            //     if (!hasValidItem) {
            //         Swal.fire({
            //             icon: 'error',
            //             title: 'Invalid Pricing',
            //             text: 'You must have at least one item with selling price greater than unit cost.',
            //         });
            //         return;
            //     }

            //     // ⚠️ If may lugi items (but allowed because may valid)
            //     if (hasInvalidItem) {
            //         Swal.fire({
            //             icon: 'warning',
            //             title: 'Some items are below cost',
            //             text: 'There are items sold below unit cost. Do you want to proceed?',
            //             showCancelButton: true,
            //             confirmButtonText: 'Yes, proceed',
            //             cancelButtonText: 'Cancel'
            //         }).then((result) => {
            //             if (result.isConfirmed) {
            //                 submitFormAjax($(this));
            //             }
            //         });
            //         return;
            //     }

            //     // ✅ If clean → proceed
            //     submitFormAjax($(this));
            // });


        });
    </script>