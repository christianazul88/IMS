<?php

$po_id      = $_GET['po_no'] ?? 2197;
$unique_key = $_GET['unique_key'] ?? '163067390281';

// --- Fetch products on this PO ---
$purchased_order_query = "SELECT 
                            poc.product_id, 
                            poc.qty,
                            p.description,
                            p.parent_barcode,
                            b.brand_name,
                            c.category_name
                        FROM purchased_order_content poc 
                        LEFT JOIN product p ON poc.product_id = p.hashed_id
                        LEFT JOIN brand b ON p.brand = b.hashed_id
                        LEFT JOIN category c ON p.category = c.hashed_id
                        WHERE poc.po_id = ?";

$stmt = $conn->prepare($purchased_order_query);
$stmt->bind_param("i", $po_id);
$stmt->execute();
$purchased_order_result = $stmt->get_result();

$products = [];

if ($purchased_order_result && $purchased_order_result->num_rows > 0) {
    while ($row = $purchased_order_result->fetch_assoc()) {
        $product_id = $row['product_id'];

        // --- Fetch stock sequences for this product under this inbound ---
        $stocks_query = "SELECT s.unique_barcode, s.capital, s.item_status 
                          FROM stocks s 
                          WHERE s.unique_key = ? AND s.product_id = ?";
        $stock_stmt = $conn->prepare($stocks_query);
        $stock_stmt->bind_param("ss", $unique_key, $product_id);
        $stock_stmt->execute();
        $stocks_result = $stock_stmt->get_result();

        $stocks = [];
        while ($stock_row = $stocks_result->fetch_assoc()) {
            $stocks[] = $stock_row;
        }
        $stock_stmt->close();

        $row['stocks'] = $stocks;
        $products[] = $row;
    }
}
$stmt->close();

$total_products = count($products);
$total_stocks    = array_sum(array_map(fn($p) => count($p['stocks']), $products));
?>

<div class="void-inbound container-fluid py-4">

    <!-- Header -->
    <div class="vi-panel vi-header mb-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

            <div>
                <div class="vi-eyebrow">Inventory / Inbound</div>
                <h1 class="vi-title">Void Inbound Stocks</h1>

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

            <button class="vi-btn vi-btn-danger vi-btn-lg"
                    data-po-id="<?= htmlspecialchars($po_id) ?>"
                    data-unique-key="<?= htmlspecialchars($unique_key) ?>">
                <i class="bi bi-trash3"></i>
                Void Entire Inbound
            </button>

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

                                <span class="vi-badge vi-badge-qty">Qty Ordered <?= htmlspecialchars($product['qty']) ?></span>

                            </div>

                        </button>

                    </h2>

                    <div id="<?= $accordion_id ?>"
                         class="accordion-collapse collapse <?= $is_first ? 'show' : '' ?>">

                        <div class="accordion-body vi-body">

                            <div class="d-flex justify-content-between align-items-center mb-2">

                                <div class="vi-subhead">Stock Sequences</div>

                                <button class="vi-btn vi-btn-outline-danger vi-btn-sm"
                                        data-product-id="<?= htmlspecialchars($product['product_id']) ?>"
                                        data-parent-barcode="<?= htmlspecialchars($product['parent_barcode'] ?? '') ?>"
                                        data-unique-key="<?= htmlspecialchars($unique_key) ?>"
                                        <?= $stock_count === 0 ? 'disabled' : '' ?>>
                                    <i class="bi bi-trash"></i>
                                    Void All Sequences
                                </button>

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

                                        <?php if ($stock_count === 0): ?>

                                            <tr>
                                                <td colspan="4" class="vi-empty-row">
                                                    No stocks found for this product under Inbound Ref # <?= htmlspecialchars($unique_key) ?>
                                                </td>
                                            </tr>

                                        <?php else: ?>

                                            <?php foreach ($stocks as $stock): ?>

                                                <?php
                                                    $status = $stock['item_status'] ?? 'Unknown';
                                                    $status_class = match (strtolower($status)) {
                                                        'active'  => 'vi-badge-active',
                                                        'sold'    => 'vi-badge-sold',
                                                        'voided'  => 'vi-badge-voided',
                                                        default   => 'vi-badge-default',
                                                    };
                                                ?>

                                                <tr>

                                                    <td><span class="vi-code vi-code-row"><?= htmlspecialchars($stock['unique_barcode']) ?></span></td>

                                                    <td class="vi-capital">&#8369;<?= number_format((float) $stock['capital'], 2) ?></td>

                                                    <td>
                                                        <span class="vi-badge <?= $status_class ?>"><?= htmlspecialchars($status) ?></span>
                                                    </td>

                                                    <td>
                                                        <button class="vi-btn vi-btn-outline-danger vi-btn-xs"
                                                                data-barcode="<?= htmlspecialchars($stock['unique_barcode']) ?>">
                                                            <i class="bi bi-trash"></i>
                                                            Void
                                                        </button>
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
                badge.classList.remove("vi-badge-active", "vi-badge-sold", "vi-badge-voided", "vi-badge-default");
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

    // ---- 1. Void Entire Inbound ---------------------------------------

    var voidEntireBtn = document.querySelector(".vi-btn-danger[data-po-id]");
    if (voidEntireBtn) {
        voidEntireBtn.addEventListener("click", function () {
            openConfirm({
                title: "Void Entire Inbound",
                message: "Are you sure you want to void the entire inbound (Ref # " + uniqueKey + ")? This cannot be undone."
            }).then(function (result) {
                if (!result.confirmed) return;

                setAllButtonsDisabled(true);
                setButtonLoading(voidEntireBtn, true);

                callVoidFunction({
                    "void-inbound": "inbound",
                    po_id: poId,
                    unique_key: uniqueKey
                }).then(function () {
                    document.querySelectorAll("[data-barcode]").forEach(function (b) {
                        markBarcodeVoided(b);
                    });
                    showResult({
                        title: "Success",
                        message: "The entire inbound has been voided.",
                        success: true,
                        onClose: function () {
                            window.location.href = "inbound.php";
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
                        message: "This void request has been marked as done.",
                        success: true
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
})();
</script>