<div class="card mb-3">
    <div class="card-body overflow-hidden p-lg-4">
        <div class="row align-items-center">
            <div class="col-lg-12">
                <form action="../Create-Return/index" method="POST">
                    <div class="row">
                        <div class="col-lg-6">
                            <label for="barcode">Enter Barcode</label>
                            <input type="text" name="barcode" class="form-control" 
                                   value="<?php echo isset($_POST['barcode']) ? $_POST['barcode'] : ''; ?>">
                        </div>
                        <button class="d-none" type="submit"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
if (isset($_POST['barcode'])) {
    $barcode = $_POST['barcode'];
    $query = "SELECT p.description, b.brand_name, c.category_name, oc.sold_price, s.parent_barcode, s.warehouse, oc.hashed_id, location_name
              FROM outbound_content oc
              LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
              LEFT JOIN item_location il ON il.id = s.item_location 
              LEFT JOIN product p ON p.hashed_id = s.product_id
              LEFT JOIN brand b ON b.hashed_id = p.brand
              LEFT JOIN category c ON c.hashed_id = p.category
              WHERE oc.unique_barcode = '$barcode' AND oc.status IN (0, 6)";
    
    $res = $conn->query($query);
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $location = $row['location_name'] ?? 'FOR SKU';
        ?>

        <form action="../config/return-product.php" method="POST" id="return-form" enctype="multipart/form-data">
            <div class="card mb-3 bg-transparent">
                <div class="card-body overflow-hidden bg-transparent p-0">
                    <div class="row align-items-center">
                        <div class="col-lg-12">
                            <div class="table-responsive scrollbar">
                                <table class="table table-bordered table-striped fs-10 mb-0">
                                    <thead class="bg-info">
                                        <tr>
                                            <th>Description</th>
                                            <th>Brand</th>
                                            <th>Category</th>
                                            <th>Parent Barcode</th>
                                            <th>Sold Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><?php echo $row['description']; ?></td>
                                            <td><?php echo $row['brand_name']; ?></td>
                                            <td><?php echo $row['category_name']; ?></td>
                                            <td><?php echo $row['parent_barcode']; ?></td>
                                            <td>₱ <?php echo $row['sold_price']; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-body overflow-hidden bg-transparent p-lg-4">
                    <div class="row align-items-center">
                        <input type="hidden" name="barcode" value="<?php echo $barcode; ?>">
                        <input type="hidden" name="warehouse" value="<?php echo $row['warehouse'];?>">
                        <input type="hidden" name="outbound_id" value="<?php echo $row['hashed_id'];?>">
                        
                        <div class="col-lg-5 mb-3">
                            <div class="row">
                                <div class="col-lg-6">
                                    <label for="">Fault</label>
                                    <select name="fault" class="form-select" id="faultSelect" required>
                                        <option value="" disabled selected>Select Fault</option>
                                        <option value="CLIENT FAULT">Customer Fault</option>
                                        <option value="SELLER FAULT">Seller Fault</option>
                                        <option value="NONE">None</option>
                                    </select>
                                </div>
                                <div class="col-lg-6">
                                    <label for="">Type</label>
                                    <select name="type_reason" class="form-select" id="typeSelect" required>
                                        <option value="" disabled selected>Select Type</option>
                                    </select>
                                </div>
                                <div class="col-lg-12">
                                    <label for="">Reason</label>
                                    <textarea name="reason" class="form-control" required></textarea>
                                </div>
                            </div>
                            
                        </div>
                        <div class="col-lg-7 mb-3">
                            <div class="row">
                                <div class="col-lg-6">
                                <label for="amount">Amount to be refunded</label>
                                    <input type="number" step="0.01" name="amount" min="0" class="form-control" value="0.00">
                                </div>
                                <div class="col-lg-6">
                                    <label>Will be returned to</label>
                                    <input type="text" class="form-control" value="<?php echo $location;?>" readonly>
                                </div>
                                <div class="col-lg-6">
                                    <label>Processed and Authorized by</label>
                                    <input type="text" class="form-control" value="<?php echo $user_fullname; ?>" readonly>
                                </div>
                                <div class="col-lg-6">
                                    <label for="">Upload proofs</label>
                                    <input type="file" class="form-control" name="images[]" id="imageInput" multiple accept="image/*" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-12 text-end">
                    <button class="btn btn-primary" type="button" id="confirm-submit">Submit</button>
                </div>
            </div>
        </form>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.getElementById('imageInput').addEventListener('change', function (e) {
            if (this.files.length < 4) {
                alert('You can only upload 3 images.');
                this.value = ''; // Clear the selected files
                }
            });

            document.getElementById('confirm-submit').addEventListener('click', function () {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to proceed with returning this product?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, submit it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('return-form').submit();
                    }
                });
            });
        </script>

        <script>
            const faultSelect = document.getElementById('faultSelect');
            const typeSelect = document.getElementById('typeSelect');

            function updateTypeOptions() {
                const fault = faultSelect.value;

                // Clear existing options
                typeSelect.innerHTML = '<option value="" disabled selected>Select Type</option>';

                if (fault === 'NONE') {
                    typeSelect.innerHTML += `
                        <option value="DEFECTIVE">Defective</option>
                        <option value="WRONG ITEM ORDER">Wrong Item Order</option>
                    `;
                } 
                else if (fault === 'CLIENT FAULT' || fault === 'SELLER FAULT') {
                    typeSelect.innerHTML += `
                        <option value="DELIVERY FAILED">Delivery Failed</option>
                    `;
                }
            }

            // Trigger when fault changes
            faultSelect.addEventListener('change', updateTypeOptions);

            // Optional: initialize on load (in case of edit/retain values)
            window.addEventListener('load', updateTypeOptions);
        </script>

    <?php 
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Invalid Barcode',
                text: 'The product has either already been returned, has not been sold yet, or does not exist. Please check the barcode and try again.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        </script>";
    }
}

if(isset($_GET['success'])){
    $result = $_GET['success'];
    if($result === "fault_invalid"){
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Invalid Form input',
                text: 'You enter a wrong combination of type reason and fault. please select the right fault for the right type.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        </script>";
    }
}