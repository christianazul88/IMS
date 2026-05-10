<?php 
include "../config/database.php";
include "../config/on_session.php";

if(isset($_GET['warehouse'])){
    $warehouse_id = $_GET['warehouse'];
    
    // Query to fetch outbound logs for the given warehouse
    $outbound_logs_query = "SELECT hashed_id
                            FROM outbound_logs
                            WHERE MONTH(date_sent) = MONTH(CURDATE()) 
                            AND YEAR(date_sent) = YEAR(CURDATE())
                            AND warehouse = '$warehouse_id'
                            ORDER BY date_sent ASC";
    $outbound_logs_res = $conn->query($outbound_logs_query);
    
    // Array to hold product data before sorting
    $product_data_array = [];
    
    if($outbound_logs_res->num_rows > 0){
        while($row = $outbound_logs_res->fetch_assoc()){
            $outbound_id = $row['hashed_id'];
            
            // Query to fetch content details for the outbound log
            $outbounbd_content_query = "SELECT 
                    p.product_img,
                    p.description,
                    b.brand_name,
                    c.category_name,
                    p.hashed_id AS product_id
                  FROM outbound_content oc
                  LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
                  LEFT JOIN product p ON p.hashed_id = s.product_id
                  LEFT JOIN brand b ON b.hashed_id = p.brand
                  LEFT JOIN category c ON c.hashed_id = p.category
                  WHERE oc.hashed_id = '$outbound_id'";
            $outbounbd_content_res = $conn->query($outbounbd_content_query);
            
            if($outbounbd_content_res->num_rows > 0){
                while($row = $outbounbd_content_res->fetch_assoc()){
                    $product_img = $row['product_img'];
                    $product_id = $row['product_id'];
                    $product_description = $row['description'];
                    $product_brand = $row['brand_name'];
                    $product_category = $row['category_name'];

                    $stock_query = "SELECT COUNT(product_id) AS available_stock FROM stocks WHERE product_id = '$product_id' AND item_status = 0 AND warehouse = '$warehouse_id'";
                    $stock_res = $conn->query($stock_query);
                    if($stock_res->num_rows > 0){
                        $row = $stock_res->fetch_assoc();
                        $available_stocks = $row['available_stock'];
                    }

                    // Store product data in the array for sorting later
                    if(isset($product_data_array[$product_id])) {
                        $product_data_array[$product_id]['outbounded'] += 1;
                    } else {
                        $product_data_array[$product_id] = [
                            'product_img' => $product_img,
                            'product_id' => $product_id,
                            'description' => $product_description,
                            'brand' => $product_brand,
                            'category' => $product_category,
                            'outbounded' => 1,
                            'stocks' => $available_stocks
                        ];
                    }
                }
            }
        }
    }

    // Sort the products by outbounded count in descending order
    usort($product_data_array, function($a, $b) {
        return $b['outbounded'] - $a['outbounded'];
    });

    // Store only the top 10 products in the session
    $top_products = array_slice($product_data_array, 0, 10);

    // Clear the existing session and store only the top 10 products
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'product_') === 0) {  // Ensure it's a product session key
            unset($_SESSION[$key]);
        }
    }

    foreach ($top_products as $product) {
        $session_key = "product_{$product['product_id']}";
        $_SESSION[$session_key] = $product;
    }
}

?>
<div class="table-responsive scrollbar">
    <table class="table table-dashboard mb-0 table-borderless fs-10 border-200">
        <thead class="bg-body-tertiary">
            <tr>
            <th class="text-900">Fast Moving Products This Month</th>
            <th class="text-900 text-center">Outbounded</th>
            <th class="text-900 text-center">Stocks</th>
            <th class="text-900 pe-x1 text-end" style="width: 8rem">Outbound (%)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Check if there are any session variables for products
            if (!empty($_SESSION)) {

                // Loop through each session variable (product)
                foreach ($_SESSION as $product_key => $product_data) {
                    if (strpos($product_key, 'product_') === 0) {  // Ensure it's a product session key
                        // if (empty($product_data['product_img']) || !isset($product_data['product_img'])) {
                        //     $dis_img = '../../assets/img/def_img.png';
                        // } else {
                        //     $imageArray = @unserialize($row['product_img']); // or json_decode(..., true)

                        //     if (is_array($imageArray) && count($imageArray) > 0) {
                        //         $firstImageBinary = base64_decode($imageArray[0]);

                        //         // Detect MIME type
                        //         $finfo = new finfo(FILEINFO_MIME_TYPE);
                        //         $mimeType = $finfo->buffer($firstImageBinary);

                        //         // Encode for output
                        //         $dis_img = 'data:' . $mimeType . ';base64,' . $imageArray[0];
                        //     } else {
                        //         $dis_img = '../../assets/img/def_img.png';
                        //     }
                        // }
                        $dis_description = htmlspecialchars($product_data['description']);
                        $dis_brand = htmlspecialchars($product_data['brand']);
                        $dis_category = htmlspecialchars($product_data['category']);
                        $dis_outbounded = number_format($product_data['outbounded']);
                        $dis_available_stocks = number_format($product_data['stocks']);
                        if ($dis_available_stocks > 0) {
                            $dis_percentage_initial = ($dis_outbounded / $dis_available_stocks) * 100;
                            $dis_percentage = number_format($dis_percentage_initial, 2);
                        } else {
                            $dis_percentage = 100; // or 0, depending on how you want to show it
                        }
                        
                        ?>
                        <tr class="border-bottom border-200">
                            <td>
                                <div class="d-flex align-items-center position-relative">
                                
                                <div class="flex-1 ms-3">
                                    <h6 class="mb-1 fw-semi-bold text-nowrap">
                                    <a class="text-900 stretched-link" href="#!"><?php echo $dis_brand . ": " . $dis_description;?></a>
                                    </h6>
                                    <p class="fw-semi-bold mb-0 text-500"><?php echo $dis_category;?></p>
                                </div>
                                </div>
                            </td>
                            <td class="align-middle text-center fw-semi-bold"><?php echo $dis_outbounded;?></td>
                            <td class="align-middle text-center fw-semi-bold"><?php echo $dis_available_stocks;?></td>
                            <td class="align-middle pe-x1">
                                <div class="d-flex align-items-center">
                                <div class="progress me-3 rounded-3 bg-200" style="height: 5px; width:80px" role="progressbar" 
                                        aria-valuenow="41" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-primary rounded-pill" 
                                        style="width: <?php echo is_numeric($dis_percentage) ? $dis_percentage : 0;?>%;"></div>
                                </div>
                                <div class="fw-semi-bold ms-2">
                                    <?php echo is_numeric($dis_percentage) ? $dis_percentage . '%' : $dis_percentage; ?>
                                </div>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                    
                }

            } else {
            ?>
            <tr class="border-bottom border-200">
                <td>
                    <div class="d-flex align-items-center position-relative">
                    <img class="rounded-1 border border-200" src="../assets/img/ecommerce/1.jpg" width="60" alt="" />
                    <div class="flex-1 ms-3">
                        <h6 class="mb-1 fw-semi-bold text-nowrap">
                        <a class="text-900 stretched-link" href="#!"><?php echo $dis_brand . ": " . $dis_description;?></a>
                        </h6>
                        <p class="fw-semi-bold mb-0 text-500"><?php echo $dis_category;?></p>
                    </div>
                    </div>
                </td>
            </tr>
            <?php
            }
            ?>

        </tbody>
    </table>
</div>
