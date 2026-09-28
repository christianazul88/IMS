<section class="col-12 ims-revenue-panel" aria-labelledby="rev-panel-title">
    <div class="row g-3 align-items-end ims-revenue-toolbar">
        <div class="col-xl-8">
            <div class="ims-section-eyebrow"><i class="fas fa-chart-line me-1" aria-hidden="true"></i>Revenue performance</div>
            <h2 class="h5 mb-1 fw-bold" id="rev-panel-title">Sales, costs, and net income</h2>
            <p class="text-600 mb-0 small" id="rev_labelDate">Current period</p>
        </div>

        <div class="col-xl-4">
            <label class="form-label small fw-semibold mb-1" for="rev_dateGross">
                <i class="far fa-calendar-alt me-1" aria-hidden="true"></i>Select date range
            </label>
            <input class="form-control datetimepicker" name="rev_dateGross" id="rev_dateGross" type="text" placeholder="dd/mm/yy to dd/mm/yy" data-options='{"mode":"range","dateFormat":"d/m/y","disableMobile":true}' />
        </div>
    </div>

    <!-- Financial KPIs stay together at the top of the section. Charts follow below. -->
    <div class="row g-3 mt-1 ims-kpi-row">
        <div class="col-12 col-md-4">
            <div class="card ims-kpi-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="ims-kpi-label mb-2">Total Sales</p>
                            <p class="ims-kpi-value mb-1" id="rev_totalSales">₱</p>
                        </div>
                        <span class="ims-kpi-icon ims-kpi-icon-primary"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                    </div>
                    <span class="ims-kpi-help">Gross revenue</span>
                    <span id="spinner_totalSales" class="spinner-border spinner-border-sm text-primary ms-1" role="status" style="display: none;">
                        <span class="visually-hidden">Loading total sales</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card ims-kpi-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="ims-kpi-label mb-2">Cost of Goods Sold</p>
                            <p class="ims-kpi-value mb-1" id="rev_goodSold">₱</p>
                        </div>
                        <span class="ims-kpi-icon ims-kpi-icon-warning"><i class="fas fa-boxes" aria-hidden="true"></i></span>
                    </div>
                    <span class="ims-kpi-help">Inventory cost</span>
                    <span id="spinner_goodSold" class="spinner-border spinner-border-sm text-warning ms-1" role="status" style="display: none;">
                        <span class="visually-hidden">Loading cost of goods sold</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card ims-kpi-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <p class="ims-kpi-label mb-2">Net Income</p>
                            <p class="ims-kpi-value mb-1" id="rev_netIncome">₱</p>
                        </div>
                        <span class="ims-kpi-icon ims-kpi-icon-success"><i class="fas fa-wallet" aria-hidden="true"></i></span>
                    </div>
                    <span class="ims-kpi-help">Sales less cost</span>
                    <span id="spinner_netIncome" class="spinner-border spinner-border-sm text-success ms-1" role="status" style="display: none;">
                        <span class="visually-hidden">Loading net income</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3 ims-inventory-context-row">
        <div class="col-12 col-md-6">
            <div class="card ims-context-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between gap-3">
                    <div>
                        <p class="ims-kpi-label mb-2">Remaining Inventory Value</p>
                        <p class="ims-context-value mb-1" id="rev_remainingInventory">₱</p>
                        <span class="ims-kpi-help">Current available inventory</span>
                    </div>
                    <span class="ims-kpi-icon ims-kpi-icon-info"><i class="fas fa-warehouse" aria-hidden="true"></i></span>
                    <span id="spinner_remainingInventory" class="spinner-border spinner-border-sm text-info ms-auto" role="status" style="display: none;">
                        <span class="visually-hidden">Loading remaining inventory value</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card ims-context-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between gap-3">
                    <div>
                        <p class="ims-kpi-label mb-2">Total Inbound Cost</p>
                        <p class="ims-context-value mb-1" id="rev_inboundCost">₱</p>
                        <span class="ims-kpi-help">All items received in selected dates</span>
                    </div>
                    <span class="ims-kpi-icon ims-kpi-icon-info"><i class="fas fa-truck-loading" aria-hidden="true"></i></span>
                    <span id="spinner_inboundCost" class="spinner-border spinner-border-sm text-info ms-auto" role="status" style="display: none;">
                        <span class="visually-hidden">Loading inbound cost</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3 align-items-stretch">
        <div class="col-12">
            <div class="card ims-chart-card h-100">
                <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="h6 mb-1 fw-bold">Revenue snapshot</h3>
                        <p class="text-600 small mb-0">Current selected period</p>
                    </div>
                    <span class="badge rounded-pill bg-200 text-primary">Live</span>
                </div>
                <div class="card-body pt-2">
                    <div id="ims-revenue-chart" class="ims-revenue-chart" role="img" aria-label="Bar chart comparing total sales, cost of goods sold, and net income"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card ims-trend-card">
                <div class="card-header bg-transparent border-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h3 class="h6 mb-1 fw-bold" id="ims-revenue-trend-title">Total Sales &amp; Profit</h3>
                        <p class="text-600 small mb-0">Sales, profit, inbound receipts, and inventory value.</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 ims-trend-controls">
                        <label class="d-flex align-items-center gap-2 mb-0 small text-600" for="ims-revenue-trend-metric">
                            <span class="visually-hidden">Chart metric</span>
                            <select id="ims-revenue-trend-metric" class="form-select form-select-sm ims-trend-select" aria-label="Chart metric">
                                <option value="both" selected>Sales &amp; Profit</option>
                                <option value="sales">Total Sales</option>
                                <option value="profit">Profit</option>
                                <option value="inventory">Inventory Flow</option>
                                <option value="remaining">Remaining Inventory</option>
                                <option value="inbound">Inbound Cost</option>
                            </select>
                        </label>
                        <label class="d-flex align-items-center gap-2 mb-0 small text-600" for="ims-revenue-trend-range">
                            <span class="visually-hidden">Trend window</span>
                            <select id="ims-revenue-trend-range" class="form-select form-select-sm ims-trend-select" aria-label="Revenue trend window">
                                <option value="3">3 months</option>
                                <option value="6" selected>6 months</option>
                                <option value="12">12 months</option>
                            </select>
                        </label>
                        <span class="ims-trend-more" aria-hidden="true">•••</span>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div id="ims-revenue-trend-chart" class="ims-revenue-trend-chart" role="img" aria-label="Line chart showing monthly sales, profit, inbound cost, and remaining inventory value"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    #ims-dashboard .ims-revenue-panel {
        padding: 1.1rem;
        border: 1px solid rgba(44, 123, 229, .12);
        border-radius: 1rem;
        background: linear-gradient(145deg, rgba(255, 255, 255, .98), rgba(248, 250, 253, .92));
    }

    #ims-dashboard .ims-revenue-toolbar {
        padding: .1rem .25rem .75rem;
        border-bottom: 1px solid rgba(39, 55, 72, .08);
    }

    #ims-dashboard .ims-revenue-toolbar .form-control {
        min-height: 2.5rem;
        border-radius: .55rem;
        font-size: .8rem;
    }

    #ims-dashboard .ims-kpi-card {
        border: 1px solid rgba(39, 55, 72, .09);
        background: #fff;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    #ims-dashboard .ims-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .55rem 1.4rem rgba(39, 55, 72, .10);
    }

    #ims-dashboard .ims-kpi-card .card-body {
        padding: 1rem;
    }

    #ims-dashboard .ims-kpi-label {
        color: #748194;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .04em;
        line-height: 1.35;
        text-transform: uppercase;
    }

    #ims-dashboard .ims-kpi-value {
        color: #344050;
        font-family: var(--falcon-font-sans-serif, sans-serif);
        font-size: clamp(1.1rem, 2vw, 1.5rem);
        font-weight: 700;
        letter-spacing: -.03em;
        line-height: 1.15;
        white-space: nowrap;
    }

    #ims-dashboard .ims-kpi-help {
        color: #9da9bb;
        font-size: .72rem;
    }

    #ims-dashboard .ims-kpi-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.15rem;
        height: 2.15rem;
        flex: 0 0 2.15rem;
        border-radius: .65rem;
        font-size: .9rem;
    }

    #ims-dashboard .ims-kpi-icon-primary { background: rgba(44, 123, 229, .12); color: #2c7be5; }
    #ims-dashboard .ims-kpi-icon-warning { background: rgba(245, 139, 31, .14); color: #f58b1f; }
    #ims-dashboard .ims-kpi-icon-success { background: rgba(0, 210, 122, .13); color: #00a86b; }
    #ims-dashboard .ims-kpi-icon-info { background: rgba(39, 176, 232, .13); color: #168aad; }

    #ims-dashboard .ims-context-card {
        border: 1px solid rgba(39, 55, 72, .09);
        background: rgba(255, 255, 255, .82);
    }

    #ims-dashboard .ims-context-card .card-body {
        min-height: 5.65rem;
        padding: .85rem 1rem;
    }

    #ims-dashboard .ims-context-value {
        color: #344050;
        font-size: clamp(1rem, 1.8vw, 1.3rem);
        font-weight: 700;
        letter-spacing: -.02em;
        line-height: 1.15;
        white-space: nowrap;
    }

    #ims-dashboard .ims-chart-card {
        min-height: 100%;
        border: 1px solid rgba(39, 55, 72, .09);
    }

    #ims-dashboard .ims-chart-card .card-header {
        padding: 1rem 1rem 0;
    }

    #ims-dashboard .ims-revenue-chart {
        width: 100%;
        min-height: 12rem;
    }

    #ims-dashboard .ims-trend-card {
        border: 1px solid rgba(39, 55, 72, .09);
        border-radius: .75rem;
        box-shadow: 0 .35rem 1rem rgba(39, 55, 72, .07);
    }

    #ims-dashboard .ims-trend-card .card-header {
        padding: 1.15rem 1.25rem .7rem;
    }

    #ims-dashboard .ims-trend-select {
        min-width: 7.5rem;
        min-height: 2.25rem;
        border: 1px solid #c9d8ea;
        border-radius: .4rem;
        background-color: #fff;
        color: #344050;
        font-size: .75rem;
    }

    #ims-dashboard .ims-trend-select:focus {
        border-color: #2c7be5;
        box-shadow: 0 0 0 .2rem rgba(44, 123, 229, .14);
    }

    #ims-dashboard .ims-trend-more {
        padding: 0 .35rem;
        color: #8a99ad;
        font-size: 1rem;
        letter-spacing: .14em;
        line-height: 1;
    }

    #ims-dashboard .ims-revenue-trend-chart {
        width: 100%;
        min-height: 19rem;
    }

    @media (max-width: 575.98px) {
        #ims-dashboard .ims-trend-controls,
        #ims-dashboard .ims-trend-controls label,
        #ims-dashboard .ims-trend-select {
            width: 100%;
        }

        #ims-dashboard .ims-trend-more {
            display: none;
        }
    }
