
<?php 
// Generate a new CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a secure random token
}
?>
<div class="row py-6 px-1">
    <div class="col-lg-12 mb-3">
        <h4>Access Level</h4>
    </div>
    
    <div class="col-lg-12">
        <div id="tableExample3" data-list='{"valueNames":["name","email","age"],"page":5,"pagination":true}'>

            <!-- Search form -->
            <div class="row justify-content-end g-0">
                <div class="col-auto col-sm-7 mb-3">
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

            <!-- Table -->
            <div class="table-responsive scrollbar">
                <table class="table table-bordered table-striped fs-10 mb-0">
                    <thead class="bg-200">
                        <tr>
                            <th class="text-900 sort" data-sort="name">Position name</th>
                            <!-- <th class="text-900 sort" data-sort="status">Status</th> -->
                            <th class="text-900 sort" data-sort="email">Published date</th>
                            <th class="text-900 sort" data-sort="age">Publish by</th>
                            <th class="text-900 sort" ></th>
                        </tr>
                    </thead>
                    <tbody class="list bg-light">
                        <?php
                            $Query = "SELECT a.*, u.user_fname, u.user_lname 
                                        FROM user_position a
                                        LEFT JOIN users u ON u.id = a.user_id 
                                        ORDER BY a.id DESC";
                  
                            $res = $conn->query($Query);
                            while($row = $res->fetch_assoc()) {
                                $position_name = $row['position_name'];
                                $publish_date = date("F j, Y", strtotime($row['date']));
                                $publish_by = $row['user_fname'] . " " . $row['user_lname'];
                                 if($row['position_status'] == 0){
                                    $status = '<span class="badge bg-danger">Disabled</span>';
                                } else {
                                    $status = '<span class="badge bg-success">Enabled</span>';
                                }
                            ?>
                            <tr>
                                <td class="name"><b><small><?php echo $position_name;?></small></b></td>
                                <!-- <td class="status"><?php // echo $status;?></td> -->
                                <td class="email"><small><?php echo $publish_date;?></small></td>
                                <td class="age"><small><?php echo $publish_by;?></small></td>
                                <td class="py-1 px-0">
                                    <?php 
                                    if($position_name !== "Superadmin"){
                                    ?>
                                    <button class="btn btn-transparent py-0" type="button"  data-bs-toggle="modal" data-bs-target="#edit-modal_<?php echo $row['id'];?>"><small><span class="far fa-edit" data-bs-toggle="tooltip" data-bs-placement="left" title="Edit" ></span></small></button>
                                    <a href="../config/delete.php?from=access_level&id=<?php echo $row['id'];?>" class="btn btn-transparent text-danger ms-1 custom-clicked" ><span class="far fa-trash-alt"></span></a>
                                    <?php 
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php
                            }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
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



<?php 
include "add_position.php";
include "update_position.php";
?>



<script>
    $(document).ready(function() {
        // Debounce function to delay the AJAX call until the user stops typing
        let debounceTimer;
        $('#position-name').on('input', function() {
            const positionName = $(this).val();

            // Clear the previous timeout
            clearTimeout(debounceTimer);

            // Set a new timeout for checking the position name
            debounceTimer = setTimeout(function() {
                // Perform the AJAX request to check the position name
                $.ajax({
                    url: '../config/check-position.php',
                    type: 'POST',
                    data: { 'position-name': positionName },
                    dataType: 'json',
                    success: function(response) {
                        // Handle the response from the PHP script
                        if (response.exists) {
                            $('#position-name').removeClass('is-valid').addClass('is-invalid');
                            $('.invalid-feedback').show();
                            $('.valid-feedback').hide();
                            $('#btnsubmit').prop('disabled', true); // Disable submit button
                        } else {
                            $('#position-name').removeClass('is-invalid').addClass('is-valid');
                            $('.valid-feedback').show();
                            $('.invalid-feedback').hide();
                            $('#btnsubmit').prop('disabled', false); // Enable submit button
                        }
                    },
                    error: function() {
                        alert('Error checking position name.');
                    }
                });
            }, 500); // Delay of 500ms after the user stops typing
        });

        // *** New functionality for update position modal ***

        // Enable #update_btnsubmit when checkboxes are changed
        $('.modal').on('change', 'input[type="checkbox"]', function() {
            let modal = $(this).closest('.modal');
            modal.find('#update_btnsubmit').prop('disabled', false);
        });

        // Form submission event
        $('.modal').on('submit', '#update_access', function(e) {
            e.preventDefault(); // Prevent page reload

            let form = $(this);
            let modal = form.closest('.modal');
            let submitBtn = modal.find('#update_btnsubmit');
            let loadingBtn = modal.find('#updateloading_btn');

            // Show loading button, hide submit button
            submitBtn.hide();
            loadingBtn.show();

            // Send AJAX request
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    // Show success message with SweetAlert2
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Position updated successfully!',
                        timer: 1000, // 1-second delay
                        showConfirmButton: false
                    });

                    // Close modal after 1 second
                    setTimeout(function() {
                        modal.modal('hide');
                        submitBtn.show();
                        loadingBtn.hide();
                        submitBtn.prop('disabled', true); // Disable again after update
                    }, 1000);
                },
                error: function() {
                    // Show error message with SweetAlert2
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to update position!',
                        showConfirmButton: true
                    });

                    // Re-enable submit button on failure
                    submitBtn.show();
                    loadingBtn.hide();
                }
            });
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