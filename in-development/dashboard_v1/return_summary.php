<div class="row mb-3">
  <div class="col-md-6 mb-3 py-3">
    <h6>Returns Summary <span id="module_date_label"></span></h6>
  </div>
  <div class="col-md-6 mb-3">
    <label class="form-label" for="module_date_range">Select Date Range</label>
    <input class="form-control datetimepicker" name="module_date_range" id="module_date_range" type="text" placeholder="dd/mm/yy to dd/mm/yy" data-options='{"mode":"range","dateFormat":"d/m/y","disableMobile":true}' />
  </div>
  <!-- Total Delivery Failed Card -->
  <div class="col-md-6">
    <div class="card font-sans-serif h-100">
      <div class="card-header pb-0 mb-3">
        <h6 class="mb-0">Total Delivery Failed</h6>
      </div>
      <div class="card-body pt-0">
        <div class="row align-items-end h-100 mb-n1">
          <div class="col-6 col-sm-5 pe-md-0 pe-lg-3">
            <div class="row g-0">
              <div class="col-7">
                <h6 class="text-600">Seller:</h6>
              </div>
              <div class="col-5">
                <h6 id="module_seller_fault" class="text-800"></h6>
              </div>
            </div>
            <div class="row g-0">
              <div class="col-7">
                <h6 class="mb-0 text-600">Client:</h6>
              </div>
              <div class="col-5">
                <h6 id="module_client_fault" class="mb-0 text-800"></h6>
              </div>
            </div>
          </div>
          <div class="col-6 col-sm-7 text-end">
            <p id="module_total_delivery_failed" class="font-sans-serif lh-1 mb-1 fs-5" data-countup='{"endValue":"69","suffix":"%"}'>0</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Total Defectives Card -->
  <div class="col-md-6">
    <div class="card font-sans-serif h-100">
      <div class="card-header pb-0 mb-3">
        <h6 class="mb-0">Total Defectives</h6>
      </div>
      <div class="card-body pt-0">
        <div class="row align-items-end h-100 mb-n1">
          <div class="col-6 col-sm-5 pe-md-0 pe-lg-3">
            <div class="row g-0">
              <div class="col-7">
                <h6 class="text-600">Locals:</h6>
              </div>
              <div class="col-5">
                <h6 id="module_local" class="text-800"></h6>
              </div>
            </div>
            <div class="row g-0">
              <div class="col-7">
                <h6 class="mb-0 text-600">Imports:</h6>
              </div>
              <div class="col-5">
                <h6 id="module_import" class="mb-0 text-800"></h6>
              </div>
            </div>
          </div>
          <div class="col-6 col-sm-7 text-end">
            <p id="module_total_defective" class="font-sans-serif lh-1 mb-1 fs-5" data-countup='{"endValue":"69","suffix":"%"}'>0</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const module_dateInput = document.getElementById("module_date_range");

        function module_fetchData(module_dateGross = null) {
            let module_bodyData = module_dateGross ? "module_date_range=" + encodeURIComponent(module_dateGross) : "";

            fetch("return_backend.php?wh=<?php echo $dashboard_wh;?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: module_bodyData
            })
            .then(response => response.json())
            .then(module_data => {
                document.getElementById("module_date_label").innerText = module_data.date_selected;
                document.getElementById("module_seller_fault").innerHTML = `<a href="../Return-logs/?date=${module_data.date_selected}&type=sf&&wh=<?php echo $dashboard_wh;?>">${module_data.seller_fault}</a>`;
                document.getElementById("module_client_fault").innerHTML = `<a href="../Return-logs/?date=${module_data.date_selected}&type=cf&&wh=<?php echo $dashboard_wh;?>">${module_data.client_fault}</a>`;
                document.getElementById("module_total_delivery_failed").innerHTML = `<a href="../Return-logs/?date=${module_data.date_selected}&type=tdf&&wh=<?php echo $dashboard_wh;?>">${module_data.total_delivery_failed}</a>`;
                document.getElementById("module_local").innerHTML = `<a href="../Return-logs/?date=${module_data.date_selected}&type=local&&wh=<?php echo $dashboard_wh;?>">${module_data.local}</a>`;
                document.getElementById("module_import").innerHTML = `<a href="../Return-logs/?date=${module_data.date_selected}&type=import&&wh=<?php echo $dashboard_wh;?>">${module_data.import}</a>`;
                document.getElementById("module_total_defective").innerHTML = `<a href="../Return-logs/?date=${module_data.date_selected}&type=both&wh=<?php echo $dashboard_wh;?>">${module_data.total_defective}</a>`;
            })
            .catch(module_error => {
                console.error("Error fetching data:", module_error);
            });
        }

        // Fetch today's data immediately when page loads
        module_fetchData();

        // Then listen if user selects a date range
        module_dateInput.addEventListener("change", function() {
            module_fetchData(this.value);
        });
    });
</script>
