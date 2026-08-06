<?php
// Compact, first-screen inventory signals. Detailed tables remain available below.
$safeWarehouseIds = array_filter(array_map(function ($id) use ($conn) {
    return "'" . mysqli_real_escape_string($conn, trim($id)) . "'";
}, $user_warehouse_ids));
$selectedWarehouse = mysqli_real_escape_string($conn, (string) $dashboard_wh);
$warehouseCondition = $selectedWarehouse !== ''
    ? "s.warehouse = '$selectedWarehouse'"
    : (!empty($safeWarehouseIds) ? 's.warehouse IN (' . implode(',', $safeWarehouseIds) . ')' : '1 = 0');

$healthQuery = "
    SELECT
        COUNT(CASE WHEN s.item_status IN (0, 3) THEN s.unique_barcode END) AS available_units,
        COUNT(CASE WHEN s.item_status IN (0, 3) AND DATE(s.date) <= DATE_SUB(CURDATE(), INTERVAL 3 MONTH) THEN s.unique_barcode END) AS aged_units,
        COUNT(DISTINCT CASE WHEN s.item_status IN (0, 3) AND COALESCE(NULLIF(s.safety, 0), NULLIF(p.safety, 0)) > 0 THEN CONCAT(s.product_id, ':', s.warehouse) END) AS tracked_skus
    FROM stocks s
    LEFT JOIN product p ON p.hashed_id = s.product_id
    WHERE $warehouseCondition
";
$health = $conn->query($healthQuery)->fetch_assoc() ?: [];

$lowStockQuery = "
    SELECT COUNT(*) AS low_stock_skus FROM (
        SELECT s.product_id, s.warehouse
        FROM stocks s
        LEFT JOIN product p ON p.hashed_id = s.product_id
        WHERE $warehouseCondition AND s.item_status IN (0, 3)
        GROUP BY s.product_id, s.warehouse
        HAVING COUNT(s.unique_barcode) < MAX(COALESCE(NULLIF(s.safety, 0), NULLIF(p.safety, 0)))
    ) low_stock
";
$lowStock = $conn->query($lowStockQuery)->fetch_assoc()['low_stock_skus'] ?? 0;
?>
<div class="row g-3 mb-3 mt-3" aria-label="Inventory health summary">
  <div class="col-md-4">
    <div class="card h-100 border-start border-4 border-primary"><div class="card-body py-3"><div class="text-600 fs-10">Available units</div><div class="fs-5 fw-semibold"><?php echo number_format($health['available_units'] ?? 0); ?></div></div></div>
  </div>
  <div class="col-md-4">
    <div class="card h-100 border-start border-4 border-<?php echo $lowStock > 0 ? 'danger' : 'success'; ?>"><div class="card-body py-3"><div class="text-600 fs-10">Low-stock SKUs</div><div class="fs-5 fw-semibold"><?php echo number_format($lowStock); ?></div><a class="fs-10" href="#undersafetyitems">Review shortages</a></div></div>
  </div>
  <div class="col-md-4">
    <div class="card h-100 border-start border-4 border-<?php echo ($health['aged_units'] ?? 0) > 0 ? 'warning' : 'success'; ?>"><div class="card-body py-3"><div class="text-600 fs-10">Stock aged 90+ days</div><div class="fs-5 fw-semibold"><?php echo number_format($health['aged_units'] ?? 0); ?></div><a class="fs-10" href="csv.php">Review aged stock</a></div></div>
  </div>
</div>
