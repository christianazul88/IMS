<?php 
include "../config/database.php";
include "../config/on_session.php";
// Retrieve session variables
$inbound_supplier = $_SESSION['inbound_supplier'];
$inbound_po = $_SESSION['inbound_po_id'];
$inbound_date = $_SESSION['inbound_received_date'];
$inbound_warehouse = $_SESSION['inbound_warehouse'];
$inbound_driver = $_SESSION['inbound_driver'];
$inbound_plate = $_SESSION['inbound_plate_num'];

$inbound_warehouse_sql = "SELECT * FROM warehouse WHERE id = '$inbound_warehouse' LIMIT 1";
$inbound_warehouse_res = $conn -> query($inbound_warehouse_sql);
$inbound_warehouse_name = $inbound_warehouse_res -> fetch_assoc()['warehouse_name'];

$inbound_supplier_sql = "SELECT * FROM supplier WHERE id = '$inbound_supplier' LIMIT 1";
$inbound_supplier_res = $conn->query($inbound_supplier_sql);
$inbound_supplier_name = $inbound_supplier_res->fetch_assoc()['supplier_name'];

?>
<div class="inbound-container" style="padding-top: 20px; padding-bottom: 20px; padding-left: 10px; padding-right: 10px; margin: 0; background-color: rgb(237, 238, 238); min-height: 8.5in; font-size: 12px;">
    <div style="text-align: center;">
        <h4>Inbound Document</h4>
    </div>
    
    <div style="text-align: right; margin-bottom: 0;">
        <small style="display: block; margin: 0;">P.O# <?php echo $inbound_po;?></small>
        <small style="display: block; margin: 0;"><?php echo $inbound_warehouse_name;?></small>
    </div>
    
    <div style="text-align: left; margin-bottom: 0;">
        <small style="display: block; margin: 0;"><?php echo $inbound_supplier_name?></small>
    </div>
    
    <div>
        <table style="width: 100%; border-collapse: collapse; text-align: center;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 5px;">Item</th>
                    <th style="border: 1px solid #000; padding: 5px;">Keyword</th>
                    <th style="border: 1px solid #000; padding: 5px;">Ordered Qty</th>
                    <th style="border: 1px solid #000; padding: 5px;">Received QTY</th>
                    <th style="border: 1px solid #000; padding: 5px;">Price</th>
                    <th style="border: 1px solid #000; padding: 5px;">Supplier</th>
                    <th style="border: 1px solid #000; padding: 5px;">Barcode</th>
                    <th style="border: 1px solid #000; padding: 5px;">Batch#</th>
                    <th style="border: 1px solid #000; padding: 5px;">Brand</th>
                    <th style="border: 1px solid #000; padding: 5px;">Category</th>
                </tr>
            </thead>
            <tbody>
            <?php
            // Path to the JSON file
            $jsonFile = "../jsons/inbound-by" . $user_id . "-PO" . $inbound_po . ".json";

            // Check if the JSON file exists
            if (!file_exists($jsonFile)) {
                echo "<tr><td colspan='5' style='border: 1px solid #000; padding: 5px; text-align: center;'>Enter product first</td></tr>";
            } else {
                // Read and decode the JSON file
                $jsonData = file_get_contents($jsonFile);
                $dataArray = json_decode($jsonData, true);

                // Check if JSON data was successfully decoded
                if ($dataArray === null) {
                    echo "<tr><td colspan='10' style='border: 1px solid #000; padding: 5px; text-align: center;'>Error decoding JSON</td></tr>";
                } else {
                    // Loop through each item in the array and display it in the table
                    foreach ($dataArray as $item) {
                        $product_id = $item['product_id'];
                        $description = $item['product_desc'] ?? 'N/A';
                        $brand = $item['brand_name'] ?? 'N/A';
                        $category = $item['category_name'] ?? 'N/A';
                        $order_qty = $item['ordered_qty'] ?? 'N/A';
                        $received_qty = $item['received_qty'] ?? 'N/A';
                        $keyword = $item['keyword'] ?? 'N/A';
                        $price = $item['price'] ?? 'N/A';
                        $barcode = $item['barcode'] ?? 'N/A';
                        $batch_num = $item['batch_num'];



                        echo "<tr>
                                <td style='border: 1px solid #000; padding: 5px;'><small>$description</small></td>
                                <td style='border: 1px solid #000; padding: 5px;'><small>$keyword</small></td>
                                <td style='border: 1px solid #000; padding: 5px;'><small>$order_qty</small></td>
                                <td style='border: 1px solid #000; padding: 5px;'><small>$received_qty</small></td>
                                <td style='border: 1px solid #000; padding: 5px;'><small>$price</small></td>
                                <td style='border: 1px solid #000; padding: 5px;'><small></small></td>
                                <td style='border: 1px solid #000; padding: 5px;'><small>$barcode</small></td>
                                <td style='border: 1px solid #000; padding: 5px;'><small>$batch_num</small></td>
                                <td style='border: 1px solid #000; padding: 5px;'><small>$brand</small></td>
                                <td style='border: 1px solid #000; padding: 5px;'><small>$category</small></td>
                            </tr>";
                    }
                }
            }
            ?>

            </tbody>
        </table>
    </div>
    
    <div style="text-align: right; margin-top: 50px;">
        <p><?php echo $user_fullname;?></p>
    </div>
</div>
