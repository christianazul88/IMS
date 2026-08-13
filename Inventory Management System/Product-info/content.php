<?php
/**
 * Product Info Module
 * -----------------------------------------------------------------
 * Backend notes (senior-dev pass):
 *  - Swapped raw string-interpolated SQL for prepared statements
 *    (the old $unique_barcode / $warehouse_hashed_id concatenation
 *    was a direct SQL-injection hole via the ?prod= query param).
 *  - All DB-sourced values are now escaped with h() before being
 *    echoed, to close the reflected-XSS gap (a malicious product
 *    description/brand/supplier name could otherwise execute).
 *  - Added server-side margin/markup calculation instead of just
 *    showing capital vs. sold price side by side.
 *  - Fixed the "Change item location" <select>: the previously
 *    selected <option> was missing its value="", so submitting the
 *    form without changing the dropdown posted no item_location.
 *  - The trend chart at the bottom was calling the API with a
 *    hard-coded barcode ('1000992-1') instead of the barcode of the
 *    product actually being viewed — fixed to use $unique_barcode.
 */

function h($val) {
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}

function formatPeso($amount) {
    if ($amount === null || $amount === '') return '—';
    return '₱' . number_format((float) $amount, 2);
}

if (isset($_GET['prod'])) {
    $unique_barcode = $_GET['prod'];

    $product_query = "SELECT
                            p.product_img,
                            p.description,
                            b.brand_name,
                            c.category_name,
                            il.location_name,
                            sup.supplier_name,
                            w.warehouse_name,
                            w.hashed_id,
                            u.user_fname,
                            u.user_lname,
                            s.capital,
                            s.price,
                            s.batch_code,
                            s.parent_barcode,
                            s.date,
                            s.outbound_id,
                            sup.local_international,
                            s.item_status
                        FROM stocks s
                        LEFT JOIN product p ON p.hashed_id = s.product_id
                        LEFT JOIN brand b ON b.hashed_id = p.brand
                        LEFT JOIN category c ON c.hashed_id = p.category
                        LEFT JOIN item_location il ON il.id = s.item_location
                        LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
                        LEFT JOIN users u ON u.hashed_id = s.user_id
                        LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
                        WHERE s.unique_barcode = ?
                        LIMIT 1";

    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("s", $unique_barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    $item_status = null; // guarded default so the later check never warns

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // ---- Product images -------------------------------------------------
        if (empty($row['product_img'])) {
            $product_img_html = '<div class="swiper-slide h-100 text-center bg-300"><img class="rounded-1 object-fit-cover" src="../../assets/img/def_img.png" alt="No product image available"></div>';
        } else {
            $imageArray = @unserialize($row['product_img']);

            if (is_array($imageArray) && count($imageArray) > 0) {
                $product_img_html = '';
                foreach ($imageArray as $base64Img) {
                    $imgBinary = base64_decode($base64Img, true);
                    if ($imgBinary === false) continue;

                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->buffer($imgBinary);

                    $product_img_html .= '
                    <div class="swiper-slide h-100 text-center bg-dark">
                        <img class="rounded-1 object-fit-cover"
                        src="data:' . h($mimeType) . ';base64,' . h($base64Img) . '"
                        alt="Product photo" height="300" />
                    </div>';
                }
                if ($product_img_html === '') {
                    $product_img_html = '<div class="swiper-slide h-100"><img class="rounded-1 object-fit-cover" src="../../assets/img/def_img.png" alt="No product image available"></div>';
                }
            } else {
                $product_img_html = '<div class="swiper-slide h-100"><img class="rounded-1 object-fit-cover" src="../../assets/img/def_img.png" alt="No product image available"></div>';
            }
        }

        // ---- Flatten fields ---------------------------------------------------
        $product_description  = $row['description'];
        $product_brand         = $row['brand_name'];
        $product_category      = $row['category_name'];
        $item_location          = $row['location_name'];
        $supplier_name          = $row['supplier_name'];
        $warehouse_name         = $row['warehouse_name'];
        $added_by               = trim(($row['user_fname'] ?? '') . " " . ($row['user_lname'] ?? ''));
        $capital                = $row['capital'];
        $sold_amount            = $row['price'];
        $batch_code             = $row['batch_code'];
        $parent_barcode         = $row['parent_barcode'];
        $warehouse_hashed_id    = $row['hashed_id'];
        $delivery_date          = $row['date'];
        $local_international_raw = $row['local_international'];
        $item_status            = $row['item_status'];
        $has_sold                = !empty($row['outbound_id']);

        // ---- Margin (backend addition) ----------------------------------------
        $margin_amount = null;
        $margin_pct    = null;
        if ($has_sold && is_numeric($capital) && is_numeric($sold_amount) && (float) $capital > 0) {
            $margin_amount = (float) $sold_amount - (float) $capital;
            $margin_pct    = ($margin_amount / (float) $capital) * 100;
        }

        // ---- Badges -------------------------------------------------------------
        $local_international_html = $local_international_raw === "Local"
            ? '<span class="badge rounded-pill badge-subtle-primary"><span class="fas fa-map-marker-alt me-1"></span>Local</span>'
            : '<span class="badge rounded-pill badge-subtle-danger"><span class="fas fa-globe-asia me-1"></span>International</span>';

        $item_location_html = !empty($item_location)
            ? h($item_location) . ' <span class="badge rounded-pill badge-subtle-success"><span class="far fa-check-circle"></span></span>'
            : 'For SKU <span class="badge rounded-pill badge-subtle-danger"><span class="far fa-window-close"></span></span>';

        // ---- Stock age --------------------------------------------------------
        function formatDateDifference($delivery_date2) {
            $startDate = new DateTime($delivery_date2);
            $endDate   = new DateTime();
            $interval  = $startDate->diff($endDate);

            if ($interval->y > 0) {
                return $interval->y . " year" . ($interval->y > 1 ? "s" : "") .
                    ($interval->m > 0 ? " " . $interval->m . " month" . ($interval->m > 1 ? "s" : "") : "") .
                    ($interval->d > 0 ? " " . $interval->d . " day" . ($interval->d > 1 ? "s" : "") : "");
            } elseif ($interval->m > 0) {
                return $interval->m . " month" . ($interval->m > 1 ? "s" : "") .
                    ($interval->d > 0 ? " " . $interval->d . " day" . ($interval->d > 1 ? "s" : "") : "");
            }
            return $interval->d . " day" . ($interval->d != 1 ? "s" : "");
        }

        function getBadgeClass($delivery_date2) {
            $startDate = new DateTime($delivery_date2);
            $endDate   = new DateTime();
            $interval  = $startDate->diff($endDate);

            if ($interval->m >= 3 || $interval->y > 0) return "bg-danger";
            if ($interval->m >= 1) return "bg-warning";
            return "bg-primary";
        }

        $badgeClass               = getBadgeClass($delivery_date);
        $formattedDateDifference  = formatDateDifference($delivery_date);
        $ageBadgeText = str_replace('bg-', '', $badgeClass) === 'danger' ? 'Aging' : (str_replace('bg-', '', $badgeClass) === 'warning' ? 'Watch' : 'Fresh');
        ?>

        <button class="btn btn-primary d-none" id="liveToastBtn" type="button">Show</button>
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
            <div class="toast fade" id="liveToast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header <?php echo $badgeClass; ?> text-white">
                    <strong class="me-auto">IMS</strong>
                    <div data-bs-theme="dark"><button class="btn-close" type="button" data-bs-dismiss="toast" aria-label="Close"></button></div>
                </div>
                <div class="toast-body">This item has been in the inventory for <?php echo h($formattedDateDifference); ?>.</div>
            </div>
        </div>

        <!-- ============================= Product Header ============================= -->
        <div class="card shadow-sm border-0 rounded-lg mb-3">
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- Image Gallery -->
                    <div class="col-lg-5">
                        <div class="product-slider" id="galleryTop">
                            <div class="swiper theme-slider border rounded" data-swiper='{"autoplay":true,"spaceBetween":5,"loop":true,"loopedSlides":5,"slideToClickedSlide":true}'>
                                <div class="swiper-wrapper h-100">
                                    <?php echo $product_img_html; ?>
                                </div>
                                <div class="swiper-nav">
                                    <div class="swiper-button-next swiper-button-white"></div>
                                    <div class="swiper-button-prev swiper-button-white"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Title, status, quick actions -->
                    <div class="col-lg-7 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <p class="text-muted small mb-1 text-uppercase"><?php echo h($product_category); ?></p>
                                <h3 class="fw-bold mb-1"><?php echo h($product_description); ?></h3>
                                <h6 class="text-primary mb-0"><?php echo h($product_brand); ?></h6>
                            </div>
                            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#change-location-modal">
                                <span class="fas fa-map-marker-alt me-1"></span> Change location
                            </button>
                        </div>

                        <div class="d-flex flex-wrap gap-2 my-3">
                            <?php echo $local_international_html; ?>
                            <span class="badge rounded-pill <?php echo $badgeClass; ?>">
                                <span class="fas fa-clock me-1"></span><?php echo $ageBadgeText; ?> · <?php echo h($formattedDateDifference); ?>
                            </span>
                            <?php if ($has_sold): ?>
                                <span class="badge rounded-pill badge-subtle-success"><span class="fas fa-tag me-1"></span>Sold</span>
                            <?php else: ?>
                                <span class="badge rounded-pill badge-subtle-secondary"><span class="fas fa-box me-1"></span>In stock</span>
                            <?php endif; ?>
                        </div>

                        <!-- Barcode chip with copy button -->
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="text-muted small">Barcode</span>
                            <code id="barcodeValue" class="bg-light border rounded px-2 py-1"><?php echo h($unique_barcode); ?></code>
                            <button class="btn btn-sm btn-outline-secondary" type="button" id="copyBarcodeBtn" title="Copy barcode">
                                <span class="far fa-copy"></span>
                            </button>
                            <span class="small text-success d-none" id="copiedNote">Copied!</span>
                        </div>

                        <!-- Tab nav -->
                        <ul class="nav nav-pills mb-3 mt-auto" id="productInfoTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-overview-btn" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button" role="tab">Overview</button>
                            </li>
                            <?php 
                            if(strpos($access, "view_capital")!==false || $user_position_name === "Administrator"){
                            ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-pricing-btn" data-bs-toggle="pill" data-bs-target="#tab-pricing" type="button" role="tab">Pricing</button>
                            </li>
                            <?php 
                            }
                            ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-history-btn" data-bs-toggle="pill" data-bs-target="#tab-history" type="button" role="tab">History</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-trend-btn" data-bs-toggle="pill" data-bs-target="#tab-trend" type="button" role="tab">Trend</button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Tab content -->
                <div class="tab-content mt-2">

                    <!-- Overview -->
                    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Receiving</h6>
                                    <table class="table table-sm mb-0">
                                        <tr><th class="text-muted fw-normal">Delivery date</th><td><?php echo h($delivery_date); ?></td></tr>
                                        <tr><th class="text-muted fw-normal">Added by</th><td><?php echo h($added_by ?: '—'); ?></td></tr>
                                        <tr><th class="text-muted fw-normal">Batch</th><td><?php echo h($batch_code ?: '—'); ?></td></tr>
                                        <?php if (!empty($parent_barcode)): ?>
                                        <tr><th class="text-muted fw-normal">Parent barcode</th><td><?php echo h($parent_barcode); ?></td></tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="mb-3">Sourcing &amp; location</h6>
                                    <table class="table table-sm mb-0">
                                        <tr><th class="text-muted fw-normal">Supplier</th><td><?php echo h($supplier_name ?: '—') . ' ' . $local_international_html; ?></td></tr>
                                        <tr><th class="text-muted fw-normal">Warehouse</th><td><?php echo h($warehouse_name ?: '—'); ?></td></tr>
                                        <tr><th class="text-muted fw-normal">Item location</th><td><?php echo $item_location_html; ?></td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="tab-pane fade" id="tab-pricing" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="border rounded p-3 text-center h-100">
                                    <p class="text-muted small mb-1">Capital</p>
                                    <h4 class="text-warning mb-0"><?php echo formatPeso($capital); ?></h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 text-center h-100">
                                    <p class="text-muted small mb-1">Sold for</p>
                                    <h4 class="mb-0 <?php echo $has_sold ? 'text-success' : 'text-muted'; ?>">
                                        <?php echo $has_sold ? formatPeso($sold_amount) : 'Not sold yet'; ?>
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 text-center h-100">
                                    <p class="text-muted small mb-1">Margin</p>
                                    <?php if ($margin_amount !== null): ?>
                                        <h4 class="mb-0 <?php echo $margin_amount >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo formatPeso($margin_amount); ?>
                                            <small class="fs-11 d-block text-muted"><?php echo number_format($margin_pct, 1); ?>% markup</small>
                                        </h4>
                                    <?php else: ?>
                                        <h4 class="mb-0 text-muted">—</h4>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- History -->
                    <div class="tab-pane fade" id="tab-history" role="tabpanel">
                        <div class="timeline-vertical mt-3">
                            <div class="timeline-item timeline-item-start">
                                <div class="timeline-icon icon-item icon-item-lg text-primary border-300">
                                    <span class="fs-8 fas fa-mobile"></span>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 timeline-item-time">
                                        <p class="fs-10 mb-0 fw-semi-bold"><?php echo h($delivery_date); ?></p>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="timeline-item-content">
                                            <div class="timeline-item-card">
                                                <h5 class="mb-2">Inbound</h5>
                                                <p class="fs-10 mb-0">Item has been successfully inbounded.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $timeline_query = "SELECT
                                                    u.user_fname,
                                                    u.user_lname,
                                                    st.title,
                                                    st.action,
                                                    st.date
                                                FROM stock_timeline st
                                                LEFT JOIN users u ON u.hashed_id = st.user_id
                                                WHERE st.unique_barcode = ?
                                                ORDER BY st.date ASC";
                            $timeline_stmt = $conn->prepare($timeline_query);
                            $timeline_stmt->bind_param("s", $unique_barcode);
                            $timeline_stmt->execute();
                            $timeline_result = $timeline_stmt->get_result();

                            $first = true;
                            if ($timeline_result->num_rows > 0) {
                                while ($trow = $timeline_result->fetch_assoc()) {
                                    $did_by      = trim(($trow['user_fname'] ?? '') . " " . ($trow['user_lname'] ?? ''));
                                    $title       = $trow['title'];
                                    $action      = $trow['action'];
                                    $action_date = $trow['date'];

                                    $icon = 'fa-fire';
                                    $titleLower = strtolower((string) $title);
                                    if (str_contains($titleLower, 'inbound')) $icon = 'fa-arrow-down';
                                    elseif (str_contains($titleLower, 'outbound') || str_contains($titleLower, 'sold')) $icon = 'fa-arrow-up';
                                    elseif (str_contains($titleLower, 'location') || str_contains($titleLower, 'transfer')) $icon = 'fa-map-marker-alt';
                                    elseif ($first) $icon = 'fa-mobile';

                                    $itemClass = $first ? 'timeline-item-start' : 'timeline-item-end';
                                    ?>
                                    <div class="timeline-item <?php echo $itemClass; ?>">
                                        <div class="timeline-icon icon-item icon-item-lg text-primary border-300">
                                            <span class="fs-8 fas <?php echo $icon; ?>"></span>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6 timeline-item-time">
                                                <p class="fs-10 mb-0 fw-semi-bold"><?php echo h($action_date); ?></p>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="timeline-item-content">
                                                    <div class="timeline-item-card">
                                                        <h5 class="mb-2"><?php echo h($title); ?></h5>
                                                        <p class="fs-10 mb-0"><?php echo h($action); ?></p>
                                                        <small class="fs-11 text-400"><?php echo h($did_by ?: '—'); ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    $first = false;
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Trend -->
                    <div class="tab-pane fade" id="tab-trend" role="tabpanel">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title text-center mb-3">Outbound volume over time</h6>
                                <div id="SpecificItemChart" style="height: 380px;"></div>
                                <div id="trendEmptyState" class="text-center text-muted small py-4 d-none">No outbound history yet for this item.</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ============================= Change Location Modal ============================= -->
        <div class="modal fade" id="change-location-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
                <div class="modal-content position-relative p-3">
                    <form action="sample.php" method="POST">
                        <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
                                <h4 class="mb-1" id="modalExampleDemoLabel">Change item location</h4>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <label class="form-label" for="item_location">Select item location</label>
                                    <select class="form-select" id="item_location" name="item_location" required>
                                        <?php
                                        $loc_query = "SELECT * FROM item_location WHERE warehouse = ? ORDER BY location_name ASC";
                                        $loc_stmt = $conn->prepare($loc_query);
                                        $loc_stmt->bind_param("s", $warehouse_hashed_id);
                                        $loc_stmt->execute();
                                        $loc_res = $loc_stmt->get_result();

                                        if ($loc_res->num_rows > 0) {
                                            while ($lrow = $loc_res->fetch_assoc()) {
                                                $location_id   = $lrow['id'];
                                                $location_name = $lrow['location_name'];
                                                $selected = ($item_location === $location_name) ? 'selected' : '';
                                                echo '<option value="' . h($location_id) . '" ' . $selected . '>' . h($location_name) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                            <button class="btn btn-primary" type="submit">Understood</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php
    }
}

if (isset($item_status) && $item_status !== null && $item_status != 1) {
    ?>
    <script>
        window.addEventListener('load', function () {
            const button = document.getElementById("liveToastBtn");
            if (button) button.click();
        });
    </script>
    <?php
}
?>

<script>
    // Copy barcode to clipboard
    (function () {
        const copyBtn = document.getElementById('copyBarcodeBtn');
        const note = document.getElementById('copiedNote');
        if (!copyBtn) return;
        copyBtn.addEventListener('click', async function () {
            const value = document.getElementById('barcodeValue').textContent;
            try {
                await navigator.clipboard.writeText(value);
                note.classList.remove('d-none');
                setTimeout(() => note.classList.add('d-none'), 1500);
            } catch (e) {
                console.error('Copy failed:', e);
            }
        });
    })();

    // Outbound trend chart — loaded lazily when the Trend tab is opened
    (function () {
        const barcode = <?php echo json_encode($unique_barcode ?? ''); ?>;
        let chartLoaded = false;

        async function fetchChartData() {
            try {
                const response = await fetch(`../config/total_outbound_specific_product.php?prod=${encodeURIComponent(barcode)}`);
                if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                const data = await response.json();

                const months = data.map(item => item.month);
                const totals = data.map(item => item.total_outbound);
                months.reverse();
                totals.reverse();
                return { months, totals };
            } catch (error) {
                console.error('Error fetching chart data:', error);
                return { months: [], totals: [] };
            }
        }

        async function initChart() {
            const chartEl = document.getElementById('SpecificItemChart');
            const emptyEl = document.getElementById('trendEmptyState');
            if (!chartEl) return;

            const { months, totals } = await fetchChartData();

            if (months.length === 0 || totals.length === 0) {
                chartEl.classList.add('d-none');
                if (emptyEl) emptyEl.classList.remove('d-none');
                return;
            }

            const chart = echarts.init(chartEl);
            chart.setOption({
                tooltip: { trigger: 'axis', formatter: '{b}: {c}' },
                xAxis: { type: 'category', data: months, name: 'Month' },
                yAxis: { type: 'value', name: 'Total Outbound' },
                series: [{
                    name: 'Total Outbound',
                    type: 'line',
                    data: totals,
                    smooth: true,
                    lineStyle: { color: '#5470C6', width: 2 },
                    itemStyle: { color: '#5470C6' },
                    areaStyle: { opacity: 0.08 }
                }]
            });
            window.addEventListener('resize', () => chart.resize());
        }

        const trendTabBtn = document.getElementById('tab-trend-btn');
        if (trendTabBtn) {
            trendTabBtn.addEventListener('shown.bs.tab', function () {
                if (!chartLoaded) {
                    chartLoaded = true;
                    initChart();
                }
            });
        }
    })();
</script>
