<div class="accordion" id="fast-moving-product-container">
  <div class="accordion-item">
    <h2 class="accordion-header" id="heading4">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fastmovingproducts" aria-expanded="true" aria-controls="fastmovingproducts">
        Fast Moving Products
      </button>
    </h2>
    <div class="accordion-collapse collapse" id="fastmovingproducts" aria-labelledby="heading4" data-bs-parent="#accordionExample">
      <div class="accordion-body">
<div class="card h-lg-100 overflow-hidden">
  <div class="card-body p-0">
    <div id="dashboard-wh-preview">
        <!-- Preview content will load here -->
        <div id="dashboard-wh-preview-spinner" class="spinner-border spinner-border-sm text-primary" role="status" style="display: none;">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div id="dashboard-wh-preview"></div>
  </div>

  <!-- Card Footer -->
  <div class="card-footer bg-body-tertiary py-2">
    <div class="row flex-between-center">
      <div class="col-auto">
        <select class="form-select form-select-sm" id="dashboard-wh">
          <?php echo implode("\n", $warehouse_options2); ?>
        </select>
      </div>
    </div>
  </div>
</div>
      </div>
    </div>
  </div>
</div>


