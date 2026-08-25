<?php

$po_id      = $_GET['po_no'] ?? 2197;
$unique_key = $_GET['unique_key'] ?? '163067390281';
$is_administrator = ($user_position_name ?? '') === 'Administrator';

// Load the void request attached to each barcode for this inbound once.
// The old page only queried `stocks`, which is why it always displayed a
// Void button even when the barcode had already been placed in void_items.
$void_info_stmt = $conn->prepare("SELECT remarks, `status` FROM void_logs WHERE unique_key = ? AND po_id = ? LIMIT 1");
$void_info_stmt->bind_param('si', $unique_key, $po_id);
$void_info_stmt->execute();
$void_info_result = $void_info_stmt->get_result();
$void_info = $void_info_result->fetch_assoc();
$void_status = $void_info['status'] ?? NULL;
$void_remarks = $void_info['remarks'] ?? NULL;


$void_requests_by_barcode = [];
$void_request_stmt = $conn->prepare(
    "SELECT vi.unique_barcode, vl.id AS void_log_id, vl.status, vl.request_type, vl.remarks
     FROM void_items vi
     INNER JOIN void_logs vl ON vl.id = vi.void_log_id
     WHERE vl.po_id = ?
       AND vl.unique_key = ?
       AND vl.request_type = 'void'
     ORDER BY vl.id DESC"
);
$void_request_stmt->bind_param('is', $po_id, $unique_key);
$void_request_stmt->execute();
$void_request_result = $void_request_stmt->get_result();
while ($void_request = $void_request_result->fetch_assoc()) {
    // Keep the most recent request if a barcode appears in historical logs.
    if (!isset($void_requests_by_barcode[$void_request['unique_barcode']])) {
        $void_requests_by_barcode[$void_request['unique_barcode']] = $void_request;
    }
}
$void_request_stmt->close();

// Once any void_logs row for this po_id + unique_key has been approved,
// treat the whole transaction as closed: no further void actions should
// be offered on it (even for barcodes/products that were never touched),
// and a single badge communicates that instead.
$transaction_approved = false;
foreach ($void_requests_by_barcode as $vr) {
    if (strtolower($vr['status'] ?? '') === 'approved') {
        $transaction_approved = true;
        break;
    }
}

// Once an inbound has been received for more than 3 days, close the void
// window entirely -- no new void requests should be startable on it. Reads
// the most recent date_received recorded for this po_id + unique_key.
// If no inbound_logs row is found at all (e.g. legacy data gaps), we don't
// assume expired -- fail open to preserve existing behavior rather than
// silently locking staff out of a legitimate void.
$voiding_window_expired = false;
$inbound_date_received = null;

$inbound_log_stmt = $conn->prepare(
    "SELECT il.date_received, u.user_fname, u.user_lname FROM inbound_logs il INNER JOIN users u ON u.hashed_id = il.user_id WHERE po_id = ? AND unique_key = ? ORDER BY date_received DESC LIMIT 1"
);
$inbound_log_stmt->bind_param('is', $po_id, $unique_key);
$inbound_log_stmt->execute();
$inbound_log_row = $inbound_log_stmt->get_result()->fetch_assoc();
$inbound_log_stmt->close();

if ($inbound_log_row && !empty($inbound_log_row['date_received'])) {
    $inbound_date_received = $inbound_log_row['date_received'];
    $inbounded_by = $inbound_log_row['user_fname'] . " " . $inbound_log_row['user_lname'];
    $received_timestamp = strtotime($inbound_date_received);
    if ($received_timestamp !== false) {
        $days_since_received = (time() - $received_timestamp) / 86400;
        $voiding_window_expired = $days_since_received > 3;
    }
}

$po_warehouse = 0;

// --- Fetch products on this PO ---
$purchased_order_query = "SELECT 
                            poc.product_id, 
                            poc.qty,
                            p.description,
                            p.parent_barcode,
                            b.brand_name,
                            c.category_name,
                            po.supplier,
                            w.warehouse_name
                        FROM purchased_order_content poc 
                        INNER JOIN purchased_order po ON po.id = poc.po_id
                        LEFT JOIN warehouse w ON w.hashed_id = po.warehouse
                        LEFT JOIN product p ON poc.product_id = p.hashed_id
                        LEFT JOIN brand b ON p.brand = b.hashed_id
                        LEFT JOIN category c ON p.category = c.hashed_id
                        WHERE poc.po_id = ?";

$stmt = $conn->prepare($purchased_order_query);
$stmt->bind_param("i", $po_id);
$stmt->execute();
$purchased_order_result = $stmt->get_result();

$products = [];
$supplier = 0;
if ($purchased_order_result && $purchased_order_result->num_rows > 0) {
    while ($row = $purchased_order_result->fetch_assoc()) {
        $product_id = $row['product_id'];
        $prev_supplier = $row['supplier'];

        if($po_warehouse == 0){
            $po_warehouse = $row['warehouse_name'];
        }

        // --- Fetch stock sequences for this product under this inbound ---
        $stocks_query = "SELECT s.unique_barcode, s.capital, s.item_status, sup.supplier_name, sup.local_international 
                          FROM stocks s 
                          LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
                          WHERE s.unique_key = ? AND s.product_id = ?";
        $stock_stmt = $conn->prepare($stocks_query);
        $stock_stmt->bind_param("ss", $unique_key, $product_id);
        $stock_stmt->execute();
        $stocks_result = $stock_stmt->get_result();

        $stocks = [];
        while ($stock_row = $stocks_result->fetch_assoc()) {
            $stocks[] = $stock_row;
            if($supplier == 0 ){
                $supplier = $stock_row['supplier_name'] . " - " . $stock_row['local_international'];
            }
        }
        $stock_stmt->close();

        $row['stocks'] = $stocks;
        $products[] = $row;
    }
}
$stmt->close();

$total_products = count($products);
$total_stocks    = array_sum(array_map(fn($p) => count($p['stocks']), $products));

// --- Voided items dashboard --------------------------------------------
// void_items only stores the barcode itself (parent_barcode + sequence).
// Once a void request is approved, the matching row in `stocks` is
// deleted -- so at that point we can no longer join void_items -> stocks
// to recover which product a voided barcode belonged to. Instead we
// match each approved-voided barcode's prefix against product.parent_barcode
// directly, which still works after the stocks row is gone.
$voided_items = [];
$voided_items_stmt = $conn->prepare(
    "SELECT vi.unique_barcode, vi.capital
     FROM void_items vi
     INNER JOIN void_logs vl ON vl.id = vi.void_log_id
     WHERE vl.po_id = ? AND vl.unique_key = ? AND vl.request_type = 'void' AND vl.status = 'approved'"
);
$voided_items_stmt->bind_param('is', $po_id, $unique_key);
$voided_items_stmt->execute();
$voided_items_result = $voided_items_stmt->get_result();
while ($voided_row = $voided_items_result->fetch_assoc()) {
    $voided_items[] = $voided_row;
}
$voided_items_stmt->close();

$total_ordered_qty = 0;
$total_voided_count = 0;
$total_pending_void_count = 0;
$total_amount = 0;

foreach ($products as &$product) {
    $parent_barcode = $product['parent_barcode'] ?? '';
    $product_voided_count = 0;
    $product_voided_stocks = [];

    if ($parent_barcode !== '' && $parent_barcode !== null) {
        foreach ($voided_items as $voided_item) {
            if (str_starts_with((string) $voided_item['unique_barcode'], (string) $parent_barcode)) {
                $product_voided_count++;
                $product_voided_stocks[] = $voided_item;
            }
        }
    }

    $product['voided_count'] = $product_voided_count;
    // Rows already voided+approved -- their `stocks` row is gone, so this
    // is the only remaining record of them. Displayed as extra read-only
    // rows in the stock sequences table (see below), not counted in
    // $stock_count / "Received" since that still reflects live stock.
    $product['voided_stocks'] = $product_voided_stocks;

    // How many of this product's current (live) stock rows have an active
    // void request against them that hasn't been approved (or rejected)
    // yet -- i.e. "to be voided" once someone acts on the request.
    $product_pending_void_count = 0;
    foreach ($product['stocks'] as $pending_check_stock) {
        $pending_check_request = $void_requests_by_barcode[$pending_check_stock['unique_barcode']] ?? null;
        if ($pending_check_request !== null && strtolower($pending_check_request['status'] ?? '') === 'pending') {
            $product_pending_void_count++;
        }
    }
    $product['pending_void_count'] = $product_pending_void_count;

    // Value of this product's current (live) stock, from stocks.capital.
    // Mirrors the "Received" qty column -- voided rows are excluded since
    // their stocks row (and capital) is gone once approved.
    $product_amount = array_sum(array_column($product['stocks'], 'capital'));
    $product['amount'] = $product_amount;

    $total_ordered_qty += (int) $product['qty'];
    $total_voided_count += $product_voided_count;
    $total_pending_void_count += $product_pending_void_count;
    $total_amount += $product_amount;
}
unset($product);
$reviewable_stock_count = 0;
$has_void_request_with_remarks = false;
$has_pending_or_approved_with_remarks = false;
// True only when a void_logs row for this inbound is status='pending' AND
// remarks IS NULL — i.e. a request is in progress (barcodes have been
// added) but Done hasn't been clicked yet to submit it. The action
// buttons are gated on this flag so they only appear while still in progress.
$has_pending_without_remarks = false;
$page_void_log_ids = [];

