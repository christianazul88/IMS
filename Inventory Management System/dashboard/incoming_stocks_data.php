<?php
include "../config/database.php";
include "../config/on_session.php";

$selectedWarehouse = trim($_GET['wh'] ?? '');
$allowedWarehouses = array_filter(array_map('trim', $user_warehouse_ids ?? []));
$quoted = array_map(function ($id) use ($conn) { return "'" . $conn->real_escape_string($id) . "'"; }, $allowedWarehouses);
$warehouseList = $quoted ? implode(',', $quoted) : "''";
if ($selectedWarehouse !== '' && in_array($selectedWarehouse, $allowedWarehouses, true)) {
    $stockWarehouse = "s.warehouse = '" . $conn->real_escape_string($selectedWarehouse) . "'";
    $poWarehouse = "po.warehouse = '" . $conn->real_escape_string($selectedWarehouse) . "'";
} elseif ($selectedWarehouse !== '') {
    $stockWarehouse = $poWarehouse = '1 = 0';
} else {
    $stockWarehouse = "s.warehouse IN ($warehouseList)";
    $poWarehouse = "po.warehouse IN ($warehouseList)";
}

$sql = "
 SELECT c.hashed_id, c.category_name, COALESCE(stock.qty, 0) AS stocks, COALESCE(incoming.qty, 0) AS incoming
 FROM category c
 LEFT JOIN (
   SELECT p.category, COUNT(s.unique_barcode) AS qty
   FROM stocks s INNER JOIN product p ON p.hashed_id = s.product_id
   WHERE s.item_status NOT IN (1, 4, 8) AND s.batch_code <> '-' AND $stockWarehouse
   GROUP BY p.category
 ) stock ON stock.category = c.hashed_id
 LEFT JOIN (
   SELECT p.category, SUM(poc.qty) AS qty
   FROM purchased_order_content poc
   INNER JOIN purchased_order po ON po.id = poc.po_id
   INNER JOIN product p ON p.hashed_id = poc.product_id
   WHERE po.status NOT IN (0, 4) AND $poWarehouse
   GROUP BY p.category
 ) incoming ON incoming.category = c.hashed_id
 ORDER BY c.category_name
 LIMIT 10
";
$rows = $conn->query($sql);
?>
<div class="row flex-between-center g-0"><div class="col-auto mb-3"><h6 class="mb-0"><a href="../incoming-stocks/?wh=<?php echo rawurlencode($selectedWarehouse); ?>">View Incoming Stocks</a></h6></div><div class="col-12"><div class="table-responsive"><table class="table mb-0 data-table fs-10"><thead><tr><th>Category</th><th class="text-end">Stocks</th><th class="text-end">Incoming Stocks</th></tr></thead><tbody>
<?php if ($rows): while ($row = $rows->fetch_assoc()): ?><tr><td><a href="../incoming-stocks/?name2=<?php echo rawurlencode($row['category_name']); ?>&name=<?php echo rawurlencode($row['hashed_id']); ?>&wh=<?php echo rawurlencode($selectedWarehouse); ?>"><?php echo htmlspecialchars($row['category_name']); ?></a></td><td class="text-end"><?php echo number_format((int) $row['stocks']); ?></td><td class="text-end"><?php echo number_format((int) $row['incoming']); ?></td></tr><?php endwhile; endif; ?>
</tbody></table></div></div></div>
