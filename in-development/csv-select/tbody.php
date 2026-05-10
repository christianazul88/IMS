<?php
if (isset($_POST['submit']) && isset($_FILES['csv_file'])) {
    $inbound_warehouse = $_POST['warehouse'];
    $file = $_FILES['csv_file'];
    $date_received = $_POST['received_date'];
    $_SESSION['inbound_po_id'] = 0;
    $_SESSION['inbound_received_date'] = htmlspecialchars($_POST['received_date']);
    $_SESSION['inbound_warehouse'] = htmlspecialchars($_POST['warehouse']);

    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $file['tmp_name'];
        $fileName = $file['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension === 'csv') {
            if (($handle = fopen($fileTmpPath, 'r')) !== false) {
                $header = fgetcsv($handle, 1000, ",");
                $expectedHeaders = ["description", "keyword", "qty", "price", "supplier", "parent barcode", "batch code", "brand", "category", "safety"];

                if ($header !== $expectedHeaders) {
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var alertButton = document.getElementById('alert-button');
                            if (alertButton) {
                                alertButton.click();
                            } else {
                                alert('CSV headers are incorrect!');
                            }
                        });
                    </script>";
                }

                $rowIndex = 0;

                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    if (count($data) >= 9) {
                        [$item, $keyword, $qty, $price, $supplier, $barcode, $batch, $brand, $category, $safety] = $data;
                        $rowIndex++;

                        // Use prepared statement for product check
                        $check_product = $conn->prepare("SELECT p.description, p.keyword, p.parent_barcode, b.brand_name, c.category_name
                                                        FROM product p
                                                        LEFT JOIN brand b ON b.hashed_id = p.brand
                                                        LEFT JOIN category c ON c.hashed_id = p.category
                                                        WHERE p.description = ? AND b.brand_name = ? AND c.category_name = ? LIMIT 1");
                        $check_product->bind_param("sss", $item, $brand, $category);
                        $check_product->execute();
                        $result = $check_product->get_result();

                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $barcode = $row['parent_barcode'];
                        } elseif (empty($barcode)) {
                            do {
                                $barcode = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                                $query = "SELECT COUNT(*) AS count FROM product WHERE parent_barcode = ?";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("s", $barcode);
                                $stmt->execute();
                                $stmt->bind_result($count);
                                $stmt->fetch();
                                $stmt->close();
                            } while ($count > 0);
                        }

                        // Use prepared statement for batch code check
                        $check_batch_stmt = $conn->prepare("SELECT batch_code FROM stocks WHERE batch_code = ? LIMIT 1");
                        $check_batch_stmt->bind_param("s", $batch);
                        $check_batch_stmt->execute();
                        $check_batch_code_res = $check_batch_stmt->get_result();

                        if ($check_batch_code_res->num_rows > 0) {
                            echo "<script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        let timeLeft = 5;
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Batch Code Exists!',
                                            html: 'You will be redirected back to the previous page in <b>' + timeLeft + '</b> seconds.',
                                            allowOutsideClick: false,
                                            showConfirmButton: false,
                                            didOpen: () => {
                                                const swalContainer = Swal.getHtmlContainer();
                                                const timerInterval = setInterval(() => {
                                                    timeLeft--;
                                                    if (timeLeft >= 0) {
                                                        swalContainer.innerHTML = 'You will be redirected back to the previous page in <b>' + timeLeft + '</b> seconds.';
                                                    }
                                                }, 1000);
                                                setTimeout(() => {
                                                    clearInterval(timerInterval);
                                                    window.location.href = document.referrer || '../Inbound-logs/';
                                                }, 5000);
                                            }
                                        });
                                    });
                                </script>";
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
                                        <?php if (($item !== "item" && $item !== "ITEM") && ($keyword !== "keyword" && $keyword !== "Keyword")) echo 'checked'; ?>
                                    />
                                </div>
                            </td>
                            <td>
                                <input class="form-control" name="item[]" type="text" value="<?php echo htmlspecialchars($item, ENT_QUOTES); ?>">
                                <div class="valid-feedback">Will be registered as new.</div>
                                <div class="invalid-feedback">Product Exist</div>
                            </td>
                            <td>
                                <input class="form-control" name="keyword[]" type="text" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES); ?>">
                            </td>
                            <td>
                                <input class="form-control" name="qty[]" type="text" value="<?php echo htmlspecialchars($qty, ENT_QUOTES); ?>">
                            </td>
                            <td>
                                <input class="form-control" name="price[]" type="text" value="<?php echo htmlspecialchars($price, ENT_QUOTES); ?>">
                            </td>
                            <td>
                                <input class="form-control" name="supplier[]" type="text" value="<?php echo htmlspecialchars($supplier, ENT_QUOTES); ?>">
                                <div class="valid-feedback">Will be registered as new.</div>
                                <div class="invalid-feedback">Supplier already exist</div>
                            </td>
                            <td>
                                <input class="form-control" name="barcode[]" type="text" value="<?php echo htmlspecialchars($barcode, ENT_QUOTES); ?>">
                            </td>
                            <td>
                                <input class="form-control" name="batch[]" type="text" value="<?php echo htmlspecialchars($batch, ENT_QUOTES); ?>">
                                <div class="valid-feedback">Will be registered as new.</div>
                                <div class="invalid-feedback">Batch already exist</div>
                            </td>
                            <td>
                                <input class="form-control" name="brand[]" type="text" value="<?php echo htmlspecialchars($brand, ENT_QUOTES); ?>">
                                <div class="valid-feedback">Will be registered as new.</div>
                                <div class="invalid-feedback">Brand name already exist</div>
                            </td>
                            <td>
                                <input class="form-control" name="category[]" type="text" value="<?php echo htmlspecialchars($category, ENT_QUOTES); ?>">
                                <div class="valid-feedback">Will be registered as new.</div>
                                <div class="invalid-feedback">Category name already exist</div>
                            </td>
                            <td>
                                <input class="form-control" name="safety[]" type="number" value="<?php echo htmlspecialchars($safety, ENT_QUOTES); ?>">
                            </td>
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