foreach ($products as $product) {
    foreach ($product['stocks'] as $stock) {
        $void_request = $void_requests_by_barcode[$stock['unique_barcode']] ?? null;
        $has_remarks = $void_request !== null
            && $void_request['remarks'] !== null
            && trim($void_request['remarks']) !== '';
        $can_review = $has_remarks
            && in_array(strtolower($void_request['status']), ['pending', 'completed'], true);

        $has_void_request_with_remarks = $has_void_request_with_remarks || $has_remarks;
        $status = strtolower($void_request['status'] ?? '');

        $has_pending_or_approved_with_remarks = $has_pending_or_approved_with_remarks
        || (
            $has_remarks &&
            in_array($status, ['pending', 'approved'], true)
        );

        $has_pending_without_remarks = $has_pending_without_remarks
            || (!$has_remarks && $status === 'pending');

        if ($can_review) {
            $reviewable_stock_count++;
            $page_void_log_ids[(int) $void_request['void_log_id']] = true;
        }
    }
}

$all_stocks_reviewable = $total_stocks > 0 && $reviewable_stock_count === $total_stocks;
$page_void_log_ids = implode(',', array_keys($page_void_log_ids));
// Show the action buttons when there's no void_logs data at all yet for
// this inbound (nothing requested), or when a request is pending without
// remarks yet (still in progress, Done not yet clicked).
// $show_page_actions = !$transaction_approved
//     && (empty($void_requests_by_barcode) || $has_pending_without_remarks);

if(($void_status === "pending" || is_null($void_status)) && is_null($void_remarks)){
    $show_page_actions = true;
} else {
    $show_page_actions = false;
}

if($po_id = 0){
    $show_page_actions = false;
}

if(isset($_GET['inbound'])){
    $show_page_actions = false;
}
// Once the 3-day voiding window has closed, no new void action can be
// started regardless of the void_logs state above -- this covers Void
// Entire Inbound, Void All Sequences, every per-barcode Void button, the
// Done button, and the supplier-update dropdown (which itself creates a
// void_logs row), since all of them are gated on $show_page_actions.
if ($voiding_window_expired) {
    $show_page_actions = false;
}


?>

