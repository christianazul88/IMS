<?php
$rows = [];

if (
    isset($_GET['supplier'], $_GET['supplier_name'], $_GET['category'], $_GET['category_name'])
) {
    $supplier_id = $_GET['supplier'];
    $supplier_name = htmlspecialchars($_GET['supplier_name'], ENT_QUOTES, 'UTF-8');
    $category_id = $_GET['category'];
    $category_name = htmlspecialchars($_GET['category_name'], ENT_QUOTES, 'UTF-8');
    $supplier_type = htmlspecialchars($_GET['supplier_type'] ?? '', ENT_QUOTES, 'UTF-8');
    $paragraph = "For Supplier: $supplier_name and Category: $category_name";

    $additional_query = (!empty($category_id) && !empty($category_name)) ? 
        " AND p.category = '$category_id'" : '';
    $additional_takes = (!empty($category_id) && !empty($category_name)) ? 
        '' : ', c.category_name';

    $total_qty = 0;
    $total_capital = 0;

    $product_stocks = "
        SELECT 
            p.product_img,
            p.description,
            b.brand_name,
            COUNT(s.unique_barcode) AS stocks,
            SUM(s.capital) AS unit_cost,
            w.warehouse_name
            $additional_takes
        FROM product p
        LEFT JOIN stocks s ON s.product_id = p.hashed_id
        LEFT JOIN brand b ON b.hashed_id = p.brand
        LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
        LEFT JOIN category c ON c.hashed_id = p.category
        WHERE s.supplier = '$supplier_id'
        AND s.item_status IN (0, 3)
        $additional_query
        GROUP BY p.product_img, p.description, b.brand_name, w.warehouse_name, s.warehouse
        ORDER BY p.category";

    $result = $conn->query($product_stocks);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $product_img = $row['product_img'] ?? 'def_img.png';
            $product_description = htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8');
            $brand_name = htmlspecialchars($row['brand_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $stocks = (int)($row['stocks'] ?? 0);
            $capital = (float)($row['unit_cost'] ?? 0);
            $product_warehouse = htmlspecialchars($row['warehouse_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $category_name2 = htmlspecialchars($row['category_name'] ?? $category_name, ENT_QUOTES, 'UTF-8');

            $total_qty += $stocks;
            $total_capital += $capital;

            $rows[] = "<tr>
                <td><img src='../../assets/img/$product_img' height='50' alt=''></td>
                <td>$product_description</td>
                <td>$brand_name</td>
                <td>$category_name2</td>
                <td>$product_warehouse</td>
                <td>$supplier_name</td>
                <td>$supplier_type</td>
                <td>$stocks</td>
                <td>" . number_format($capital, 2) . "</td>
            </tr>";
        }

        $rows[] = "<tr class='table-info'>
            <td>Total for $supplier_name</td>
            <td colspan='6'></td>
            <td>$total_qty</td>
            <td>" . number_format($total_capital, 2) . "</td>
        </tr>";
    }

} else {
    $supplier_result = $conn->query("SELECT * FROM supplier ORDER BY supplier_name ASC");
    $paragraph = "All";

    if ($supplier_result && $supplier_result->num_rows > 0) {
        while ($row = $supplier_result->fetch_assoc()) {
            $supplier_id = $row['hashed_id'];
            $supplier_name = htmlspecialchars($row['supplier_name'], ENT_QUOTES, 'UTF-8');
            $supplier_type = htmlspecialchars($row['local_international'], ENT_QUOTES, 'UTF-8');
            $total_qty = 0;
            $total_capital = 0;

            $product_stocks = "
                SELECT 
                    p.product_img,
                    p.description,
                    b.brand_name,
                    COUNT(s.unique_barcode) AS stocks,
                    SUM(s.capital) AS unit_cost,
                    w.warehouse_name,
                    c.category_name
                FROM product p
                LEFT JOIN stocks s ON s.product_id = p.hashed_id
                LEFT JOIN brand b ON b.hashed_id = p.brand
                LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
                LEFT JOIN category c ON c.hashed_id = p.category
                WHERE s.item_status IN (0, 3)
                AND s.supplier = '$supplier_id'
                GROUP BY p.product_img, p.description, b.brand_name, w.warehouse_name, s.warehouse
                ORDER BY c.category_name";

            $result = $conn->query($product_stocks);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $product_img = $row['product_img'] ?? 'def_img.png';
                    $product_description = htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8');
                    $brand_name = htmlspecialchars($row['brand_name'] ?? '', ENT_QUOTES, 'UTF-8');
                    $stocks = (int)($row['stocks'] ?? 0);
                    $capital = (float)($row['unit_cost'] ?? 0);
                    $product_warehouse = htmlspecialchars($row['warehouse_name'] ?? '', ENT_QUOTES, 'UTF-8');
                    $category_name = htmlspecialchars($row['category_name'] ?? '', ENT_QUOTES, 'UTF-8');

                    $total_qty += $stocks;
                    $total_capital += $capital;

                    $rows[] = "<tr>
                        <td><img src='../../assets/img/$product_img' height='50' alt=''></td>
                        <td>$product_description</td>
                        <td>$brand_name</td>
                        <td>$category_name</td>
                        <td>$product_warehouse</td>
                        <td>$supplier_name</td>
                        <td>$supplier_type</td>
                        <td>$stocks</td>
                        <td>" . number_format($capital, 2) . "</td>
                    </tr>";
                }

                $rows[] = "<tr class='table-info'>
                    <td>Total for $supplier_name</td>
                    <td colspan='6'></td>
                    <td>$total_qty</td>
                    <td>" . number_format($total_capital, 2) . "</td>
                </tr>";
            }
        }
    }
}
?>

<!-- Output HTML -->
<div class="row">
    <div class="col-xxl-14">
        <div class="card">
            <div class="card-header bg-success bg-gradient">
                <h2>Stock Summary</h2>
                <p><?= $paragraph; ?></p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table mb-0 data-table fs-11 table-sm">
                        <thead class="bg-dark">
                            <tr>
                                <th></th>
                                <th>Description</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Warehouse</th>
                                <th>Supplier</th>
                                <th>Local/Import</th>
                                <th>Stocks</th>
                                <th>Unit Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $stock) echo $stock; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
