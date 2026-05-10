<div class="col-lg-12">
    <div class="card">
        <div class="card-header">
            <h2>Stocks</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
                    <thead>
                        <tr>
                            <th></th> <!-- images -->
                            <th>Unique ID</th>
                            <th>Product Count</th>
                            <th>Descriptions</th>
                            <th>Brand</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $inventory_query = "SELECT COUNT(p.parent_barcode) AS qty, p.unique_id, p.description, b.brand_name, c.category_name
                        FROM product p
                        LEFT JOIN brand b ON b.hashed_id = p.brand
                        LEFT JOIN category c ON c.hashed_id = p.category
                        GROUP BY p.unique_id, p.brand, p.category";

                        $inventory_result = $conn->query($inventory_query);
                        if ($inventory_result->num_rows > 0) {
                            while ($row = $inventory_result->fetch_assoc()) {
                                $qty = $row['qty'];
                                $unique_id = $row['unique_id'];  
                                $brand = $row['brand_name'];
                                $category = $row['category_name'];

                                $product_query = "SELECT description FROM product WHERE unique_id = '$unique_id'";
                                $product_res = $conn->query($product_query);
                                $descriptions = [];
                                if ($product_res->num_rows > 0) {
                                    while ($desc_row = $product_res->fetch_assoc()) {
                                        $descriptions[] = $desc_row['description'];
                                    }
                                }
                                ?>
                                <tr>
                                    <td><img src="../../assets/img/def_img.png" height="50" alt=""></td>
                                    <td><?php echo htmlspecialchars($unique_id); ?></td>
                                    <td><?php echo htmlspecialchars($qty); ?></td>
                                    <td class="d-inline-block text-truncate" style="max-width: 850px;"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="<?php echo htmlspecialchars(implode(', ', $descriptions)); ?>"
                                        data-bs-html="true">
                                        <?php echo htmlspecialchars(implode(', ', $descriptions)); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($brand); ?></td>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                </tr>
                                <?php
                            }

                        }
                        ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>