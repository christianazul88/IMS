<div class="card h-100">
  <div class="card-header bg-body-tertiary py-2 d-flex flex-between-center">
    <h6 class="mb-0">Top Brands</h6>
    <div class="d-flex">
      <a class="btn btn-link btn-sm me-2" href="#!">View Details</a>
      <div class="dropdown font-sans-serif btn-reveal-trigger">
        <button 
          class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal" 
          type="button" 
          id="dropdown-top-products" 
          data-bs-toggle="dropdown" 
          data-boundary="viewport" 
          aria-haspopup="true" 
          aria-expanded="false"
        >
          <span class="fas fa-ellipsis-h fs-11"></span>
        </button>
        <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-top-products">
          <a class="dropdown-item" href="#!">View</a>
          <a class="dropdown-item" href="#!">Export</a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item text-danger" href="#!">Remove</a>
        </div>
      </div>
    </div>
  </div>
  
  <div class="card-body d-flex h-100 flex-column justify-content-end">
    <!-- Find the JS file for the following chart at: src/js/charts/echarts/top-products.js -->
    <!-- If you are not using a gulp-based workflow, you can find the transpiled code at: public/assets/js/theme.js -->
    <div class="echart-bar-top-products echart-bar-top-products-ecommerce" data-echart-responsive="true"></div>
  </div>
</div>
