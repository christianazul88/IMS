<?php
include "../config/database.php";
include "../config/on_session.php";


$target_id = $_GET['target-id'] ?? "";


if(isset($target_id) && !empty($target_id)){
    $po_query = "SELECT po.*, w.warehouse_name, sup.supplier_name, sup.local_international, u.user_fname, u.user_lname FROM purchased_order po LEFT JOIN warehouse w ON w.hashed_id = po.warehouse LEFT JOIN supplier sup ON sup.hashed_id = po.supplier LEFT JOIN users u ON u.hashed_id = po.user_id WHERE po.id = '$target_id' LIMIT 1";
    $po_res = $conn->query($po_query);
    if($po_res->num_rows>0){
        $row=$po_res->fetch_assoc();
        $warehouseName = $row['warehouse_name'];
        $supplierName = $row['supplier_name'];
        $receivedBy = $row['user_fname'] . " " . $row['user_lname'];
        $orderDate = $row['date_order'];
        $receivedDate = $row['date_received'];
        if($row['local_international'] === "International"){
            $supplier_type = "Import";
        } else {
            $supplier_type = $row['local_international'];
        }

        if($row['status'] == 1){
          $status = '<span class="badge rounded-pill badge-subtle-info">Sent to Supplier</span>';
        } elseif($row['status'] == 2){
          $status = '<span class="badge rounded-pill badge-subtle-secondary">Confirmed by Supplier</span>';
        } elseif($row['status'] == 3) {
          $status = '<span class="badge rounded-pill badge-subtle-primary">In Transit/ Shipped</span>';
        } elseif($row['status'] == 4){
          $status = '<span class="badge rounded-pill badge-subtle-success">Received</span>';
        } else {
          $status = '<span class="badge rounded-pill badge-subtle-warning">Drafted</span>';
        }
        if($row['status'] == 0){
          $button_anchor = '<a href="../config/receive-po.php?status=1&&po=' . $target_id . '" class="btn btn-info requires-confirmation" type="button">Sent to supplier</a>';
        } elseif($row['status'] == 1){
          $button_anchor = '<a href="../config/receive-po.php?status=2&&po=' . $target_id . '" class="btn btn-secondary requires-confirmation" type="button">Confirmed by supplier</a>';
        } elseif($row['status'] == 2) {
          $button_anchor = '<a href="../config/receive-po.php?status=3&&po=' . $target_id . '" class="btn btn-primary requires-confirmation" type="button">In Transit/ Shipped</a>';
        } elseif($row['status'] == 3){
          $button_anchor = '<a href="../config/receive-po.php?status=4&&po=' . $target_id . '" class="btn btn-success requires-confirmation" type="button">Received</a>';
        } else {
          $button_anchor = '';
        }

        $purchased_order_contents = "SELECT p.description, b.brand_name, c.category_name, poc.* FROM purchased_order_content poc LEFT JOIN product p ON p.hashed_id = poc.product_id LEFT JOIN brand b ON b.hashed_id = p.brand LEFT JOIN category c ON c.hashed_id = p.category WHERE po_id = '$target_id'";
        ?>
       
        <div class="row mt-0">
            <div class="col-12 mt-0 text-center">
                <p class="mt-0">#<?php echo $target_id;?></p>
                <p></p>
            </div>
        </div>
        <hr class="my-3">
        <div class="row">
            <div class="col-12">
                <div class="table-responsive">
                    <table class="table table-sm">  
                        <tr>
                            <th class="fs-10">To: </th>
                            <td class="fs-10"><?php echo $supplierName;?></td>
                            <th class="fs-10">From: </th>
                            <td class="fs-10"><?php echo $warehouseName;?></td>
                        </tr>
                        <tr>
                            <th class="fs-10">Address: </th>
                            <td class="fs-10"><?php echo $supplier_type;?></td>
                            <th class="fs-10">Order Date: </th>
                            <td class="fs-10"><?php echo $orderDate;?></td>
                        </tr>
                        <tr>
                            <th class="fs-10">Status: </th>
                            <td class="fs-10"><?php echo $status;?></td>
                            <th class="fs-10">Prepared by: </th>
                            <td class="fs-10"><?php echo $receivedBy;?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <hr class="my-3">
        <div class="row">
            <div class="col-12">
                <div class="table-responsive">
                    <table class="table bordered-table table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Description</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Order QTY</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $number = 0;
                            $purchased_order_results = $conn->query($purchased_order_contents);
                            if($purchased_order_results->num_rows>0){
                                while($row=$purchased_order_results->fetch_assoc()){
                                    $order_qty = $row['qty'];
                                    $description = $row['description'];
                                    $brand_name = $row['brand_name'];
                                    $category_name = $row['category_name'];
                                    $number++;
                                    ?>
                                    <tr>
                                        <td class="fs-11"><?php echo $number; ?></td>
                                        <td class="fs-11"><?php echo $description; ?></td>
                                        <td class="fs-11"><?php echo $brand_name; ?></td>
                                        <td class="fs-11"><?php echo $category_name; ?></td>
                                        <td class="text-end fs-11"><?php echo $order_qty;?></td>
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
        <div class="row mt-5">
            <div class="col-12 text-end">
                <?php if($supplier_type !== "Local"){ echo $button_anchor; }?>
                <a href="../config/generate-po-pdf.php?target-id=<?php echo $target_id;?>" class="btn btn-warning">Save as PDF</a>
            </div>
        </div>
        <?php

    }
}
?>
