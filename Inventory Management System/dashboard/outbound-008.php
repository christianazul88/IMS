<?php
include "../config/database.php";
include "../config/on_session.php";

$hour = (int) date('H');
$timeName = $hour >= 5 && $hour < 12 ? 'Morning' : ($hour < 17 ? 'Afternoon' : ($hour < 21 ? 'Evening' : 'Midnight'));
$selectedWarehouse = trim($_GET['wh'] ?? '');
$allowedWarehouses = array_filter(array_map('trim', $user_warehouse_ids ?? []));

if ($selectedWarehouse !== '' && in_array($selectedWarehouse, $allowedWarehouses, true)) {
    $warehouseCondition = "s.warehouse = '" . $conn->real_escape_string($selectedWarehouse) . "'";
} elseif ($selectedWarehouse !== '') {
    $warehouseCondition = '1 = 0';
} else {
    $quotedWarehouses = array_map(function ($warehouse) use ($conn) {
        return "'" . $conn->real_escape_string($warehouse) . "'";
    }, $allowedWarehouses);
    $warehouseCondition = $quotedWarehouses ? 's.warehouse IN (' . implode(',', $quotedWarehouses) . ')' : '1 = 0';
}

// Set-based aggregates replace the former one-query-per-product loop.
$summaryQuery = "
    SELECT
        SUM(CASE WHEN s.`date` < CURDATE() - INTERVAL 3 MONTH THEN 1 ELSE 0 END) AS aged_units,
        SUM(CASE WHEN s.item_status = 0 THEN 1 ELSE 0 END) AS available_units
    FROM stocks s
    WHERE $warehouseCondition
";
$summary = $conn->query($summaryQuery)->fetch_assoc() ?: [];
$agedUnits = (int) ($summary['aged_units'] ?? 0);
$availableUnits = (int) ($summary['available_units'] ?? 0);

$lowStockQuery = "
    SELECT COUNT(*) AS under_safety FROM (
        SELECT s.product_id
        FROM stocks s
        LEFT JOIN product p ON p.hashed_id = s.product_id
        WHERE $warehouseCondition
        GROUP BY s.product_id
        HAVING SUM(s.item_status = 0) < MAX(p.safety)
    ) AS low_stock
";
$underSafety = (int) (($conn->query($lowStockQuery)->fetch_assoc() ?: [])['under_safety'] ?? 0);
?>
<div class="card bg-transparent-50 overflow-hidden">
  <div class="card-header position-relative">
    <div class="bg-holder d-none d-md-block bg-card z-1" style="background-image:url(../assets/img/illustrations/ecommerce-bg.png);background-size:230px;background-position:right bottom;z-index:-1;"></div>
    <div class="position-relative z-2"><h3 class="text-primary mb-1">Good <?php echo $timeName . ', ' . htmlspecialchars($user_fname); ?>!</h3><p>Here’s what is happening with your inventory today.</p></div>
  </div>
  <div class="card-body p-0"><ul class="mb-0 list-unstyled list-group font-sans-serif">
    <li class="list-group-item mb-0 rounded-0 py-3 px-x1 list-group-item-<?php echo $agedUnits > 0 ? 'warning' : 'success'; ?> border-x-0 border-top-0"><div class="row flex-between-center"><div class="col"><div class="d-flex"><div class="fas fa-circle mt-1 fs-11"></div><p class="fs-10 ps-2 mb-0"><strong><?php echo number_format($agedUnits); ?> products</strong> have been in inventory for more than 3 months.</p></div></div><?php if ($agedUnits > 0): ?><div class="col-auto"><a class="fs-10 fw-medium text-warning-emphasis" href="csv.php">View products <i class="fas fa-chevron-right ms-1 fs-11"></i></a></div><?php endif; ?></div></li>
    <li class="list-group-item mb-0 rounded-0 py-3 px-x1 list-group-item-<?php echo $underSafety > 0 ? 'danger' : 'success'; ?> greetings-item text-700 border-x-0 border-top-0"><div class="row flex-between-center"><div class="col"><div class="d-flex"><div class="fas fa-circle mt-1 fs-11 text-primary"></div><p class="fs-10 ps-2 mb-0"><strong><?php echo number_format($underSafety); ?> products</strong> are under safety level.</p></div></div><?php if ($underSafety > 0): ?><div class="col-auto"><a class="fs-10 fw-medium" href="../Inventory-stock/">View products <i class="fas fa-chevron-right ms-1 fs-11"></i></a></div><?php endif; ?></div></li>
    <li class="list-group-item mb-0 rounded-0 py-3 px-x1 greetings-item text-700 border-0"><div class="row flex-between-center"><div class="col"><div class="d-flex"><div class="fas fa-circle mt-1 fs-11 text-primary"></div><p class="fs-10 ps-2 mb-0"><strong><?php echo number_format($availableUnits); ?> products</strong> are available in your warehouse<?php echo $selectedWarehouse === '' ? 's' : ''; ?>.</p></div></div></div></li>
  </ul></div>
</div>
