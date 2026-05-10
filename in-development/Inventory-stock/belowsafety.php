<div class="card h-md-100 ecommerce-card-min-width">
    <div class="card-header pb-0">
    <h6 class="mb-0 mt-2 d-flex align-items-center">
        Under safety
        <span class="ms-1 text-400" data-bs-toggle="tooltip" data-bs-placement="top" title="items that are under safety stock levbel">
        <span class="far fa-question-circle" data-fa-transform="shrink-1"></span>
        </span>
    </h6>
    </div>
    <div class="card-body d-flex flex-column justify-content-end">
        <!-- -------------------- -->
        <div class="row">
        <div class="col-lg-12 mb-4 mb-lg-0">
            <div class="swiper theme-slider" data-swiper='{"autoplay":true,"spaceBetween":5,"loop":true,"loopedSlides":5,"slideToClickedSlide":true}'>
            <div class="swiper-wrapper">
                
                <?php 
                $query = "SELECT hashed_id, warehouse_name FROM warehouse WHERE hashed_id IN ($user_warehouse_id)";
                $result = $conn->query($query);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $notification_Warehouse_id = $row['hashed_id'];
                        $notification_warehouse_name = $row['warehouse_name'];
                        $BELOW_SAFETY = 0;
                        $quantity = 0;
                
                        // Fetch stocks and safety levels
                        $sql = "SELECT 
                                    SUM(CASE WHEN s.item_status IN (0, 2, 3) THEN 1 ELSE 0 END) AS quantity,
                                    p.safety AS safety
                                FROM stocks s
                                LEFT JOIN product p ON p.hashed_id = s.product_id
                                WHERE s.warehouse = ?
                                GROUP BY s.product_id, s.warehouse";
                
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $notification_Warehouse_id);
                        $stmt->execute();
                        $res = $stmt->get_result();
                
                        if ($res && $res->num_rows > 0) {
                            while ($stock = $res->fetch_assoc()) {
                                $quantity = $stock['quantity'];
                                $safety = $stock['safety'];
                
                                if ($quantity <= $safety) {
                                    $BELOW_SAFETY++;
                                }
                            }
                        }
                
                        ?>
                        <div class="swiper-slide">
                            <a class="text-dark" href="../Below-Safety/?ware=<?php echo $notification_Warehouse_id . "&&waren=" . $notification_warehouse_name;?>">
                            <div class="row">
                                <div class="col">
                                <p class="font-sans-serif lh-1 mb-1 fs-5"><?php echo $BELOW_SAFETY;?></p>
                                <span class="badge badge-subtle-success rounded-pill fs-11"><?php echo $notification_warehouse_name;?></span>
                                </div>
                                <div class="col-auto ps-0">
                                <div class="h-100"><span class="far fa-chart-bar"></span></div>
                                </div>
                            </div>
                            </a>
                        </div>
                        <?php
                    }
                }
                ?>
                
            </div>
            
            </div>
        </div>
        </div>
        <!-- -------------------- -->
    
    </div>
</div>