<div class="void-inbound container-fluid py-4">

    <!-- Header -->
    <div class="vi-panel vi-header mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

            <div>
                <div class="vi-eyebrow">Inventory / Inbound</div>
                <?php 
                if(isset($_GET['inbound'])){
                    echo '<h1 class="vi-title">Inbounded</h1>';
                } else {
                    echo '<h1 class="vi-title">Void Inbounded Stocks</h1>';
                }
                ?>
                

                <div class="vi-meta">
                    <span class="vi-meta-item">
                        <span class="vi-meta-label">Purchase Order</span>
                        <span class="vi-meta-value">#<?= htmlspecialchars($po_id) ?></span>
                    </span>

                    <span class="vi-divider">/</span>

                    <span class="vi-meta-item">
                        <span class="vi-meta-label">Inbound Ref #</span>
                        <span class="vi-code"><?= htmlspecialchars($unique_key) ?></span>
                    </span>

                    <span class="vi-divider">/</span>

                    <span class="vi-meta-item">
                        <span class="vi-meta-label">Scope</span>
                        <span class="vi-meta-value"><?= $total_products ?> product<?= $total_products === 1 ? '' : 's' ?>, <?= $total_stocks ?> unit<?= $total_stocks === 1 ? '' : 's' ?></span>
                    </span>
                </div>
            </div>

            <div class="d-flex align-items-start gap-2 flex-wrap">

                <button type="button" class="vi-btn vi-btn-outline-secondary vi-btn-lg"
                        data-bs-toggle="modal" data-bs-target="#inboundReportModal">
                    <i class="bi bi-file-earmark-text"></i>
                    Generate Inbound Report
                </button>

                <?php if ($transaction_approved): ?>
                    <span class="vi-badge vi-badge-transaction-approved">
                        <i class="bi bi-check-circle-fill"></i>
                        This transaction was approved
                    </span>
                <?php elseif ($is_administrator && $page_void_log_ids !== ''): ?>
                    <div class="vi-action-group">
                        <button class="vi-btn vi-btn-success vi-btn-lg"
                                data-void-approval="approve"
                                data-approval-scope="inbound"
                                data-void-log-ids="<?= htmlspecialchars($page_void_log_ids) ?>">
                            Approved
                        </button>
                        <button class="vi-btn vi-btn-outline-danger vi-btn-lg"
                                data-void-approval="reject"
                                data-approval-scope="inbound"
                                data-void-log-ids="<?= htmlspecialchars($page_void_log_ids) ?>">
                            Reject
                        </button>
                    </div>
                <?php elseif ($show_page_actions): ?>
                    <div class="vi-action-group">
                        <button class="vi-btn vi-btn-danger vi-btn-lg"
                                data-po-id="<?= htmlspecialchars($po_id) ?>"
                                data-unique-key="<?= htmlspecialchars($unique_key) ?>">
                            <i class="bi bi-trash3"></i>
                            Void Entire Inbound
                        </button>
                    </div>
                <?php elseif ($voiding_window_expired): ?>
                    <span class="vi-badge vi-badge-window-closed" title="Received more than 3 days ago">
                        <i class="bi bi-lock-fill"></i>
                        Void window closed
                    </span>
                <?php endif; ?>

            </div>

        </div>

        <?php if ($show_page_actions): ?>
            <div class="vi-supplier-row">
                <div class="vi-supplier-info">
                    <div class="vi-supplier-label"><i class="bi bi-truck"></i> Supplier</div>
                    <div class="vi-supplier-hint">Wrong supplier on this inbound? Change it here — it takes effect once this request is approved.</div>
                </div>
                <select name="select-supplier" id="select-supplier" class="form-select form-select-sm vi-supplier-select">
                    <option value="">Update Supplier</option>
                    <?php
                    $supplier_query = "SELECT * FROM supplier ORDER BY supplier_name ASC";
                    $supplier_stmt = $conn->prepare($supplier_query);
                    $supplier_stmt->execute();
                    $supplier_result = $supplier_stmt->get_result();
                    while ($supplier_row = $supplier_result->fetch_assoc()) {
                        $local_international = htmlspecialchars($supplier_row['local_international']) ?? "NOT SET";
                        echo "<option value='" . htmlspecialchars($supplier_row['hashed_id']) . "'>" . htmlspecialchars($supplier_row['supplier_name']) . " - " . $local_international . "</option>";
                    }
                    ?>
                </select>
            </div>
        <?php endif; ?>
    </div>

    <!-- Summary Dashboard -->
    <div class="vi-panel vi-summary mb-3">
        <div class="vi-summary-grid">
            <div class="vi-stat">
                <div class="vi-stat-label">Qty Ordered</div>
                <div class="vi-stat-value"><?= number_format($total_ordered_qty) ?></div>
            </div>
            <div class="vi-stat">
                <div class="vi-stat-label">Received</div>
                <div class="vi-stat-value"><?= number_format($total_stocks) ?></div>
            </div>
            <div class="vi-stat vi-stat-danger">
                <div class="vi-stat-label">Voided</div>
                <div class="vi-stat-value"><?= number_format($total_voided_count) ?></div>
            </div>
            <div class="vi-stat vi-stat-pending">
                <div class="vi-stat-label">To Be Voided</div>
                <div class="vi-stat-value"><?= number_format($total_pending_void_count) ?></div>
            </div>
            <div class="vi-stat vi-stat-success">
                <div class="vi-stat-label">Total Amount</div>
                <div class="vi-stat-value">&#8369;<?= number_format($total_amount, 2) ?></div>
            </div>
        </div>

        <div class="table-responsive mt-3">
            <table class="table vi-table vi-summary-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Qty Ordered</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">Voided</th>
                        <th class="text-end">To Be Voided</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" class="vi-empty-row">No products found for this purchase order.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $summary_product): ?>
                            <tr>
                                <td><?= htmlspecialchars($summary_product['description'] ?? 'Unknown Product') ?></td>
                                <td class="text-end"><?= number_format((int) $summary_product['qty']) ?></td>
                                <td class="text-end"><?= number_format(count($summary_product['stocks'])) ?></td>
                                <td class="text-end"><?= number_format($summary_product['voided_count']) ?></td>
                                <td class="text-end"><?= number_format($summary_product['pending_void_count']) ?></td>
                                <td class="text-end vi-capital">&#8369;<?= number_format($summary_product['amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($products)): ?>
                    <tfoot>
                        <tr class="vi-summary-totals">
                            <td>Total</td>
                            <td class="text-end"><?= number_format($total_ordered_qty) ?></td>
                            <td class="text-end"><?= number_format($total_stocks) ?></td>
                            <td class="text-end"><?= number_format($total_voided_count) ?></td>
                            <td class="text-end"><?= number_format($total_pending_void_count) ?></td>
                            <td class="text-end vi-capital">&#8369;<?= number_format($total_amount, 2) ?></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>


    <!-- Products -->
    <div class="accordion vi-accordion" id="productsAccordion">

        <?php if (empty($products)): ?>

            <div class="vi-panel vi-empty">
                No products found for PO #<?= htmlspecialchars($po_id) ?>.
            </div>

        <?php else: ?>

            <?php foreach ($products as $index => $product): ?>

                <?php
                    $accordion_id = "product" . $index;
                    $is_first     = $index === 0;
                    $stocks       = $product['stocks'];
                    $stock_count  = count($stocks);
                    $voided_stocks = $product['voided_stocks'] ?? [];
                    $has_any_stock_rows = $stock_count > 0 || count($voided_stocks) > 0;
                    $product_has_void_request_with_remarks = false;
                    // Same idea as $has_pending_without_remarks above, scoped to this product.
                    $product_has_pending_without_remarks = false;
                    $product_has_any_void_request = false;
                    $product_reviewable_stock_count = 0;
                    $product_void_log_ids = [];
                    foreach ($stocks as $product_stock) {
                        $product_void_request = $void_requests_by_barcode[$product_stock['unique_barcode']] ?? null;
                        $product_has_any_void_request = $product_has_any_void_request || $product_void_request !== null;
                        $product_has_remarks = $product_void_request !== null
                            && $product_void_request['remarks'] !== null
                            && trim($product_void_request['remarks']) !== '';
                        $product_status = strtolower($product_void_request['status'] ?? '');
                        $product_can_review = $product_has_remarks
                            && in_array($product_status, ['pending', 'completed'], true);

                        $product_has_void_request_with_remarks = $product_has_void_request_with_remarks || $product_has_remarks;
                        $product_has_pending_without_remarks = $product_has_pending_without_remarks
                            || (!$product_has_remarks && $product_status === 'pending');
                        if ($product_can_review) {
                            $product_reviewable_stock_count++;
                            $product_void_log_ids[(int) $product_void_request['void_log_id']] = true;
                        }
                    }
                    $all_product_stocks_reviewable = $stock_count > 0 && $product_reviewable_stock_count === $stock_count;
                    $product_void_log_ids = implode(',', array_keys($product_void_log_ids));
                    // Show "Void All Sequences" when this product has no void_logs
                    // data yet at all, or when it's pending without remarks (still in progress).
                    $product_show_actions = !$transaction_approved
                        && (!$product_has_any_void_request || $product_has_pending_without_remarks);
                ?>

                <!-- Product -->
                <div class="accordion-item vi-panel vi-product mb-2">

                    <h2 class="accordion-header">

                        <button class="accordion-button vi-accordion-btn <?= $is_first ? '' : 'collapsed' ?>"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= $accordion_id ?>">

                            <div class="w-100 d-flex justify-content-between align-items-center">

                                <div class="vi-product-info">
                                    <div class="vi-product-name"><?= htmlspecialchars($product['description'] ?? 'Unknown Product') ?></div>

                                    <div class="vi-product-tags">
                                        <span class="vi-tag"><?= htmlspecialchars($product['brand_name'] ?? 'N/A') ?></span>
                                        <span class="vi-tag vi-tag-muted"><?= htmlspecialchars($product['category_name'] ?? 'N/A') ?></span>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <span class="vi-badge vi-badge-qty">Qty Ordered <?= htmlspecialchars($product['qty']) ?></span>
                                    <span class="vi-badge vi-badge-received">Received <?= htmlspecialchars($stock_count) ?></span>
                                    <span class="vi-badge vi-badge-voided-count">Voided <?= htmlspecialchars($product['voided_count']) ?></span>
                                    <span class="vi-badge vi-badge-pending-void">To Be Voided <?= htmlspecialchars($product['pending_void_count']) ?></span>
                                </div>

                            </div>

                        </button>

                    </h2>

                    <div id="<?= $accordion_id ?>"
                         class="accordion-collapse collapse <?= $is_first ? 'show' : '' ?>">

                        <div class="accordion-body vi-body">

                            <div class="d-flex justify-content-between align-items-center mb-2">

                                <div class="vi-subhead">Stock Sequences</div>

                                    <?php if ($show_page_actions): ?>
                                    <button class="vi-btn vi-btn-outline-danger vi-btn-sm"
                                            data-product-id="<?= htmlspecialchars($product['product_id']) ?>"
                                            data-parent-barcode="<?= htmlspecialchars($product['parent_barcode'] ?? '') ?>"
                                            data-unique-key="<?= htmlspecialchars($unique_key) ?>"
                                            <?= $stock_count === 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-trash"></i>
                                        Void All Sequences
                                    </button>
                                <?php endif; ?>

                            </div>


                            <div class="table-responsive">

                                <table class="table vi-table align-middle">

                                    <thead>
                                        <tr>
                                            <th>Unique Barcode</th>
                                            <th>Capital</th>
                                            <th>Status</th>
                                            <th width="110">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php if (!$has_any_stock_rows): ?>

                                            <tr>
                                                <td colspan="4" class="vi-empty-row">
                                                    No stocks found for this product under Inbound Ref # <?= htmlspecialchars($unique_key) ?>
                                                </td>
                                            </tr>

                                        <?php else: ?>

                                            <?php foreach ($stocks as $stock): ?>

                                                 <?php
                                                     // item_status is numeric: 0 = available on system,
                                                     // 1 = outbounded, 3 = enroute.
                                                     [$item_status_label, $status_class] = match ((int) ($stock['item_status'] ?? -1)) {
                                                         0 => ['Available', 'vi-badge-active'],
                                                         1 => ['Outbounded', 'vi-badge-outbounded'],
                                                         3 => ['En Route', 'vi-badge-enroute'],
                                                         default => ['Unknown', 'vi-badge-default'],
                                                     };
                                                     $void_request = $void_requests_by_barcode[$stock['unique_barcode']] ?? null;
                                                     $is_void_requested = $void_request !== null;
                                                     $has_remarks = $is_void_requested
                                                         && $void_request['remarks'] !== null
                                                         && trim($void_request['remarks']) !== '';
                                                     $can_review_void_request = $has_remarks
                                                         && in_array(strtolower($void_request['status']), ['pending', 'completed'], true);
                                                 ?>

                                                <tr>

                                                    <td><span class="vi-code vi-code-row"><?= htmlspecialchars($stock['unique_barcode']) ?></span></td>

                                                    <td class="vi-capital">&#8369;<?= number_format((float) $stock['capital'], 2) ?></td>

                                                    <td>
                                                        <span class="vi-badge <?= $status_class ?>"><?= htmlspecialchars($item_status_label) ?></span>
                                                    </td>

                                                     <td>
                                                         <?php
                                                            $status = strtolower($void_request['status'] ?? '');
                                                            // Show Void when this barcode has no void_logs data yet
                                                            // at all, or when its void_logs row is status='pending'
                                                            // AND remarks IS NULL (still in progress, Done not
                                                            // yet clicked).
                                                            $showVoidButton = !$transaction_approved
                                                                && (!$is_void_requested
                                                                    || (!$has_remarks && $status === 'pending'));
                                                            ?>

                                                            <?php if ($show_page_actions): ?>
                                                             <button class="vi-btn vi-btn-outline-danger vi-btn-xs"
                                                                     data-barcode="<?= htmlspecialchars($stock['unique_barcode']) ?>">
                                                                 <i class="bi bi-trash"></i>
                                                                 Void
                                                             </button>
                                                         <?php elseif ($is_administrator && $status === 'approved'): ?>
                                                             <span class="vi-approved-label">Approved</span>
                                                         <?php elseif ($is_administrator && $status === 'rejected'): ?>
                                                             <span class="vi-rejected-label">Rejected</span>
                                                         <?php elseif ($is_void_requested): ?>
                                                             <span class="vi-requested-label">Requested to be voided</span>
                                                         <?php else: ?>
                                                             <span class="vi-text-muted">&mdash;</span>
                                                         <?php endif; ?>
                                                     </td>

                                                </tr>

                                            <?php endforeach; ?>

                                            <?php foreach ($voided_stocks as $voided_stock): ?>

                                                <tr class="vi-row-voided">

                                                    <td><span class="vi-code vi-code-row"><?= htmlspecialchars($voided_stock['unique_barcode']) ?></span></td>

                                                    <td class="vi-capital">&#8369;<?= number_format((float) $voided_stock['capital'], 2) ?></td>

                                                    <td>
                                                        <span class="vi-badge vi-badge-voided">Voided</span>
                                                    </td>

                                                    <td>
                                                        <span class="vi-text-muted">&mdash;</span>
                                                    </td>

                                                </tr>

                                            <?php endforeach; ?>

                                        <?php endif; ?>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <?php if ($show_page_actions): ?>
        <!-- Done -->
        <div class="vi-panel vi-footer mt-3 d-flex justify-content-between align-items-center">
            <div class="vi-footer-note">Once all necessary voids are complete, finish this void request with remarks.</div>
            <button type="button"
                    id="viDoneBtn"
                    class="vi-btn vi-btn-danger vi-btn-lg"
                    data-po-id="<?= htmlspecialchars($po_id) ?>"
                    data-unique-key="<?= htmlspecialchars($unique_key) ?>">
                <i class="bi bi-check2-circle"></i>
                Done
            </button>
        </div>
    <?php endif; ?>

