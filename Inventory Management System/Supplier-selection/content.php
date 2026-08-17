<?php
$selected_warehouse_id = $_SESSION['selected_warehouse_id'];
$selected_warehouse_name = $_SESSION['selected_warehouse_name'];

// CSRF token for the AJAX calls and PO submit on this page
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<div class="card">
    <div class="card-header bg-warning d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h2 class="text-white mb-0">Confirmation of Orders</h2>
            <p class="text-white mb-0">Please confirm your orders then select your supplier.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-dark fs-10">
                <span class="fas fa-warehouse me-1"></span>
                <?php echo htmlspecialchars($selected_warehouse_name ?? 'No warehouse selected'); ?>
            </span>
        </div>
    </div>
    <div class="card-body overflow-hidden py-6 px-2">
        <form id="import" method="POST">
            <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="row px-3">
                <div class="col-9">
                    <div class="mb-3 position-relative">
                        <input type="text" id="search_item" class="form-control" placeholder="Search Item name" autocomplete="off">
                        <span id="search_spinner" class="spinner-border spinner-border-sm text-primary d-none position-absolute" style="right: 12px; top: 10px;"></span>
                    </div>
                    <div class="scrollbar overflow-auto mb-3" style="max-height: 250px;">
                        <div id="showhere"></div>
                    </div>
                </div>
                <div class="col-3 text-start">
                    <button class="btn btn-primary" type="submit" id="btnSubmitImport">Add</button>
                    <div class="mt-2">
                        <span class="badge bg-secondary" id="cart-count-badge">0 items in cart</span>
                    </div>
                </div>
            </div>


        </form>
        <form action="../config/create_po.php" method="POST" id="createPoForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="card shadow-none">
                <div class="card-body p-0 pb-3" data-list='{"valueNames":["desc","barcode","brand","cat","qty"]}'>
                    <div class="d-flex align-items-center justify-content-end my-3 flex-wrap gap-2">

                        <div class="col-auto text-end mb-3 me-1">
                            <select class="form-select" name="supplier" id="supplier_select" required>
                                <option value="">Select Supplier</option>
                                <?php
                                $supplier_query = "SELECT * FROM supplier ORDER BY supplier_name ASC";
                                $supplier_res = $conn->query($supplier_query);
                                if ($supplier_res->num_rows > 0) {
                                    while ($supplier_row = $supplier_res->fetch_assoc()) {
                                        $supplier = $supplier_row['supplier_name'];
                                        $supplier_id = $supplier_row['hashed_id'];
                                        $local_international = $supplier_row['local_international'];
                                        if ($local_international === "Hakot") {
                                            $local_international = "Bidding";
                                        } elseif ($local_international === "International") {
                                            $local_international = "Imports";
                                        } elseif (empty($local_international)) {
                                            $local_international = "Requires update on supplier module";
                                        }
                                        echo '<option value="' . htmlspecialchars($supplier_id) . '">' . htmlspecialchars($supplier) . ' - ' . htmlspecialchars($local_international) . '</option>';
                                    }
                                } else {
                                    echo '<option value="">No Supplier Available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-auto text-end mb-3 me-1">
                            <div id="bulk-select-replace-element">
                                <span class="d-inline-block" tabindex="0" id="submit-po-tooltip-wrap">
                                    <button class="btn btn-falcon-success btn-sm" type="submit" id="submit-po-btn" disabled>
                                        <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                        <span class="ms-1">Submit</span>
                                    </button>
                                </span>
                            </div>
                        </div>
                        <div class="d-none ms-3" id="bulk-select-actions"></div>
                    </div>

                    <div id="submit-blockers" class="alert alert-warning py-2 px-3 fs-11 mb-3 d-none"></div>

                    <div class="table-responsive scrollbar">
                        <table class="table mb-0 table-sm">
                            <thead class="bg-200">
                                <tr>
                                    <th width="50"></th>
                                    <th class="text-black fs-11 dark__text-white align-middle sort" data-sort="desc">Description</th>
                                    <th class="text-black fs-11 dark__text-white align-middle sort" data-sort="desc">Parent Barcode</th>
                                    <th class="text-black fs-11 dark__text-white align-middle sort" data-sort="barcode">Brand</th>
                                    <th class="text-black fs-11 dark__text-white align-middle sort" data-sort="cat">Category</th>
                                    <th class="text-black fs-11 dark__text-white align-middle sort" style="min-width: 250px;" hidden>Supplier</th>
                                    <th class="text-black fs-11 dark__text-white align-middle sort" style="min-width: 150px;">Order Quantity</th>
                                    <th class="text-black fs-11 dark__text-white align-middle white-space-nowrap pe-3 sort" data-sort="qty">Current Stock</th>
                                </tr>
                            </thead>
                            <tbody class="list" data-sortable="data-sortable" id="preview">

                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center align-items-center py-6" style="height: 100px;">
                        <div class="form-check">
                            <input class="form-check-input" id="flexCheckChecked" type="checkbox" value="" required />
                            <label class="form-check-label text-danger" for="flexCheckChecked">
                            I reviewed and checked the data before I submitted it.
                            </label>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>

<style>
/* Sticky action bar for long item lists */
.po-sticky-bar {
    position: sticky;
    bottom: 0;
    background: #fff;
    z-index: 5;
    border-top: 1px solid #e9ecef;
    box-shadow: 0 -2px 6px rgba(0,0,0,0.05);
}
#preview tr.row-invalid input[name='order_qty[]'] {
    border: 2px solid #dc3545 !important;
}
</style>

