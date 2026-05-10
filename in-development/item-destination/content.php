<div class="row">
    <div class="col-lg-12">
        <h4>Item Destination</h4>
    </div>
    
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body overflow-hidden">

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <?php 
                    // Initialize a flag to handle the first item differently
                    $first = true;

                    // Loop through each ID and display differently for the first item
                    foreach ($user_warehouse_ids as $id) {
                        // Trim any extra whitespace
                        $id = trim($id);
                        $warehouse_info_query = "SELECT * FROM warehouse WHERE hashed_id = '$id'";
                        $warehouse_info_result = mysqli_query($conn, $warehouse_info_query);
                        if($warehouse_info_result->num_rows>0){
                            // Check if it's the first item
                            $row=$warehouse_info_result->fetch_assoc();
                            $tab_warehouse_name = $row['warehouse_name'];
                            if ($first) {
                                // echo "this is first data $id<br>";
                                echo '  <li class="nav-item">
                                            <a class="nav-link active" id="tab-' . $id . '" data-bs-toggle="tab" href="#tab-wh' . $id . '" role="tab" aria-controls="tab-' . $id . '" aria-selected="true">' . $tab_warehouse_name . '</a>
                                        </li>';
                                $first = false; // Set the flag to false after first item is processed
                            } else {
                                // echo "other data $id<br>";
                                echo '
                                        <li class="nav-item">
                                            <a class="nav-link" id="tab-' . $id . '" data-bs-toggle="tab" href="#tab-wh' . $id . '" role="tab" aria-controls="tab-' . $id . '" aria-selected="false">' . $tab_warehouse_name . '</a>
                                        </li>
                                ';
                            }
                        } else {
                            //handle no warehouse access
                        }
                        
                        
                    }
                    ?>
                </ul>

                <!-- Tabs Content -->
                <div class="tab-content border border-top-0 p-3" id="myTabContent">
                    <?php 
                    // Initialize a flag to handle the first item differently
                    $_first = true;

                    // Loop through each ID and display differently for the first item
                    foreach ($user_warehouse_ids as $_id) {
                        // Trim any extra whitespace
                        $_id = trim($_id);
                        if($_first){
                            ?>
                            <div class="tab-pane fade show active" id="tab-wh<?php echo $_id?>" role="tabpanel" aria-labelledby="tab-<?php echo $_id?>">
                                <!-- Search and Add New Button -->
                                <div id="table<?php echo $_id;?>" data-list='{"valueNames":["name<?php echo $_id;?>","email<?php echo $_id;?>","age<?php echo $_id;?>"],"page":5,"pagination":true}'>
                                    <div class="row justify-content-end g-0">
                                        <div class="col-auto col-sm-7 mb-3 text-start">
                                            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#warehouse_modal<?php echo $_id;?>">
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

                                    <!-- Table Content -->
                                    <div class="table-responsive scrollbar">
                                        <table class="table table-bordered table-striped fs-10 mb-0">
                                            <thead class="bg-200">
                                                <tr>
                                                    <th class="text-900 sort" data-sort="name<?php echo $_id;?>">Item Destination</th>
                                                    <th class="text-900 sort" data-sort="email<?php echo $_id;?>">Published Date</th>
                                                    <th class="text-900 sort" data-sort="age<?php echo $_id;?>">Publish By</th>
                                                    <th class="text-900">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="list">
                                                <?php 
                                                    $item_location_query = "SELECT item_location.*, users.user_fname, users.user_lname FROM item_location LEFT JOIN users ON users.id = item_location.user_id WHERE item_location.warehouse = '$_id' AND item_location.current_status = 0 ORDER BY item_location.id DESC";
                                                    $item_location_result = mysqli_query($conn, $item_location_query);
                                                    if ($item_location_result->num_rows > 0) {
                                                        while ($row = $item_location_result->fetch_assoc()) {
                                                            $item_location_name = $row['location_name'];
                                                            $publish_Date = $row['date'];
                                                            $by = $row['user_fname'] . " " . $row['user_lname'];
                                                ?>
                                                <tr>
                                                    <td class="name<?php echo $_id;?>"><?php echo htmlspecialchars($item_location_name); ?></td>
                                                    <td class="email<?php echo $_id;?>"><?php echo htmlspecialchars($publish_Date); ?></td>
                                                    <td class="age<?php echo $_id;?>"><?php echo htmlspecialchars($by); ?></td>
                                                    <td class="d-flex align-items-center"> 
                                                        <button class="btn btn-transparent" type="button" data-bs-toggle="modal" data-bs-target="#edit-modal" target-id="<?php echo $row['id']; ?>"><span class="far fa-edit"></span></button>
                                                        <a href="../config/delete.php?from=item-destination&id=<?php echo $row['id'];?>" class="btn btn-transparent text-danger ms-1 custom-clicked" ><span class="far fa-trash-alt"></span></a>
                                                    </td>
                                                </tr>
                                                <?php 
                                                        }
                                                    }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination Controls -->
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
                            <?php
                            $_first=false;
                        } else {
                            ?>
                            <div class="tab-pane fade" id="tab-wh<?php echo $_id?>" role="tabpanel" aria-labelledby="tab-<?php echo $_id?>">
                                <!-- Search and Add New Button -->
                                <div id="table<?php echo $_id;?>" data-list='{"valueNames":["name<?php echo $_id;?>","email<?php echo $_id;?>","age<?php echo $_id;?>"],"page":5,"pagination":true}'>
                                    <div class="row justify-content-end g-0">
                                        <div class="col-auto col-sm-7 mb-3 text-start">
                                            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#warehouse_modal<?php echo $_id;?>">
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

                                    <!-- Table Content -->
                                    <div class="table-responsive scrollbar">
                                        <table class="table table-bordered table-striped fs-10 mb-0">
                                            <thead class="bg-200">
                                                <tr>
                                                    <th class="text-900 sort" data-sort="name<?php echo $_id;?>">Item Destination</th>
                                                    <th class="text-900 sort" data-sort="email<?php echo $_id;?>">Published Date</th>
                                                    <th class="text-900 sort" data-sort="age<?php echo $_id;?>">Publish By</th>
                                                    <th class="text-900">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="list">
                                                <?php 
                                                    $item_location_query = "SELECT item_location.*, users.user_fname, users.user_lname FROM item_location LEFT JOIN users ON users.hashed_id = item_location.user_id WHERE item_location.warehouse = '$_id' ORDER BY item_location.id DESC";
                                                    $item_location_result = mysqli_query($conn, $item_location_query);
                                                    if ($item_location_result->num_rows > 0) {
                                                        while ($row = $item_location_result->fetch_assoc()) {
                                                            $item_location_name = $row['location_name'];
                                                            $publish_Date = $row['date'];
                                                            $by = $row['user_fname'] . " " . $row['user_lname'];
                                                ?>
                                                <tr>
                                                    <td class="name<?php echo $_id;?>"><?php echo htmlspecialchars($item_location_name); ?></td>
                                                    <td class="email<?php echo $_id;?>"><?php echo htmlspecialchars($publish_Date); ?></td>
                                                    <td class="age<?php echo $_id;?>"><?php echo htmlspecialchars($by); ?></td>
                                                    <td class="d-flex align-items-center">
                                                    <button class="btn btn-transparent" type="button" data-bs-toggle="modal" data-bs-target="#edit-modal" target-id="<?php echo $row['id']; ?>"><span class="far fa-edit"></span></button>
                                                    <a href="../config/delete.php?from=item-destination&id=<?php echo $row['id'];?>" class="btn btn-transparent text-danger ms-1 custom-clicked" ><span class="far fa-trash-alt"></span></a>
                                                    </td>
                                                </tr>
                                                <?php 
                                                        }
                                                    }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination Controls -->
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
                            <?php
                        }
                    }
                    ?>

                </div>

                
            </div>
        </div>
    </div>