</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="viConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viConfirmModalTitle">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="viConfirmModalMessage" class="mb-3"></p>
                <div id="viConfirmRemarksWrap" class="d-none">
                    <label for="viConfirmRemarks" class="form-label">Remarks <span class="text-danger">*</span></label>
                    <textarea id="viConfirmRemarks" class="form-control" rows="3" placeholder="Enter remarks..."></textarea>
                    <div class="invalid-feedback" id="viRemarksError">Remarks are required.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="viConfirmModalProceed">Proceed</button>
            </div>
        </div>
    </div>
</div>

<!-- Result Modal -->
<div class="modal fade" id="viResultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viResultModalTitle">Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="viResultModalMessage" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="viResultModalClose">OK</button>
            </div>
        </div>
    </div>
</div>


<!-- Inbound Report Modal -->
<div class="modal fade" id="inboundReportModal" tabindex="-1" aria-labelledby="inboundReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inboundReportModalLabel">Inbound Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="inboundReportPrintArea" class="vi-report">
                    <div class="vi-report-header">
                        <img src="../../assets/img/logo/LPO Emblem.png" alt="Company Logo" class="vi-report-logo">
                        <div class="vi-report-heading">
                            <div class="vi-report-title">Inbound Report</div>
                            <div class="vi-report-sub">Purchase Order #<?= htmlspecialchars($po_id) ?> &middot; Inbound Ref # <?= htmlspecialchars($unique_key) ?></div>
                        </div>
                    </div>

                    <div class="vi-report-meta-grid">
                        <div class="vi-report-meta-col">
                            <div class="vi-report-meta-item">
                                <span class="vi-report-meta-label">Purchase Order</span>
                                <span class="vi-report-meta-value">#<?= htmlspecialchars($po_id) ?></span>
                            </div>
                            <div class="vi-report-meta-item">
                                <span class="vi-report-meta-label">Inbound Ref #</span>
                                <span class="vi-report-meta-value"><?= htmlspecialchars($unique_key) ?></span>
                            </div>
                            <div class="vi-report-meta-item">
                                <span class="vi-report-meta-label">Date Generated</span>
                                <span class="vi-report-meta-value"><?= htmlspecialchars(date('F j, Y g:i A')) ?></span>
                            </div>
                            <div class="vi-report-meta-item">
                                <span class="vi-report-meta-label">Date Inbounded</span>
                                <span class="vi-report-meta-value">
                                    <?= !empty($inbound_date_received) ? htmlspecialchars(date('F j, Y g:i A', strtotime($inbound_date_received))) : '' ?>
                                </span>
                            </div>
                        </div>
                        <div class="vi-report-meta-col">
                            <div class="vi-report-meta-item">
                                <span class="vi-report-meta-label">Generated By</span>
                                <span class="vi-report-meta-value"><?= htmlspecialchars($user_fullname ?? 'N/A') ?></span>
                            </div>
                            <div class="vi-report-meta-item">
                                <span class="vi-report-meta-label">Position</span>
                                <span class="vi-report-meta-value"><?= htmlspecialchars($user_position_name ?? 'N/A') ?></span>
                            </div>
                            <div class="vi-report-meta-item">
                                <span class="vi-report-meta-label">Supplier</span>
                                <span class="vi-report-meta-value"><?= htmlspecialchars($supplier) ?></span>
                            </div>
                             <div class="vi-report-meta-item">
                                <span class="vi-report-meta-label">Inbounded by</span>
                                <span class="vi-report-meta-value"><?= htmlspecialchars($inbounded_by) ?></span>
                            </div>
                        </div>
                    </div>

                    
                        <div class="vi-report-warehouse-row">
                            <span class="vi-report-meta-label">Received by:</span>
                            <div class="vi-report-warehouse-chips">
                                
                                    <span class="vi-report-chip"><?= htmlspecialchars($po_warehouse) ?></span>
                                
                            </div>
                        </div>

                    <table class="table vi-table vi-report-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th>Parent Barcode</th>
                                <th class="text-end">Qty Ordered</th>
                                <th class="text-end">Qty Received</th>
                                <th class="text-end">Qty Voided</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="8" class="vi-empty-row">No products found for this purchase order.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $report_product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($report_product['description'] ?? 'Unknown Product') ?></td>
                                        <td><?= htmlspecialchars($report_product['category_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($report_product['brand_name'] ?? 'N/A') ?></td>
                                        <td><span class="vi-code"><?= htmlspecialchars($report_product['parent_barcode'] ?? 'N/A') ?></span></td>
                                        <td class="text-end"><?= number_format((int) $report_product['qty']) ?></td>
                                        <td class="text-end"><?= number_format(count($report_product['stocks'])) ?></td>
                                        <td class="text-end"><?= number_format($report_product['voided_count']) ?></td>
                                        <td class="text-end">&#8369;<?= number_format($report_product['amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="vi-report-totals">
                                <td colspan="4">Total</td>
                                <td class="text-end"><?= number_format($total_ordered_qty) ?></td>
                                <td class="text-end"><?= number_format($total_stocks) ?></td>
                                <td class="text-end"><?= number_format($total_voided_count) ?></td>
                                <td class="text-end">&#8369;<?= number_format($total_amount, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="vi-report-footer">
                        Inbound Report &middot; Ref # <?= htmlspecialchars($unique_key) ?> &middot; Generated <?= htmlspecialchars(date('F j, Y g:i A')) ?> by <?= htmlspecialchars($user_fullname ?? 'N/A') ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="viPrintReportBtn">
                    <i class="bi bi-printer"></i>
                    Print
                </button>
            </div>
        </div>
    </div>
</div>

<style>

:root{
    --vi-bg:#f3f5f8;
    --vi-panel:#ffffff;
    --vi-border:#e4e8ee;
    --vi-text:#1f2937;
    --vi-text-muted:#6b7280;
    --vi-navy:#1f3a5f;
    --vi-navy-soft:#eef2f8;
    --vi-danger:#b3261e;
    --vi-danger-soft:#fbebea;
    --vi-danger-soft-hover:#f6d9d7;
    --vi-success-bg:#e6f4ea;
    --vi-success-text:#1e7a3a;
    --vi-radius:10px;
}

/* body{
    background:var(--vi-bg);
    color:var(--vi-text);
} */

.void-inbound{
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
    font-size:14px;
    max-width:1080px;
    margin:0 auto;
}

.vi-panel{
    background:var(--vi-panel);
    border:1px solid var(--vi-border);
    border-radius:var(--vi-radius);
    box-shadow:0 1px 2px rgba(16,24,40,0.04);
}

.vi-header{
    padding:20px 24px;
}

.vi-eyebrow{
    font-size:11px;
    font-weight:600;
    letter-spacing:.06em;
    text-transform:uppercase;
    color:var(--vi-text-muted);
    margin-bottom:4px;
}

.vi-title{
    font-size:19px;
    font-weight:600;
    color:var(--vi-text);
    margin:0 0 10px;
}

.vi-meta{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
    font-size:13px;
}

.vi-meta-item{
    display:flex;
    align-items:center;
    gap:6px;
}

.vi-meta-label{
    color:var(--vi-text-muted);
}

.vi-meta-value{
    color:var(--vi-text);
    font-weight:600;
}

.vi-divider{
    color:var(--vi-border);
}

.vi-code{
    font-family:"SF Mono",SFMono-Regular,Consolas,"Liberation Mono",Menlo,monospace;
    font-size:12.5px;
    font-weight:600;
    background:var(--vi-navy-soft);
    color:var(--vi-navy);
    padding:2px 7px;
    border-radius:6px;
    letter-spacing:.01em;
}

/* Buttons */
.vi-btn{
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-size:13px;
    font-weight:600;
    border-radius:8px;
    border:1px solid transparent;
    padding:8px 14px;
    transition:background .15s ease,border-color .15s ease,color .15s ease;
    cursor:pointer;
}

.vi-btn i{
    font-size:12.5px;
}

.vi-btn-lg{
    padding:9px 16px;
    font-size:13.5px;
}

.vi-btn-sm{
    padding:5px 11px;
    font-size:12.5px;
}

.vi-btn-xs{
    padding:4px 9px;
    font-size:12px;
}

.vi-btn-danger{
    background:var(--vi-danger);
    color:#fff;
}
.vi-btn-danger:hover{
    background:#94201a;
}

.vi-btn-outline-danger{
    background:var(--vi-danger-soft);
    color:var(--vi-danger);
    border-color:transparent;
}
.vi-btn-outline-danger:hover{
    background:var(--vi-danger-soft-hover);
}
.vi-btn-outline-danger:disabled{
    background:#f1f2f4;
    color:#9aa1ab;
    cursor:not-allowed;
}
.vi-btn-success{
    background:#1e7a3a;
    color:#fff;
}
.vi-btn-success:hover{
    background:#17612e;
}
.vi-btn-outline-secondary{
    background:var(--vi-navy-soft);
    color:var(--vi-navy);
    border-color:transparent;
}
.vi-btn-outline-secondary:hover{
    background:#e0e8f2;
}
.vi-action-group{
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}

/* Supplier change row — sits inside the header, below the title/actions,
   as its own clearly-labeled strip rather than crowded next to the
   Void Entire Inbound button. */
.vi-supplier-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;
    margin-top:16px;
    padding-top:16px;
    border-top:1px dashed var(--vi-border);
}
.vi-supplier-info{
    min-width:220px;
}
.vi-supplier-label{
    display:flex;
    align-items:center;
    gap:6px;
    font-size:13px;
    font-weight:600;
    color:var(--vi-text);
    margin-bottom:2px;
}
.vi-supplier-hint{
    font-size:12px;
    color:var(--vi-text-muted);
}
.vi-supplier-select{
    max-width:280px;
    min-width:220px;
}
.vi-keep-label,
.vi-requested-label,
.vi-approved-label,
.vi-rejected-label{
    display:inline-block;
    font-size:12px;
    font-weight:600;
    padding:4px 8px;
    border-radius:6px;
    white-space:nowrap;
}
.vi-keep-label{
    background:#eef0f3;
    color:#5b6472;
}
.vi-text-muted{
    color:var(--vi-text-muted);
}
.vi-requested-label{
    background:#fdeeda;
    color:#b5580c;
}
.vi-approved-label{
    background:var(--vi-success-bg);
    color:var(--vi-success-text);
}
.vi-rejected-label{
    background:var(--vi-danger-soft);
    color:var(--vi-danger);
}

/* Accordion */
.vi-accordion{
    display:flex;
    flex-direction:column;
    gap:0;
}

.vi-product{
    overflow:hidden;
}

.vi-accordion-btn{
    background:var(--vi-panel);
    padding:14px 18px;
    border:none;
    box-shadow:none !important;
}

.vi-accordion-btn:not(.collapsed){
    background:var(--vi-navy-soft);
}

.vi-accordion-btn:focus{
    box-shadow:none;
}

.vi-product-name{
    font-size:14.5px;
    font-weight:600;
    color:var(--vi-text);
    margin-bottom:4px;
}

.vi-product-tags{
    display:flex;
    gap:6px;
}

.vi-tag{
    font-size:11.5px;
    font-weight:600;
    color:var(--vi-navy);
    background:#e4ecf7;
    padding:2px 8px;
    border-radius:5px;
}

.vi-tag-muted{
    color:var(--vi-text-muted);
    background:#eef0f3;
}

.vi-badge{
    font-size:12px;
    font-weight:600;
    padding:3px 10px;
    border-radius:999px;
    white-space:nowrap;
}

.vi-badge-qty{
    background:var(--vi-navy-soft);
    color:var(--vi-navy);
}

.vi-badge-received{
    background:var(--vi-success-bg);
    color:var(--vi-success-text);
}

.vi-badge-voided-count{
    background:var(--vi-danger-soft);
    color:var(--vi-danger);
}

.vi-badge-transaction-approved{
    background:var(--vi-success-bg);
    color:var(--vi-success-text);
    font-size:14px;
    padding:8px 16px;
    display:inline-flex;
    align-items:center;
    gap:6px;
}

.vi-badge-window-closed{
    background:#eef0f3;
    color:#5b6472;
    font-size:14px;
    padding:8px 16px;
    display:inline-flex;
    align-items:center;
    gap:6px;
}

.vi-summary{
    padding:20px 24px;
}
.vi-summary-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));
    gap:16px;
}
.vi-stat{
    background:var(--vi-navy-soft);
    border-radius:var(--vi-radius);
    padding:12px 16px;
}
.vi-stat-danger{
    background:var(--vi-danger-soft);
}
.vi-stat-pending{
    background:#fdf3e0;
}
.vi-stat-pending .vi-stat-value{
    color:#8a6110;
}
.vi-stat-success{
    background:var(--vi-success-bg);
}
.vi-stat-success .vi-stat-value{
    color:var(--vi-success-text);
}
.vi-stat-label{
    font-size:11px;
    font-weight:600;
    letter-spacing:.04em;
    text-transform:uppercase;
    color:var(--vi-text-muted);
    margin-bottom:4px;
}
.vi-stat-value{
    font-size:22px;
    font-weight:700;
    color:var(--vi-text);
}
.vi-stat-danger .vi-stat-value{
    color:var(--vi-danger);
}
.vi-summary-table th{
    font-size:11px;
    font-weight:600;
    letter-spacing:.04em;
    text-transform:uppercase;
    color:var(--vi-text-muted);
}

