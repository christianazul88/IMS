<div class="row py-6 px-1">
    <div class="col-lg-12 mb-3">
        <h4>User Employee Accounts</h4>
    </div>
    
    <div class="col-lg-12">
        <div id="tableExample3" data-list='{"valueNames":["name","positio","status","warehouse","email","bday","address"],"page":5,"pagination":true}'>

            <!-- Search form -->
            <div class="row justify-content-end g-0">
                <div class="col-auto col-sm-4 mb-3 text-start">
                    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#error-modal">
                        <span class="fas fa-plus"></span> New
                    </button>
                </div>
                <div class="col-auto col-sm-3 mb-3"><select class="form-select form-select-sm mb-3" data-list-filter="warehouse">
                <option selected="" value="">Select Warehouse</option>
                <?php echo implode("\n", $warehouse_options); ?>
                <!-- <option value="Pending">Pending</option>
                <option value="Success">Received</option>
                <option value="Blocked">Sent to Supplier</option> -->
                </select></div>
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
                            <th style="max-width: 50px;"></th>
                            <th class="text-1000 sort" data-sort="name">Name</th>
                            <th class="text-900 sort" data-sort="position">Position</th>
                            <th class="text-900 sort" data-sort="status">Status</th>
                            <th class="text-1000 sort" data-sort="warehouse" style="min-width: 250px;">Warehouse Access</th>
                            <th class="text-900 sort" data-sort="email">Email</th>
                            <th class="text-900 sort" data-sort="bday">Birth Date</th>
                            <th class="text-900 sort" data-sort="address" hidden>Address</th>
                            <th class="text-900 sort" style="min-width: 200px;"></th>
                        </tr>
                    </thead>
                    <tbody class="list bg-light">
                        <?php
                        $user_Query = "SELECT u.*, up.position_name
                                        FROM users u
                                        LEFT JOIN user_position up ON up.hashed_id = u.user_position
                                        ORDER BY u.id DESC";
                        $user_res = $conn->query($user_Query);
                        if($user_res->num_rows>0){
                            while($row = $user_res ->fetch_Assoc()){
                                $employee_fullname = $row['user_fname'] . " " . $row['user_lname'];
                                $employee_pfp = $row['pfp'];
                                $employee_pos = $row['position_name'];
                                $employee_email = $row['email'];
                                $employee_bday = date("F j, Y", strtotime($row['birth_date']));
                                $employee_address = $row['address'];
                                if($row['status'] == 0){
                                    $employee_Account_status = '<span class="badge bg-danger"><span class="fas fa-user-alt-slash"></span> Disabled</span>';
                                    
                                } else {
                                    $employee_Account_status = '<span class="badge bg-success"><span class="fas fa-user-check"></span> Activated</span>';
                                }
                                // Explode the warehouse_access values into an array
                                $warehouse_ids = explode(',', $row['warehouse_access']);

                                // Initialize an array to store warehouse names
                                $warehouse_names = [];

                                // Loop through each warehouse ID and query the warehouse table
                                foreach ($warehouse_ids as $id) {
                                    // Prepare the SQL statement
                                    $sql = "SELECT warehouse_name FROM warehouse WHERE hashed_id = ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("s", $id);
                                    $stmt->execute();
                                    $stmt->bind_result($warehouse_name);

                                    // Fetch the result and store the warehouse name
                                    if ($stmt->fetch()) {
                                        $warehouse_names[] = '<span class="badge badge-subtle-secondary">' . $warehouse_name . '</span>';
                                    }

                                    // Close the statement after each loop iteration
                                    $stmt->close();
                                }
                                
                        ?>
                            <tr>
                                <td class="p-2"><img class="img img-fluid img-rounded-circle" src="../../assets/img/def_pfp.png" width="30" alt="pfp"></td>
                                <td class="name"><b><small><b><?php echo $employee_fullname;?></b></small></b></td>
                                <td class="position"><small><?php echo $employee_pos;?></small></td>
                                <td class="status"><?php echo $employee_Account_status;?></td>
                                <td class="warehouse"><?php echo implode(' ', $warehouse_names); ?></td>
                                <td class="email"><small><?php echo $employee_email;?></small></td>
                                <td class="bday"><small><?php echo $employee_bday;?></small></td>
                                <td class="address" hidden><small><?php echo $employee_address;?></small></td>
                                <td class="py-1 px-2 py-1">
                                    <?php 
                                    if($row['status'] == 0){
                                    ?>
                                    <a href="../config/employee-set-status.php?user=<?php echo $row['hashed_id']; ?>&activate=true" 
                                    class="btn-activate btn btn-transparent py-0 mx-0" 
                                    data-hashed-id="<?php echo $row['hashed_id']; ?>"
                                    title="activate">
                                        <small><span class="fas fa-user-check"></span></small>
                                    </a>
                                    <?php 
                                    } else {
                                    ?>
                                    <a href="../config/employee-set-status.php?user=<?php echo $row['hashed_id']; ?>&activate=false" 
                                    class="btn-disable btn btn-transparent py-0 mx-0" 
                                    data-hashed-id="<?php echo $row['hashed_id']; ?>"
                                    title="disable">
                                        <small><span class="fas fa-user-alt-slash"></span></small>
                                    </a>

                                    <?php 
                                    }
                                    ?>
                                    <button class="btn btn-transparent py-0 mx-0" title="update access" type="button" data-bs-toggle="modal" data-bs-target="#edit-modal" target-id="<?php echo $row['hashed_id']; ?>"><small><span class="fas fa-edit"></span></small></button> <!-- update button -->
                                    <!-- Form HTML -->
                                    <button class="btn btn-transparent py-0 mx-0 reset-pwd" target-id="<?php echo $row['hashed_id'];?>"><small><span class="fas fa-circle-notch"></span></small></button>
                                </td>
                            </tr>
                        <?php 
                            }
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

<!-- Add employee Modal -->
<div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <form action="../config/add-employee.php" id="myForm" method="POST">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content position-relative">
                <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
                        <h4 class="mb-1" id="modalExampleDemoLabel">New employee form</h4>
                    </div>
                    <div class="row p-4 pb-0">
                        <div class="col-lg-4 mb-3">
                            <label for="">First Name</label>
                            <input type="text" name="fname" class="form-control">
                        </div>
                        <div class="col-lg-4 pb-4 mb-3">
                            <label for="">Middle Name</label>
                            <input type="text" name="mname" class="form-control">
                        </div>
                        <div class="col-lg-4 pb-4 mb-3">
                            <label for="">Last Name</label>
                            <input type="text" name="lname" class="form-control">
                        </div>
                        
                        <div class="col-lg-4 mb-3">
                            <label for="">E-mail</label>
                            <input type="email" name="email" id="user-email" class="form-control">
                            <div class="valid-feedback">Looks good!</div>
                            <div class="invalid-feedback">Email already exist</div>
                        </div>
                        <div class="col-lg-3 mb-3">
                            <label class="form-label" for="datepicker">Birth Date</label>
                            <input class="form-control datetimepicker" name="bday" id="datepicker" type="text" placeholder="dd/mm/yy" data-options='{"disableMobile":true}' />
                        </div>
                        <div class="col-lg-5 mb-3">
                            <label for="">Position</label>
                            <select class="form-select js-choice" id="organizerSingle" size="1" name="organizerSingle" data-options='{"removeItemButton":true,"placeholder":true}' name="employee_pos" id="employee_pos">
                                <option value="">Select Position...</option>
                                <?php 
                                $position_query = "SELECT hashed_id, position_name FROM user_position ORDER BY position_name ASC";
                                $position_result = mysqli_query($conn, $position_query);
                                if($position_result->num_rows>0){
                                    while($position_row = $position_result->fetch_assoc()){
                                        echo "<option value='".$position_row['hashed_id']."'>".$position_row['position_name']."</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <hr>
                        <div class="col-lg-12 mb-3">
                            <label for="">Warehouse Access</label>
                            <div class="row">
                                <?php 
                                $warehouse_query = "SELECT hashed_id, warehouse_name FROM warehouse ORDER by warehouse_name ASC";
                                $warehouse_result = $conn->query($warehouse_query);
                                if($warehouse_result->num_rows>0){
                                    while($row = $warehouse_result -> fetch_Assoc()){
                                ?>
                                <div class="col-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" name="warehouses[]" id="flexSwitchCheckDefault" type="checkbox" value="<?php echo $row['hashed_id'];?>"/>
                                        <label class="form-check-label" for="flexSwitchCheckDefault"><?php echo $row['warehouse_name'];?></label>
                                    </div>
                                </div>
                                <?php 
                                    }
                                }
                                ?>
                            </div>
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
        <form id="update_form" action="../config/update_employee.php" method="POST">
        <div class="modal-content position-relative">
                <input type="text" name="user_id" value="<?php echo $modal_id;?>" hidden>
                <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                    <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
                    <h4 class="mb-1" id="modalExampleDemoLabel">Update Employee Information </h4>
                    </div>
                    <div class="p-4 pb-0">
                    <form>
                        <div id="form-content" class="mb-3">
                        
                        </div>
                    </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary" type="submit">Submit</button>
                </div>
            
        </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Debounce function to delay the AJAX call until the user stops typing
        let debounceTimer;
        $('#user-email').on('input', function() {
            const userEmail = $(this).val();

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                $.ajax({
                    url: '../config/check-employee.php',
                    type: 'POST',
                    data: { 'email': userEmail },
                    dataType: 'json',
                    success: function(response) {
                        if (response.exists) {
                            $('#user-email').removeClass('is-valid').addClass('is-invalid');
                            $('.invalid-feedback').show();
                            $('.valid-feedback').hide();
                            $('#btnsubmit').prop('disabled', true);
                        } else {
                            $('#user-email').removeClass('is-invalid').addClass('is-valid');
                            $('.valid-feedback').show();
                            $('.invalid-feedback').hide();
                            $('#btnsubmit').prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Error checking email.');
                    }
                });
            }, 500);
        });
    });

    // When the edit button is clicked
    $(document).on("click", "[data-bs-target='#edit-modal']", function() {
        var targetId = $(this).attr("target-id"); // Get the target-id attribute
        if (targetId) {
            // Load the form-content.php inside #form-content
            $("#form-content").load("form-content.php?id=" + targetId);
        }
    });

    document.getElementById('update_form').addEventListener('submit', function (event) {
        event.preventDefault(); // Prevent the default form submission

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to submit the changes?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, submit it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Collect form data
                const formData = new FormData(this);

                // Send data using fetch
                fetch(this.action, {
                    method: this.method,
                    body: formData
                })
                .then(response => response.json()) // Assuming the server responds with JSON
                .then(data => {
                    console.log('Server Response:', data); // Log server response to the console

                    // Show success message
                    Swal.fire(
                        'Submitted!',
                        'The changes have been successfully submitted.',
                        'success'
                    );
                    setTimeout(function() {
                        location.reload();
                    }, 1000); // 1000 milliseconds = 1 second

                })
                .catch(error => {
                    console.error('Error:', error);

                    // Show error message
                    Swal.fire(
                        'Error!',
                        'There was an error submitting the form. Please try again.',
                        'error'
                    );
                });
            }
        });
    });

    // Handle activation
    document.querySelectorAll('.btn-activate').forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const hashedId = this.getAttribute('data-hashed-id');
            Swal.fire({
                title: "Are you sure to activate this user?",
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: "Save",
                denyButtonText: `Don't save`
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../config/employee-set-status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `user=${encodeURIComponent(hashedId)}&activate=true`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: "Good job!",
                                text: "User activated successfully!",
                                icon: "success"
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            throw new Error(data.error || "Failed to activate user");
                        }
                    })
                    .catch(error => {
                        Swal.fire({ title: "Error!", text: error.message, icon: "error" });
                    });
                } else if (result.isDenied) {
                    Swal.fire("Changes are not saved", "", "info");
                }
            });
        });
    });

    // Handle disabling
    document.querySelectorAll('.btn-disable').forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const hashedId = this.getAttribute('data-hashed-id');
            Swal.fire({
                title: "Are you sure to disable this user?",
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: "Disable",
                denyButtonText: `Don't disable`
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../config/employee-set-status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `user=${encodeURIComponent(hashedId)}&activate=false`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: "Done!",
                                text: "User disabled successfully!",
                                icon: "success"
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            throw new Error(data.error || "Failed to disable user");
                        }
                    })
                    .catch(error => {
                        Swal.fire({ title: "Error!", text: error.message, icon: "error" });
                    });
                } else if (result.isDenied) {
                    Swal.fire("Changes are not saved", "", "info");
                }
            });
        });
    });

    // Handle password reset
    document.querySelectorAll('.reset-pwd').forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('target-id');

            Swal.fire({
                title: 'Reset Password?',
                text: 'Are you sure you want to reset the user password?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, reset it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`../config/resetuserpassword.php?target_id=${targetId}`)
                        .then(response => response.json())
                        .then(data => {
                            Swal.fire({
                                title: data.success ? 'Success' : 'Error',
                                text: data.message,
                                icon: data.success ? 'success' : 'error',
                                confirmButtonText: 'OK'
                            });
                        })
                        .catch(error => {
                            Swal.fire({
                                title: 'Error',
                                text: 'Something went wrong. Please try again later.',
                                icon: 'error'
                            });
                            console.error('Error:', error);
                        });
                }
            });
        });
    });

</script>
