<div class="card h-md-100 ecommerce-card-min-width">
    <div class="card-header pb-0">
        <h6 class="mb-0 mt-2 d-flex align-items-center">
            More than 3 months
            <span class="ms-1 text-400" data-bs-toggle="tooltip" data-bs-placement="top" title="Items that are taking longer to sell">
                <span class="far fa-question-circle" data-fa-transform="shrink-1"></span>
            </span>
        </h6>
    </div>
    <a href="../Extended-Shelf-Items/">
    <div class="card-body d-flex flex-column justify-content-end">
        <?php 
        // Calculate the threshold for "more than 1 month ago"
        $dateThreshold = date('Y-m-d H:i:s', strtotime('-3 month'));

        // SQL query for items older than 1 month
        $sql = "
            SELECT COUNT(DISTINCT unique_barcode) AS quantity 
            FROM stocks 
            WHERE `date` < '$dateThreshold' 
            AND item_status IN (0, 2, 3) 
            AND warehouse IN ($user_warehouse_id)
        ";

        // Execute query and fetch the result
        $prolong_quantity = 0; // Default if no rows are returned
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $prolong_quantity = $row['quantity'];
        }
        ?>
        <div class="row">
            <div class="col-lg-12 mb-4 mb-lg-0">
                <div class="row">
                    <div class="col">
                        <p class="font-sans-serif lh-1 mb-1 fs-5 text-dark">
                            <?php echo $prolong_quantity ?: "0"; ?>
                        </p>
                        <span class="badge badge-subtle-danger rounded-pill fs-11">
                            All of your warehouses
                        </span>
                    </div>
                    <div class="col-auto ps-0">
                        <div class="h-100">
                            <span class="far fa-calendar-alt"></span>
                        </div>
                    </div>
                </div>   
            </div>
        </div>
    </div>
    </a>
</div>
