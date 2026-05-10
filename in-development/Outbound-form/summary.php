<div class="row">
    <div class="col-12">
        <div class="table-responsive">
            <table class="table table-sm bordered-table">
                <thead class="table-dark">
                    <tr>
                        <th>Description</th>
                        <th>Brand Name</th>
                        <th>Category Name</th>
                        <th>QTY</th>
                    </tr>
                </thead>
                <tbody>
<?php
include "../config/database.php";
include "../config/on_session.php";

$outbound_id = $_SESSION['outbound_id'];

if (isset($_GET['view'])) {
    $jsonFilePath = $outbound_id . '.json';

    if (file_exists($jsonFilePath)) {
        $jsonData = file_get_contents($jsonFilePath);
        $products = json_decode($jsonData, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (!empty($products)) {
                // Grouping products
                $grouped = [];

                foreach ($products as $product) {
                    $key = $product['product_description'] . '|' . $product['brand_name'] . '|' . $product['category_name'];
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'product_description' => $product['product_description'],
                            'brand_name' => $product['brand_name'],
                            'category_name' => $product['category_name'],
                            'quantity' => 0
                        ];
                    }
                    $grouped[$key]['quantity'] += $product['quantity'] ?? 1; // fallback to 1 if quantity is missing
                }

                // Output grouped results
                foreach ($grouped as $item) {
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_description']); ?></td>
                        <td><?php echo htmlspecialchars($item['brand_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td class="text-end"><?php echo htmlspecialchars($item['quantity']); ?></td>
                    </tr>
                    <?php
                }
            } else {
                echo "Nothing yet";
            }
        } else {
            echo "Error decoding JSON: " . json_last_error_msg();
        }
    } else {
        echo "Nothing yet";
    }
}
?>

                </tbody>
            </table>
        </div>
    </div>
</div>
