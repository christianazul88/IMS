<?php 
include "../config/database.php";
include "../config/on_session.php";

if (isset($_GET['id'])) {
    $order_no = $_GET['id'];

    // Using prepared statements for the first query
    $stmt = $conn->prepare("SELECT u.user_fname, u.user_lname, w.warehouse_name, ol.*, lp.logistic_name, c.courier_name
                            FROM outbound_logs ol
                            LEFT JOIN users u ON u.hashed_id = ol.user_id
                            LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse
                            LEFT JOIN logistic_partner lp ON lp.hashed_id = ol.platform
                            LEFT JOIN courier c ON c.hashed_id = ol.courier 
                            WHERE ol.order_num = ? LIMIT 1");
    $stmt->bind_param("s", $order_no); // Bind the outbound_id variable
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $outbound_id = $row['hashed_id'];
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
    
    <div class="p-4 pb-0">
        <div class="container">
            <div class="document-container">
                <div class="header">
                    <p><strong>Reference No:</strong><?php echo $outbound_id;?></p>
                </div>
                
                <div class="row">
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
                            // Second query: Using prepared statements and handling result
                            $query = "
                                SELECT p.description, b.brand_name, c.category_name, s.parent_barcode, 
                                       oc.quantity_before, oc.quantity_after, COUNT(s.parent_barcode) AS quantity, 
                                       s.capital, oc.sold_price
                                FROM outbound_content oc
                                LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
                                LEFT JOIN product p ON p.hashed_id = s.product_id
                                LEFT JOIN brand b ON b.hashed_id = p.brand
                                LEFT JOIN category c ON c.hashed_id = p.category
                                WHERE oc.hashed_id = ?
                                GROUP BY s.parent_barcode";
                                
                            $stmt2 = $conn->prepare($query);
                            $stmt2->bind_param("s", $outbound_id);
                            $stmt2->execute();
                            $res = $stmt2->get_result();
                            
                            $count = 1;
                            $total = 0;
                            $total_profit = 0;
                            if ($res->num_rows > 0) {
                                while ($row = $res->fetch_assoc()) {
                                    $productDescription = $row['description'];
                                    $brandName = $row['brand_name'];
                                    $categoryName = $row['category_name'];
                                    $parentBarcode = $row['parent_barcode'];
                                    $quantityBefore = $row['quantity_before'];
                                    $quantityAfter = $row['quantity_after'];
                                    $quantity = $row['quantity'];
                                    $productCapital = $row['capital'];
                                    $soldPrice = $row['sold_price'];
                                    $sub_Total = $quantity * $soldPrice;
                                    $profit = $soldPrice - $productCapital;
                                    $sub_profit = $profit * $quantity;
                                    ?>
                                     
                                    <tr>
                                        <td class="fs-10" style="width: 550px;"><?php echo $count;?></td>
                                        <td class="fs-10"><?php echo $productDescription;?></td>
                                        <td class="fs-10"><?php echo $brandName;?></td>
                                        <td class="fs-10"><?php echo $categoryName;?></td>
                                        <td class="fs-11">
                                            <?php 
                                            echo $parentBarcode . "<br>";
                                            $last_query = "SELECT unique_barcode FROM outbound_content WHERE hashed_id = '$outbound_id'";
                                            $last_res = $conn->query($last_query);
                                            if($last_res->num_rows>0){
                                                while($row=$last_res->fetch_assoc()){
                                                    $unique_bc = $row['unique_barcode'];
                                                    echo $unique_bc . ", ";
                                                }
                                            }
                                            ?>
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
    } else {
    ?>
    <div class="row">
        <div class="col-xxl-12 text-center py-6">
            <h2>Do not exist on the IMS.</h2>
        </div>
    </div>
    <?php
    }
} else {
    echo "You are not authorized here!";
}
?>
