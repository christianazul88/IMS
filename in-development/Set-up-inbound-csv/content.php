<div class="row">
    <div class="col-lg-12"></div>
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body overflow-hidden">
                <div class="table-responsive">
                    <table class="table table=bordered">
                        <thead class="table-dark">
                            <tr>
                                <th></th>
                                <th>Item</th>
                                <th>Keyword</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Supplier</th>
                                <th>Barcode</th>
                                <th>Batch#</th>
                                <th>Brand</th>
                                <th>$Category</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (isset($_POST['submit'])) {
                                // Check if the file was uploaded without errors
                                if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                                    $fileTmpPath = $_FILES['csv_file']['tmp_name'];
                                    $fileName = $_FILES['csv_file']['name'];
                                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                                    // Validate the uploaded file type
                                    if ($fileExtension === 'csv') {
                                        // Open the uploaded file for reading
                                        if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
                                            echo "<h3>Contents of the CSV file:</h3>";
                                            echo "<form>";

                                            // Loop through each row of the CSV file
                                            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                                                // Only process rows that contain at least 2 columns
                                                if (count($data) >= 9) {
                                                    list($item, $keyword, $qty, $price, $supplier, $barcode, $batch, $brand, $category) = $data;
                            ?>
                                                    <tr>
                                                        <td>
                                                            <div class="form-check">
                                                                <input class="form-check-input" id="flexCheckChecked" type="checkbox" value="<?php echo htmlspecialchars($batch); ?>" checked="" />
                                                                
                                                            </div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($item); ?></td>
                                                        <td><?php echo htmlspecialchars($keyword); ?></td>
                                                        <td><?php echo htmlspecialchars($qty); ?></td>
                                                        <td><?php echo htmlspecialchars($price); ?></td>
                                                        <td><?php echo htmlspecialchars($supplier); ?></td>
                                                        <td><?php echo htmlspecialchars($barcode); ?></td>
                                                        <td><?php echo htmlspecialchars($batch); ?></td>
                                                        <td><?php echo htmlspecialchars($brand); ?></td>
                                                        <td><?php echo htmlspecialchars($category); ?></td>
                                                    </tr>
                            <?php
                                                }
                                            }
                                            echo "</form>";
                                            fclose($handle);
                                        } else {
                                            echo "Error opening the file.";
                                        }
                                    } else {
                                        echo "Invalid file type. Please upload a CSV file.";
                                    }
                                } else {
                                    echo "Error uploading the file.";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
