<div class="row">
    <div class="col-lg-12">
        <h4>brand</h4>
    </div>
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body overflow-hidden">
                <div id="tableExample3" data-list='{"valueNames":["name","email","age"],"page":5,"pagination":true}'>
                    <div class="row justify-content-end g-0">
                        <div class="col-auto col-sm-7 mb-3 text-start">
                            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#error-modal">
                                <span class="fas fa-plus"></span> New
                            </button>
                        </div>
                        <div class="col-auto col-sm-5 mb-3">
                            <form>
                                <div class="input-group">
                                    <input class="form-control form-control-sm shadow-none search" type="search" placeholder="Search..." aria-label="search" />
                                    <div class="input-group-text bg-transparent">
                                        <span class="fa fa-search fs-10 text-600"></span>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive scrollbar">
                        <table class="table table-bordered table-striped fs-10 mb-0">
                            <thead class="bg-200">
                                <tr>
                                    <th class="text-900 sort" data-sort="name">brand</th>
                                    <th class="text-900 sort" data-sort="email">Published date</th>
                                    <th class="text-900 sort" data-sort="age">Publish by</th>
                                    <th class="text-900" style="width: 50px;"></th> <!-- Added Actions header -->
                                </tr>
                            </thead>
                            <tbody class="list">
                                <?php 
                                $brand_query = "SELECT brand.*, users.user_fname, users.user_lname FROM brand LEFT JOIN users ON users.hashed_id = brand.user_id WHERE brand.current_status = 0 ORDER BY brand.id DESC";
                                $brand_result = mysqli_query($conn, $brand_query);
                                if ($brand_result->num_rows > 0) {
                                    while ($row = $brand_result->fetch_assoc()) {
                                        $brand_name = $row['brand_name'];
                                        $publish_Date = $row['date'];
                                        $by = $row['user_fname'] . " " . $row['user_lname'];
                                ?>
                                <tr>
                                    <td class="name"><?php echo htmlspecialchars($brand_name); ?></td>
                                    <td class="email"><?php echo htmlspecialchars($publish_Date); ?></td>
                                    <td class="age"><?php echo htmlspecialchars($by); ?></td>
                                    <td class="d-flex align-items-center">
                                        <button class="btn btn-transparent" type="button" data-bs-toggle="modal" data-bs-target="#edit-modal" target-id="<?php echo $row['hashed_id']; ?>"><span class="far fa-edit"></span></button>
                                        <a href="../config/delete.php?from=brand&id=<?php echo $row['hashed_id'];?>" class="btn btn-transparent text-danger ms-1 custom-clicked" ><span class="far fa-trash-alt"></span></a>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        <button class="btn btn-sm btn-falcon-default me-1" type="button" title="Previous" data-list-pagination="prev">
                            <span class="fas fa-chevron-left"></span>
                        </button>
                        <ul class="pagination mb-0"></ul>
                        <button class="btn btn-sm btn-falcon-default ms-1" type="button" title="Next" data-list-pagination="next">
                            <span class="fas fa-chevron-right"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="../config/add-brand.php" id="myForm" method="POST">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
            <div class="modal-content position-relative">
                <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
                        <h4 class="mb-1" id="modalExampleDemoLabel">Add a new brand</h4>
                    </div>
                    <div class="p-4 pb-0">
                        <div class="mb-3">
                            <label class="col-form-label" for="brand-name">brand Name:</label>
                            <input class="form-control" id="brand-name" name="brand_name" type="text" />
                            <div class="valid-feedback">Looks good!</div>
                            <div class="invalid-feedback">Warehouse name already exist</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary" id="btnsubmit" type="submit" disabled>Submit</button>
                </div>
            </div>
        </div>
    </form>
</div>



<div class="modal fade" id="edit-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="update-form" action="../config/update-admin.php?type=brand" method="POST">
        <div class="modal-body p-0">
            <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
            <h4 class="mb-1" id="modalExampleDemoLabel">Update </h4>
            </div>
            <div class="p-4 pb-0">
                <div id="form-content" class="mb-3">
                
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
            <button class="btn btn-primary" type="submit">Submit </button>
        </div>
    </form>
    </div>
  </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $("#update-form").on("submit", function(e) {
            e.preventDefault(); // Prevent default form submission
            
            Swal.fire({
                title: "Are you sure?",
                text: "Do you want to update this brand?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, update it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading alert
                    Swal.fire({
                        title: "Processing...",
                        text: "Please wait while updating the brand.",
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Submit the form via AJAX
                    $.ajax({
                        url: $("#update-form").attr("action"),
                        type: $("#update-form").attr("method"),
                        data: $("#update-form").serialize(),
                        success: function(response) {
                            // Assuming the server responds with a success message
                            Swal.fire({
                                title: "Updated!",
                                text: "Brand has been successfully updated.",
                                icon: "success",
                                timer: 2000,
                                showConfirmButton: false
                            });

                            setTimeout(() => {
                                location.reload(); // Reload after success
                            }, 2000);
                        },
                        error: function() {
                            Swal.fire({
                                title: "Error!",
                                text: "Something went wrong. Please try again.",
                                icon: "error"
                            });
                        }
                    });
                }
            });
        });
    });
</script>
<script>
    $(document).ready(function() {
        // Debounce function to delay the AJAX call until the user stops typing
        let debounceTimer;
        $('#brand-name').on('input', function() {
            const brandName = $(this).val();

            // Clear the previous timeout
            clearTimeout(debounceTimer);

            // Set a new timeout for checking the warehouse name
            debounceTimer = setTimeout(function() {
                // Perform the AJAX request to check the warehouse name
                $.ajax({
                    url: '../config/check-brand.php',
                    type: 'POST',
                    data: { 'brand-name': brandName },
                    dataType: 'json',
                    success: function(response) {
                        // Handle the response from the PHP script
                        if (response.exists) {
                            $('#brand-name').removeClass('is-valid').addClass('is-invalid');
                            $('.invalid-feedback').show();
                            $('.valid-feedback').hide();
                            $('#btnsubmit').prop('disabled', true); // Disable submit button
                        } else {
                            $('#brand-name').removeClass('is-invalid').addClass('is-valid');
                            $('.valid-feedback').show();
                            $('.invalid-feedback').hide();
                            $('#btnsubmit').prop('disabled', false); // Enable submit button
                        }
                    },
                    error: function() {
                        // Handle any errors that occur during the AJAX request
                        alert('Error checking warehouse name.');
                    }
                });
            }, 500); // Delay of 500ms after the user stops typing
        });
    });
</script>

<script>
    $(document).ready(function () {
    function checkBrandExistence() {
        // Loop through each row and check brand existence
        $('table tr').each(function (index) {
            const brandInput = $(this).find('input[name="brand[]"]');
            const brandName = brandInput.val();
            
            // Skip if the brand input is empty
            if (brandName) {
                $.ajax({
                    url: '../config/check-brand.php',
                    type: 'POST',
                    data: { 'brand-name': brandName },
                    dataType: 'json',
                    success: function (response) {
                        // Handle brand existence feedback
                        if (response.exists) {
                            brandInput.removeClass('is-valid').addClass('is-invalid');
                            brandInput.next('.invalid-feedback').text('Brand already exists').show();
                            brandInput.next('.valid-feedback').hide();
                        } else {
                            brandInput.removeClass('is-invalid').addClass('is-valid');
                            brandInput.next('.valid-feedback').text('Brand will be registered as new.').show();
                            brandInput.next('.invalid-feedback').hide();
                        }
                    },
                    error: function (xhr, status, error) {
                        // Log any errors in the console (can be deleted/commented later)
                        console.error('Error checking brand existence:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                    }
                });
            }
        });
    }

    // Check brand existence once on page load
    checkBrandExistence();

    // Debounce input check (after the user stops typing)
    let debounceTimer;
    $('input[name="brand[]"]').on('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(checkBrandExistence, 500);
    });
});

</script>

<script>
$(document).ready(function() {
    $(document).on("click", ".custom-clicked", function(e) {
        e.preventDefault();
        let link = $(this).attr("href");

        Swal.fire({
            title: "Are you sure?",
            text: "This action cannot be undone!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = link;
            }
        });
    });
});
</script>

<?php
if (isset($_GET['update']) && $_GET['update'] === "success") {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Success!',
                text: 'The update was successful.',
                icon: 'success',
                confirmButtonText: 'OK'
            });
        });
    </script>";
}
?>