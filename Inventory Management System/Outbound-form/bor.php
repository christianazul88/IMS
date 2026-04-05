<?php
include "../config/database.php";
include "../config/on_session.php";

$outbound_id = $_SESSION['outbound_id'];

if (isset($_GET['view'])) {
    // Path to the JSON file
    $jsonFilePath = $outbound_id . '.json';

    // Check if the file exists
    if (file_exists($jsonFilePath)) {
        // Get the content of the file
        $jsonData = file_get_contents($jsonFilePath);

        // Decode the JSON data into a PHP array
        $products = json_decode($jsonData, true);

        // Check for JSON decoding errors
        if (json_last_error() === JSON_ERROR_NONE) {
            // Check if the products array is empty
            if (!empty($products)) {
                // Iterate through each product and display its details
                foreach ($products as $product) {
                    ?>
                    <tr>
                        <td><button class="btn btn-transparent action-button" type="button" data-barcode="<?php echo $product['barcode']; ?>"><span class="far fa-window-close text-danger"></span></button></td>
                        <td><input type="text" name="barcode[]" class="form-control m-0 py-0" value="<?php echo $product['barcode']; ?>" readonly></td>
                        <td><?php echo $product['product_description'];?></td>
                        <?php 
                        if(strpos($access ?? '', "stock")!==false || $user_position_name === "Administrator"){
                        ?>
                        <td class="unit-cost" data-cost="<?php echo $product['capital']; ?>">
                            <?php echo number_format($product['capital'], 2); ?>
                        </td>
                        <?php 
                        }
                        ?>
                        <td><input type="number" name="selling[]" min="0" max="999999" class="form-control m-0 py-0 selling-input" step="0.01" placeholder="Selling Price" required></td>
                        <td><?php echo $product['batch_num']; ?></td>
                        <td><?php echo $product['brand_name'];?></td>
                        <td><?php echo $product['category_name'];?></td>
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
