please improve user interface
<div class="card">
    <div class="card-body overflow-hidden">
        <form action="index.php" method="POST">
            <div class="row">
                <div class="col-6">
                    <label for="search">Input Barcode: </label>
                    <input type="text" name="search" class="form-control" <?php 
                        if (isset($_POST['barcode'])) {
                            echo "value='" . htmlspecialchars($_POST['barcode'], ENT_QUOTES, 'UTF-8') . "'";
                        }
                        ?>>            
                </div>
                <div class="col-2">
                    <button type="submit" class="btn btn-primary" hidden>Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
if (isset($_POST['search'])) {

    $search_input = trim($_POST['search']);
    $search = "%{$search_input}%";

    /* ============================
       PRODUCT SEARCH
    ============================ */

    $search_product_query = "
        SELECT
            p.description,
            b.brand_name,
            c.category_name,
            p.parent_barcode,
            s.unique_barcode,
            w.warehouse_name
        FROM stocks s
        LEFT JOIN product p
            ON p.hashed_id = s.product_id
        LEFT JOIN brand b
            ON b.hashed_id = p.brand
        LEFT JOIN category c
            ON c.hashed_id = p.category
        LEFT JOIN warehouse w
            ON w.hashed_id = s.warehouse
        WHERE
            p.description LIKE ?
            OR b.brand_name LIKE ?
            OR c.category_name LIKE ?
            OR p.parent_barcode LIKE ?
            OR s.unique_barcode LIKE ?
            OR w.warehouse_name LIKE ?
    ";

    $product_stmt = $conn->prepare($search_product_query);
    $product_stmt->bind_param(
        "ssssss",
        $search,
        $search,
        $search,
        $search,
        $search,
        $search
    );
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();


    /* ============================
       OUTBOUND SEARCH
    ============================ */

    $search_outbound_num_query = "
        SELECT
            ol.hashed_id,
            ol.customer_fullname,
            ol.order_num,
            ol.order_line_id,
            w.warehouse_name,
            u.user_fname,
            u.user_lname,
            oc.unique_barcode,
            oc.sold_price,
            oc.status,
            p.description,
            b.brand_name,
            c.category_name
        FROM outbound_content oc
        INNER JOIN outbound_logs ol
            ON ol.hashed_id = oc.hashed_id
        INNER JOIN stocks s
            ON s.unique_barcode = oc.unique_barcode
        LEFT JOIN product p
            ON p.hashed_id = s.product_id
        LEFT JOIN brand b
            ON b.hashed_id = p.brand
        LEFT JOIN category c
            ON c.hashed_id = p.category
        LEFT JOIN warehouse w
            ON w.hashed_id = ol.warehouse
        LEFT JOIN users u
            ON u.hashed_id = ol.user_id
        WHERE
            ol.hashed_id LIKE ?
            OR ol.customer_fullname LIKE ?
            OR ol.order_num LIKE ?
            OR ol.order_line_id LIKE ?
            OR w.warehouse_name LIKE ?
            OR u.user_fname LIKE ?
            OR u.user_lname LIKE ?
            OR oc.unique_barcode LIKE ?
            OR oc.sold_price LIKE ?
            OR oc.status LIKE ?
            OR p.description LIKE ?
            OR b.brand_name LIKE ?
            OR c.category_name LIKE ?
    ";

    $outbound_stmt = $conn->prepare($search_outbound_num_query);
    $outbound_stmt->bind_param(
        "sssssssssssss",
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search,
        $search
    );
    $outbound_stmt->execute();
    $outbound_result = $outbound_stmt->get_result();
}
?>



<?php 
if ($product_result->num_rows > 0) { 
    echo '
    <div class="card mt-3">
        <div class="card-body">
            <h2>Stocks Found</h2>
        </div>
    </div>
    ';
    while ($row = $product_result->fetch_assoc()){
        $search_unique_barcode = htmlspecialchars($row['unique_barcode']);
        $search_product_description = htmlspecialchars($row['description']);
        $search_brand = htmlspecialchars($row['brand_name']);
        $search_category = htmlspecialchars($row['category_name']);
        $search_warehouse = htmlspecialchars($row['warehouse_name']);

        echo '
        <div class="card mt-2">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h2>'. $search_product_description .'</h2>
                        <span>' . $search_brand . ' |</span>
                        <span> ' . $search_category . '</span>
                    </div>
                    <div class="col-6">
                        <h3>' . $search_unique_barcode . '</h3>
                        <span>' . $search_warehouse . '</span>
                    </div>
                </div>
                
            </div>
        </div>
        ';
    }    
}

if($outbound_result->num_rows>0){
    echo '
        <div class="card mt-5">
            <div class="card-body">
                <h2>Outbounds</h2>
            </div>
        </div>
    ';
    while($row = $outbound_result->fetch_assoc()){
        $outbound_id = $row['hashed_id'];
        $customer_fullname = $row['customer_fullname'];
        $order_num = $row['order_num'];
        $order_line_id = $row['order_line_id'];
        $order_warehouse = $row['warehouse_name'];
        $outbound_by = $row['user_fname'] . " " . $row['user_lname'];
        $outbound_barcode = $row['unique_barcode'];
        $outbound_sold_amount = $row['sold_price'];
        $outbound_status = $row['status'];
        $outbound_desc = $row['description'];
        $outbound_brand = $row['brand_name'];
        $outbound_cate = $row['category_name'];
        echo '
            <div class="card mt-3">
                <div class="card-body">
                    <h2>Outbound NUMBER</h2>
                    <p class="mb-0">barcode | Description | brand | category</p>
                    <small class="mt-0">outbound id | warehouse</small>
                </div>
            </div>
        ';
    }
}

