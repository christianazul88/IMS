<?php
include "../config/database.php";
include "../config/on_session.php";
if (isset($_GET['id'])) {
    $unique_key = htmlspecialchars($_GET['id']);
    $total = 0;
    $SQL = "SELECT il.*, w.warehouse_name, u.user_fname, u.user_lname, s.supplier_name, up.position_name, s.local_international, il.staff_reason
            FROM inbound_logs il
            LEFT JOIN supplier s ON s.hashed_id = il.supplier
            LEFT JOIN users u ON u.hashed_id = il.user_id
            LEFT JOIN warehouse w ON w.hashed_id = il.warehouse
            LEFT JOIN user_position up ON up.hashed_id = u.user_position
            WHERE il.unique_key = '$unique_key'
            LIMIT 1";
    $res = $conn->query($SQL);
    if($res->num_rows>0){
        $row = $res -> fetch_assoc();
        $inbound_warehouse_name = $row['warehouse_name'];
        $inbound_supplier_name = $row['supplier_name'];
        $inbound_receiver = $row['user_fname'] . " " . $row['user_lname'];
        $inbound_receiver_pos = $row['position_name'];
        $supplier_info = $row['local_international'];
        $date_received = new DateTime($row['date_received']);
        $date_received = $date_received->format('F j, Y');
        $staff_reason = !empty($row['staff_reason']) ? $row['staff_reason'] : null;
        $authorized_reason = !empty($row['authorize_reason']) ? $row['authorize_reason'] : null;
        $void_request_date = !empty($row['date_request_void']) ? $row['date_request_void'] : null;
        $approved_void_date = !empty($row['date_approved']) ? $row['date_approved'] : null;

    }
    ?>
    <style>
        .header { text-align: center; margin-bottom: 20px; }
    </style>
    <div class="rounded-top-3 py-3 ps-4 pe-6 bg-info">
        <h4 class="mb-1" id="modalExampleDemoLabel">Inbound #: <?php echo $unique_key; ?></h4>
    </div>
    <div class="p-4 pb-0">
        <div class="container">
            <div class="document-container">
                <div class="header">
                    <h2>Inbound Document</h2>
                    <p><strong>Reference No:</strong> <?php echo $unique_key; ?></p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>Sender Details</h5>
                        <p class="mb-0 mt-0"><strong>Name:</strong> <?php echo $inbound_supplier_name;?></p>
                        <p class="mb-0 mt-0"><strong>Address:</strong> <?php echo $supplier_info;?></p>
                        <p class="mb-0 mt-0"><strong>Date Received:</strong> <?php echo $date_received; ?></p>
                    </div>
                </div>
                
                <h5 class="mt-4">Item Details</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-primary">
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Parent Barcode</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "
                                SELECT 
                                    COUNT(s.product_id) AS quantity, 
                                    s.capital, 
                                    p.description, 
                                    b.brand_name, 
                                    c.category_name, 
                                    p.parent_barcode
                                FROM stocks s
                                LEFT JOIN product p ON s.product_id = p.hashed_id
                                LEFT JOIN brand b ON p.brand = b.hashed_id
                                LEFT JOIN category c ON p.category = c.hashed_id
                                WHERE s.unique_key = '$unique_key'
                                GROUP BY s.product_id
                            ";
                            $result = $conn->query($query);
                            if($result->num_rows>0){
                                $number = 0;
                                while($row=$result->fetch_assoc()){
                                    $description = $row['description'];
                                    $brand_name = $row['brand_name'];
                                    $category_name = $row['category_name'];
                                    $product_quantity = $row['quantity'];
                                    $parent_barcode = $row['parent_barcode'];
                                    $unit_price = $row['capital'];
                                    $subtotal = $unit_price * $product_quantity;
                                    $number ++;
                                    $total += $subtotal;
                                    ?>
                                    <tr>
                                        <td><?php echo $number;?></td>
                                        <td><?php echo $description;?></td>
                                        <td><?php echo $brand_name;?></td>
                                        <td><?php echo $category_name; ?></td>
                                        <td><?php echo $parent_barcode;?></td>
                                        <td class="text-end"><?php echo $product_quantity;?></td>
                                        <td class="text-end">₱<?php echo number_format($unit_price, 2); ?></td>
                                        <td class="text-end">₱<?php echo number_format($subtotal, 2);?></td>
                                    </tr>
                                    <?php 
                                }
                            }
                            ?>
                        </tbody>
                        <tfoot class="table-info">
                            <tr>
                                <td class="text-start" colspan="6"><b><i>Total</i></b></td>
                                <td class="text-end"><strong><b>₱</b></strong></td>
                                <td class="text-end"><b><i>₱<?php echo number_format($total, 2);?></i></b></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="row mt-4 mb-4">
                    <div class="col-md-6">
                        <h5>Received By</h5>
                        <p class="mb-0 mt-0"><u><?php echo $inbound_receiver;?></u></p>
                        <p class="mb-0 mt-0"><?php echo $inbound_receiver_pos;?></p>
                        <b class="mb-0 mt-0"><small> <?php echo $inbound_warehouse_name;?></small></b>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-reason bg-info">
            <table class="table table-bordered table-sm">
                <tr>
                    <th>
                        <b>Staff Reason:</b><br>
                        <small><?php echo $void_request_date;?></small><br>
                        <?php echo $staff_reason;?>
                    </th>
                    <th>
                        <b>Reason(if declined)</b><br>
                        <small><?php echo $approved_void_date;?></small><br>
                        <?php echo $authorized_reason;?>
                    </th>
                </tr>
            </table>
        </div>
        <div class="text-end my-3">
            <a href="url.php?id=<?php echo $unique_key;?>" class="btn btn-warning">Export</a>
        </div>
    </div>
    <?php
}
$conn->close();