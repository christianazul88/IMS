<div class="card">
    <div class="card-body">
        <div class="row flex-between-center g-0">
            <div class="col-auto mb-3">
                <h6 class="mb-0">
                    <a href="../incoming-stocks/?wh=<?php echo $dashboard_wh; ?>">Incoming Stocks</a>
                </h6>
            </div>
            <div class="col-12">
                <div class="table-responsive">
                    <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-end">Stocks</th>
                                <th class="text-end">Incoming Stocks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Make sure you already have $conn connected before this point

                            $incoming_stocks = "SELECT * FROM category LIMIT 10";

                            $incoming_stocks_res = $conn->query($incoming_stocks);
                            if ($incoming_stocks_res && $incoming_stocks_res->num_rows > 0) {
                                while ($row = $incoming_stocks_res->fetch_assoc()) {
                                    $incoming_category_id = $row['hashed_id'];
                                    $incoming_category = $row['category_name'];
                                    $current_stocks = 0;
                                    $incoming_qty = 0;
                                    if(empty($dashboard_wh)){
                                        $products_query = "
                                        SELECT 
                                            COUNT(s.unique_barcode) AS stocks
                                        FROM product p
                                        LEFT JOIN stocks s ON s.product_id = p.hashed_id
                                        WHERE s.item_status NOT IN (1, 4, 8) AND s.batch_code != '-' AND p.category = '$incoming_category_id'
                                        AND s.warehouse IN ($imploded_warehouse_ids)
                                        GROUP BY p.category
                                        ";

                                        $po_query = "
                                            SELECT 
                                                SUM(poc.qty) AS incoming
                                            FROM purchased_order_content poc
                                            LEFT JOIN purchased_order po ON po.id = poc.po_id
                                            LEFT JOIN product p ON p.hashed_id = poc.product_id
                                            LEFT JOIN category c ON c.hashed_id = p.category
                                            WHERE po.status NOT IN (0, 4) AND p.category = '$incoming_category_id' AND c.hashed_id = '$incoming_category_id'
                                            AND po.warehouse IN ($imploded_warehouse_ids)
                                            GROUP BY c.hashed_id, p.category
                                        ";
                                    } else {
                                        $products_query = "
                                        SELECT 
                                            COUNT(s.unique_barcode) AS stocks
                                        FROM product p
                                        LEFT JOIN stocks s ON s.product_id = p.hashed_id
                                        WHERE s.item_status NOT IN (1, 4, 8) AND s.batch_code != '-' AND p.category = '$incoming_category_id'
                                        AND s.warehouse = '$dashboard_wh'
                                        GROUP BY p.category
                                        ";

                                        $po_query = "
                                            SELECT 
                                                SUM(poc.qty) AS incoming
                                            FROM purchased_order_content poc
                                            LEFT JOIN purchased_order po ON po.id = poc.po_id
                                            LEFT JOIN product p ON p.hashed_id = poc.product_id
                                            LEFT JOIN category c ON c.hashed_id = p.category
                                            WHERE po.status NOT IN (0, 4) AND p.category = '$incoming_category_id' AND c.hashed_id = '$incoming_category_id'
                                            AND po.warehouse = '$dashboard_wh'
                                            GROUP BY c.hashed_id, p.category
                                        ";
                                    }
                                    $product_res = $conn->query($products_query);
                                    if($product_res->num_rows>0){
                                        $row=$product_res->fetch_assoc();
                                        $current_stocks = $row['stocks'];
                                    }

                            
                                    $po_res = $conn->query($po_query);
                                    if($po_res->num_rows>0){
                                        $row=$po_res->fetch_assoc();
                                        $incoming_qty = $row['incoming'];
                                    }

                                    echo '<tr>
                                        <td><a href="../incoming-stocks/?name2=' . $incoming_category . '&&name=' . $incoming_category_id . '&&wh=' . $dashboard_wh . '">' . htmlspecialchars($incoming_category) . '</a></td>
                                        <td class="text-end">' . $current_stocks . '</td>
                                        <td class="text-end">' . $incoming_qty . '</td>
                                    </tr>';
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
