<?php 
if (isset($_GET['type']) && isset($_GET['cat'])) {
    $selected_type = $_GET['type'];
    $selected_category = $_GET['cat'];
    $selected_warehouse_id = $_GET['wh'] ?? '';

    // First day of the current month
    $startOfMonthDateTime = date('Y-m-01 00:00:00');
    $startOfMonthFormatted = date('M d, Y', strtotime($startOfMonthDateTime));

    // Current date and time
    $currentDateTime = date('Y-m-d H:i:s');
    $currentDateFormatted = date('M d, Y');

    // Ensure $user_warehouse_ids is set
    if (!isset($user_warehouse_ids) || !is_array($user_warehouse_ids)) {
        die("Warehouse access list missing.");
    }

    // Quote each warehouse ID
    $quoted_warehouse_ids = array_map(function ($id) {
        return "'" . trim($id) . "'";
    }, $user_warehouse_ids);
    $imploded_warehouse_ids = implode(",", $quoted_warehouse_ids);

    // Get category ID
    $category_query = "SELECT hashed_id FROM category WHERE category_name = ? LIMIT 1";
    $stmt = $conn->prepare($category_query);
    $stmt->bind_param("s", $selected_category);
    $stmt->execute();
    $category_res = $stmt->get_result();

    if ($category_res->num_rows > 0) {
        $row = $category_res->fetch_assoc();
        $category_id = $row['hashed_id'];
    } else {
        echo "
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Category Not Found',
                text: 'The selected category does not exist in the system.',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'dashboard.php';
                }
            });
        </script>";
        exit;
    }

    // Title setup
    switch($selected_type) {
        case 'fast':
            $title = "Fast Moving " . $selected_category;
            $paragraph = "from " . $startOfMonthFormatted . " to " . $currentDateFormatted;
            $bg = "bg-info";
            break;
        case 'slow':
            $title = "Slow Moving " . $selected_category;
            $paragraph = "from " . $startOfMonthFormatted . " to " . $currentDateFormatted;
            $bg = "bg-warning";
            break;
        default:
            die("Invalid type selected.");
    }

    // SQL for both fast and slow
    $date_filter = "
        AND ol.date_sent >= DATE_FORMAT(NOW(), '%Y-%m-01')
        AND ol.date_sent < DATE_ADD(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 1 MONTH)
    ";

    $warehouse_filter = empty($selected_warehouse_id) 
        ? "AND ol.warehouse IN ($imploded_warehouse_ids)" 
        : "AND ol.warehouse = '$selected_warehouse_id'";

    $outbound_check_sql = "
        SELECT 
            COUNT(DISTINCT oc.unique_barcode) AS total_outbound,
            SUM(CASE WHEN s.item_status IN (0, 2, 3) THEN 1 ELSE 0 END) AS total_available_qty,
            p.product_img,
            p.description,
            b.brand_name,
            w.warehouse_name
        FROM product p
        LEFT JOIN stocks s ON s.product_id = p.hashed_id
        LEFT JOIN outbound_content oc 
            ON oc.unique_barcode = s.unique_barcode AND oc.status IN (0, 6)
        LEFT JOIN outbound_logs ol 
            ON ol.hashed_id = oc.hashed_id
            $date_filter
            $warehouse_filter
        LEFT JOIN brand b ON b.hashed_id = p.brand
        LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
        WHERE p.category = '$category_id' 
          AND (s.batch_code IS NOT NULL AND s.batch_code != '-')
        GROUP BY p.description, b.brand_name, w.warehouse_name
    ";

    $moving_result = $conn->query($outbound_check_sql);
}
?>

<div class="card">
    <div class="card-header <?php echo $bg;?>">
        <h2 class="text-white"><?php echo $title; ?></h2>
        <p class="text-300"><?php echo $paragraph; ?></p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0 data-table fs-10" data-datatables='{"paging":false,"scrollY":"300px","scrollCollapse":true}'>
                <thead>
                    <th class="px-0" width="50"></th>
                    <th>Description</th>
                    <th>Brand</th>
                    <th>Warehouse</th>
                    <th>Outbounded / Paid</th>
                    <th>Stocks</th>
                </thead>
                <tbody>
                <?php 
                if ($moving_result && $moving_result->num_rows > 0) {
                    while ($row = $moving_result->fetch_assoc()) {
                        $total_outbound = intval($row['total_outbound']);

                        if ($selected_type === 'fast' && $total_outbound === 0) {
                            continue; // Skip 0-outbound in fast
                        }
                        $total_Available = $row['total_available_qty'];
                        $product_img = $row['product_img'] ?? 'def_img.png';
                        $description = $row['description'];
                        $brand_name = $row['brand_name'];
                        $warehouse_name = $row['warehouse_name'] ?? 'ALL ACCESSIBLE WAREHOUSE';

                        echo '<tr>
                            <td class="px-0">
                                <img src="../../assets/img/' . basename($product_img) . '" height="50" alt="">
                            </td>
                            <td>' . $description . '</td>
                            <td>' . $brand_name . '</td>
                            <td>' . $warehouse_name . '</td>
                            <td>' . $total_outbound . '</td>
                            <td>' . $total_Available . '</td>
                        </tr>';
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center'>No products found.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