.vi-badge-active{
    background:var(--vi-success-bg);
    color:var(--vi-success-text);
}
.vi-badge-sold{
    background:#eef0f3;
    color:#5b6472;
}
.vi-badge-voided{
    background:var(--vi-danger-soft);
    color:var(--vi-danger);
}
.vi-badge-default{
    background:#fdf3e0;
    color:#8a6110;
}
.vi-badge-outbounded{
    background:var(--vi-navy-soft);
    color:var(--vi-navy);
}
.vi-badge-enroute{
    background:#fdf3e0;
    color:#8a6110;
}
.vi-badge-pending-void{
    background:#fdeeda;
    color:#b5580c;
}
.vi-badge-void-requested{
    background:#fdeeda;
    color:#b5580c;
}

.vi-body{
    padding:16px 18px 18px;
    border-top:1px solid var(--vi-border);
}

.vi-subhead{
    font-size:12.5px;
    font-weight:700;
    letter-spacing:.03em;
    text-transform:uppercase;
    color:var(--vi-text-muted);
}

/* Table */
.vi-table{
    margin-bottom:0;
    font-size:13px;
}

.vi-table thead th{
    font-size:11.5px;
    font-weight:600;
    letter-spacing:.03em;
    text-transform:uppercase;
    color:var(--vi-text-muted);
    background:#fafbfc;
    border-bottom:1px solid var(--vi-border);
    padding:9px 12px;
}

