<div class="col-8">
    <h6 class="mb-0 mt-2 d-flex align-items-center" id="rev_labelDate">${date}</h6>
</div>

<div class="col-4">
    <label class="form-label" for="rev_dateGross">Select Date Range</label>
    <input class="form-control datetimepicker" name="rev_dateGross" id="rev_dateGross" type="text" placeholder="dd/mm/yy to dd/mm/yy" data-options='{"mode":"range","dateFormat":"d/m/y","disableMobile":true}' />
</div>

<div class="col-4">
    <div class="card h-md-100 ecommerce-card-min-width">
        <div class="card-header pb-0">
            <h6 class="mb-0 mt-2 d-flex align-items-center">Total Sales</h6>
        </div>
        <div class="card-body d-flex flex-column justify-content-end">
            <div class="row">
                <div class="col">
                    <!-- Total Sales -->
                    <p class="font-sans-serif lh-1 mb-1 fs-7">
                        <span id="rev_totalSales">₱</span>
                        <span id="spinner_totalSales" class="spinner-border spinner-border-sm ms-2" role="status" style="display: none;">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                    </p>    
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-4">
    <div class="card h-md-100 ecommerce-card-min-width">
        <div class="card-header pb-0">
            <h6 class="mb-0 mt-2 d-flex align-items-center">Cost of Good Sold</h6>
        </div>
        <div class="card-body d-flex flex-column justify-content-end">
            <div class="row">
                <div class="col">
                    <!-- Cost of Goods Sold -->
                    <p class="font-sans-serif lh-1 mb-1 fs-7">
                        <span id="rev_goodSold">₱</span>
                        <span id="spinner_goodSold" class="spinner-border spinner-border-sm ms-2" role="status" style="display: none;">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-4">
    <div class="card h-md-100 ecommerce-card-min-width">
        <div class="card-header pb-0">
            <h6 class="mb-0 mt-2 d-flex align-items-center">Net Income</h6>
        </div>
        <div class="card-body d-flex flex-column justify-content-end">
            <div class="row">
                <div class="col">
                    <!-- Net Income -->
                    <p class="font-sans-serif lh-1 mb-1 fs-7">
                        <span id="rev_netIncome">₱</span>
                        <span id="spinner_netIncome" class="spinner-border spinner-border-sm ms-2" role="status" style="display: none;">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    function rev_numberFormat(num) {
        return num.toLocaleString();
    }

    function rev_currencyFormat(num) {
        return "₱ " + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function showSpinners(show = true) {
        const display = show ? "inline-block" : "none";
        document.getElementById("spinner_totalSales").style.display = display;
        document.getElementById("spinner_goodSold").style.display = display;
        document.getElementById("spinner_netIncome").style.display = display;
    }

    window.onload = function() {
        const rev_dateInput = document.getElementById("rev_dateGross");

        function rev_fetchData(rev_dateGross = null) {
            showSpinners(true); // Show spinners

            let rev_bodyData = rev_dateGross ? "rev_dateGross=" + encodeURIComponent(rev_dateGross) : "";

            fetch("revenue_backend.php?wh=<?php echo $dashboard_wh;?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: rev_bodyData
            })
            .then(response => response.json())
            .then(rev_data => {
                document.getElementById("rev_labelDate").innerText = rev_data.date_selected || "No date";
                document.getElementById("rev_totalSales").innerText = rev_currencyFormat(rev_data.total_sales || 0);
                document.getElementById("rev_goodSold").innerText = rev_currencyFormat(rev_data.good_sold || 0);
                document.getElementById("rev_netIncome").innerText = rev_currencyFormat(rev_data.net_income || 0);
            })
            .catch(rev_error => {
                console.error("Error fetching data:", rev_error);
            })
            .finally(() => {
                showSpinners(false); // Hide spinners after fetch
            });
        }

        rev_fetchData(); // Initial load

        rev_dateInput.addEventListener("change", function() {
            rev_fetchData(this.value); // Reload on date change
        });
    };
</script>
