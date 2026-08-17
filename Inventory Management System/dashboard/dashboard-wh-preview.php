<?php
include "../config/database.php";
include "../config/on_session.php";

$warehouseId = trim($_GET['warehouse'] ?? '');
$allowedWarehouses = array_filter(array_map('trim', $user_warehouse_ids ?? []));
$products = [];

if ($warehouseId !== '' && in_array($warehouseId, $allowedWarehouses, true)) {
    $warehouse = $conn->real_escape_string($warehouseId);
    // One aggregation replaces the old log -> content -> stock nested queries.
    $sql = "
        SELECT p.hashed_id AS product_id, p.description, b.brand_name, c.category_name,
               COUNT(DISTINCT oc.unique_barcode) AS outbounded,
               COALESCE(stock.available_stock, 0) AS available_stock
        FROM outbound_logs ol
        INNER JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id
        INNER JOIN stocks sold_stock ON sold_stock.unique_barcode = oc.unique_barcode
        INNER JOIN product p ON p.hashed_id = sold_stock.product_id
        LEFT JOIN brand b ON b.hashed_id = p.brand
        LEFT JOIN category c ON c.hashed_id = p.category
        LEFT JOIN (
            SELECT product_id, COUNT(*) AS available_stock
            FROM stocks
            WHERE item_status = 0 AND warehouse = '$warehouse'
            GROUP BY product_id
        ) stock ON stock.product_id = p.hashed_id
        WHERE ol.warehouse = '$warehouse'
          AND ol.date_sent >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
          AND ol.date_sent < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
          AND oc.status IN (0, 6)
        GROUP BY p.hashed_id, p.description, b.brand_name, c.category_name, stock.available_stock
        ORDER BY outbounded DESC
        LIMIT 10
    ";
    $result = $conn->query($sql);
    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<div class="table-responsive scrollbar">
  <table class="table table-dashboard mb-0 table-borderless fs-10 border-200">
    <thead class="bg-body-tertiary"><tr><th class="text-900">Fast Moving Products This Month</th><th class="text-900 text-center">Outbounded</th><th class="text-900 text-center">Stocks</th><th class="text-900 pe-x1 text-end" style="width:8rem">Outbound (%)</th></tr></thead>
    <tbody>
    <?php if (!$products): ?><tr><td colspan="4" class="text-center py-3 text-600">No outbound data available this month.</td></tr>
    <?php else: foreach ($products as $product):
        $outbounded = (int) $product['outbounded'];
        $stock = (int) $product['available_stock'];
        $percentage = $stock > 0 ? min(100, round(($outbounded / $stock) * 100, 2)) : 100;
    ?>
      <tr class="border-bottom border-200"><td><div class="flex-1 ms-3"><h6 class="mb-1 fw-semi-bold text-nowrap"><?php echo htmlspecialchars(($product['brand_name'] ?? '') . ': ' . ($product['description'] ?? '')); ?></h6><p class="fw-semi-bold mb-0 text-500"><?php echo htmlspecialchars($product['category_name'] ?? ''); ?></p></div></td><td class="align-middle text-center fw-semi-bold"><?php echo number_format($outbounded); ?></td><td class="align-middle text-center fw-semi-bold"><?php echo number_format($stock); ?></td><td class="align-middle pe-x1"><div class="d-flex align-items-center"><div class="progress me-3 rounded-3 bg-200" style="height:5px;width:80px"><div class="progress-bar bg-primary rounded-pill" style="width:<?php echo $percentage; ?>%"></div></div><div class="fw-semi-bold ms-2"><?php echo $percentage; ?>%</div></div></td></tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