</div>

<?php 
// Initialize a flag to handle the first item differently
$m_first = true;

// Loop through each ID and display differently for the first item
foreach ($user_warehouse_ids as $m_id) {
    // Trim any extra whitespace
    $m_id = trim($m_id);
    $m_warehouse_info_query = "SELECT * FROM warehouse WHERE hashed_id = '$m_id'";
    $m_warehouse_info_result = mysqli_query($conn, $m_warehouse_info_query);
    if($warehouse_info_result->num_rows>0){
        // Check if it's the first item
        $row=$m_warehouse_info_result->fetch_assoc();
        $m_tab_warehouse_name = $row['warehouse_name'];
    }
    ?>
    <!-- Modal for Adding New Item Location -->
    <div class="modal fade" id="warehouse_modal<?php echo $m_id;?>" tabindex="-1" role="dialog" aria-hidden="true">
        <form action="../config/add-item_location.php" method="POST">
            <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
                <div class="modal-content position-relative">
                    <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
                            <h4 class="mb-1" id="modalExampleDemoLabel">Add a New Item Location to <?php echo $m_tab_warehouse_name;?></h4>
                        </div>
                        <div class="p-4 pb-0">
                            <div class="mb-3">
                                <label class="col-form-label" for="item_location-name">Item Location Name:</label>
                                <input class="form-control" id="item_location-name" name="item_location_name" type="text" />
                                <input type="text" name="warehouse" value="<?php echo $m_id;?>" readonly hidden>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-primary" type="submit">Submit</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php
}
?>

<div class="modal fade" id="edit-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="update-form" action="../config/update-admin.php?type=location" method="POST">
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
                text: "Do you want to update this location?",
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
                        text: "Please wait while updating the location.",
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
                                text: "location has been successfully updated.",
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
        // When the edit button is clicked
        $(document).on("click", "[data-bs-target='#edit-modal']", function() {
            var targetId = $(this).attr("target-id"); // Get the target-id attribute
            if (targetId) {
                // Load the form-content.php inside #form-content
                $("#form-content").load("form-content.php?id=" + targetId);
            }
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