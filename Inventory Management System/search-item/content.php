<div class="row">
    <div class="col-12 mb-2">
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
    </div>

    <?php
        $total_rows = 0;

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
                    OR s.unique_barcode LIKE ?
                LIMIT 100
            ";

            $product_stmt = $conn->prepare($search_product_query);
            $product_stmt->bind_param(
                "ss",
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
                    OR oc.unique_barcode LIKE ?
                    OR p.description LIKE ?
                LIMIT 100
            ";

            $outbound_stmt = $conn->prepare($search_outbound_num_query);
            $outbound_stmt->bind_param(
                "ssssss",
                $search,
                $search,
                $search,
                $search,
                $search,
                $search
            );
            $outbound_stmt->execute();
            $outbound_result = $outbound_stmt->get_result();


            /* ============================
            STOCK TRANSFER SEARCH
            ============================ */

            $search_stock_transfer_query = "
                SELECT
                    stc.unique_barcode,
                    st.id AS stock_transfer_id,
                    st.status,
                    st.date_out,
                    st.date_received,
                    st.remarks_sender,
                    st.remarks_receiver,
                    p.description,
                    b.brand_name,
                    c.category_name
                FROM stock_transfer_content stc
                INNER JOIN stock_transfer st
                    ON st.id = stc.st_id
                INNER JOIN stocks s 
                    ON s.unique_barcode = stc.unique_barcode
                INNER JOIN product p
                    ON p.hashed_id = s.product_id
                INNER JOIN brand b
                    ON b.hashed_id = p.brand
                INNER JOIN category c
                    ON c.hashed_id = p.category
                WHERE 
                    stc.unique_barcode LIKE ?
                    OR (st.id + 10000) LIKE ?
                    OR p.description LIKE ?
                LIMIT 100
            ";

            $stc_stmt = $conn->prepare($search_stock_transfer_query);
            $stc_stmt->bind_param(
                "sss",
                $search,
                $search,
                $search
            );
            $stc_stmt->execute();
            $stc_result = $stc_stmt->get_result();


            $product_row_count = $product_result->num_rows;
            $outbound_row_count = $outbound_result->num_rows;
            $stock_transfer_row_count = $stc_result->num_rows;

            $total_rows = $product_row_count + $outbound_row_count + $stock_transfer_row_count;
            // echo $total_rows;
        }
    ?>


    <?php 
    if($total_rows<=100){
    ?>
    <div class="col-12 p-2">
        <div class="row">
            <div class="col-md-12 col-lg-12 mb-3">

                <?php 
                if ($product_result->num_rows > 0) { 
                    

                    if($product_result->num_rows>=100){
                        echo '
                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <h4 class="fw-bold">
                                    Stocks Found
                                </h4>

                                <span class="badge bg-danger fs-11">
                                    please refine your search, more than '.$product_result->num_rows.' Result(s)
                                </span>

                            </div>
                        ';
                    } else {
                        echo '
                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <h4 class="fw-bold">
                                    Stocks Found
                                </h4>

                                <span class="badge bg-success fs-11">
                                    '.$product_result->num_rows.' Result(s)
                                </span>

                            </div>
                        ';
                    }


                    while ($row = $product_result->fetch_assoc()){
                        $search_unique_barcode = htmlspecialchars($row['unique_barcode']);
                        $search_product_description = htmlspecialchars($row['description']);
                        $search_brand = htmlspecialchars($row['brand_name']);
                        $search_category = htmlspecialchars($row['category_name']);
                        $search_warehouse = htmlspecialchars($row['warehouse_name']);

                        echo '

                            <div class="card mb-3">

                                <div class="card-body">

                                    <div class="row align-items-center">

                                        <div class="col-lg-7">

                                            <h5 class="fw-bold mb-2">
                                                <a href="../Product-info/?prod=' . $search_unique_barcode . '">
                                                    '.$search_product_description.'
                                                </a>
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

                                            <div class="fw-semibold text-primary fs-5">

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
                ?>
            </div>

            <div class="col-lg-12 col-md-12 col-sm-12 mb-2">
                <?php

                if($outbound_result->num_rows > 0){

                    echo '

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h4 class="fw-bold">
                            <i class="bi bi-truck text-primary"></i>
                            Outbound Records
                        </h4>

                        <span class="badge bg-primary">
                            '.$outbound_result->num_rows.' Record(s)
                        </span>

                    </div>

                    ';

                    while($row = $outbound_result->fetch_assoc()){

                        $status = strtolower($row['status']);

                        if($status=="completed"){
                            $badge="bg-success";
                            $icon="bi-check-circle-fill";
                        }
                        elseif($status=="pending"){
                            $badge="bg-warning text-dark";
                            $icon="bi-clock-history";
                        }
                        else{
                            $badge="bg-secondary";
                            $icon="bi-truck";
                        }

                ?>

                <div class="card shadow-sm border-0 mb-3">

                    <div class="card-body">

                        <div class="row align-items-center">

                            <div class="col-lg-8">

                                <div class="d-flex justify-content-between">

                                    <div>

                                        <h5 class="fw-bold mb-1">

                                            <i class="bi bi-receipt-cutoff text-primary"></i>

                                            <?= htmlspecialchars($row['order_num']) ?>

                                        </h5>

                                        <div class="text-muted">

                                            <?= htmlspecialchars($row['customer_fullname']) ?>

                                        </div>

                                    </div>

                                    <div>

                                        <span class="badge <?= $badge ?> fs-6">

                                            <i class="bi <?= $icon ?>"></i>

                                            <?= ucfirst($row['status']) ?>

                                        </span>

                                    </div>

                                </div>

                                <hr>

                                <div class="fw-bold fs-5">

                                    <?= htmlspecialchars($row['description']) ?>

                                </div>

                                <div class="mt-2">

                                    <span class="badge bg-primary">
                                        <?= htmlspecialchars($row['brand_name']) ?>
                                    </span>

                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($row['category_name']) ?>
                                    </span>

                                </div>

                                <div class="mt-3 text-muted">

                                    <div>

                                        <i class="bi bi-upc-scan"></i>

                                        <?= htmlspecialchars($row['unique_barcode']) ?>

                                    </div>

                                    <div>

                                        <i class="bi bi-person-circle"></i>

                                        <?= htmlspecialchars($row['user_fname']." ".$row['user_lname']) ?>

                                    </div>

                                </div>

                            </div>

                            <div class="col-lg-4">

                                <div class="rounded bg-light p-3 h-100">

                                    <table class="table table-borderless table-sm mb-0">

                                        <tr>

                                            <td class="text-muted">

                                                Warehouse

                                            </td>

                                            <td class="fw-bold text-end">

                                                <?= htmlspecialchars($row['warehouse_name']) ?>

                                            </td>

                                        </tr>

                                        <tr>

                                            <td class="text-muted">

                                                Order Line

                                            </td>

                                            <td class="fw-bold text-end">

                                                <?= htmlspecialchars($row['order_line_id']) ?>

                                            </td>

                                        </tr>

                                        <tr>

                                            <td class="text-muted">

                                                Sold Price

                                            </td>

                                            <td class="fw-bold text-success text-end">

                                                ₱<?= number_format($row['sold_price'],2) ?>

                                            </td>

                                        </tr>

                                    </table>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <?php
                    }
                }
                ?>
            </div>


            
            <div class="col-lg-12 col-md-12 col-sm-12 mb-3">

                <?php

                if($stc_result->num_rows > 0){

                    echo '
                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h4 class="fw-bold">
                            <i class="bi bi-arrow-left-right text-primary"></i>
                            Stock Transfers
                        </h4>

                        <span class="badge bg-primary fs-11">
                            '.$stc_result->num_rows.' Record(s)
                        </span>

                    </div>';

                    while($row = $stc_result->fetch_assoc()){

                        $badge = "bg-warning text-dark";
                        $icon  = "bi-truck";

                        if(strtolower($row['status'])=="received"){
                            $badge = "bg-success";
                            $icon  = "bi-check-circle";
                        }
                        elseif(strtolower($row['status'])=="cancelled"){
                            $badge = "bg-danger";
                            $icon  = "bi-x-circle";
                        }

                ?>

                <div class="card shadow-sm border-0 mb-3">

                    <div class="card-body">

                        <div class="row">

                            <div class="col-lg-8">

                                <div class="d-flex align-items-center mb-3">

                                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">

                                        <i class="bi bi-box-seam fs-3 text-primary"></i>

                                    </div>

                                    <div>

                                        <h5 class="fw-bold mb-0">
                                            <?= htmlspecialchars($row['description']) ?>
                                        </h5>

                                        <small class="text-muted">
                                            Transfer #<?= $row['stock_transfer_id'] + 10000 ?>
                                        </small>

                                    </div>

                                </div>

                                <div class="mb-3">

                                    <span class="badge bg-primary">
                                        <?= htmlspecialchars($row['brand_name']) ?>
                                    </span>

                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($row['category_name']) ?>
                                    </span>

                                    <span class="badge <?= $badge ?>">
                                        <i class="bi <?= $icon ?>"></i>
                                        <?= ucfirst($row['status']) ?>
                                    </span>

                                </div>

                                <div class="row gy-2">

                                    <div class="col-md-6">

                                        <small class="text-muted d-block">
                                            Barcode
                                        </small>

                                        <strong>
                                            <?= htmlspecialchars($row['unique_barcode']) ?>
                                        </strong>

                                    </div>

                                    <div class="col-md-6">

                                        <small class="text-muted d-block">
                                            Date Out
                                        </small>

                                        <strong>
                                            <?= date("M d, Y h:i A", strtotime($row['date_out'])) ?>
                                        </strong>

                                    </div>

                                    <div class="col-md-6">

                                        <small class="text-muted d-block">
                                            Date Received
                                        </small>

                                        <strong>

                                        <?php

                                        if(!empty($row['date_received'])){
                                            echo date("M d, Y h:i A", strtotime($row['date_received']));
                                        }else{
                                            echo '<span class="text-muted">Not yet received</span>';
                                        }

                                        ?>

                                        </strong>

                                    </div>

                                </div>

                            </div>

                            <div class="col-lg-4">

                                <div class="border rounded p-3 bg-light mb-2">

                                    <small class="text-muted">
                                        Sender Remarks
                                    </small>

                                    <div class="fw-semibold">

                                        <?= !empty($row['remarks_sender']) ? nl2br(htmlspecialchars($row['remarks_sender'])) : '<span class="text-muted">No remarks</span>' ?>

                                    </div>

                                </div>

                                <div class="border rounded p-3 bg-light">

                                    <small class="text-muted">
                                        Receiver Remarks
                                    </small>

                                    <div class="fw-semibold">

                                        <?= !empty($row['remarks_receiver']) ? nl2br(htmlspecialchars($row['remarks_receiver'])) : '<span class="text-muted">No remarks</span>' ?>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <?php

                    }

                }

                ?>

            </div>




        </div>
        
        
    </div>
    <?php 
    } else {
        echo '
        <div class="alert alert-warning border-0 shadow-sm">

            <i class="bi bi-funnel-fill me-2"></i>

            <strong>Refine your search.</strong>

            More than <strong>100 records</strong> were found.
            Please search using a barcode, order number, customer name, or product description.

        </div>';
    }
    ?>
</div>