.vi-table tbody td{
    padding:10px 12px;
    border-bottom:1px solid var(--vi-border);
    color:var(--vi-text);
}

.vi-table tbody tr:last-child td{
    border-bottom:none;
}

.vi-row-voided{
    background:#fafbfc;
}

.vi-row-voided td{
    color:var(--vi-text-muted);
}

.vi-table tbody tr{
    transition:background .12s ease;
}

.vi-table tbody tr:hover{
    background:#fafbfc;
}

.vi-code-row{
    font-size:12px;
    padding:2px 6px;
}

.vi-capital{
    font-weight:600;
    color:var(--vi-text);
}

.vi-empty-row{
    text-align:center;
    color:var(--vi-text-muted);
    padding:16px;
    font-size:13px;
}

.vi-empty{
    padding:20px 24px;
    color:var(--vi-text-muted);
    font-size:13.5px;
}

.vi-footer{
    padding:16px 20px;
    gap:16px;
    flex-wrap:wrap;
}

.vi-footer-note{
    font-size:13px;
    color:var(--vi-text-muted);
}

.vi-btn:disabled{
    opacity:.65;
    cursor:not-allowed;
}

.vi-spinner{
    display:inline-block;
    width:12px;
    height:12px;
    border:2px solid rgba(255,255,255,.5);
    border-top-color:#fff;
    border-radius:50%;
    animation:vi-spin .6s linear infinite;
}

.vi-btn-outline-danger .vi-spinner{
    border-color:rgba(179,38,30,.35);
    border-top-color:var(--vi-danger);
}

@keyframes vi-spin{
    to{ transform:rotate(360deg); }
}

@media (max-width: 640px){
    .vi-header{ padding:16px; }
    .vi-body{ padding:14px; }
    .vi-report-meta-grid{ grid-template-columns:1fr; }
}

/* Safety-net modal styling, only matters if Bootstrap's own CSS
   isn't present on the page; harmless/redundant otherwise. */
.modal{
    position:fixed;
    top:0; left:0; right:0; bottom:0;
    z-index:1055;
    display:none;
    outline:0;
}
.modal.show{
    display:block;
}
.modal-dialog{
    max-width:500px;
    margin:1.75rem auto;
    position:relative;
}
.modal-dialog.modal-lg{
    max-width:800px;
}
.modal-dialog.modal-xl{
    max-width:1140px;
}
.modal-dialog-centered{
    display:flex;
    align-items:center;
    min-height:calc(100% - 3.5rem);
}
.modal-content{
    background:#fff;
    border-radius:10px;
    border:1px solid var(--vi-border, #e4e8ee);
    box-shadow:0 8px 24px rgba(16,24,40,0.15);
    width:100%;
}
.modal-header, .modal-footer{
    display:flex;
    align-items:center;
    padding:14px 18px;
    border-bottom:1px solid var(--vi-border, #e4e8ee);
}
.modal-footer{
    border-top:1px solid var(--vi-border, #e4e8ee);
    border-bottom:none;
    justify-content:flex-end;
    gap:8px;
}
.modal-title{
    margin:0;
    font-size:16px;
    font-weight:600;
}
.modal-body{
    padding:16px 18px;
}
.modal-backdrop{
    position:fixed;
    top:0; left:0; right:0; bottom:0;
    z-index:1050;
    background:rgba(0,0,0,.5);
}
body.modal-open{
    overflow:hidden;
}

.vi-report{
    background:#fff;
}
.vi-report-header{
    display:flex;
    align-items:center;
    gap:16px;
    padding-bottom:16px;
    margin-bottom:16px;
    border-bottom:2px solid var(--vi-navy);
}
.vi-report-logo{
    height:52px;
    width:auto;
    object-fit:contain;
    flex-shrink:0;
}
.vi-report-heading{
    display:flex;
    flex-direction:column;
    gap:2px;
}
.vi-report-title{
    font-size:20px;
    font-weight:700;
    color:var(--vi-navy);
    letter-spacing:.01em;
}
.vi-report-sub{
    font-size:12.5px;
    color:var(--vi-text-muted);
}
.vi-report-meta-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:4px 24px;
    padding:14px 16px;
    margin-bottom:18px;
    background:var(--vi-navy-soft);
    border-radius:var(--vi-radius);
}
.vi-report-meta-col{
    display:flex;
    flex-direction:column;
    gap:6px;
}
.vi-report-meta-item{
    display:flex;
    justify-content:space-between;
    gap:12px;
    font-size:12.5px;
}
.vi-report-meta-label{
    color:var(--vi-text-muted);
    font-weight:600;
}
.vi-report-meta-value{
    color:var(--vi-text);
    font-weight:600;
    text-align:right;
}
.vi-report-warehouse-row{
    padding:12px 16px;
    margin-bottom:18px;
    border:1px solid var(--vi-border);
    border-radius:var(--vi-radius);
}
.vi-report-warehouse-row .vi-report-meta-label{
    display:block;
    margin-bottom:8px;
}
.vi-report-warehouse-chips{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
}
.vi-report-chip{
    font-size:11.5px;
    font-weight:600;
    color:var(--vi-navy);
    background:var(--vi-navy-soft);
    border:1px solid var(--vi-border);
    padding:3px 10px;
    border-radius:999px;
    white-space:nowrap;
}
.vi-report-table{
    border:1px solid var(--vi-border);
}
.vi-report-table th{
    font-size:10.5px;
    font-weight:700;
    letter-spacing:.03em;
    text-transform:uppercase;
    color:var(--vi-navy);
    background:var(--vi-navy-soft);
    padding:8px 10px;
    white-space:nowrap;
}
.vi-report-table td{
    padding:8px 10px;
    border-top:1px solid var(--vi-border);
    font-size:13px;
}
.vi-report-table td .vi-code{
    font-size:11.5px;
    padding:1px 6px;
}
.vi-report-totals td{
    font-weight:700;
    border-top:2px solid var(--vi-navy);
    background:var(--vi-navy-soft);
}
.vi-summary-totals td{
    font-weight:700;
    border-top:2px solid var(--vi-navy);
    background:var(--vi-navy-soft);
}
.vi-report-footer{
    margin-top:18px;
    padding-top:10px;
    border-top:1px solid var(--vi-border);
    font-size:11px;
    color:var(--vi-text-muted);
    text-align:center;
}

@media print{
    body > *:not(#inboundReportPrintArea){
        display:none !important;
    }
    #inboundReportPrintArea{
        display:block !important;
        position:static;
        width:auto;
        max-width:none;
        margin:0;
        padding:0;
    }
    #inboundReportPrintArea thead{
        display:table-header-group;
    }
    #inboundReportPrintArea tfoot{
        display:table-footer-group;
    }
    #inboundReportPrintArea tr{
        page-break-inside:avoid;
    }
    #inboundReportPrintArea .vi-report-header,
    #inboundReportPrintArea .vi-report-meta-grid,
    #inboundReportPrintArea .vi-report-warehouse-row{
        page-break-inside:avoid;
    }
    @page{
        margin:14mm 12mm;
    }
}

