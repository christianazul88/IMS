<?php
/*
 * Dashboard layout and loader.
 *
 * The legacy layout is kept in content-legacy.php for rollback/reference.
 * The inventory section below intentionally remains a direct include of
 * inventory.php. Other expensive, server-rendered cards are loaded on demand
 * through dashboard-widget.php so the first dashboard response is smaller and
 * the cards can render in parallel.
 */
$hour = (int) date('G');
if ($hour >= 5 && $hour < 12) {
    $time_name = 'Morning';
} elseif ($hour >= 12 && $hour < 17) {
    $time_name = 'Afternoon';
} elseif ($hour >= 17 && $hour < 21) {
    $time_name = 'Evening';
} else {
    $time_name = 'Midnight';
}

$user_warehouse_ids = array_values(array_filter(array_map(
    'trim',
    (array) ($user_warehouse_ids ?? [])
), static fn($id) => $id !== ''));

// Keep the warehouse scope used by inventory.php exactly as the existing
// dashboard did. A selected warehouse is accepted only when it belongs to the
// current user's accessible warehouse list.
$requested_dashboard_wh = trim((string) ($_GET['wh'] ?? ''));
$dashboard_wh = $requested_dashboard_wh !== ''
    && in_array($requested_dashboard_wh, $user_warehouse_ids, true)
    ? $requested_dashboard_wh
    : '';
$dashboard_heading = $dashboard_wh !== ''
    ? (string) ($_GET['wha'] ?? 'Selected Warehouse')
    : 'All Accessible Warehouse';

$quoted_warehouse_ids = array_map(
    static fn($id) => "'" . trim($id) . "'",
    $user_warehouse_ids
);
$imploded_warehouse_ids = $quoted_warehouse_ids
    ? implode(',', $quoted_warehouse_ids)
    : "''";

// inventory.php expects this pre-quoted list from on_session.php.
$user_warehouse_id = $imploded_warehouse_ids;

$dashboard_widget_slot = static function (string $widget, string $label): void {
    $safe_widget = htmlspecialchars($widget, ENT_QUOTES, 'UTF-8');
    $safe_label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<div class="dashboard-widget-slot" data-dashboard-widget="{$safe_widget}" aria-live="polite">
    <div class="card border-0 shadow-sm">
        <div class="card-body py-4 text-center text-600">
            <span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>
            <span>{$safe_label}</span>
        </div>
    </div>
</div>
HTML;
};
?>

