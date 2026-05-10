<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Classification for Categories</h5>
        <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#newclassification">
            <span class="fas fa-plus me-1"></span> New
        </button>
    </div>

    <div class="card-body">
        <div id="tableExample3" data-list='{"valueNames":["num","name","date","staff"],"page":10,"pagination":true}'>
            
            <!-- Search -->
            <div class="mb-3">
                <input class="form-control form-control-sm search" placeholder="Search classification...">
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th data-sort="name">Classification</th>
                            <th data-sort="date">Date Added</th>
                            <th data-sort="staff">Added by</th>
                        </tr>
                    </thead>
                    <tbody class="list">
                        <?php
                        $number = 1;
                        $classification_query = "SELECT cl.*, u.user_fname, u.user_lname 
                                                 FROM classification cl 
                                                 LEFT JOIN users u ON u.hashed_id = cl.user_id 
                                                 ORDER BY cl.date_added ASC";
                        $classification_res = $conn->query($classification_query);

                        if($classification_res->num_rows > 0){
                            while($row = $classification_res->fetch_assoc()){
                        ?>
                        <tr>
                            <td class="num"><?php echo $number++; ?></td>
                            <td class="name fw-semibold"><?php echo $row['classification_name']; ?></td>
                            <td class="date">
                                <?php echo date("M d, Y", strtotime($row['date_added'])); ?>
                            </td>
                            <td class="staff text-muted">
                                <?php echo $row['user_fname'] . " " . $row['user_lname']; ?>
                            </td>
                        </tr>
                        <?php } } ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-end mt-3">
                <ul class="pagination pagination-sm mb-0"></ul>
            </div>
        </div>
    </div>
</div>



<div class="modal fade" id="newclassification" data-bs-backdrop="static" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow">
      
      <form action="../config/add-classification.php" method="POST">

        <!-- Header -->
        <div class="modal-header">
          <h5 class="modal-title">Add New Classification</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <div class="row g-3">

            <!-- Left -->
            <div class="col-lg-8">
                <label class="form-label">Classification Name</label>
                <input type="text" name="classification_name" class="form-control" required>
            </div>

            <!-- Right -->
            <div class="col-lg-4">
                <label class="form-label">Categories</label>

                <!-- Search -->
                <input type="text" class="form-control form-control-sm mb-2" id="categorySearch" placeholder="Search category...">

                <!-- Buttons -->
                <div class="d-flex gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="selectAll()">Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="unselectAll()">Clear</button>
                </div>

                <!-- Category List -->
                <div class="border rounded p-2" style="height:250px; overflow-y:auto;">
                    <div id="categoryList">
                        <?php 
                        $category_query = "SELECT * FROM category WHERE classification_id IS NULL";
                        $category_res = $conn->query($category_query);

                        if($category_res && $category_res->num_rows > 0){
                            while($row = $category_res->fetch_assoc()){
                                $id = $row['hashed_id'];
                                $name = $row['category_name'];
                                $checkbox_id = "cat_" . $id;
                        ?>
                        <div class="form-check category-item">
                            <input class="form-check-input category-checkbox" 
                                   id="<?php echo $checkbox_id; ?>" 
                                   type="checkbox" 
                                   value="<?php echo $id; ?>" 
                                   name="categories[]">

                            <label class="form-check-label" for="<?php echo $checkbox_id; ?>">
                                <?php echo $name; ?>
                            </label>
                        </div>
                        <?php 
                            }
                        } else {
                            echo '<small class="text-muted">No categories available</small>';
                        }
                        ?>
                    </div>
                </div>
            </div>

          </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Classification</button>
        </div>

      </form>
    </div>
  </div>
</div>


<script>
// Search filter
document.getElementById('categorySearch').addEventListener('keyup', function() {
    let value = this.value.toLowerCase();
    document.querySelectorAll('#categoryList .category-item').forEach(function(item) {
        item.style.display = item.innerText.toLowerCase().includes(value) ? '' : 'none';
    });
});

// Select all
function selectAll() {
    document.querySelectorAll('.category-checkbox').forEach(cb => cb.checked = true);
}

// Unselect all
function unselectAll() {
    document.querySelectorAll('.category-checkbox').forEach(cb => cb.checked = false);
}
</script>