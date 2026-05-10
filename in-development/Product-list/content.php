<div class="row" id="tableExample4" data-list='{"valueNames":["desc","cat","brand","barcode","by", "date"]}'>
  <div class="col-lg-12">
    <h4>Product list</h4>
  </div>

  <div class="col-lg-12 text-end px-3">
    <button class="btn btn-primary py-0 me-auto" type="button" data-bs-toggle="modal" data-bs-target="#create-modal">Create</button>
  </div>

  <div class="col-lg-12">
    <div class="card">
      <div class="card-body overflow-hidden">
        <div class="table-responsive">
          <table class="table mb-0 data-table fs-11" data-datatables="data-datatables">
            <thead class="bg-200">
              <tr>
                <th style="width: 10px;"></th>
                <th data-sort="desc"><small>Description</small></th>
                <th data-sort="cat"><small>Category</small></th>
                <th data-sort="brand"><small>Brand</small></th>
                <th data-sort="brand"><small>Product ID</small></th>
                <th data-sort="barcode"><small>Parent Barcode</small></th>
                <th data-sort="by"><small>Created by</small></th>
                <th data-sort="date"><small>Date</small></th>
                <th>Safety</th>
                <th style="width:30px;"></th>
              </tr>
            </thead>

            <tbody class="list" id="table-purchase-body">
              <?php 
              $product_list_query = "
                SELECT product.*, users.user_fname, users.user_lname, category.category_name, brand.brand_name
                FROM product 
                LEFT JOIN users ON users.hashed_id = product.user_id 
                LEFT JOIN category ON category.hashed_id = product.category
                LEFT JOIN brand ON brand.hashed_id = product.brand
                WHERE product.current_status = 0
                ORDER BY product.id DESC
              ";
              $product_list_res = $conn->query($product_list_query);
              if($product_list_res->num_rows > 0) {
                while($row = $product_list_res->fetch_assoc()) {
                  $product_id = $row['id'];
                  $product_pbarcode = $row['parent_barcode'];
                  $unique_id = $row['unique_id'];

                  // Check if unique_id is invalid
                  // if (empty($unique_id) || $unique_id == '0') {
                  //     // Generate a consistent unique_id using hash
                  //     $unique_id = substr(sha1($product_pbarcode), 0, 12); // shorten as needed

                  //     // Update the product record in the database
                  //     $updateQuery = "UPDATE product SET unique_id = ? WHERE id = ?";
                  //     $stmt = $conn->prepare($updateQuery);
                  //     $stmt->bind_param("si", $unique_id, $product_id);
                  //     $stmt->execute();
                  //     $stmt->close();
                  // }

                  if (empty($row['product_img']) || !isset($row['product_img'])) {
                      $product_img = '../../assets/img/def_img.png';
                  } else {
                      $imageArray = @unserialize($row['product_img']); // or json_decode(..., true)

                      if (is_array($imageArray) && count($imageArray) > 0) {
                          $firstImageBinary = base64_decode($imageArray[0]);

                          // Detect MIME type
                          $finfo = new finfo(FILEINFO_MIME_TYPE);
                          $mimeType = $finfo->buffer($firstImageBinary);

                          // Encode for output
                          $product_img = 'data:' . $mimeType . ';base64,' . $imageArray[0];
                      } else {
                          $product_img = '../../assets/img/def_img.png';
                      }
                  }


                  
                  $product_category = $row['category_name'];
                  $product_brand = $row['brand_name'];
                  $product_des = $row['description'];
                  $product_date = $row['date'];
                  $product_publisher = $row['user_fname'] . " " . $row['user_lname'];
                  $table_safety = $row['safety'];
              ?>
                <tr>
                  <td class="p-0 m-0">
                    <img class="img img-fluid m-0" src="<?php echo $product_img; ?>" alt="" style="heigh:10px;">
                  </td>
                  <td class="desc"><small><?php echo $product_des; ?></small></td>
                  <td class="cat"><small><?php echo $product_category; ?></small></td>
                  <td class="brand"><small><?php echo $product_brand; ?></small></td>
                  <td class="barcode"><code><?php echo $unique_id; ?></code></small></td>
                  <td class="barcode"><small><?php echo $product_pbarcode; ?></small></td>
                  <td class="by"><small><?php echo $product_publisher; ?></small></td>
                  <td class="date"><small><?php echo $product_date; ?></small></td>
                  <td class="safety text-end pe-4"><small><?php echo $table_safety;?></small></td>
                  <td class="d-flex align-items-center">
                    <button class="btn btn-transparent py-0 fs-11" type="button" target-id="<?php echo $product_id;?>" data-bs-toggle="modal" data-bs-target="#edit-modal" data-bs-toggle="tooltip" data-bs-placement="left" title="Edit product information">
                      <span class="far fa-edit m-0 p-0"></span>
                    </button>
                    <a href="../config/delete.php?from=product_list&id=<?php echo $product_id;?>" class="btn btn-transparent text-danger ms-1 custom-clicked fs-11" ><span class="far fa-trash-alt"></span></a>
                  </td>
                </tr>
              <?php 
                }
              } else {
              ?>
                <tr>
                  <td colspan="8" class="py-6 px-6 text-center cat brand"><h2>No Data</h2></td>
                </tr>
              <?php
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="create-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <form action="../config/add-product.php" method="POST" enctype="multipart/form-data">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content position-relative">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
          <h4 class="mb-1" id="modalExampleDemoLabel">Add a new product</h4>
        </div>
        <div class="row bg-body-tertiary">
          <div class="col-lg-7 mb-3">
            <label for="">Product Description</label>
            <input type="text" class="form-control" name="product_description" required>
          </div>
          <div class="col-lg-5 mb-3">
            <label for="">Upload Product Image</label>
            <input type="file" class="form-control" name="product_image[]" id="product_image" accept="image/*" multiple>
          </div>
          <div class="col-lg-3 mb-3">
            <label for="">Category</label>
            <select class="form-select" name="category" id="" required>
              <option value="">Select Category</option>
              <?php 
              $category_selection = "SELECT * FROM category ORDER BY category_name ASC";
              $category_result = $conn->query($category_selection);
              if($category_result->num_rows > 0) {
                while($row = $category_result->fetch_assoc()) {
                  echo '<option value="' . $row['hashed_id'] . '">' . $row['category_name'] . '</option>';
                }
              } else {
                echo '<option value="">No category found</option>';
              }
              ?>
            </select>
          </div>
          <div class="col-lg-3 mb-3">
            <label for="">Brand</label>
            <select class="form-select" name="brand" id="" required>
              <?php 
              $brand_selection = "SELECT * FROM brand ORDER BY brand_name ASC";
              $brand_result = $conn->query($brand_selection);
              if($brand_result->num_rows > 0) {
                while($row = $brand_result->fetch_assoc()) {
                  echo '<option value="' . $row['hashed_id'] . '">' . $row['brand_name'] . '</option>';
                }
              } else {
                echo '<option value="">No brand found</option>';
              }
              ?>
            </select>
          </div>
          <div class="col-lg-3 mb-3">
            <label for="">Parent Barcode</label>
            <input 
              type="text" 
              class="form-control" 
              id="parent_barcode"
              name="parent_barcode" 
              placeholder="enter barcode" 
              oninput="this.value = this.value.replace(/\s/g, '')"
            >
            <small id="barcode-message" style="color:red;display:none;">
              This barcode already exists!
            </small>
          </div>
          <div class="col-lg-3 mb-3">
            <label for="">Safety</label>
            <input type="number" min="2" max="1000" name="safety" class="form-control" required>
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


<div class="modal fade" id="edit-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document" >
    <form id="editProductForm" action="../config/edit-product.php" method="POST"  enctype="multipart/form-data">
      <div class="modal-content position-relative">
        <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
          <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0">
          <div id="edit-content"></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
          <button id="submitBtn" class="btn btn-primary" type="submit">Submit </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  $(document).ready(function () {
    $('#editProductForm').submit(function (e) {
      e.preventDefault(); // Prevent form from submitting the traditional way

      // Disable the submit button and show "Loading..."
      var submitBtn = $('#submitBtn');
      submitBtn.prop('disabled', true).text('Loading...');

      // Perform the AJAX request
      $.ajax({
        url: $(this).attr('action'), // The URL from the form's action attribute
        type: 'POST',
        data: new FormData(this),
        processData: false, // Important: prevents jQuery from trying to convert the FormData
        contentType: false, // Important: prevents jQuery from setting the content type
        success: function (response) {
          var jsonResponse = JSON.parse(response); // Parse the JSON response

          if (jsonResponse.status === 'success') {
            // Show a SweetAlert2 success message
            Swal.fire({
              title: 'Update Successful!',
              text: jsonResponse.message,
              icon: 'success',
              confirmButtonText: 'Close'
            }).then((result) => {
              if (result.isConfirmed) {
                // Reload the page after the alert is closed
                location.reload();
              }
            });
          } else {
            // Handle failure (e.g., show an error message if response is not 'success')
            Swal.fire({
              title: 'Error!',
              text: jsonResponse.message,
              icon: 'error',
              confirmButtonText: 'Close'
            }).then(() => {
              // Re-enable the submit button
              submitBtn.prop('disabled', false).text('Submit');
            });
          }
        },
        error: function () {
          // If AJAX request fails
          Swal.fire({
            title: 'Error!',
            text: 'There was an issue with the request. Please try again.',
            icon: 'error',
            confirmButtonText: 'Close'
          }).then(() => {
            submitBtn.prop('disabled', false).text('Submit');
          });
        }
      });
    });
  });

</script>
<script>
  $(document).on('click', 'button[data-bs-target="#edit-modal"]', function () {
    const targetId = $(this).attr('target-id');
    const editContentDiv = $('#edit-content');
    
    editContentDiv.html('<p>Loading...</p>');

    $.get(`edit-content.php?product_id=${targetId}`, function (data) {
      editContentDiv.html(data);
    }).fail(function () {
      editContentDiv.html('<p>Error loading content.</p>');
    });
  });
</script>

<?php
// Check if $_GET['update'] is set and get the value, otherwise leave it empty
$update_id = isset($_GET['update']) ? $_GET['update'] : null;
?>

<script>
// Wait for the document to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if the update id exists (i.e., $_GET['update'] was set)
    var updateId = <?php echo json_encode($update_id); ?>;

    // Only proceed if updateId is set and not null
    if (updateId) {
        // Find the button with the matching target-id
        var button = document.querySelector('button[target-id="' + updateId + '"]');
        
        // If the button exists, click it
        if (button) {
            button.click();
        }
    }
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

<script>
document.getElementById('parent_barcode').addEventListener('input', function () {
    const barcode = this.value.trim();
    const msg = document.getElementById('barcode-message');

    if (barcode.length === 0) {
        msg.style.display = 'none';
        return;
    }

    fetch('check_barcode.php?barcode=' + encodeURIComponent(barcode))
        .then(res => res.json())
        .then(data => {
            if (data.exists) {
                msg.textContent = "This barcode already exists!";
                msg.style.color = "red";
                msg.style.display = "inline";
            } else {
                msg.textContent = "Barcode is available.";
                msg.style.color = "green";
                msg.style.display = "inline";
            }
        })
        .catch(err => {
            console.error(err);
        });
});
</script>