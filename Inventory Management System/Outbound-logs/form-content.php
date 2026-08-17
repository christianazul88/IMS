<?php 
include "../config/database.php";
include "../config/on_session.php";

if (isset($_GET['id'])) {
    $outbound_id = $_GET['id'];

    // Using prepared statements for the first query
    $stmt = $conn->prepare("SELECT u.user_fname, u.user_lname, w.warehouse_name, ol.*, lp.logistic_name, c.courier_name
                            FROM outbound_logs ol
                            LEFT JOIN users u ON u.hashed_id = ol.user_id
                            LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
                            LEFT JOIN logistic_partner lp ON lp.hashed_id = ol.platform
                            LEFT JOIN courier c ON c.hashed_id = ol.courier 
                            WHERE ol.hashed_id = ? LIMIT 1");
    $stmt->bind_param("s", $outbound_id); // Bind the outbound_id variable
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $staff_name = $row['user_fname'] . " " . $row['user_lname'];
        $warehouse_outbound = $row['warehouse_name'];
        $customer_fullname = $row['customer_fullname'];
        $order_num = $row['order_num'];
        $order_line_id = $row['order_line_id'];
        $logistic_name = $row['logistic_name'];
        $courier = $row['courier_name'];
        $date_Sent = $row['date_sent'];
        $outbound_status = $row['status'];
        $outbound_user_id = $row['user_id'];
        $staff_reason = !empty($row['staff_reason']) ? $row['staff_reason'] : null;
        $authorized_reason = !empty($row['authorize_reason']) ? $row['authorize_reason'] : null;
        $void_request_date = !empty($row['date_request_void']) ? $row['date_request_void'] : null;
        $approved_void_date = !empty($row['date_approved']) ? $row['date_approved'] : null;
    ?>
    <style>
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
        }
    </style>
    
    <div class="rounded-top-3 py-3 ps-4 pe-6 bg-info">
        <h4 class="mb-1" id="modalExampleDemoLabel">Outbound </h4>
    </div>
    
    <div class="p-4 pb-0">
        <div class="container">
            <div class="document-container">
                <div class="header">
                    <p><strong>Reference No:</strong><?php echo $outbound_id;?></p>
                </div>
                
                <div class="row">
                    <div class="col-lg-12 text-end my-3">
                    <?php 
                    if($outbound_status == 6 && $user_id === $outbound_user_id){
                    ?>
                        <button class="btn btn-primary fs-11 me-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $outbound_id;?>" aria-expanded="false" aria-controls="collapse<?php echo $outbound_id;?>"><span class="fas fa-trash-alt"></span> Void</button>
                    
                    <?php 
                    }
                    ?>
                        <a class="btn btn-warning" href="export.php?name=<?php echo $outbound_id;?>">Export</a>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-sm">
                            <tr>
                                    <th class='text-start fs-10'>Logistic Partner:</th>
                                    <td class='text-start fs-10'><?php echo $logistic_name;?></td>
                                </tr>
                                <tr>
                                    <th class='text-start fs-10'>Courier:</th>
                                    <td class='text-start fs-10'><?php echo $courier;?></td>
                                </tr>
                                <tr>
                                    <th class='text-start fs-10'>Order no.:</th>
                                    <td class='text-start fs-10'><?php echo $order_num;?></td>
                                </tr>
                                <tr>
                                    <th class='text-start fs-10'>Order Line ID:</th>
                                    <td class='text-start fs-10'><?php echo $order_line_id;?></td>
                                </tr>
                                <tr>
                                    <th class='text-start fs-10'>Customer:</th>
                                    <td class='text-start fs-10'><?php echo $customer_fullname;?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <table class="table table-sm">
                            <tr>
                                <th class='text-start fs-10'>Staff Name:</th>
                                <td class='text-start fs-10'><?php echo $staff_name;?></td>
                            </tr>
                            <tr>
                                <th class='text-start fs-10'>Warehouse:</th>
                                <td class='text-start fs-10'><?php echo $warehouse_outbound;?></td>
                            </tr>
                            <tr>
                                <th class='text-start fs-10'>Outbound date:</th>
                                <td class='text-start fs-10'><?php echo $date_Sent;?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <h5 class="mt-4">Item Details</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-primary">
                            <tr>
                                <?php
                                $table_headers = [
                                    '#', 'Item Name', 'Brand', 'Category', 'Parent Barcode', 
                                    'Quantity before', 'Quantity', 'Quantity after', 
                                    'Unit Price', 'Sold Price', 'Profit', 'Total'
                                ];
                                foreach ($table_headers as $header) {
                                    if($header === "Parent Barcode"){
                                        echo "<th class='fs-10 text-end' style='width: 400px;'>{$header}</th>";
                                    } else {
                                        echo "<th class='fs-10 text-end'>{$header}</th>";
                                    }
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Second query: one row per unit, so each unique_barcode can be checked
                            // against the returns table individually (returns are logged per
                            // unique_barcode, not per parent_barcode/SKU). Grouping into line
                            // items now happens in PHP below instead of via SQL GROUP BY, since
                            // we need the individual barcodes - not just a count of them - to
                            // know which specific unit(s) came back.
                            //
                            // returns.date >= ol.date_sent (this outbound's own send date, bound
                            // as $date_Sent) makes sure we only match a return that happened as a
                            // result of *this* outbound, in case a unique_barcode gets reused for
                            // a different unit later on and picks up an unrelated return record.
                            $query = "
                                SELECT p.description, b.brand_name, c.category_name, s.parent_barcode,
                                       oc.quantity_before, oc.quantity_after, oc.unique_barcode,
                                       s.capital, oc.sold_price,
                                       r.reason AS return_reason, r.fault AS return_fault,
                                       r.fault_type AS return_fault_type, r.date AS return_date
                                FROM outbound_content oc
                                LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
                                LEFT JOIN product p ON p.hashed_id = s.product_id
                                LEFT JOIN brand b ON b.hashed_id = p.brand
                                LEFT JOIN category c ON c.hashed_id = p.category
                                LEFT JOIN returns r ON r.unique_barcode = oc.unique_barcode AND r.date >= ?
                                WHERE oc.hashed_id = ?
                                ORDER BY s.parent_barcode, oc.unique_barcode";

                            $stmt2 = $conn->prepare($query);
                            $stmt2->bind_param("ss", $date_Sent, $outbound_id);
                            $stmt2->execute();
                            $res = $stmt2->get_result();

                            // Group the per-unit rows into one line item per parent_barcode
                            $items = [];
                            while ($unit = $res->fetch_assoc()) {
                                $pb = $unit['parent_barcode'];
                                if (!isset($items[$pb])) {
                                    $items[$pb] = [
                                        'description'     => $unit['description'],
                                        'brand_name'       => $unit['brand_name'],
                                        'category_name'    => $unit['category_name'],
                                        'quantity_before'  => $unit['quantity_before'],
                                        'quantity_after'   => $unit['quantity_after'],
                                        'capital'          => $unit['capital'],
                                        'sold_price'       => $unit['sold_price'],
                                        'units'            => [],
                                    ];
                                }
                                $items[$pb]['units'][] = [
                                    'barcode'     => $unit['unique_barcode'],
                                    'returned'    => !empty($unit['return_date']),
                                    'reason'      => $unit['return_reason'],
                                    'fault'       => $unit['return_fault'],
                                    'fault_type'  => $unit['return_fault_type'],
                                    'return_date' => $unit['return_date'],
                                ];
                            }

                            $count = 1;
                            $total = 0;
                            $total_profit = 0;
                            if (!empty($items)) {
                                foreach ($items as $parentBarcode => $item) {
                                    $productDescription = $item['description'];
                                    $brandName = $item['brand_name'];
                                    $categoryName = $item['category_name'];
                                    $quantityBefore = $item['quantity_before'];
                                    $quantityAfter = $item['quantity_after'];
                                    $quantity = count($item['units']);
                                    $productCapital = $item['capital'];
                                    $soldPrice = $item['sold_price'];
                                    $sub_Total = $quantity * $soldPrice;
                                    $profit = $soldPrice - $productCapital;
                                    $sub_profit = $profit * $quantity;
                                    $returned_count = count(array_filter($item['units'], fn($u) => $u['returned']));
                                    ?>
                                     
                                    <tr>
                                        <td class="fs-10" style="width: 550px;"><?php echo $count;?></td>
                                        <td class="fs-10"><?php echo htmlspecialchars($productDescription);?></td>
                                        <td class="fs-10"><?php echo htmlspecialchars($brandName);?></td>
                                        <td class="fs-10"><?php echo htmlspecialchars($categoryName);?></td>
                                        <td class="fs-11">
                                            <?php echo htmlspecialchars($parentBarcode); ?>
                                            <?php if ($returned_count > 0): ?>
                                                <span class="badge rounded-pill badge-subtle-danger ms-1">
                                                    <?php echo $returned_count;?>/<?php echo $quantity;?> returned
                                                </span>
                                            <?php endif; ?>
                                            <br>
                                            <?php $unit_count = count($item['units']); ?>
                                            <?php foreach ($item['units'] as $u_idx => $unit): ?>
                                                <?php if ($unit['returned']): ?>
                                                    <span class="badge rounded-pill badge-subtle-danger fs-11 me-1 mb-1"
                                                          data-bs-toggle="tooltip"
                                                          title="<?php echo htmlspecialchars(trim(
                                                              ($unit['fault'] ? $unit['fault'] . ' - ' : '') .
                                                              ($unit['fault_type'] ?? '') .
                                                              ($unit['reason'] ? ' (' . $unit['reason'] . ')' : '') .
                                                              ($unit['return_date'] ? ' on ' . $unit['return_date'] : '')
                                                          ));?>">
                                                        <span class="fas fa-undo fs-11"></span> <?php echo htmlspecialchars($unit['barcode']);?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="fs-11"><?php echo htmlspecialchars($unit['barcode']);?></span>
                                                <?php endif; ?><?php echo ($u_idx < $unit_count - 1) ? ', ' : '';?>
                                            <?php endforeach; ?>
                                        </td>
                                        <td class="text-end fs-10" style="width: 250px;"><?php echo $quantityBefore;?></td>
                                        <td class="text-end fs-10" style="width: 250px;"><?php echo $quantity;?></td>
                                        <td class="text-end fs-10" style="width: 250px;"><?php echo $quantityAfter;?></td>
                                        <?php 
                                        if(strpos($access, "view_capital")!==false || $user_position_name === "Administrator" || $user_position_name === "administrator"){
                                        ?>
                                        <td class="text-end fs-10" style="width: 250px;">₱ <?php echo number_format($productCapital, 2);?></td>
                                        <?php 
                                        } 
                                        ?>
                                        <td class="text-end fs-10" style="width: 250px;">₱ <?php echo number_format($soldPrice, 2);?></td>
                                        <?php 
                                        if(strpos($access, "view_profit")!==false || $user_position_name === "Administrator" || $user_position_name === "administrator"){
                                        ?>
                                        <td class="text-end fs-10" style="width: 250px;">₱ <?php echo number_format($sub_profit, 2);?></td>
                                        <?php 
                                        }
                                        ?>
                                        <td class="text-end fs-10" style="width: 250px;">₱ <?php echo number_format($sub_Total, 2);?></td>
                                    </tr>
                                    
                                            <?php
                                    $count++;
                                    $total += $sub_Total;
                                    $total_profit += $sub_profit;
                                }
                            } else {
                                echo "<tr><td colspan='12' class='text-center'>No items found</td></tr>";
                            }
                            ?>
                            
                        </tbody>
                        <tfoot class="table-info">
                            <?php 
                            if(strpos($access, "view_profit")!==false || $user_position_name === "Administrator" || $user_position_name === "administrator"){
                            ?>
                            <tr>
                                <td class="text-start fs-10 text-end" colspan="10"><b><i>Total Profit</i></b></td>
                                <td class="text-end fs-10"><strong>₱</strong></td>
                                <td class="text-end fs-10"><b><i>₱<?php echo number_format($total_profit, 2); ?></i></b></td>
                            </tr>
                            <?php 
                            }
                            ?>

                            <tr>
                                <td class="text-start fs-10 text-end" colspan="10"><b><i>Total Sales</i></b></td>
                                <td class="text-end fs-10"><strong>₱</strong></td>
                                <td class="text-end fs-10"><b><i>₱<?php echo number_format($total, 2); ?></i></b></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Fragment is loaded via $("#target-id").load(...), so this <script> runs
    // once per open. Init tooltips just for the returned-barcode badges above.
    (function () {
        var tooltipEls = document.querySelectorAll('#target-id [data-bs-toggle="tooltip"]');
        tooltipEls.forEach(function (el) {
            var existing = bootstrap.Tooltip.getInstance(el);
            if (existing) existing.dispose();
            new bootstrap.Tooltip(el);
        });
    })();
    </script>

    <?php 
        if($outbound_status == 6 && $user_id === $outbound_user_id){
    ?>
    <div class="collapse" id="collapse-<?php echo $outbound_id;?>">
        <div class="row px-6">
            <form class="void-form" method="POST" action="../config/void-outbound.php">
                <input type="text" name="outbound_id" value="<?php echo $outbound_id;?>" hidden>
                <div class="col-lg-12 mb-3">
                    <label for="reason">Reason for Void?</label>
                    <textarea name="reason" class="form-control" required></textarea>
                </div>
                <div class="col-lg-12 text-center mb-3">
                    <button class="btn btn-primary fs-11" type="submit">Submit</button>
                </div>
            </form>
        </div>
    </div>
    <?php 
        } elseif($outbound_status == 6 && $user_id !== $outbound_user_id){
    ?>

    <?php
        } elseif($outbound_status == 3 && $user_id !== $outbound_user_id && strpos($access, "approve_inbound")!==false || $outbound_status == 3 && $user_id !== $outbound_user_id && $user_position_name === "Administrator"){
    ?>
    <div class="p-4 pb-0">
        <div class="row">
            <div class="col-lg-12">
            <form class="void-decision" method="POST" action="../config/void-decision.php">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reason for Void</th>
                                <th>Reason for Void Authorization</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <small><i><?php echo $void_request_date;?></i></small><br>
                                    <?php echo $staff_reason;?>
                                </td>
                                <td>
                                    <input class="form-control" name="outbound_id" type="text" value="<?php echo $outbound_id; ?>" hidden>
                                    <input class="form-control" name="to_userid" type="text" value="<?php echo $outbound_user_id; ?>" hidden>

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="form-check form-check-inline"><input class="form-check-input" id="inlineRadio1" type="radio" name="response" value="approve" required/><label class="form-check-label" for="inlineRadio1">Approve</label></div>
                                            <div class="form-check form-check-inline"><input class="form-check-input" id="inlineRadio2" type="radio" name="response" value="decline" required/><label class="form-check-label" for="inlineRadio2">Decline</label></div>
                                        </div>
                                        
                                        <div class="col-lg-12">
                                            <label for="reason_admin">Reason(if Decline)</label>
                                            <textarea class="form-control" name="reason_admin" required></textarea>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        
                    </table>
                </div>

                <div class="col-lg-12 text-center">
                    <button class="btn btn-primary fs-11" type="submit">Submit</button>
                </div>
            </form>
        </div>
    </div>
    <?php
        } elseif($outbound_status == 4 && $user_id !== $outbound_user_id && strpos($access, "approve_inbound")!==false || $outbound_status == 4 && $user_id !== $outbound_user_id && $user_position_name === "Administrator" || $outbound_status == 5 && $user_id !== $outbound_user_id && strpos($access, "approve_inbound")!==false || $outbound_status == 5 && $user_id !== $outbound_user_id && $user_position_name === "Administrator"){
    ?>
    <div class="p-4 pb-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reason for Void</th>
                                <th>Reason for Void Authorization</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <small><i><?php echo $void_request_date;?></i></small><br>
                                    <?php echo $staff_reason;?>
                                </td>
                                <td>
                                    <small><i><?php echo $approved_void_date;?></i></small><br>
                                    <?php echo $authorized_reason;?>
                                </td>
                            </tr>
                        </tbody>
                        
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
        }
    ?>
    <?php
    }
} else {
    echo "You are not authorized here!";
}
?>