<script>
$(document).ready(function() {

    function escapeHtml(str) {
        if (str === null || str === undefined) return "";
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    $(document).on("change", "#flexCheckChecked", function () {
        checkFormCompletion();
    });

    function updateCartBadge() {
        const count = $("#preview tr.sortable-item").length;
        $("#cart-count-badge").text(count + (count === 1 ? " item in cart" : " items in cart"));
    }

    function loadPreview() {
        $("#preview").load("preview.php", function () {
            checkFormCompletion();
            updateCartBadge();
        });
    }
    loadPreview();

    // --- Search with debounce + request abort to avoid race conditions ---
    let searchTimer = null;
    let searchXhr = null;

    $("#search_item").on("keyup", function() {
        const query = $(this).val().trim();
        clearTimeout(searchTimer);

        if (query.length <= 1) {
            $("#showhere").html("");
            $("#search_spinner").addClass("d-none");
            return;
        }

        searchTimer = setTimeout(function() {
            if (searchXhr) {
                searchXhr.abort();
            }
            $("#search_spinner").removeClass("d-none");

            searchXhr = $.ajax({
                url: "search.php",
                method: "POST",
                data: { query: query },
                dataType: "json",
                success: function(response) {
                    let output = "<ul class='list-group'>";
                    if (response.length > 0) {
                        response.forEach(function(item) {
                            const barcode = escapeHtml(item.parent_barcode);
                            const desc = escapeHtml(item.description);
                            const brand = escapeHtml(item.brand_name);
                            const cat = escapeHtml(item.category_name);
                            output += `
                                <li class="list-group-item d-flex align-items-center">
                                    <input class="form-check-input me-2 item-checkbox" name="parent_barcode[]" type="checkbox" value="${barcode}" id="chk_${barcode}">
                                    <label for="chk_${barcode}" class="flex-grow-1">
                                        <strong>${desc}</strong> - ${brand} - ${cat}
                                    </label>
                                    <span class="badge bg-primary">${barcode}</span>
                                </li>`;
                        });
                    } else {
                        output += "<li class='list-group-item text-muted'>No results found</li>";
                    }
                    output += "</ul>";
                    $("#showhere").html(output);
                },
                complete: function() {
                    $("#search_spinner").addClass("d-none");
                }
            });
        }, 300);
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
                    data: { parent_barcodes: selectedBarcodes, csrf_token: $("#csrf_token").val() },
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

    function checkFormCompletion() {
        let allFilled = true;
        let reasons = [];

        const supplierSelected = $("#supplier_select").val() !== "";
        if (!supplierSelected) {
            allFilled = false;
            reasons.push("Select a supplier");
        }

        const rowCount = $("#preview tr.sortable-item").length;
        if (rowCount === 0) {
            allFilled = false;
            reasons.push("Add at least one item");
        }

        let missingQty = false;
        $("#preview tr.sortable-item").each(function () {
            const $row = $(this);
            const qty = $row.find("input[name='order_qty[]']").val();
            if (!qty || parseInt(qty) <= 0) {
                missingQty = true;
                $row.addClass("row-invalid");
            } else {
                $row.removeClass("row-invalid");
            }
        });
        if (missingQty) {
            allFilled = false;
            reasons.push("Enter an order quantity greater than 0 for every item");
        }

        const isChecked = $("#flexCheckChecked").is(":checked");
        if (!isChecked) {
            allFilled = false;
            reasons.push("Check the confirmation box");
        }

        const $btn = $("#submit-po-btn");
        const $blockers = $("#submit-blockers");
        if (allFilled) {
            $btn.prop("disabled", false).removeAttr("title");
            $blockers.addClass("d-none").text("");
        } else {
            $btn.prop("disabled", true).attr("title", reasons.join(", "));
            $blockers.removeClass("d-none").html("<span class='fas fa-circle-info me-1'></span>Before you can submit: " + reasons.join(" &middot; "));
        }
    }

    $(document).on("input change", "input[name='order_qty[]'], select[name='supplier']", function () {
        checkFormCompletion();
    });

    $(document).on("click", ".delete-btn", function () {
        const id = $(this).attr("target-id");
        const $btn = $(this);

        Swal.fire({
            title: "Remove item?",
            text: "This item will be removed from the current PO draft.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, remove",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "remove_item.php",
                method: "POST",
                data: { id: id, csrf_token: $("#csrf_token").val() },
                dataType: "json",
                success: function (response) {
                    if (response.status === "success") {
                        loadPreview();
                    } else {
                        Swal.fire("Error", response.message, "error");
                    }
                },
                error: function () {
                    Swal.fire("Error", "Failed to communicate with server.", "error");
                }
            });
        });
    });

});
</script>
