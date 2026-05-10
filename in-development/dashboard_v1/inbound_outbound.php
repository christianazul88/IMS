<div class="card py-3 mb-3">
  <div class="card-body py-3">
    <div class="row">
        <div class="col-8 col-md-6 mb-3">
            <label class="form-label" for="timepicker2">Select Date Range</label>
            <input class="form-control datetimepicker" name="date_between" id="timepicker2" type="text" placeholder="dd/mm/yy to dd/mm/yy" data-options='{"mode":"range","dateFormat":"d/m/y","disableMobile":false}' />
        </div>
        <div class="col-6 mb-3">
          <h6 id="dateLabel"><span class="spinner-border spinner-border-sm text-primary" role="status"></span></h6>
        </div>
    </div>

    <div class="row g-0">

      <!-- Orders -->
      <div class="col-6 col-md-6 border-200 border-bottom border-end pb-4">
        <h6 class="pb-1 text-700">Outbound</h6>
        <p id="outboundQty" class="font-sans-serif lh-1 mb-1 fs-7">
          <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
        </p>
        <div class="d-flex align-items-center">
          <h6 class="fs-10 text-500 mb-0">qty</h6>
        </div>
      </div>

      <!-- Items Sold -->
      <div class="col-6 col-md-6 border-200 border-bottom border-end-md pb-4 ps-3">
        <h6 class="pb-1 text-700">Inbound</h6>
        <p id="inboundQty" class="font-sans-serif lh-1 mb-1 fs-7">
          <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
        </p>
        <div class="d-flex align-items-center">
          <h6 class="fs-10 text-500 mb-3">qty</h6>
        </div>
        <div class="d-flex align-items-center">
          <h6 class="fs-11 text-600 mb-0">Local:</h6>
          <h6 id="local_qty" class="fs-11 text-600 mb-0">
            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
          </h6>
        </div>
        <div class="d-flex align-items-center">
          <h6 class="fs-11 text-600 mb-0">Import:</h6>
          <h6 id="import_qty" class="fs-11 text-600 mb-0">
            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
          </h6>
        </div>
        
      </div>

      <!-- Gross Sale -->
      <div class="col-6 col-md-6 border-200 border-bottom border-bottom-md-0 border-end-md pt-4 pb-md-0 ps-3 ps-md-0">
        <h6 class="pb-1 text-700">Outbound ₱</h6>
        <p id="outboundSales" class="font-sans-serif lh-1 mb-1 fs-7">
          <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
        </p>
      </div>

      <!-- Shipping -->
      <div class="col-6 col-md-6 border-200 border-bottom-md-0 border-end pt-4 pb-md-0 ps-md-3">
        <h6 class="pb-1 text-700">Inbound ₱</h6>
        <p id="inboundCost" class="font-sans-serif lh-1 mb-1 fs-7">
          <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
        </p>
        <div class="d-flex align-items-center">
          <h6 class="fs-11 text-600 mb-0">Local:</h6>
          <h6 id="local_cost" class="fs-11 text-600 mb-0">
            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
          </h6>
        </div>
        <div class="d-flex align-items-center">
          <h6 class="fs-11 text-600 mb-0">Import:</h6>
          <h6 id="import_cost" class="fs-11 text-600 mb-0">
            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
          </h6>
        </div>
      </div>

    </div>
  </div>
</div>



<script>
function numberFormat(num) {
    return num.toLocaleString();
}
function currencyFormat(num) {
    return "₱ " + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.addEventListener("DOMContentLoaded", function() {
    const dateInput = document.getElementById("timepicker2");

    function fetchData(date_between = null) {
        let bodyData = date_between ? "date_between=" + encodeURIComponent(date_between) : "";

        fetch("test.php?wh=<?php echo $dashboard_wh;?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: bodyData
        })
        .then(response => response.json())
        .then(data => {
            setTimeout(() => {
                document.getElementById("dateLabel").innerHTML =
                    data.date;

                document.getElementById("outboundQty").innerHTML =
                    `<a href="../Outbound-logs/?date_range=${data.date}&type=outboundQty&wh=<?php echo $dashboard_wh;?>">${numberFormat(data.outbound_qty)}</a>`;

                document.getElementById("inboundQty").innerHTML =
                    `<a href="../Inbound-logs/?date_range=${data.date}&type=inboundQty&wh=<?php echo $dashboard_wh;?>">${numberFormat(data.inbound_qty)}</a>`;

                document.getElementById("outboundSales").innerHTML =
                    `<a href="../Outbound-logs_costing/?date_range=${data.date}&type=outboundSales&wh=<?php echo $dashboard_wh;?>">${currencyFormat(data.outbound_sales)}</a>`;

                document.getElementById("inboundCost").innerHTML =
                    `<a href="../Inbound-logs_costing/?date_range=${data.date}&type=inboundCost&wh=<?php echo $dashboard_wh;?>">${currencyFormat(data.inbound_cost)}</a>`;

                document.getElementById("local_qty").innerHTML =
                    `<a href="../Inbound-logs/?date_range=${data.date}&type=localQty&wh=<?php echo $dashboard_wh;?>">${numberFormat(data.local_inbound_qty)}</a>`;

                document.getElementById("import_qty").innerHTML =
                    `<a href="../Inbound-logs/?date_range=${data.date}&type=importQty&wh=<?php echo $dashboard_wh;?>">${numberFormat(data.import_inbound_qty)}</a>`;

                document.getElementById("local_cost").innerHTML =
                    `<a href="../Inbound-logs_costing/?date_range=${data.date}&type=localCost&wh=<?php echo $dashboard_wh;?>">${currencyFormat(data.local_inbound_amount)}</a>`;

                document.getElementById("import_cost").innerHTML =
                    `<a href="../Inbound-logs_costing/?date_range=${data.date}&type=importCost&wh=<?php echo $dashboard_wh;?>">${currencyFormat(data.import_inbound_amount)}</a>`;
            }, 2000); // ⏱ wait 2 seconds
        })
        .catch(error => {
            console.error("Error fetching data:", error);
        });
    }


    // 🔥 Fetch today's data immediately when page loads
    fetchData();

    // 🔥 Then listen if user selects a date range
    dateInput.addEventListener("change", function() {
        fetchData(this.value);
    });
});

</script>
