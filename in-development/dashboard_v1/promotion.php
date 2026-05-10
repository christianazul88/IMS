<?php 
$promotion_data = [];

if (!isset($conn)) {
    die("Database connection not available.");
}

if (!empty($_GET['wh'])) {
    $warehouse_dashboard_id = $_GET['wh'];

    // Use prepared statement
    $stmt = $conn->prepare("
        SELECT 
            p.description,
            p.product_img,
            b.brand_name,
            c.category_name,
            COUNT(oc.unique_barcode) AS total_outbound
        FROM product p
        LEFT JOIN brand b ON b.hashed_id = p.brand
        LEFT JOIN category c ON c.hashed_id = p.category
        LEFT JOIN stocks s ON s.product_id = p.hashed_id
        LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
        WHERE oc.status = 0
            AND (s.batch_code IS NOT NULL AND s.batch_code != '-')
            AND ol.date_sent >= DATE_FORMAT(NOW(), '%Y-%m-01')
            AND ol.date_sent < DATE_ADD(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 1 MONTH)
            AND s.warehouse = ?
        GROUP BY p.description
        ORDER BY total_outbound ASC
        LIMIT 10
    ");
    $stmt->bind_param("s", $warehouse_dashboard_id);
    $stmt->execute();
    $promotion_product_res = $stmt->get_result();

} else {
    $warehouse_list_raw = explode(',', $_SESSION['warehouse_ids'] ?? '');

    if (empty($warehouse_list_raw)) {
        die("No warehouses provided.");
    }

    // Sanitize each warehouse ID and build placeholders
    $warehouse_list = [];
    $placeholders = [];
    foreach ($warehouse_list_raw as $index => $warehouse) {
        $warehouse = trim($warehouse);
        if (!empty($warehouse)) {
            $warehouse_list[] = $warehouse;
            $placeholders[] = '?';
        }
    }

    if (empty($warehouse_list)) {
        die("Invalid warehouse list.");
    }

    $placeholders_str = implode(',', $placeholders);

    $query = "
        SELECT 
            p.description,
            p.product_img,
            b.brand_name,
            c.category_name,
            COUNT(oc.unique_barcode) AS total_outbound
        FROM product p
        LEFT JOIN brand b ON b.hashed_id = p.brand
        LEFT JOIN category c ON c.hashed_id = p.category
        LEFT JOIN stocks s ON s.product_id = p.hashed_id
        LEFT JOIN outbound_content oc ON oc.unique_barcode = s.unique_barcode
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
        WHERE s.warehouse IN ($placeholders_str)
            AND oc.status = 0
            AND (s.batch_code IS NOT NULL AND s.batch_code != '-')
            AND ol.date_sent >= DATE_FORMAT(NOW(), '%Y-%m-01')
            AND ol.date_sent < DATE_ADD(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 1 MONTH)
        GROUP BY p.description
        ORDER BY total_outbound ASC
        LIMIT 10
    ";

    $stmt = $conn->prepare($query);
    $types = str_repeat('s', count($warehouse_list));
    $stmt->bind_param($types, ...$warehouse_list);
    $stmt->execute();
    $promotion_product_res = $stmt->get_result();
}

if ($promotion_product_res && $promotion_product_res->num_rows > 0) {
    while ($row = $promotion_product_res->fetch_assoc()) {
        $promotion_item = htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8');
        $promotion_item_img = !empty($row['product_img']) ? basename($row['product_img']) : 'def_img.png';
        $promotion_brand = htmlspecialchars($row['brand_name'], ENT_QUOTES, 'UTF-8');
        $promotion_category = htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8');
        $promotion_total_outbound = number_format((int)$row['total_outbound']);

        $promotion_data[] = '
            <tr>
                <td><img class="img" src="../../assets/img/' . htmlspecialchars($promotion_item_img, ENT_QUOTES, 'UTF-8') . '" style="height: 30px; width: 30px;" alt=""></td>
                <td>' . $promotion_item . '</td>
                <td>' . $promotion_brand . '</td>
                <td>' . $promotion_category . '</td>
                <td class="text-end">' . $promotion_total_outbound . '</td>
            </tr>';
    }
}
?>

<div class="card">
    <div class="card-header">
        <h6><a href="../Promotions/?wh=<?php echo $dashboard_wh;?>">For Promotion</a> <?php echo htmlspecialchars($date_today ?? '', ENT_QUOTES, 'UTF-8'); ?></h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0 data-table fs-10" data-datatables='{"paging":false,"scrollY":"300px","scrollCollapse":true}'>
                <thead class="bg-warning">
                    <tr>
                        <th></th>
                        <th>Description</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th class="text-end">Total Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($promotion_data as $promotion_display) {
                        echo $promotion_display;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
