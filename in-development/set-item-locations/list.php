<?php
include "../config/database.php";
include "../config/on_session.php";

// Construct the filename
$filename = '../jsons/' . htmlspecialchars($_SESSION['unique_key']) . '.json';
?>
<div class="table-responsive">
    <table class="table bordered-table table-bordered">
        <thead>
            <th></th>
            <th>Item Location</th>
            <th>Barcode</th>
            <th>Description</th>
            <th>Brand</th>
            <th>Category</th>
        </thead>
        <tbody>
            <?php 
            // Check if the file exists
            if (file_exists($filename)) {
                $jsonContent = file_get_contents($filename);
                $data = json_decode($jsonContent, true);

                if (is_array($data)) {
                    foreach ($data as $item) {
                        $rack_id = htmlspecialchars($item['warehouse']);
                        $barcode = htmlspecialchars($item['barcode']);

                        // Prepare and execute the query to get product details
                        $stmt = $conn->prepare("
                            SELECT 
                                s.unique_barcode, 
                                p.description, 
                                p.product_img,
                                b.brand_name, 
                                c.category_name 
                            FROM stocks s
                            LEFT JOIN product p ON s.product_id = p.hashed_id
                            LEFT JOIN brand b ON p.brand = b.hashed_id
                            LEFT JOIN category c ON p.category = c.hashed_id
                            WHERE s.unique_barcode = ? LIMIT 1
                        ");
                        $stmt->bind_param("s", $barcode);
                        $stmt->execute();
                        $product_res = $stmt->get_result();

                        $product_desc = $brand_name = $category_name = "N/A"; // Default values
                        if ($product_res->num_rows > 0) {
                            $row = $product_res->fetch_assoc();
                            $product_desc = $row['description'];
                            $brand_name = $row['brand_name'];
                            $category_name = $row['category_name'];
                            $product_img = $row['product_img'];
                        }

                        // Prepare and execute the query to get location details
                        $stmt_loc = $conn->prepare("
                            SELECT location_name 
                            FROM item_location 
                            WHERE id = ? LIMIT 1
                        ");
                        $stmt_loc->bind_param("s", $rack_id);
                        $stmt_loc->execute();
                        $location_res = $stmt_loc->get_result();

                        $location = "Unknown";
                        if ($location_res->num_rows > 0) {
                            $loc_row = $location_res->fetch_assoc();
                            $location = $loc_row['location_name'];
                        }

                        // Display the data in a table row
                        ?>
                        <tr>
                            <td><img src="<?php echo $product_img;?>" class="img img-fluid" width="30" alt=""></td>
                            <td><?php echo $location; ?></td>
                            <td><?php echo $barcode; ?></td>
                            <td><?php echo $product_desc; ?></td>
                            <td><?php echo $brand_name; ?></td>
                            <td><?php echo $category_name; ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='5'>Invalid JSON format.</td></tr>";
                }
            } else {
                echo "<tr><td colspan='5'>File not found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
