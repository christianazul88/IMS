<?php 
if (isset($_POST['submit']) && isset($_FILES['csv_file'])) {
    $inbound_warehouse = $_POST['warehouse'];
    $file = $_FILES['csv_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $file['tmp_name'];
        $fileName = $file['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension === 'csv') {
            if (($handle = fopen($fileTmpPath, 'r')) !== false) {
                $rowIndex = 0;

                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    if (count($data) >= 9) {
                        [$item, $keyword, $qty, $price, $supplier, $barcode, $batch, $brand, $category] = $data;
                        $rowIndex++;
                        $new_product = 0;
                        $new = '<span class="badge rounded-pill bg-danger">New</span>';
                        // Brand Check
                        $check_brand = "SELECT * FROM brand WHERE brand_name LIKE '%$brand%' COLLATE utf8mb4_general_ci LIMIT 1";
                        $brand_res = $conn->query($check_brand);
                        if ($brand_res && $brand_res->num_rows > 0) {
                            $brand_row = $brand_res->fetch_assoc();
                            $brand_input = $brand_row['id'];
                            $brand_display = $brand_row['brand_name'];
                        } else {
                            $brand_display = $new . " " . $brand;
                            $brand_input = $brand;
                            $item_display = $new . " " . $item;
                            $item_input = $item;
                            $new_product++;
                        }

                        // Category Check
                        $check_category = "SELECT * FROM category WHERE category_name LIKE '%$category%' COLLATE utf8mb4_general_ci LIMIT 1";
                        $category_res = $conn->query($check_category);
                        if ($category_res && $category_res->num_rows > 0) {
                            $category_row = $category_res->fetch_assoc();
                            $category_input = $category_row['id'];
                            $category_display = $category_row['category_name'];
                        } else {
                            $category_display = $new . " " . $category;
                            $category_input = $category;
                            $item_display = $new . " " . $item;
                            $item_input = $item;
                            $new_product++;
                        }

                        // Supplier Check
                        $check_supplier = "SELECT * FROM supplier WHERE supplier_name LIKE '%$supplier%' COLLATE utf8mb4_general_ci LIMIT 1";
                        $supplier_res = $conn->query($check_supplier);
                        if ($supplier_res && $supplier_res->num_rows > 0) {
                            $supplier_row = $supplier_res->fetch_assoc();
                            $supplier_input = $supplier_row['id'];
                            $supplier_display = $supplier_row['supplier_name'];
                        } else {
                            $supplier_display = $new . " " . $supplier;
                            $supplier_input = $supplier;
                        }

                        if($new_product === 0){
                            $check_product = "SELECT * FROM product WHERE `description` LIKE '%$item%' COLLATE utf8mb4_general_ci AND brand = '$brand_input' AND category = '$category_input' LIMIT 1";
                            $product_res = $conn->query($check_product);
                            if ($product_res->num_rows > 0) {
                                $row = $product_res -> fetch_assoc();
                                $item_input = $row['id'];
                                $item_display = $row['description'];
                            } else {
                                $item_display = $new . " " . $item;
                                $item_input = $item;
                            }
                        }

                        
                        ?>
                        <tr>
                            <td>
                                <div class="form-check">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        name="csv_unique[]" 
                                        value="<?php echo $rowIndex; ?>" 
                                        checked 
                                    />
                                </div>
                                <input type="text" name="item[]" value="<?php echo $item_input; ?>" hidden>
                                <input type="text" name="keyword[]" value="<?php echo $keyword; ?>" hidden>
                                <input type="text" name="qty[]" value="<?php echo $qty; ?>" hidden>
                                <input type="text" name="price[]" value="<?php echo $price; ?>" hidden>
                                <input type="text" name="supplier[]" value="<?php echo $supplier_input; ?>" hidden>
                                <input type="text" name="barcode[]" value="<?php echo $barcode; ?>" hidden>
                                <input type="text" name="batch[]" value="<?php echo $batch; ?>" hidden>
                                <input type="text" name="brand[]" value="<?php echo $brand_input; ?>" hidden>
                                <input type="text" name="category[]" value="<?php echo $category_input; ?>" hidden>
                            </td>
                            <td><?php echo $item_display; ?></td>
                            <td><?php echo $keyword; ?></td>
                            <td><?php echo $qty; ?></td>
                            <td><?php echo $price; ?></td>
                            <td><?php echo $supplier_display; ?></td>
                            <td><?php echo $barcode; ?></td>
                            <td><?php echo $batch; ?></td>
                            <td><?php echo $brand_display; ?></td>
                            <td><?php echo $category_display; ?></td>
                        </tr>
                        <?php
                    }
                }

                fclose($handle);
            } else {
                echo "<tr><td colspan='10'>Error opening the file.</td></tr>";
            }
        } else {
            echo "<tr><td colspan='10'>Invalid file type. Please upload a CSV file.</td></tr>";
        }
    } else {
        echo "<tr><td colspan='10'>Error uploading the file.</td></tr>";
    }
}
?>