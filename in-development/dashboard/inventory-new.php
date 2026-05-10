<!-- Summary Card -->
<div class="card mb-3">
  <div class="card-body">
    <form class="row flex-between-center" method="POST" id="inventory-form">

      <!-- Sort and View Options -->
      <div class="col-sm-auto">
        <div class="row gx-2 align-items-center">

          <!-- Sort Form -->
          <div class="col-auto">
            <div class="row gx-2">
              <div class="col-auto">
                <small>Warehouse:</small>
              </div>
              <div class="col-auto">
                <select class="form-select form-select-sm" id="warehouses" name="warehouses" aria-label="Bulk actions">
                  <option selected value="Warehouse 1">Warehouse 1</option>
                  <option value="Warehouse 2">Warehouse 2</option>
                  <option value="Warehouse 3">Warehouse 3</option>
                </select>
              </div>
            </div>
          </div>

        </div>
      </div>
      <!-- Product Count -->
      <div class="col-sm-auto mb-2 mb-sm-0">
        <div class="input-group">
            <input class="form-control form-control-sm shadow-none search" id="search-input" name="search_input" type="search" placeholder="Search..." aria-label="search" />
            <button type="submit" class="input-group-text bg-primary">
                <span class="fa fa-search fs-10"></span>
            </button type="submit" id="submit-btn">
        </div>

      </div>

    </form>
  </div>
</div>

<!-- Product Cards -->
<div class="card">
    <div class="card-body">
        <div class="row">
            <div id="inventory-content"></div>

        </div>
    </div>
    <div class="card-footer bg-body-tertiary d-flex justify-content-center">
        <div>
            <button class="btn btn-falcon-default btn-sm me-2" type="button" disabled="disabled" data-bs-toggle="tooltip" data-bs-placement="top" title="Prev">
                <span class="fas fa-chevron-left"></span>
            </button><a class="btn btn-sm btn-falcon-default text-primary me-2" href="#!">1</a>
            <a class="btn btn-sm btn-falcon-default me-2" href="#!">2</a>
            <a class="btn btn-sm btn-falcon-default me-2" href="#!"> 
                <span class="fas fa-ellipsis-h"></span>
            </a>
            <a class="btn btn-sm btn-falcon-default me-2" href="#!">35</a>
            <button class="btn btn-falcon-default btn-sm" type="button" data-bs-toggle="tooltip" data-bs-placement="top" title="Next">
                <span class="fas fa-chevron-right">     </span>
            </button>
        </div>
    </div>
</div>


<script>
  document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("inventory-form");
    const warehouseSelect = document.getElementById("warehouses");
    const searchInput = document.getElementById("search-input");
    const inventoryContent = document.getElementById("inventory-content");

    // Load initial content
    loadInventory();

    // On form submit
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      loadInventory();
    });

    // Function to fetch and display inventory
    function loadInventory() {
      const warehouse = warehouseSelect.value;
      const search = encodeURIComponent(searchInput.value.trim());

      const url = `sample.php?warehouse=${warehouse}&search=${search}`;

      // Optional: Add loading state
      inventoryContent.innerHTML = '<p>Loading...</p>';

      fetch(url)
        .then(response => {
          if (!response.ok) throw new Error("Failed to load inventory");
          return response.text();
        })
        .then(data => {
          inventoryContent.innerHTML = data;
        })
        .catch(error => {
          inventoryContent.innerHTML = `<p class="text-danger">Error: ${error.message}</p>`;
        });
    }
  });
</script>
