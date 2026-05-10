<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body overflow-hidden">
                <h5 class="mb-3">Add Product:</h5>
                <form id="import" method="POST">
                    <div class="mb-3">
                        <label for="search_item">Item Name:</label>
                        <input type="text" id="search_item" class="form-control">
                    </div>
                    <div class="scrollbar overflow-auto mb-3" style="max-height: 250px;">
                        <div id="showhere"></div>
                    </div>
                    <button class="btn btn-primary w-100" type="submit" id="btnSubmitImport">Submit</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-12 mt-3">
        <div class="card">
            <div class="row p-3">
                <div class="col-md-12">
                    <h4>Inbounded Items from PO#<?php echo $_SESSION['inbound_po_id'];?>:</h4>
                </div>
            </div>
            <form action="../config/save-inbound.php" id="save-inbound" method="POST">
                <div class="card-body overflow-hidden">
                    <div id="tableExample" data-list='{"valueNames":["name","email","age"],"page":5,"pagination":true}'>
                        <div class="table-responsive scrollbar">
                            <table class="table table-bordered table-striped fs-10 mb-0 table-sm">
                                <thead class="table-info fs-11">
                                    <tr>
                                        <th class="text-900 fs-11 sort" data-sort="name">Description</th>
                                        <th class="text-900 fs-11 sort" data-sort="email">Brand</th>
                                        <th class="text-900 fs-11 sort" data-sort="age">Category</th>
                                        <th class="text-900 fs-11 sort" data-sort="age">Parent Barcode</th>
                                        <th class="text-900 fs-11 sort" data-sort="age">Quantity Ordered</th>
                                        <th class="text-900 fs-11 sort" data-sort="age">Quantity Received</th>
                                        <th class="text-900 fs-11 sort" id="unit_amount" data-sort="age">Unit Price</th>
                                        <th class="text-900 fs-11 sort" id="subtotal_th" data-sort="age">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="scrollbar overflow-auto" style="max-height: 500px;" id="preview"></tbody>
                                <tfoot>
                                    <tr>
                                        <td class="text-end fs-11" colspan="7"><b><i>Total:</i></b></td>
                                        <td class="text-end fs-11 sticky-bottom"><b><i id="total_amount"></i></b></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="text-end p-3">
                    <button class="btn btn-primary" type="submit">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="toastMessage" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastBody"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    function calculateSubtotal() {
        let total = 0;

        $("#preview tr").each(function () {
            let qtyReceived = $(this).find("#qty_received").val();
            let unitAmount = $(this).find("#unit_amount").val();
            let subtotalTd = $(this).find("#subtotal_td");

            // Ensure values are numbers
            qtyReceived = parseFloat(qtyReceived) || 0;
            unitAmount = parseFloat(unitAmount) || 0;
            let subtotal = qtyReceived * unitAmount;

            // Display subtotal
            subtotalTd.text(subtotal.toFixed(2));

            // Add to total amount
            total += subtotal;
        });

        // Update the total amount display
        $("#total_amount").text(total.toFixed(2));
    }

    // Attach event listeners
    $(document).on("input", "#qty_received, #unit_amount", function () {
        calculateSubtotal();
    });

    
    function loadPreview() {
        $("#preview").load("preview.php", function () {
            calculateSubtotal(); // Recalculate after loading preview
        });
    }
    loadPreview();

    $("#search_item").on("keyup", function() {
        let query = $(this).val();
        if (query.length > 1) {
            $.ajax({
                url: "search.php",
                method: "POST",
                data: { query: query },
                dataType: "json",
                success: function(response) {
                    let output = "<ul class='list-group'>";
                    if (response.length > 0) {
                        response.forEach(function(item) {
                            output += `
                                <li class="list-group-item d-flex align-items-center">
                                    <input class="form-check-input me-2 item-checkbox" name="parent_barcode[]" type="checkbox" value="${item.parent_barcode}" id="chk_${item.parent_barcode}">
                                    <label for="chk_${item.parent_barcode}" class="flex-grow-1">
                                        <strong>${item.description}</strong> - ${item.brand_name} - ${item.category_name}
                                    </label>
                                    <span class="badge bg-primary">${item.parent_barcode}</span>
                                </li>`;
                        });
                    } else {
                        output += "<li class='list-group-item text-muted'>No results found</li>";
                    }
                    output += "</ul>";
                    $("#showhere").html(output);
                }
            });
        } else {
            $("#showhere").html("");
        }
    });

    $("#import").on("submit", function(event) {
        event.preventDefault();
        let selectedBarcodes = $(".item-checkbox:checked").map(function() {
            return this.value;
        }).get();
        
        if (selectedBarcodes.length === 0) {
            Swal.fire("Warning", "Please select at least one product.", "warning");
            return;
        }

        Swal.fire({
            title: "Are you sure?",
            text: "Do you want to submit the selected products?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, submit!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "import.php",
                    type: "POST",
                    data: { parent_barcodes: selectedBarcodes },
                    dataType: "json",
                    success: function(response) {
                        let toastMessage = $("#toastMessage");
                        if (response.status === "success") {
                            toastMessage.removeClass("bg-danger").addClass("bg-success");
                            loadPreview();
                        } else {
                            toastMessage.removeClass("bg-success").addClass("bg-danger");
                        }
                        $("#toastBody").text(response.message);
                        new bootstrap.Toast(toastMessage[0]).show();
                        $("#import")[0].reset();
                        $("#showhere").html("");
                    },
                    error: function() {
                        $("#toastMessage").removeClass("bg-success").addClass("bg-danger");
                        $("#toastBody").text("Something went wrong. Please try again.");
                        new bootstrap.Toast(document.getElementById("toastMessage")).show();
                    }
                });
            }
        });
    });

    $("#save-inbound").submit(function (event) {
        event.preventDefault(); // Prevent normal submission

        Swal.fire({
            title: "Are you sure?",
            text: "Do you want to submit this inbound data?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, submit!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                let formData = $(this).serialize();

                $.ajax({
                    url: "../config/save-inbound.php",
                    type: "POST",
                    data: formData,
                    dataType: "json",
                    beforeSend: function () {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while we submit your data.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function (response) {
                        Swal.fire({
                            title: response.status === "success" ? "Success!" : "Error!",
                            text: response.message,
                            icon: response.status === "success" ? "success" : "error",
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            if (response.status === "success") {
                                window.location.href = "../New-Unique-Barcodes?success=0";
                            }
                        });
                    },
                    error: function () {
                        Swal.fire("Error!", "Something went wrong. Please try again.", "error");
                    }
                });
            }
        });
    });
});
</script>

<script>
    const LOCK_KEY = 'csv_po_lock';
    const thisTabId = Date.now().toString();

    function showBlockedAlert() {
      Swal.fire({
        icon: 'warning',
        title: 'Another Page is Already Open',
        text: 'Only one of the CSV or PO Import pages can be open at a time.',
        confirmButtonText: 'Return to Inbound Logs',
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
      }).then(() => {
        // Redirect to your inbound logs page
        window.location.href = '/IMS/Inventory%20Management%20System/inbound-logs/';
      });
    }

    function checkLock() {
      const current = localStorage.getItem(LOCK_KEY);
      if (current && current !== thisTabId) {
        showBlockedAlert();
      } else {
        localStorage.setItem(LOCK_KEY, thisTabId);
      }
    }

    checkLock();

    // If another tab sets the lock, show alert
    window.addEventListener('storage', function(event) {
      if (event.key === LOCK_KEY && event.newValue !== thisTabId) {
        showBlockedAlert();
      }
    });

    // Clean up on tab close
    window.addEventListener('beforeunload', function() {
      const current = localStorage.getItem(LOCK_KEY);
      if (current === thisTabId) {
        localStorage.removeItem(LOCK_KEY);
      }
    });
  </script>
