<div class="card">
    <div class="card-body overflow-hidden p-lg-6">
        <?php 
        $inbound_sql = "SELECT id FROM inbound_logs ORDER BY id DESC LIMIT 1";
        $inbound_res = $conn->query($inbound_sql);
        if ($inbound_res->num_rows > 0) {
            $row = $inbound_res->fetch_assoc();
            $inbound_id = $row['id'];
        } else {
            $inbound_id = 1000;
        }
        
        
        if (isset($_SESSION['inbound_id'])) {
            // $inbound_id = $inbound_id + 1;
            // echo $inbound_id . " - " . $_SESSION['inbound_id'];
            if ($_SESSION['inbound_id'] < $inbound_id) {
                echo '<script type="text/javascript">';
                echo 'window.location.href = "../Inbound-logs/";'; // Redirect with JavaScript
                echo '</script>';
                exit(); // Stop script execution
            } else {
        
            }
        } else {
            $_SESSION['inbound_id'] = $inbound_id;
        }
        ?>
        <div class="row">
            <div class="col-lg-12">
                <form id="myForm" action="csv_process.php" method="POST" enctype="multipart/form-data">
                    <div class="row justify-content-end">
                        <div class="col-auto mb-3">
                            <button class="btn btn-primary" id="submitBTN" type="button">Submit</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table bordered-table">
                            <thead class="table-info">
                                <tr>
                                    <th></th>
                                    <th style="min-width: 300px;">Product Description</th>
                                    <th style="min-width: 200px;">Keyword</th>
                                    <th style="min-width: 130px;">Qty</th>
                                    <th style="min-width: 150px;">Price</th>
                                    <th style="min-width: 200px;">Supplier</th>
                                    <th style="min-width: 200px;">Barcode</th>
                                    <th style="min-width: 200px;">Batch no.</th>
                                    <th style="min-width: 200px;">Brand</th>
                                    <th style="min-width: 200px;">Category</th>
                                    <th style="min-width: 200px;">Safety</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php include "tbody.php";?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<button class="d-none" id="alert-button"></button>
<button class="d-none" id="batch-btn"></button>
<script>
    document.getElementById("alert-button").addEventListener("click", function() {
        Swal.fire({
            icon: "error",
            title: "Invalid CSV Format",
            text: "Please use the correct header format: description, keyword, qty, price, supplier, parent barcode, batch code, brand, category, safety.",
            confirmButtonText: "Go Back",
            allowOutsideClick: false
        }).then(() => {
            window.location.href = "../Inbound-logs/";
        });
    });
</script>
<script>
document.getElementById('submitBTN').addEventListener('click', function(event) {
    Swal.fire({
        title: "Do you want to save the changes?",
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: "Save",
        denyButtonText: `Don't save`
    }).then((result) => {
        if (result.isConfirmed) {
            // Show the loading spinner before the request
            const swalInstance = Swal.fire({
                title: 'Saving...',
                text: 'Please wait while we process your request.',
                didOpen: () => {
                    Swal.showLoading(); // Show the loading spinner
                },
                allowOutsideClick: false, // Prevent closing the modal by clicking outside
                showCancelButton: false,
                showConfirmButton: false
            });

            var formData = new FormData(document.getElementById('myForm'));

            fetch('csv_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Close the loading spinner
                swalInstance.close();

                // Handle the response based on status
                if (data.status === 'success') {
                    Swal.fire({
                        title: "Saved!",
                        text: data.message,
                        icon: "success",
                        confirmButtonText: "OK"
                    }).then(() => {
                        // Redirect to the dashboard after clicking "OK"
                        window.location.href = '../New-Unique-Barcodes?success=0';
                    });
                } else if (data.status === 'info') {
                    Swal.fire("Info", data.message, "info");
                } else {
                    Swal.fire("Error", "There was an issue saving the data.", "error");
                }
            })
            .catch(error => {
                // Close the loading spinner in case of error
                swalInstance.close();
                Swal.fire("Error", "There was an issue with the request.", "error");
            });
        } else if (result.isDenied) {
            Swal.fire("Changes are not saved", "", "info");
        }
    });
});
</script>
<script>
    $(document).ready(function () {
    function checkAllRows() {
        const items = [];
        const brands = [];
        const categories = [];
        const rows = $('table tr'); // Adjust selector to your table

        // Collect all item, brand, and category values from the rows
        rows.each(function (index) {
            const item = $(this).find('input[name="item[]"]').val();
            const brand = $(this).find('input[name="brand[]"]').val();
            const category = $(this).find('input[name="category[]"]').val();
            
            items.push(item);
            brands.push(brand);
            categories.push(category);
        });

        // Send batch data (item, brand, category) to the server
        $.ajax({
            url: '../config/check-product-csv-existence.php',  // Ensure this points to the correct path
            type: 'POST',
            data: JSON.stringify({ items, brands, categories }),  // Sending item, brand, and category values
            contentType: 'application/json',
            dataType: 'json',
            success: function (response) {
                rows.each(function (index) {
                    const row = $(this);
                    const itemInput = row.find('input[name="item[]"]');
                    const result = response[index];

                    // Item Feedback: show feedback based on product existence
                    if (result.itemExists) {
                        itemInput.removeClass('is-valid').addClass('is-invalid');
                        itemInput.next('.invalid-feedback').text('Product already exists').show();
                        itemInput.next('.valid-feedback').hide();
                    } else {
                        itemInput.removeClass('is-invalid').addClass('is-valid');
                        itemInput.next('.valid-feedback').text('Will be registered as new.').show();
                        itemInput.next('.invalid-feedback').hide();
                    }
                });
            },
            error: function (xhr, status, error) {
                // Log any errors in the console (can be deleted/commented later)
                console.error('Error checking product existence:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                
                // Optional: Show an alert to inform the user
                alert('Error checking products.');
            }
        });
    }

    // Check all rows once on page load
    checkAllRows();

    // Debounce input check (after the user stops typing)
    let debounceTimer;
    $('input[name="item[]"], input[name="brand[]"], input[name="category[]"]').on('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(checkAllRows, 500);
    });
});
</script>