</style>



<script>
(function () {
    "use strict";

    var poId      = <?= json_encode((string) $po_id) ?>;
    var uniqueKey = <?= json_encode((string) $unique_key) ?>;

    // Lightweight modal wrapper: uses real Bootstrap's Modal if/when it's
    // available on the page, otherwise falls back to a manual show/hide so
    // this doesn't hard-fail with "bootstrap is not defined" if the
    // Bootstrap JS bundle hasn't loaded (or isn't loaded) on this page.
    function ViModal(el) {
        this.el = el;
        this.backdropEl = null;

        if (window.bootstrap && window.bootstrap.Modal) {
            this.native = window.bootstrap.Modal.getOrCreateInstance(el);
        } else {
            this.native = null;
            var self = this;
            el.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (btn) {
                btn.addEventListener("click", function () { self.hide(); });
            });
        }
    }

    ViModal.prototype.show = function () {
        if (this.native) { this.native.show(); return; }

        this.el.classList.add("show");
        this.el.style.display = "block";
        this.el.removeAttribute("aria-hidden");
        this.el.setAttribute("aria-modal", "true");
        document.body.classList.add("modal-open");

        var self = this;
        this.backdropEl = document.createElement("div");
        this.backdropEl.className = "modal-backdrop fade show";
        document.body.appendChild(this.backdropEl);
        this.backdropEl.addEventListener("click", function () { self.hide(); });

        this._escHandler = function (e) {
            if (e.key === "Escape") self.hide();
        };
        document.addEventListener("keydown", this._escHandler);
    };

    ViModal.prototype.hide = function () {
        if (this.native) { this.native.hide(); return; }

        this.el.classList.remove("show");
        this.el.style.display = "none";
        this.el.setAttribute("aria-hidden", "true");
        this.el.removeAttribute("aria-modal");
        document.body.classList.remove("modal-open");

        if (this.backdropEl) {
            this.backdropEl.remove();
            this.backdropEl = null;
        }
        if (this._escHandler) {
            document.removeEventListener("keydown", this._escHandler);
            this._escHandler = null;
        }

        // Mirror Bootstrap's event so the rest of the code (which listens
        // for "hidden.bs.modal") behaves the same either way.
        this.el.dispatchEvent(new CustomEvent("hidden.bs.modal"));
    };

    var confirmModalEl   = document.getElementById("viConfirmModal");
    var resultModalEl    = document.getElementById("viResultModal");
    var confirmModal     = new ViModal(confirmModalEl);
    var resultModal      = new ViModal(resultModalEl);

    var confirmTitleEl   = document.getElementById("viConfirmModalTitle");
    var confirmMessageEl = document.getElementById("viConfirmModalMessage");
    var confirmProceedBtn= document.getElementById("viConfirmModalProceed");
    var remarksWrap       = document.getElementById("viConfirmRemarksWrap");
    var remarksInput      = document.getElementById("viConfirmRemarks");

    var resultTitleEl    = document.getElementById("viResultModalTitle");
    var resultMessageEl  = document.getElementById("viResultModalMessage");

    // ---- helpers -----------------------------------------------------

    function setButtonLoading(btn, loading) {
        if (!btn) return;
        if (loading) {
            if (btn.dataset.viOriginalHtml === undefined) {
                btn.dataset.viOriginalHtml = btn.innerHTML;
            }
            btn.disabled = true;
            btn.innerHTML = '<span class="vi-spinner"></span> Processing...';
        } else {
            btn.disabled = false;
            if (btn.dataset.viOriginalHtml !== undefined) {
                btn.innerHTML = btn.dataset.viOriginalHtml;
                delete btn.dataset.viOriginalHtml;
            }
        }
    }

    function setAllButtonsDisabled(disabled) {
        document.querySelectorAll(".void-inbound .vi-btn").forEach(function (b) {
            // Respect a product row that legitimately has no stocks to void.
            if (!disabled && b.dataset.viKeepDisabled === "1") return;
            b.disabled = disabled;
        });
    }

    // Permanently marks a single barcode row's status badge as "Void
    // Requested" and disables/relabels its Void button, so the UI reflects
    // the void without needing a page reload.
    function markBarcodeVoided(barcodeBtn) {
        var row = barcodeBtn.closest("tr");
        if (row) {
            var badge = row.querySelector(".vi-badge");
            if (badge) {
                badge.classList.remove("vi-badge-active", "vi-badge-sold", "vi-badge-voided", "vi-badge-default", "vi-badge-outbounded", "vi-badge-enroute");
                badge.classList.add("vi-badge-void-requested");
                badge.textContent = "Void Requested";
            }
        }
        markButtonVoided(barcodeBtn, "Requested");
    }

    // Permanently disables/relabels any action button once its void has
    // gone through, so setAllButtonsDisabled(false) won't re-enable it.
    function markButtonVoided(btn, label) {
        if (btn.dataset.viOriginalHtml !== undefined) {
            delete btn.dataset.viOriginalHtml;
        }
        btn.dataset.viKeepDisabled = "1";
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + label;
    }

    // Returns a promise that resolves with { confirmed, remarks }
    function openConfirm(opts) {
        opts = opts || {};
        return new Promise(function (resolve) {
            confirmTitleEl.textContent = opts.title || "Confirm Action";
            confirmMessageEl.textContent = opts.message || "Are you sure you want to proceed?";
            remarksInput.value = "";
            remarksInput.classList.remove("is-invalid");
            remarksWrap.classList.toggle("d-none", !opts.needsRemarks);

            function cleanup() {
                confirmProceedBtn.removeEventListener("click", onProceed);
                confirmModalEl.removeEventListener("hidden.bs.modal", onHidden);
            }

            var resolved = false;

            function onProceed() {
                if (opts.needsRemarks && !remarksInput.value.trim()) {
                    remarksInput.classList.add("is-invalid");
                    return;
                }
                resolved = true;
                cleanup();
                confirmModal.hide();
                resolve({ confirmed: true, remarks: remarksInput.value.trim() });
            }

            function onHidden() {
                cleanup();
                if (!resolved) {
                    resolve({ confirmed: false });
                }
            }

            confirmProceedBtn.addEventListener("click", onProceed);
            confirmModalEl.addEventListener("hidden.bs.modal", onHidden);
            confirmModal.show();
        });
    }

    function showResult(opts) {
        resultTitleEl.textContent = opts.title || "";
        resultMessageEl.textContent = opts.message || "";
        resultTitleEl.classList.toggle("text-danger", !opts.success);
        resultTitleEl.classList.toggle("text-success", !!opts.success);

        if (opts.onClose) {
            resultModalEl.addEventListener("hidden.bs.modal", opts.onClose, { once: true });
        }
        resultModal.show();
    }

    function callVoidFunction(params) {
        var url = "void-function.php?" + new URLSearchParams(params).toString();
        return fetch(url, { method: "GET", credentials: "same-origin" })
            .then(function (res) {
                return res.text().then(function (text) {
                    var data = null;
                    try { data = text ? JSON.parse(text) : null; } catch (e) { /* not JSON */ }

                    if (!res.ok) {
                        var message = (data && data.message) ? data.message : (text || ("Request failed with status " + res.status));
                        throw new Error(message);
                    }
                    if (data && data.success === false) {
                        throw new Error(data.message || "The request could not be completed.");
                    }
                    return data;
                });
            });
    }

    // ---- Administrator approval actions --------------------------------

    document.querySelectorAll("[data-void-approval]").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var action = btn.dataset.voidApproval;
            var isApproval = action === "approve";

            openConfirm({
                title: isApproval ? "Approve Void Request" : "Reject Void Request",
                message: isApproval
                    ? "Approve this completed void request?"
                    : "Reject this completed void request?"
            }).then(function (result) {
                if (!result.confirmed) return;

                setButtonLoading(btn, true);
                callVoidFunction({
                    "void-inbound": action,
                    void_log_ids: btn.dataset.voidLogIds,
                    approval_scope: btn.dataset.approvalScope,
                    approval_barcode: btn.dataset.approvalBarcode || "",
                    approval_parent: btn.dataset.approvalParent || "",
                    po_id: poId,
                    unique_key: uniqueKey
                }).then(function (data) {
                    showResult({
                        title: "Success",
                        message: data.message || "The void request has been updated.",
                        success: true,
                        onClose: function () { window.location.reload(); }
                    });
                }).catch(function (err) {
                    showResult({
                        title: "Request Failed",
                        message: err.message || "Something went wrong.",
                        success: false
                    });
                    setButtonLoading(btn, false);
                });
            });
        });
    });

    // ---- 1. Void Entire Inbound ---------------------------------------

    var voidEntireBtn = document.querySelector(".vi-btn-danger[data-po-id]");
    if (voidEntireBtn) {
        voidEntireBtn.addEventListener("click", function () {
            openConfirm({
                title: "Void Entire Inbound",
                message: "Are you sure you want to void the entire inbound (Ref # " + uniqueKey + ")? This cannot be undone."
            }).then(function (result) {
                if (!result.confirmed) return;

                // Voiding the entire inbound leaves nothing else to add, so
                // it's the same next step as clicking "Done" -- immediately
                // ask for the remarks needed to submit this request.
                return openConfirm({
                    title: "Complete Void Request",
                    message: "Please confirm and provide remarks to finish this void request.",
                    needsRemarks: true
                }).then(function (doneResult) {
                    if (!doneResult.confirmed) return;

                    setAllButtonsDisabled(true);
                    setButtonLoading(voidEntireBtn, true);

                    return callVoidFunction({
                        "void-inbound": "inbound",
                        po_id: poId,
                        unique_key: uniqueKey
                    }).then(function () {
                        return callVoidFunction({
                            "void-inbound": "done",
                            po_id: poId,
                            unique_key: uniqueKey,
                            remarks: doneResult.remarks
                        });
                    }).then(function () {
                        document.querySelectorAll("[data-barcode]").forEach(function (b) {
                            markBarcodeVoided(b);
                        });
                        showResult({
                            title: "Success",
                            message: "The entire inbound has been voided and submitted for approval.",
                            success: true,
                            onClose: function () {
                                window.location.reload();
                            }
                        });
                    }).catch(function (err) {
                        showResult({
                            title: "Void Failed",
                            message: err.message || "Something went wrong.",
                            success: false
                        });
                        setAllButtonsDisabled(false);
                        setButtonLoading(voidEntireBtn, false);
                    });
                });
            });
        });
    }

    // ---- 2. Void All Sequences (per product) --------------------------

    document.querySelectorAll("[data-product-id]").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var parentBarcode = btn.dataset.parentBarcode || "";
            var accordionBody = btn.closest(".accordion-body");
            var barcodeButtons = accordionBody ? accordionBody.querySelectorAll("[data-barcode]") : [];

            openConfirm({
                title: "Void All Sequences",
                message: "Are you sure you want to void all sequences for this product?"
            }).then(function (result) {
                if (!result.confirmed) return;

                setAllButtonsDisabled(true);
                setButtonLoading(btn, true);
                barcodeButtons.forEach(function (b) { setButtonLoading(b, true); });

                callVoidFunction({
                    "void-inbound": "parent",
                    parent: parentBarcode,
                    po_id: poId,
                    unique_key: uniqueKey
                }).then(function () {
                    barcodeButtons.forEach(function (b) { markBarcodeVoided(b); });
                    markButtonVoided(btn, "Sequences Voided");
                    showResult({
                        title: "Success",
                        message: "All sequences for this product have been voided.",
                        success: true
                    });
                }).catch(function (err) {
                    showResult({
                        title: "Void Failed",
                        message: err.message || "Something went wrong.",
                        success: false
                    });
                    setButtonLoading(btn, false);
                    barcodeButtons.forEach(function (b) { setButtonLoading(b, false); });
                }).finally(function () {
                    setAllButtonsDisabled(false);
                });
            });
        });
    });

    // ---- 3. Void a single barcode sequence -----------------------------

    document.querySelectorAll("[data-barcode]").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var barcode = btn.dataset.barcode;

            openConfirm({
                title: "Void Sequence",
                message: "Are you sure you want to void barcode " + barcode + "?"
            }).then(function (result) {
                if (!result.confirmed) return;

                setAllButtonsDisabled(true);
                setButtonLoading(btn, true);

                callVoidFunction({
                    "void-inbound": "sequence",
                    sequence: barcode,
                    po_id: poId,
                    unique_key: uniqueKey
                }).then(function () {
                    markBarcodeVoided(btn);
                    showResult({
                        title: "Success",
                        message: "Barcode " + barcode + " has been voided.",
                        success: true
                    });
                }).catch(function (err) {
                    showResult({
                        title: "Void Failed",
                        message: err.message || "Something went wrong.",
                        success: false
                    });
                    setButtonLoading(btn, false);
                }).finally(function () {
                    setAllButtonsDisabled(false);
                });
            });
        });
    });

    // ---- 4. Done ---------------------------------------------------------

    var doneBtn = document.getElementById("viDoneBtn");
    if (doneBtn) {
        doneBtn.addEventListener("click", function () {
            openConfirm({
                title: "Complete Void Request",
                message: "Please confirm and provide remarks to finish this void request.",
                needsRemarks: true
            }).then(function (result) {
                if (!result.confirmed) return;

                setButtonLoading(doneBtn, true);

                callVoidFunction({
                    "void-inbound": "done",
                    po_id: poId,
                    unique_key: uniqueKey,
                    remarks: result.remarks
                }).then(function () {
                    showResult({
                        title: "Success",
                        message: "This void request has been marked as for approval",
                        success: true,
                        onClose: function () {
                            window.location.reload();
                        }
                    });
                }).catch(function (err) {
                    showResult({
                        title: "Failed",
                        message: err.message || "Something went wrong.",
                        success: false
                    });
                }).finally(function () {
                    setButtonLoading(doneBtn, false);
                });
            });
        });
    }

    // ---- 5. Print Inbound Report ---------------------------------------

    // Bootstrap's modal centers itself via a CSS transform on .modal-dialog.
    // An absolutely-positioned print target inside a transformed ancestor is
    // positioned relative to THAT ancestor, not the page -- which is why the
    // report used to print with a huge blank lead-in and spill across many
    // pages. Instead, physically move the report node out to be a direct
    // child of <body> right before printing (any trigger -- the button below
    // or Ctrl+P), then restore it to its original spot right after.
    var printReportBtn   = document.getElementById("viPrintReportBtn");
    var reportPrintArea  = document.getElementById("inboundReportPrintArea");
    var reportPlaceholder = document.createComment("inbound-report-placeholder");
    var reportHomeParent  = null;

    window.addEventListener("beforeprint", function () {
        if (!reportPrintArea) return;
        reportHomeParent = reportPrintArea.parentNode;
        reportHomeParent.insertBefore(reportPlaceholder, reportPrintArea);
        document.body.appendChild(reportPrintArea);
    });

    window.addEventListener("afterprint", function () {
        if (!reportPrintArea || !reportPlaceholder.parentNode) return;
        reportPlaceholder.parentNode.insertBefore(reportPrintArea, reportPlaceholder);
        reportPlaceholder.remove();
    });

    if (printReportBtn) {
        printReportBtn.addEventListener("click", function () {
            window.print();
        });
    }
})();
</script>
<!-- ====================================================
======supplier update=============================== -->

<script>
window.onload = function () {

    var viSupplierUniqueKey = <?= json_encode((string) $unique_key) ?>;
    var viSupplierPoId      = <?= json_encode((string) $po_id) ?>;
    var viSupplierPrev      = <?= json_encode((string) ($prev_supplier ?? '')) ?>;

    const supplierSelect = document.getElementById('select-supplier');

    supplierSelect.addEventListener('change', function () {

        if (this.value === '') return;

        var supplierUpdateUrl = 'update-supplier-info.php?' + new URLSearchParams({
            unique_key: viSupplierUniqueKey,
            po_id: viSupplierPoId,
            supplier: viSupplierPrev
        }).toString();

        fetch(supplierUpdateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'select_supplier=' + encodeURIComponent(this.value)
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);

            Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: 'Supplier has been updated. Approval is still required to take effect. please press "Done" button to complete this transaction.',
                timer: 5000,
                showConfirmButton: false
            });
        })
        .catch(error => {
            console.error(error);

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Unable to update supplier.'
            });
        });

    });

};
</script>