<style>
    /* Dashboard-only visual system. Falcon supplies the base theme; these
       scoped rules add a calmer hierarchy without changing inventory.php. */
    body:has(#ims-dashboard) {
        overflow-x: hidden;
    }

    .content:has(#ims-dashboard) {
        overflow-x: hidden;
    }

    #ims-dashboard {
        --ims-primary: #2c7be5;
        --ims-ink: #344050;
        --ims-muted: #748194;
        --ims-border: rgba(39, 55, 72, .10);
        --ims-surface: #fff;
        color: var(--ims-ink);
        font-size: .875rem;
        overflow-x: clip;
    }

    #ims-dashboard .ims-page-header {
        align-items: center;
        margin-bottom: 1.25rem;
        padding: 1.35rem 1.5rem;
        border: 1px solid var(--ims-border);
        border-radius: 1rem;
        background: linear-gradient(120deg, #fff 0%, #f8fafd 100%);
        box-shadow: 0 .35rem 1.2rem rgba(39, 55, 72, .06);
    }

    #ims-dashboard .ims-page-eyebrow,
    #ims-dashboard .ims-section-eyebrow {
        margin-bottom: .35rem;
        color: var(--ims-primary);
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .12em;
        line-height: 1.2;
        text-transform: uppercase;
    }

    #ims-dashboard .ims-page-title {
        margin: 0;
        color: var(--ims-ink);
        font-size: clamp(1.35rem, 2.2vw, 1.85rem);
        font-weight: 700;
        letter-spacing: -.02em;
    }

    #ims-dashboard .ims-page-subtitle {
        max-width: 42rem;
        margin: .45rem 0 0;
        color: var(--ims-muted);
        font-size: .82rem;
    }

    #ims-dashboard .ims-warehouse-label {
        display: block;
        margin-bottom: .4rem;
        color: var(--ims-muted);
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    #ims-dashboard .ims-warehouse-button {
        min-width: 11rem;
        border: 1px solid rgba(44, 123, 229, .25);
        border-radius: .55rem;
        background: rgba(44, 123, 229, .08);
        color: var(--ims-primary);
        font-size: .8rem;
        font-weight: 600;
    }

    #ims-dashboard .ims-warehouse-button:hover,
    #ims-dashboard .ims-warehouse-button:focus {
        border-color: var(--ims-primary);
        background: rgba(44, 123, 229, .14);
        color: #1d5fb5;
    }

    #ims-dashboard .card,
    #ims-dashboard .accordion-item {
        border-color: var(--ims-border);
        border-radius: .85rem;
        box-shadow: 0 .25rem .9rem rgba(39, 55, 72, .045);
    }

    #ims-dashboard .accordion-item {
        overflow: hidden;
        background: var(--ims-surface);
    }

    #ims-dashboard .accordion-button {
        min-height: 3rem;
        padding: .8rem 1rem;
        color: var(--ims-ink);
        font-size: .82rem;
        font-weight: 600;
    }

    #ims-dashboard .accordion-button:not(.collapsed) {
        background: rgba(44, 123, 229, .045);
        color: var(--ims-primary);
        box-shadow: inset 0 -1px 0 var(--ims-border);
    }

    #ims-dashboard .dashboard-widget-slot > .card {
        min-height: 5.5rem;
    }

    @media (max-width: 767.98px) {
        #ims-dashboard .ims-page-header {
            padding: 1rem;
        }

        #ims-dashboard .ims-page-subtitle {
            font-size: .78rem;
        }

        #ims-dashboard .ims-warehouse-button {
            width: 100%;
        }
    }
</style>

<div
    id="ims-dashboard"
    data-widget-endpoint="dashboard-widget.php"
    data-warehouse="<?= htmlspecialchars($dashboard_wh, ENT_QUOTES, 'UTF-8') ?>"