</style>

<script>
    function rev_numberFormat(num) {
        return num.toLocaleString();
    }

    function rev_currencyFormat(num) {
        return "₱ " + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    let imsRevenueChart = null;
    let imsRevenueTrendChart = null;
    let imsRevenueTrendData = [];

    function rev_renderChart(data) {
        const chartElement = document.getElementById("ims-revenue-chart");
        if (!chartElement || !window.echarts) return;

        if (!imsRevenueChart) {
            imsRevenueChart = window.echarts.init(chartElement);
            window.addEventListener('resize', function () {
                if (imsRevenueChart) imsRevenueChart.resize();
            });
        }

        const sales = Number(data.total_sales || 0);
        const goods = Number(data.good_sold || 0);
        const income = Number(data.net_income || 0);
        imsRevenueChart.setOption({
            animationDuration: 500,
            grid: { top: 12, right: 8, bottom: 24, left: 8, containLabel: true },
            xAxis: {
                type: 'category',
                data: ['Sales', 'COGS', 'Net income'],
                axisTick: { show: false },
                axisLine: { lineStyle: { color: '#d8e0ea' } },
                axisLabel: { color: '#748194', fontSize: 10 }
            },
            yAxis: {
                type: 'value',
                splitLine: { lineStyle: { color: '#edf0f4' } },
                axisLabel: { color: '#9da9bb', fontSize: 9, formatter: function (value) { return value >= 1000 ? (value / 1000) + 'k' : value; } }
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                valueFormatter: function (value) { return rev_currencyFormat(Number(value)); }
            },
            series: [{
                type: 'bar',
                barMaxWidth: 30,
                data: [
                    { value: sales, itemStyle: { color: '#2c7be5', borderRadius: [5, 5, 0, 0] } },
                    { value: goods, itemStyle: { color: '#f58b1f', borderRadius: [5, 5, 0, 0] } },
                    { value: income, itemStyle: { color: '#00a86b', borderRadius: [5, 5, 0, 0] } }
                ]
            }]
        });
    }

    function rev_renderTrendChart(points) {
        const chartElement = document.getElementById("ims-revenue-trend-chart");
        if (!chartElement || !window.echarts) return;

        if (!imsRevenueTrendChart) {
            imsRevenueTrendChart = window.echarts.init(chartElement);
            window.addEventListener('resize', function () {
                if (imsRevenueTrendChart) imsRevenueTrendChart.resize();
            });
        }

        const trendPoints = Array.isArray(points) ? points : [];
        imsRevenueTrendData = trendPoints;
        const metric = document.getElementById("ims-revenue-trend-metric")?.value || "both";
        const showSales = metric === "both" || metric === "sales";
        const showProfit = metric === "both" || metric === "profit";
        const showRemaining = metric === "inventory" || metric === "remaining";
        const showInbound = metric === "inventory" || metric === "inbound";
        const titleMap = {
            sales: "Total Sales",
            profit: "Profit",
            inventory: "Inventory Flow",
            remaining: "Remaining Inventory",
            inbound: "Inbound Cost",
            both: "Total Sales & Profit"
        };
        const title = titleMap[metric] || titleMap.both;
        const titleElement = document.getElementById("ims-revenue-trend-title");
        if (titleElement) titleElement.textContent = title;

        const series = [];
        if (showSales) {
            series.push({
                name: 'Total Sales',
                type: 'line',
                smooth: .18,
                symbol: 'circle',
                symbolSize: 8,
                showSymbol: true,
                lineStyle: { width: 3, color: '#2c7be5' },
                itemStyle: { color: '#fff', borderColor: '#2c7be5', borderWidth: 2 },
                areaStyle: { color: new window.echarts.graphic.LinearGradient(0, 0, 0, 1, [{ offset: 0, color: 'rgba(44,123,229,.22)' }, { offset: 1, color: 'rgba(44,123,229,.015)' }]) },
                data: trendPoints.map(function (point) { return point.sales; })
            });
        }
        if (showProfit) {
            series.push({
                name: 'Profit',
                type: 'line',
                smooth: .18,
                symbol: 'circle',
                symbolSize: 8,
                showSymbol: true,
                lineStyle: { width: 3, color: '#00a86b' },
                itemStyle: { color: '#fff', borderColor: '#00a86b', borderWidth: 2 },
                areaStyle: { color: new window.echarts.graphic.LinearGradient(0, 0, 0, 1, [{ offset: 0, color: 'rgba(0,168,107,.14)' }, { offset: 1, color: 'rgba(0,168,107,.012)' }]) },
                data: trendPoints.map(function (point) { return point.profit; })
            });
        }
        if (showRemaining) {
            series.push({
                name: 'Remaining Inventory',
                type: 'line',
                smooth: .18,
                symbol: 'circle',
                symbolSize: 8,
                showSymbol: true,
                lineStyle: { width: 3, color: '#6f42c1', type: 'dashed' },
                itemStyle: { color: '#fff', borderColor: '#6f42c1', borderWidth: 2 },
                areaStyle: { color: new window.echarts.graphic.LinearGradient(0, 0, 0, 1, [{ offset: 0, color: 'rgba(111,66,193,.14)' }, { offset: 1, color: 'rgba(111,66,193,.012)' }]) },
                data: trendPoints.map(function (point) { return point.remaining_inventory; })
            });
        }
        if (showInbound) {
            series.push({
                name: 'Inbound Cost',
                type: 'line',
                smooth: .18,
                symbol: 'circle',
                symbolSize: 8,
                showSymbol: true,
                lineStyle: { width: 3, color: '#f58b1f' },
                itemStyle: { color: '#fff', borderColor: '#f58b1f', borderWidth: 2 },
                areaStyle: { color: new window.echarts.graphic.LinearGradient(0, 0, 0, 1, [{ offset: 0, color: 'rgba(245,139,31,.13)' }, { offset: 1, color: 'rgba(245,139,31,.012)' }]) },
                data: trendPoints.map(function (point) { return point.inbound_cost; })
            });
        }

        imsRevenueTrendChart.setOption({
            animationDuration: 650,
            grid: { top: 32, right: 18, bottom: 26, left: 18, containLabel: true },
            legend: {
                show: metric === 'both' || metric === 'inventory',
                top: 2,
                left: 0,
                icon: 'roundRect',
                textStyle: { color: '#748194', fontSize: 11 }
            },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: trendPoints.map(function (point) { return point.label; }),
                axisTick: { show: false },
                axisLine: { lineStyle: { color: '#d8e0ea' } },
                axisLabel: { color: '#9aa9bd', fontSize: 10, margin: 12 }
            },
            yAxis: {
                type: 'value',
                splitLine: { lineStyle: { color: '#d7e4f2', type: 'dashed' } },
                axisLabel: { color: '#9aa9bd', fontSize: 9, formatter: function (value) { return value >= 1000 || value <= -1000 ? '₱' + (value / 1000) + 'k' : '₱' + value; } }
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'line' },
                backgroundColor: '#fff',
                borderColor: '#d8e2ef',
                borderWidth: 1,
                padding: [9, 12],
                extraCssText: 'box-shadow: 0 8px 24px rgba(39,55,72,.14); border-radius: 6px;',
                formatter: function (params) {
                    const period = trendPoints[params[0]?.dataIndex]?.period || '';
                    const rows = params.map(function (item) { return item.marker + ' ' + item.seriesName + ': ' + rev_currencyFormat(Number(item.value || 0)); });
                    return [period].concat(rows).join('<br>');
                }
            },
            series: series
        }, { replaceMerge: ['series'] });
    }

    function showSpinners(show = true) {
        const display = show ? "inline-block" : "none";
        document.getElementById("spinner_totalSales").style.display = display;
        document.getElementById("spinner_goodSold").style.display = display;
        document.getElementById("spinner_netIncome").style.display = display;
        document.getElementById("spinner_remainingInventory").style.display = display;
        document.getElementById("spinner_inboundCost").style.display = display;
    }

    window.addEventListener('load', function() {
        const rev_dateInput = document.getElementById("rev_dateGross");
        if (!rev_dateInput) return;

        function rev_fetchData(rev_dateGross = null) {
            // Flatpickr emits a change event after the first click in range
            // mode. Wait until both dates are present before calling the
            // backend, which expects "start to end".
            if (rev_dateGross && !rev_dateGross.includes(' to ')) {
                return;
            }
            showSpinners(true);

            const trendMonths = document.getElementById("ims-revenue-trend-range")?.value || "6";
            let rev_bodyData = rev_dateGross ? "rev_dateGross=" + encodeURIComponent(rev_dateGross) : "";
            rev_bodyData += (rev_bodyData ? "&" : "") + "trend_months=" + encodeURIComponent(trendMonths);

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
                document.getElementById("rev_totalSales").innerText = rev_currencyFormat(Number(rev_data.total_sales || 0));
                document.getElementById("rev_goodSold").innerText = rev_currencyFormat(Number(rev_data.good_sold || 0));
                document.getElementById("rev_netIncome").innerText = rev_currencyFormat(Number(rev_data.net_income || 0));
                document.getElementById("rev_remainingInventory").innerText = rev_currencyFormat(Number(rev_data.remaining_inventory || 0));
                document.getElementById("rev_inboundCost").innerText = rev_currencyFormat(Number(rev_data.inbound_cost || 0));
                rev_renderChart(rev_data);
                rev_renderTrendChart(rev_data.trend);
            })
            .catch(rev_error => {
                console.error("Error fetching data:", rev_error);
                rev_renderChart({ total_sales: 0, good_sold: 0, net_income: 0 });
                document.getElementById("rev_remainingInventory").innerText = rev_currencyFormat(0);
                document.getElementById("rev_inboundCost").innerText = rev_currencyFormat(0);
            })
            .finally(() => {
                showSpinners(false);
            });
        }

        rev_fetchData();

        rev_dateInput.addEventListener("change", function() {
            if (this.value.includes(' to ')) {
                rev_fetchData(this.value);
            }
        });

        document.getElementById("ims-revenue-trend-range")?.addEventListener("change", function() {
            rev_fetchData(rev_dateInput.value.includes(' to ') ? rev_dateInput.value : null);
        });

        document.getElementById("ims-revenue-trend-metric")?.addEventListener("change", function() {
            rev_renderTrendChart(imsRevenueTrendData);
        });
    });
</script>
