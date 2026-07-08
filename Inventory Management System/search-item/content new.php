<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">

        <h4 class="fw-bold mb-1">
            <i class="bi bi-search"></i> Inventory Search
        </h4>

        <p class="text-muted mb-4">
            Search using barcode, product name, brand, category, warehouse, customer or outbound number.
        </p>

        <form action="index.php" method="POST">

            <div class="input-group input-group-lg">

                <span class="input-group-text bg-primary text-white">
                    <i class="bi bi-upc-scan"></i>
                </span>

                <input
                    type="text"
                    class="form-control"
                    name="search"
                    placeholder="Scan or type barcode..."
                    autofocus
                    value="<?php
                    if(isset($_POST['search'])){
                        echo htmlspecialchars($_POST['search']);
                    }
                    ?>">

                <button class="btn btn-primary px-4">
                    <i class="bi bi-search"></i>
                    Search
                </button>

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
    <div class="d-flex justify-content-between align-items-center mb-3">

        <h4 class="fw-bold">
            <i class="bi bi-box-seam text-success"></i>
            Stocks Found
        </h4>

        <span class="badge bg-success fs-6">
            '.$product_result->num_rows.' Result(s)
        </span>

    </div>
    ';
    while ($row = $product_result->fetch_assoc()){
        $search_unique_barcode = htmlspecialchars($row['unique_barcode']);
        $search_product_description = htmlspecialchars($row['description']);
        $search_brand = htmlspecialchars($row['brand_name']);
        $search_category = htmlspecialchars($row['category_name']);
        $search_warehouse = htmlspecialchars($row['warehouse_name']);

        echo '

        <div class="card shadow-sm border-0 mb-3">

            <div class="card-body">

            <div class="row align-items-center">

            <div class="col-lg-7">

            <h5 class="fw-bold mb-2">
            '.$search_product_description.'
            </h5>

            <div class="mb-2">

            <span class="badge bg-primary me-2">
            '.$search_brand.'
            </span>

            <span class="badge bg-secondary">
            '.$search_category.'
            </span>

            </div>

            <div class="text-muted">

            <i class="bi bi-upc-scan"></i>

            '.$search_unique_barcode.'

            </div>

            </div>

            <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">

            <div class="fw-semibold text-primary">

            <i class="bi bi-building"></i>

            '.$search_warehouse.'

            </div>

            </div>

            </div>

            </div>

        </div>

        ';
    }    
}

if($outbound_result->num_rows>0){
    echo '

    <div class="d-flex justify-content-between align-items-center mt-5 mb-3">

    <h4 class="fw-bold">

    <i class="bi bi-truck"></i>

    Outbound Records

    </h4>

    <span class="badge bg-danger fs-6">

    '.$outbound_result->num_rows.' Record(s)

    </span>

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

        <div class="card border-0 shadow-sm mb-3">

        <div class="card-body">

        <div class="row">

        <div class="col-lg-8">

        <h5 class="fw-bold">

        '.$order_num.'

        </h5>

        <div class="text-muted mb-2">

        '.$customer_fullname.'

        </div>

        <div>

        <span class="badge bg-primary">

        '.$outbound_brand.'

        </span>

        <span class="badge bg-secondary">

        '.$outbound_cate.'

        </span>

        </div>

        <div class="mt-2">

        <strong>'.$outbound_desc.'</strong>

        </div>

        <div class="text-muted">

        '.$outbound_barcode.'

        </div>

        </div>

        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">

        <div>

        <strong>Warehouse</strong>

        <br>

        '.$order_warehouse.'

        </div>

        <div class="mt-2">

        <strong>Sold Price</strong>

        <br>

        ₱'.number_format($outbound_sold_amount,2).'

        </div>

        <div class="mt-2">

        <span class="badge '.($outbound_status=="completed"
        ? "bg-success"
        : "bg-warning text-dark").'">

        '.$outbound_status.'

        </span>

        </div>

        </div>

        </div>

        </div>

        </div>

        ';
    }
}