>
    <div class="row g-3 mb-3">
        <div class="col-xxl-12">
            <div class="row g-3 ims-page-header">
                <div class="col-lg-7">
                    <div class="ims-page-eyebrow"><i class="fas fa-chart-pie me-1" aria-hidden="true"></i>Operations overview</div>
                    <h1 class="ims-page-title"><?= htmlspecialchars($dashboard_heading, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="ims-page-subtitle">Track revenue, stock movement, and warehouse health from one focused view.</p>
                </div>
                <div class="col-lg-5 text-lg-end">
                    <span class="ims-warehouse-label"><i class="fas fa-warehouse me-1" aria-hidden="true"></i>Warehouse scope</span>
                    <div class="btn-group">
                        <button class="btn dropdown-toggle ims-warehouse-button" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-layer-group me-1" aria-hidden="true"></i>Select Warehouse
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="../dashboard/">All</a>
                            <?php foreach (($warehouse_dropdow_dashboard ?? []) as $link_dashboard): ?>
                                <?= $link_dashboard ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($user_position_name === 'Superadmin'
                || strpos((string) $access, 'revenue_summary') !== false): ?>
                <div class="row g-3 mt-3">
                    <?php include 'revenue_tracker.php'; ?>
                </div>
            <?php endif; ?>

            <?php if ($user_position_name === 'Superadmin'
                || strpos((string) $access, 'outbound_safety_available') !== false
                || strpos((string) $access, 'under_safety') !== false): ?>
                <div class="row g-3 mt-3">
                    <div class="col-12">
                        <?php $dashboard_widget_slot('inventory_health', 'Loading inventory health…'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-xxl-6 col-xl-12">
            <div class="row g-3">
                <?php if ($user_position_name === 'Superadmin'
                    || strpos((string) $access, 'outbound_safety_available') !== false): ?>
                    <div class="col-12">
                        <?php include 'outbound.php'; ?>
                    </div>
                <?php endif; ?>

                <?php if ($user_position_name === 'Superadmin'
                    || strpos((string) $access, 'fast_moving_product') !== false): ?>
                    <div class="col-lg-12">
                        <?php include 'fast_moving_product.php'; ?>
                    </div>
                <?php endif; ?>

                <?php if ($user_position_name === 'Superadmin'
                    || strpos((string) $access, 'fast_slow_category') !== false): ?>
                    <div class="col-lg-12">
                        <?php include 'fast_moving_category.php'; ?>
                    </div>
                <?php endif; ?>

                <?php if ($user_position_name === 'Superadmin'
                    || strpos((string) $access, 'stock_summary') !== false): ?>
                    <div class="col-lg-12">
                        <?php include 'stocks_summary.php'; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-xxl-6 col-xl-12">
            <div class="row">
                <?php if ($user_position_name === 'Superadmin'
                    || strpos((string) $access, 'return_summary') !== false): ?>
                    <div class="col-lg-12">
                        <?php include 'return_summary.php'; ?>
                    </div>
                <?php endif; ?>

                <?php if ($user_position_name === 'Superadmin'
                    || strpos((string) $access, 'incoming_stocks') !== false): ?>
                    <div class="col-lg-12 mb-3">
                        <?php include 'incoming_stocks.php'; ?>
                    </div>
                <?php endif; ?>

                <?php if ($user_position_name === 'Superadmin'
                    || strpos((string) $access, 'fast_slow_category') !== false): ?>
                    <div class="col-lg-12">
                        <?php include 'slow_moving_category.php'; ?>
                    </div>
                <?php endif; ?>

                <?php if ($user_position_name === 'Superadmin'
                    || strpos((string) $access, 'promotion') !== false): ?>
                    <div class="col-lg-12 mb-3">
                        <?php $dashboard_widget_slot('promotion', 'Loading promotion insights…'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($user_position_name === 'Superadmin'
                    || strpos((string) $access, 'under_safety') !== false): ?>
                    <div class="col-lg-12 mb-3">
                        <?php $dashboard_widget_slot('under_safety', 'Loading under-safety items…'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($user_position_name === 'Superadmin'
                    || strpos((string) $access, 'revenue_drop') !== false): ?>
                    <div class="col-lg-12 mb-3">
                        <?php $dashboard_widget_slot('revenue_dropping', 'Loading revenue trends…'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($user_position_name === 'Superadmin'
                    || strpos((string) $access, 'inbound_outbound') !== false): ?>
                    <div class="col-lg-12">
                        <?php include 'inbound_outbound.php'; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($user_position_name === 'Superadmin'
            || strpos((string) $access, 'dashboard_inventory') !== false): ?>
            <div class="col-xxl-12 col-xl-12">
                <?php include 'inventory.php'; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// A few legacy dashboard modules mark their accordion button as expanded but
// omit the panel's Bootstrap `show` class. Normalize that mismatch after the
// complete page (including Bootstrap) is ready, and emit the same event that
// a normal user click would emit so existing lazy loaders still run.
(function () {
    function normalizeExpandedAccordions() {
        document.querySelectorAll('#ims-dashboard [data-bs-toggle="collapse"][aria-expanded="true"]').forEach(function (button) {
            var selector = button.getAttribute('data-bs-target');
            if (!selector) return;

            var panel = document.querySelector(selector);
            if (!panel || panel.classList.contains('show')) return;

            button.classList.remove('collapsed');
            panel.classList.add('show');
            panel.dispatchEvent(new Event('shown.bs.collapse', { bubbles: true }));
        });
    }

    window.imsNormalizeExpandedAccordions = normalizeExpandedAccordions;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', normalizeExpandedAccordions);
    } else {
        normalizeExpandedAccordions();
    }
})();
</script>

<script>
(function () {
    'use strict';

    var root = document.getElementById('ims-dashboard');
    if (!root) return;

    var endpoint = root.dataset.widgetEndpoint;
    var warehouse = root.dataset.warehouse || '';
    var slots = Array.prototype.slice.call(root.querySelectorAll('[data-dashboard-widget]'));

    function widgetUrl(widget) {
        var params = new URLSearchParams({ widget: widget });
        if (warehouse) params.set('wh', warehouse);
        return endpoint + '?' + params.toString();
    }

    function showWidgetError(slot) {
        slot.innerHTML = '<div class="card border-0 shadow-sm"><div class="card-body py-4 text-center text-danger">Unable to load this dashboard section. <button type="button" class="btn btn-link btn-sm p-0 dashboard-widget-retry">Retry</button></div></div>';
        var retry = slot.querySelector('.dashboard-widget-retry');
        if (retry) retry.addEventListener('click', function () { loadSlot(slot); });
    }

    function loadSlot(slot) {
        if (slot.dataset.loading === 'true' || slot.dataset.loaded === 'true') return;

        var widget = slot.dataset.dashboardWidget;
        if (!widget || !window.jQuery) return;

        slot.dataset.loading = 'true';
        window.jQuery(slot).load(widgetUrl(widget), function (_response, status) {
            delete slot.dataset.loading;
            if (status === 'error') {
                showWidgetError(slot);
                return;
            }
            slot.dataset.loaded = 'true';
            if (window.imsNormalizeExpandedAccordions) {
                window.imsNormalizeExpandedAccordions();
            }
        });
    }

    // Load first-screen cards immediately. Below-the-fold SQL-heavy cards are
    // requested only as they approach the viewport.
    var eagerWidgets = ['inventory_health'];
    slots.forEach(function (slot) {
        if (eagerWidgets.indexOf(slot.dataset.dashboardWidget) !== -1) loadSlot(slot);
    });

    var deferredSlots = slots.filter(function (slot) {
        return eagerWidgets.indexOf(slot.dataset.dashboardWidget) === -1;
    });

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                loadSlot(entry.target);
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '320px 0px' });
        deferredSlots.forEach(function (slot) { observer.observe(slot); });

        // A large scroll jump can skip the observer's intersection window.
        // Check after layout and on scroll so those sections still load when
        // the user lands below them through a hash link or restored position.
        var checkDeferredSlots = function () {
            deferredSlots.forEach(function (slot) {
                var rect = slot.getBoundingClientRect();
                if (rect.top < window.innerHeight + 320) loadSlot(slot);
            });
        };
        window.setTimeout(checkDeferredSlots, 0);
        var scrollCheckQueued = false;
        window.addEventListener('scroll', function () {
            if (scrollCheckQueued) return;
            scrollCheckQueued = true;
            window.requestAnimationFrame(function () {
                scrollCheckQueued = false;
                checkDeferredSlots();
            });
        }, { passive: true });
    } else {
        deferredSlots.forEach(loadSlot);
    }
})();
</script>

<script>
// Keep the existing fast-moving-product warehouse preview behavior without
// changing inventory.php or its own lazy-loaded detail requests.
(function () {
    function loadWarehousePreview(warehouse) {
        if (!warehouse || !window.jQuery) return;
        var target = window.jQuery('#dashboard-wh-preview');
        if (!target.length) return;
        window.jQuery('#dashboard-wh-preview-spinner').show();
        target.load('dashboard-wh-preview.php', { warehouse: warehouse }, function (_response, status) {
            if (status === 'error') {
                target.html('<div class="text-danger">Failed to load preview.</div>');
            }
            window.jQuery('#dashboard-wh-preview-spinner').hide();
        });
    }

    function initWarehousePreview() {
        var selector = window.jQuery ? window.jQuery('#dashboard-wh') : null;
        if (!selector || !selector.length) return;
        loadWarehousePreview(selector.val());
        selector.off('change.dashboardPreview').on('change.dashboardPreview', function () {
            loadWarehousePreview(this.value);
        });
    }

    if (window.jQuery) {
        window.jQuery(initWarehousePreview);
    }
})();
</script>
