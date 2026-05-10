<?php 
include "../config/database.php";
include "../config/on_session.php";

$hour = date("H");
if ($hour >= 5 && $hour < 12) $time_name = "Morning";
elseif ($hour >= 12 && $hour < 17) $time_name = "Afternoon";
elseif ($hour >= 17 && $hour < 21) $time_name = "Evening";
else $time_name = "Midnight";

$quoted_warehouse_ids = array_map(function ($id) {
    return "'" . trim($id) . "'";
}, $user_warehouse_ids);
$imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);

$dashboard_wh = $_GET['wh'] ?? "";
$data = [];

$category_sql = "SELECT * FROM category";
$category_res = $conn->query($category_sql);

if ($category_res && $category_res->num_rows > 0) {
    while ($row = $category_res->fetch_assoc()) {
        $category_id = $row['hashed_id'];
        $category_name = $row['category_name'];
        $outbounded = 0;

        $condition = empty($dashboard_wh) ? 
            "ol.warehouse IN ($imploded_warehouse_ids)" : 
            "ol.warehouse = '" . $conn->real_escape_string($dashboard_wh) . "'";

        $query = "
            SELECT COUNT(DISTINCT oc.unique_barcode) AS total_outbound
            FROM outbound_content oc
            LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
            LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
            LEFT JOIN product p ON p.hashed_id = s.product_id
            WHERE oc.status IN (0, 6)
              AND p.category = '$category_id'
              AND ol.date_sent >= DATE_FORMAT(NOW(), '%Y-%m-01')
              AND ol.date_sent < DATE_ADD(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 1 MONTH)
              AND $condition
        ";

        $res = $conn->query($query);
        if ($res && $res->num_rows > 0) {
            $out = $res->fetch_assoc();
            $outbounded = $out['total_outbound'] ?? 0;
        }

        $data[] = [
            'category_name' => $category_name,
            'outbounded' => $outbounded
        ];
    }
}

usort($data, function ($a, $b) {
    return $a['outbounded'] - $b['outbounded'];
});
$top_10_slow = array_slice($data, 0, 10);
?>

<div class="card h-100">
  <div class="card-header">
    <h6 class="mb-0">Top 10 Slow Moving (Category)</h6>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover fs-10">
        <thead>
          <tr>
            <th>Category</th>
            <th class="text-end">Outbounded</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($top_10_slow)): ?>
            <?php foreach ($top_10_slow as $row): ?>
              <tr>
                <td>
                  <a href="../Category-Product/?type=slow&&cat=<?php echo urlencode($row['category_name']); ?>&wh=<?php echo htmlspecialchars($dashboard_wh); ?>">
                    <?php echo htmlspecialchars($row['category_name']); ?>
                  </a>
                </td>
                <td class="text-end"><?php echo number_format($row['outbounded']); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="2" class="text-center">No outbound data available this month.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
