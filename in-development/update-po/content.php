<?php  
$selected_warehouse_id = $_SESSION['selected_warehouse_id'];
$selected_warehouse_name = $_SESSION['selected_warehouse_name'];

if(isset($_GET['blue'])){
    $blue = $_GET['blue'];
} else {
    $blue = "";
}
?>

<div class="card">
    <div class="card-header bg-warning">
        <h2 class="text-white">Confirmation of Orders</h2>
        <p class="text-white">Please confirm your orders then select your supplier.</p>
    </div>
    <div class="card-body overflow-hidden py-6 px-2">
        <form id="import" method="POST">
            <div class="row px-3">
                <div class="col-9">
                    <div class="mb-3">
                        <input type="text" id="search_item" class="form-control" placeholder="Search Item name">
                    </div>
                    <div class="scrollbar overflow-auto mb-3" style="max-height: 250px;">
                        <div id="showhere"></div>
                    </div>
                </div>
                <div class="col-3 text-start">
                    <button class="btn btn-primary" type="submit" id="btnSubmitImport">Add</button>
                </div>
            </div>
            
            
        </form>
        <form action="../config/update_po.php" method="POST">
            <div class="card shadow-none">
                <div class="card-body p-0 pb-3" data-list='{"valueNames":["desc","barcode","brand","cat","qty"]}'>
                    <div class="d-flex align-items-center justify-content-end my-3">

                        <div class="col-auto text-end mb-3 me-1">
                            <select class="form-select" name="supplier" required>
                                <option value="">Select Supplier</option>
                                <?php 
                                $supplier_query = "SELECT * FROM supplier ORDER BY supplier_name ASC";
                                $supplier_res = $conn->query($supplier_query);
                                if ($supplier_res->num_rows > 0) {
                                    while ($supplier_row = $supplier_res->fetch_assoc()) {
                                        $supplier = $supplier_row['supplier_name'];
                                        $supplier_id = $supplier_row['hashed_id'];
                                        echo '<option value="' . $supplier_id . '">' . $supplier . '</option>';
                                    }
                                } else {
                                    echo '<option value="">No Supplier Available</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-auto text-end mb-3 me-1">
                            <div id="bulk-select-replace-element">
                                <button class="btn btn-falcon-success btn-sm" type="submit" id="submit-po-btn">
                                    <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                    <span class="ms-1">Submit</span>
                                </button>
                            </div>
                        </div>
                        <div class="d-none ms-3" id="bulk-select-actions"></div>
                    </div>

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
                                    <th class="text-black fs-11 dark__text-white align-middle white-space-nowrap pe-3 sort" data-sort="qty">Quantity</th>
                                </tr>
                            </thead>
                            <tbody class="list" data-sortable="data-sortable" id="preview">
                               
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center align-items-center py-6" style="height: 100px;">
                        <div class="form-check">
                            <input type="number" name="po_id" value="<?php echo $blue;?>" required hidden>
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
<script>
$(document).ready(function() {
    $(document).on("change", "#flexCheckChecked", function () {
        checkFormCompletion();
    });

    
    function loadPreview() {
        $("#preview").load("preview.php", function () {
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

    function checkFormCompletion() {
        let allFilled = true;

        // Check if supplier is selected
        const supplierSelected = $("select[name='supplier']").val() !== "";
        if (!supplierSelected) allFilled = false;

        // Check if all order_qty[] fields have values > 0
        $("#preview tr").each(function () {
            const qty = $(this).find("input[name='order_qty[]']").val();
            if (!qty || parseInt(qty) <= 0) {
                allFilled = false;
                return false; // break loop
            }
        });

        // ✅ Check if the confirmation checkbox is ticked
        const isChecked = $("#flexCheckChecked").is(":checked");
        if (!isChecked) allFilled = false;

        // Show or hide submit button
        if (allFilled) {
            $("#submit-po-btn").removeClass("d-none");
        } else {
            $("#submit-po-btn").addClass("d-none");
        }
    }


    // Re-check form on changes
    $(document).on("input change", "input[name='order_qty[]'], select[name='supplier']", function () {
        checkFormCompletion();
    });

    // Also check after preview reload
    function loadPreview() {
        $("#preview").load("preview.php?blue=<?php echo $blue; ?>", function () {
            checkFormCompletion(); // run after new rows are loaded
        });
    }


    $(document).on("click", ".delete-btn", function () {
        const id = $(this).attr("target-id");

        $.ajax({
            url: "remove_item.php",
            method: "POST",
            data: { id: id },
            dataType: "json",
            success: function (response) {
                if (response.status === "success") {
                    // Reload the preview list
                    $("#preview").load("preview.php");
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
</script>
