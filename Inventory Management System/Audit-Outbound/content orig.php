<div class="row">
    <div class="col-lg-12 mb-3">
        <div id="barcode-form"></div>
    </div>
    <div class="col-lg-12 mb-3">
        <div class="card">
            <div class="card-body overflow-hidden" style="height: 300px;">
                <h3>Products Transaction</h3>
                <div class="table-responsive" id="product-table"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="card" >
            <div class="card-body">
                <div class="row">
                    <div class="col-5 mb-3">
                        <label for="">Customer Name</label>
                        <input class="form-control" type="text">
                    </div>
                    <div class="col-3 mb-3">
                        <label for="">Platform</label>
                        <select class="form-select" name="platform" id="">
                            <?php 
                            $sql = "SELECT * FROM logistic_partner ORDER BY logistic_name";
                            $res = $conn->query($sql);
                            if($res->num_rows>0){
                                while($row = $res->fetch_assoc()){
                                    echo "<option value='".$row['hashed_id']."'>".$row['logistic_name']."</option>";
                                }
                            } else {
                                echo "<option value=''>No Data</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-4 mb-3">
                        <label for="">Courier</label>
                        <select class="form-select" name="courier" id="">
                            <?php 
                            $sql = "SELECT * FROM courier ORDER BY courier_name ASC";
                            $res = $conn->query($sql);
                            if($res->num_rows>0){
                                while($row = $res->fetch_assoc()){
                                    echo "<option value='".$row['hashed_id']."'>".$row['courier_name']."</option>";
                                }
                            } else {
                                echo "<option value=''>No Data</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-3 mb-3">
                        <label for="">Order no.</label>
                        <input class="form-control" type="number">
                    </div>
                    <div class="col-3 mb-3">
                        <label for="">Order Line ID</label>
                        <input type="number" class="form-control">
                    </div>
                    <div class="col-4 mb-3">
                        <label for="">Process by</label>
                        <input class="form-control" type="text" readonly value="<?php echo $user_fullname;?>">
                    </div>
                    <div class="col-2 mb-3 pt-4">
                        <button class="btn btn-primary">Save</button>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        // Load the barcode form initially
        loadBarcodeForm();

        // Function to load the barcode form
        function loadBarcodeForm() {
            $('#barcode-form').load('barcode-form.php', function () {
                $('#barcode').focus();
            });
        }

        // Function to load the product table
        function loadProductTable() {
            $.getJSON('products.json', function (data) {
                if (data.length > 0) {
                    let table = `
                    <div class="table-responsive scrollbar overflow-hidden">
                        <table class="table table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Barcode</th>
                                    <th>Description</th>
                                    <th>Selling Price</th>
                                    <th>Keyword</th>
                                    <th>Batch Number</th>
                                    <th>Brand Name</th>
                                    <th>Category Name</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    $.each(data, function (index, product) {
                        table += `
                            <tr>
                                <td>${product.barcode}</td>
                                <td>${product.product_description}</td>
                                <td class="text-end">${product.selling_price}</td>
                                <td>${product.keyword}</td>
                                <td>${product.batch_num}</td>
                                <td>${product.brand_name}</td>
                                <td>${product.category_name}</td>
                            </tr>
                        `;
                    });
                    table += `
                            </tbody>
                        </table>
                    </div>
                    `;
                    $('#product-table').html(table);
                } else {
                    $('#product-table').html('<p>No products available.</p>');
                }
            }).fail(function () {
                $('#product-table').html('<p>No transaction yet.</p>');
            });
        }

        // Handle form submission
        $(document).on('submit', '#myForm', function (e) {
            e.preventDefault();

            $.ajax({
                type: $(this).attr('method'),
                url: $(this).attr('action'),
                data: $(this).serialize(),
                dataType: 'json',
                success: function () {
                    loadBarcodeForm();
                    loadProductTable();
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                }
            });
        });

        // Initial load of the product table
        loadProductTable();
    });
</script>
