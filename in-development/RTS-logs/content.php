<div class="card">
    <div class="card-body overflow-hidden">
        <div class="row align-items-center">
            <div class="col-lg-12">
              <div id="tableExample3" data-list='{"valueNames":["name","email","age"],"page":5,"pagination":true}'>
                <div class="row justify-content-end g-0">
                    <div class="col-auto col-sm-5 mb-3">
                    <form>
                        <div class="input-group"><input class="form-control form-control-sm shadow-none search" type="search" placeholder="Search..." aria-label="search" />
                        <div class="input-group-text bg-transparent"><span class="fa fa-search fs-10 text-600"></span></div>
                        </div>
                    </form>
                    </div>
                </div>
                <div class="table-responsive scrollbar">
                    <table class="table table-bordered table-striped fs-10 mb-0">
                    <thead class="bg-200">
                        <tr>
                        <th class="text-900">#</th>
                        <th class="text-900 sort" data-sort="name">Supplier Name</th>
                        <th class="text-900 sort" data-sort="status">Fulfillment Status</th>
                        <th class="text-900 sort" data-sort="email">From Warehouse</th>
                        <th class="text-900 sort" data-sort="date">Date Processed</th>
                        <th class="text-900 sort" data-sort="date_returned">Date Returned</th>
                        <th class="text-900 sort" data-sort="age">Staff</th>
                        </tr>
                    </thead>
                    <tbody class="list">
                        <?php 
                        // Quote each ID in the array
                        $quoted_warehouse_ids = array_map(function ($id) {
                            return "'" . trim($id) . "'";
                        }, $user_warehouse_ids);
                
                        // Create a comma-separated string of quoted IDs
                        $imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);
                        $sql = "SELECT s.supplier_name, w.warehouse_name, r.date, u.user_fname, u.user_lname, r.id, r.status, r.returned_date
                                FROM rts_logs r
                                LEFT JOIN supplier s ON s.hashed_id = r.supplier
                                LEFT JOIN warehouse w ON w.hashed_id = r.warehouse
                                LEFT JOIN users u ON u.hashed_id = r.user_id
                                WHERE r.warehouse IN ($imploded_warehouse_ids)
                                ORDER BY r.date DESC
                                ";
                        $rezult = $conn->query($sql);
                        if($rezult->num_rows>0){
                            $number = 0;
                            while($row=$rezult->fetch_assoc()){
                                $supplier_name = $row['supplier_name'];
                                $rts_from = $row['warehouse_name'];
                                $rts_date = $row['date'];
                                $rts_staff = $row['user_fname'] . " " . $row['user_lname'];
                                $returned_date = $row['returned_date'];
                                if($row['status'] == 0){
                                    $rts_status = '<span class="badge rounded-pill badge-subtle-warning">Pending</span>';
                                } else {
                                    $rts_status = '<span class="badge rounded-pill badge-subtle-success">Returned</span>';
                                }
                                $number ++;
                        ?>
                        <tr>
                        <td><a type="button" data-bs-toggle="modal" data-bs-target="#view-modal" target-id="<?php echo $row['id']; ?>"><?php echo $number;?></a></td>
                        <td class="name"><?php echo $supplier_name;?></td>
                        <td class="status"><?php echo $rts_status;?></td>
                        <td class="email"><?php echo $rts_from;?></td>
                        <td class="date"><?php echo $rts_date;?></td>
                        <td class="date"><?php echo $returned_date ;?></td>
                        <td class="age"><?php echo $rts_staff;?></td>
                        </tr>
                        <?php 
                            }
                        } else {
                        ?>
                        <tr>
                            <td colspan="7" class="p-6 text-center fs-1"><b>No Data yet!</b></td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3"><button class="btn btn-sm btn-falcon-default me-1" type="button" title="Previous" data-list-pagination="prev"><span class="fas fa-chevron-left"></span></button>
                    <ul class="pagination mb-0"></ul><button class="btn btn-sm btn-falcon-default ms-1" type="button" title="Next" data-list-pagination="next"><span class="fas fa-chevron-right"> </span></button>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="view-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="min-height: 500px;">
        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
        </div>
        <div id="preview"></div> 
        
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function(){
    // Handle modal show event to load preview.php without reloading the page
    $('#view-modal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); 
        var targetId = button.attr('target-id'); 
        if (targetId) {
            $('#preview').html('<p>Loading...</p>'); 
            loadPreview(targetId); // Load content dynamically
        }
    });

    // Function to load preview content dynamically
    function loadPreview(targetId) {
    $.ajax({
        url: 'preview.php',
        type: 'GET',
        data: { id: targetId },
        success: function(response) {
        $('#preview').html(response);
        initSwiper(); // ✅ Re-initialize Swiper after new content is loaded
        },
        error: function() {
        $('#preview').html('<p class="text-danger">Error loading content.</p>');
        }
    });
    }




    // Handle replace and refund button clicks with SweetAlert2 confirmation
    $(document).on("click", "#replace-btn, #refund-btn", function(e){
        e.preventDefault(); // Prevent default link navigation
        var url = $(this).attr("href"); // Get the link URL
        var action = $(this).attr("id") === "replace-btn" ? "replace this item" : "refund this item"; // Identify action
        var targetId = new URLSearchParams(window.location.search).get('id'); // Get current target ID from URL

        Swal.fire({
            title: "Are you sure?",
            text: "Do you really want to " + action + "? This action cannot be undone.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, proceed!"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url, // Send AJAX request to process the action
                    type: "GET",
                    success: function(response){
                        Swal.fire({
                            title: "Success!",
                            text: "The action has been completed.",
                            icon: "success",
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // Hide both the clicked anchor tag and its sibling anchor tag
                        $(e.target).closest('td').find('a').hide(); // Hide both anchors in the same <td>
                    },
                    error: function(){
                        Swal.fire("Error!", "There was a problem processing your request.", "error");
                    }
                });
            }
        });
    });
});


let swiperInstance;

function initSwiper() {
  if (swiperInstance) {
    swiperInstance.destroy(true, true); // Clean up
  }

  swiperInstance = new Swiper('.theme-slider', {
    spaceBetween: 5,
    loop: true,
    zoom: true,
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev"
    }
  });
}


</script>