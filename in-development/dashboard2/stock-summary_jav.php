<?php 
include "../config/database.php";
include "../config/on_session.php";

// Determine the time of day
$hour = date("H");
$time_name = ($hour >= 5 && $hour < 12) ? "Morning" :
             (($hour >= 12 && $hour < 17) ? "Afternoon" :
             (($hour >= 17 && $hour < 21) ? "Evening" : "Midnight"));

$dashboard_wh = $_GET['wh'] ?? null;

// Prepare warehouse list for fallback (when GET[wh] is not set)
$warehouse_list = explode(',', $_SESSION['warehouse_ids'] ?? '');
$warehouse_list = array_map(function($warehouse) use ($conn) {
    return "'" . mysqli_real_escape_string($conn, trim($warehouse)) . "'";
}, $warehouse_list);
$imploded_warehouse_ids = implode(",", $warehouse_list);

// Get all suppliers
$dashboard_supplier_query = "SELECT * FROM supplier";
$dashboard_supplier_res = $conn->query($dashboard_supplier_query);

// Get all categories (moved outside supplier loop)
$category_dashboard_sql = "SELECT hashed_id, category_name FROM category";
$category_dashboard_res = $conn->query($category_dashboard_sql);
$categories = [];
if($category_dashboard_res->num_rows > 0){
    while($category_row = $category_dashboard_res->fetch_assoc()){
        $categories[] = $category_row;
    }
}

$stock_summary = [];

if($dashboard_supplier_res->num_rows > 0){
    while($row = $dashboard_supplier_res->fetch_assoc()){
        $supplier_id = $row['hashed_id'];
        $supplier_name = htmlspecialchars($row['supplier_name'], ENT_QUOTES, 'UTF-8');
        $supplier_type = htmlspecialchars($row['local_international'], ENT_QUOTES, 'UTF-8');

        $sub_total_capital = 0;
        $sub_total_qty = 0;

        foreach ($categories as $category) {
            $category_id = $category['hashed_id'];
            $category_name = htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8');

            if (!empty($dashboard_wh)) {
                $warehouse_id = mysqli_real_escape_string($conn, $dashboard_wh);
                $warehouse_condition = "s.warehouse = '$warehouse_id'";
            } else {
                $warehouse_condition = "s.warehouse IN ($imploded_warehouse_ids)";
            }

            $dashboard_stocks_query = "
                SELECT
                    SUM(s.capital) AS total_capital,
                    COUNT(s.unique_barcode) AS qty
                FROM stocks s 
                LEFT JOIN product p ON p.hashed_id = s.product_id
                WHERE
                    p.category = '$category_id'
                    AND s.item_status IN (0, 3)
                    AND $warehouse_condition
                    AND s.supplier = '$supplier_id'
                    AND (s.batch_code IS NOT NULL AND s.batch_code != '-')
            ";

            $dashboard_stocks_res = $conn->query($dashboard_stocks_query);
            if($dashboard_stocks_res && $dashboard_stocks_res->num_rows > 0){
                $stock_row = $dashboard_stocks_res->fetch_assoc();
                $total_capital = (float)($stock_row['total_capital'] ?? 0);
                $total_qty = (int)($stock_row['qty'] ?? 0);

                if ($total_qty > 0 || $total_capital > 0) {
                    $sub_total_capital += $total_capital;
                    $sub_total_qty += $total_qty;

                    $stock_summary[] = '<tr>
                        <td><a href="../Stock-Summary/?supplier=' . $supplier_id . '&&supplier_name=' . $supplier_name . '&&category=&&category_name=&&supplier_type=' . $supplier_type . '">' . $supplier_name . '</a></td>
                        <td><a href="../Stock-Summary/?supplier=' . $supplier_id . '&&supplier_name=' . $supplier_name . '&&category=' . $category_id . '&&category_name=' . $category_name . '&&supplier_type=' . $supplier_type . '">' . $category_name . '</a></td>
                        <td>' . $total_qty . '</td>
                        <td>' . number_format($total_capital, 2) . '</td>
                    </tr>';
                }
            }
        }

        // Add total row for the supplier
        $stock_summary[] = '<tr class="bg-400">
            <td>' . $supplier_name . ' Total</td>
            <td></td>
            <td>' . $sub_total_qty . '</td>
            <td>' . number_format($sub_total_capital, 2) . '</td>
        </tr>';
    }
}
?>

<div class="card">
    <div class="card-header">
        <h6><a href="../Stock-Summary/">Stock Summary</a> as of <?php echo date("Y-m-d"); ?></h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0 data-table fs-10" data-datatables='{"paging":false,"scrollY":"500px","scrollCollapse":true}'>
                <thead class="bg-dark">
                    <tr>
                        <th>Supplier</th>
                        <th>Category</th>
                        <th>Qty</th>
                        <th>Total Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach($stock_summary as $stock_summary_display){
                        echo $stock_summary_display;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
