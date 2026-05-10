<?php
if (isset($_POST['submit']) && isset($_FILES['csv_file'])) {
    $inbound_warehouse = $_POST['warehouse'];
    $file = $_FILES['csv_file'];
    $date_received = $_POST['received_date'];
    $_SESSION['inbound_po_id'] = 0;
    $_SESSION['inbound_received_date'] = htmlspecialchars($_POST['received_date']);
    $_SESSION['inbound_warehouse'] = htmlspecialchars($_POST['warehouse']);

    $existing_batches = [];
    $existing_barcodes = [];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $file['tmp_name'];
        $fileName = $file['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if ($fileExtension === 'csv') {
            if (($handle = fopen($fileTmpPath, 'r')) !== false) {
                $rows = [];
                $rowIndex = 0;

                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    if (count($data) >= 9) {
                        [$item, $keyword, $qty, $price, $supplier, $barcode, $batch, $brand, $category, $safety] = $data;
                        // Generate barcode if missing (your existing code remains)
                        if (empty($barcode)) {
                            do {
                                $barcode = str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
                                $query = "SELECT COUNT(*) AS count FROM product WHERE parent_barcode = ?";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("s", $barcode);
                                $stmt->execute();
                                $stmt->bind_result($count);
                                $stmt->fetch();
                                $stmt->close();
                            } while ($count > 0);
                        }

                        // Track duplicates within the CSV itself
                        static $csv_barcodes_seen = [];
                        static $csv_duplicate_barcodes = [];

                        if (isset($csv_barcodes_seen[$barcode])) {
                            $csv_duplicate_barcodes[] = $barcode;
                        } else {
                            $csv_barcodes_seen[$barcode] = true;
                        }

                        $rowIndex++;

                        // Check existing batch
                        if (!empty($batch)) {
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM stocks WHERE batch_code = ?");
                            $stmt->bind_param("s", $batch);
                            $stmt->execute();
                            $stmt->bind_result($batch_count);
                            $stmt->fetch();
                            $stmt->close();
                            if ($batch_count > 0) {
                                $existing_batches[] = $batch;
                            }
                        }

                        // Check or generate barcode
                        if (empty($barcode)) {
                            do {
                                $barcode = str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
                                $query = "SELECT COUNT(*) AS count FROM product WHERE parent_barcode = ?";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("s", $barcode);
                                $stmt->execute();
                                $stmt->bind_result($count);
                                $stmt->fetch();
                                $stmt->close();
                            } while ($count > 0);
                        }

                        // Check existing barcode in stocks
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM stocks WHERE unique_barcode = ?");
                        $stmt->bind_param("s", $barcode);
                        $stmt->execute();
                        $stmt->bind_result($barcode_count);
                        $stmt->fetch();
                        $stmt->close();
                        if ($barcode_count > 0) {
                            $existing_barcodes[] = $barcode;
                        }

                        // Store row
                        $rows[] = [
                            'index' => $rowIndex,
                            'item' => $item,
                            'keyword' => $keyword,
                            'qty' => $qty,
                            'price' => $price,
                            'supplier' => $supplier,
                            'barcode' => $barcode,
                            'batch' => $batch,
                            'brand' => $brand,
                            'category' => $category,
                            'safety' => $safety
                        ];
                    }
                }
                fclose($handle);

                // Sort rows by barcode
                usort($rows, function($a, $b) {
                    return strcmp($a['barcode'], $b['barcode']);
                });

                // Inject SweetAlert2 scripts if there are any duplicates
                if (!empty($existing_batches) || !empty($existing_barcodes) || !empty($csv_duplicate_barcodes)) {
                    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                    echo "<script>";
                    echo "Swal.fire({";
                    echo "  title: 'Duplicate Data Found!',";
                    echo "  icon: 'warning',";
                    echo "  html: `";

                    if (!empty($existing_batches)) {
                        echo "<strong>Existing Batch Codes (DB):</strong><br>";
                        foreach ($existing_batches as $batch) {
                            echo htmlspecialchars($batch) . "<br>";
                        }
                        echo "<br>";
                    }

                    if (!empty($existing_barcodes)) {
                        echo "<strong>Existing Barcodes (DB):</strong><br>";
                        foreach ($existing_barcodes as $barcode) {
                            echo htmlspecialchars($barcode) . "<br>";
                        }
                        echo "<br>";
                    }

                    if (!empty($csv_duplicate_barcodes)) {
                        echo "<strong>Duplicate Barcodes in CSV:</strong><br>";
                        foreach (array_unique($csv_duplicate_barcodes) as $dup) {
                            echo htmlspecialchars($dup) . "<br>";
                        }
                    }

                    echo "`,"; // closing backtick for HTML
                    echo "  confirmButtonText: 'Go back'";
                    echo "}).then(() => {";
                    echo "  window.location.href = '../Inbound-logs/';";
                    echo "});";
                    echo "</script>";
                }


                // Display sorted rows
                foreach ($rows as $row) {
                    ?>
                    <tr>
                        <td>
                            <div class="form-check">
                                <input 
                                    class="form-check-input" 
                                    type="checkbox" 
                                    name="csv_unique[]" 
                                    value="<?php echo $row['index']; ?>" 
                                    <?php if ((is_numeric($row['price']) || is_numeric($row['qty']))) echo 'checked'; ?>
                                />
                            </div>
                        </td>
                        <td><input class="form-control" name="item[]" type="text" value="<?php echo $row['item']; ?>"></td>
                        <td><input class="form-control" name="keyword[]" type="text" value="<?php echo $row['keyword']; ?>"></td>
                        <td><input class="form-control" name="qty[]" type="text" value="1"></td>
                        <td><input class="form-control" name="price[]" type="text" value="<?php echo $row['price']; ?>"></td>
                        <td><input class="form-control" name="supplier[]" type="text" value="<?php echo $row['supplier']; ?>"></td>
                        <td><input class="form-control" name="barcode[]" type="text" value="<?php echo $row['barcode']; ?>"></td>
                        <td><input class="form-control" name="batch[]" type="text" value="<?php echo $row['batch']; ?>" required></td>
                        <td><input class="form-control" name="brand[]" type="text" value="<?php echo $row['brand']; ?>"></td>
                        <td><input class="form-control" name="category[]" type="text" value="<?php echo $row['category']; ?>"></td>
                        <td><input class="form-control" name="safety[]" type="number" value="<?php echo $row['safety']; ?>"></td>
                    </tr>
                    <?php
                }
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