<script>
    $(document).ready(function () {
    // Function to check brand existence
    function checkBrandExistence() {
        const brands = [];
        const rows = $('table tr'); // Adjust selector to your table rows

        // Collect all brand values from the rows
        rows.each(function (index) {
            const brand = $(this).find('input[name="brand[]"]').val();
            
            if (brand) {
                brands.push(brand);
            }
        });

        // Send batch brand data to the server for checking existence
        if (brands.length > 0) {
            $.ajax({
                url: '../config/check-brand-csv.php', // Path to the new server-side script
                type: 'POST',
                data: { 'brands': brands },  // Sending brands array
                dataType: 'json',
                success: function (response) {
                    rows.each(function (index) {
                        const row = $(this);
                        const brandInput = row.find('input[name="brand[]"]');
                        const brandName = brandInput.val();
                        const result = response.find(res => res.brand === brandName);

                        // If brand exists, show invalid feedback
                        if (result && result.exists) {
                            brandInput.removeClass('is-valid').addClass('is-invalid');
                            brandInput.next('.invalid-feedback').text('Brand already exists').show();
                            brandInput.next('.valid-feedback').hide();
                        } else {
                            // If brand doesn't exist, show valid feedback
                            brandInput.removeClass('is-invalid').addClass('is-valid');
                            brandInput.next('.valid-feedback').text('Brand will be registered as new.').show();
                            brandInput.next('.invalid-feedback').hide();
                        }
                    });
                },
                error: function (xhr, status, error) {
                    console.error('Error checking brand existence:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                }
            });
        }
    }

    // Function to check category existence
    function checkCategoryExistence() {
        const categories = [];
        const rows = $('table tr'); // Adjust selector to your table rows

        // Collect all category values from the rows
        rows.each(function (index) {
            const category = $(this).find('input[name="category[]"]').val();
            
            if (category) {
                categories.push(category);
            }
        });

        // Send batch category data to the server for checking existence
        if (categories.length > 0) {
            $.ajax({
                url: '../config/check-category-csv.php', // Path to the new server-side script
                type: 'POST',
                data: { 'categories': categories },  // Sending categories array
                dataType: 'json',
                success: function (response) {
                    rows.each(function (index) {
                        const row = $(this);
                        const categoryInput = row.find('input[name="category[]"]');
                        const categoryName = categoryInput.val();
                        const result = response.find(res => res.category === categoryName);

                        // If category exists, show invalid feedback
                        if (result && result.exists) {
                            categoryInput.removeClass('is-valid').addClass('is-invalid');
                            categoryInput.next('.invalid-feedback').text('Category already exists').show();
                            categoryInput.next('.valid-feedback').hide();
                        } else {
                            // If category doesn't exist, show valid feedback
                            categoryInput.removeClass('is-invalid').addClass('is-valid');
                            categoryInput.next('.valid-feedback').text('Category will be registered as new.').show();
                            categoryInput.next('.invalid-feedback').hide();
                        }
                    });
                },
                error: function (xhr, status, error) {
                    console.error('Error checking category existence:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                }
            });
        }
    }

    // Automatically check brand and category existence on page load
    checkBrandExistence();
    checkCategoryExistence();

    // Debounce input check (after the user stops typing)
    let debounceTimer;
    $('input[name="brand[]"]').on('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(checkBrandExistence, 500);
    });

    $('input[name="category[]"]').on('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(checkCategoryExistence, 500);
    });
});

</script>

<script>
    $(document).ready(function () {
    // Function to check supplier existence
    function checkSupplierExistence() {
        const suppliers = [];
        const rows = $('table tr'); // Adjust selector to your table rows

        // Collect all supplier values from the rows
        rows.each(function (index) {
            const supplier = $(this).find('input[name="supplier[]"]').val();
            
            if (supplier) {
                suppliers.push(supplier);
            }
        });

        // Send batch supplier data to the server for checking existence
        if (suppliers.length > 0) {
            $.ajax({
                url: '../config/check-supplier-csv.php', // Path to the supplier checking script
                type: 'POST',
                data: { 'suppliers': suppliers },  // Sending suppliers array
                dataType: 'json',
                success: function (response) {
                    rows.each(function (index) {
                        const row = $(this);
                        const supplierInput = row.find('input[name="supplier[]"]');
                        const supplierName = supplierInput.val();
                        const result = response.find(res => res.supplier === supplierName);

                        // If supplier exists, show invalid feedback
                        if (result && result.exists) {
                            supplierInput.removeClass('is-valid').addClass('is-invalid');
                            supplierInput.next('.invalid-feedback').text('Supplier already exists').show();
                            supplierInput.next('.valid-feedback').hide();
                        } else {
                            // If supplier doesn't exist, show valid feedback
                            supplierInput.removeClass('is-invalid').addClass('is-valid');
                            supplierInput.next('.valid-feedback').text('Supplier will be registered as new.').show();
                            supplierInput.next('.invalid-feedback').hide();
                        }
                    });
                },
                error: function (xhr, status, error) {
                    console.error('Error checking supplier existence:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                }
            });
        }
    }

    // Automatically check supplier existence on page load
    checkSupplierExistence();

    // Debounce input check (after the user stops typing)
    let debounceTimer;
    $('input[name="supplier[]"]').on('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(checkSupplierExistence, 500);
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