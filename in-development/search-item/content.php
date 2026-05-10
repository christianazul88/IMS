<div class="card">
    <div class="card-body overflow-hidden">
        <form action="index.php" method="POST">
            <div class="row">
                <div class="col-6">
                    <label for="search">Input Barcode: </label>
                    <input type="text" name="barcode" class="form-control" <?php 
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
if(isset($_POST['barcode'])){
$unique_barcode = $_POST['barcode'];
$product_query = "SELECT 
                        p.product_img, 
                        p.description, 
                        b.brand_name, 
                        c.category_name, 
                        il.location_name, 
                        sup.supplier_name, 
                        w.warehouse_name, 
                        w.hashed_id,
                        u.user_fname, 
                        u.user_lname, 
                        s.capital, 
                        s.price, 
                        s.batch_code, 
                        s.parent_barcode,
                        s.date,
                        s.outbound_id,
                        sup.local_international,
                        s.item_status
                    FROM stocks s
                    LEFT JOIN product p ON p.hashed_id = s.product_id
                    LEFT JOIN brand b ON b.hashed_id = p.brand
                    LEFT JOIN category c ON c.hashed_id = p.category
                    LEFT JOIN item_location il ON il.id = s.item_location
                    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
                    LEFT JOIN users u ON u.hashed_id = s.user_id
                    LEFT JOIN supplier sup ON sup.hashed_id = s.supplier
                    WHERE s.unique_barcode = '$unique_barcode' 
                    LIMIT 1
                    ";
$result = $conn->query($product_query);
    if($result->num_rows>0){
        $row = $result->fetch_assoc();
        
        if (empty($row['product_img']) || !isset($row['product_img'])) {
            $product_img_html = '<div class="swiper-slide h-100 text-center bg-300"><img class="rounded-1 object-fit-cover" src="../../assets/img/def_img.png" alt="Default Image"></div>';
        } else {
            $imageArray = @unserialize($row['product_img']); // or json_decode($row['product_img'], true)

            if (is_array($imageArray) && count($imageArray) > 0) {
                // Instead of just one image, generate multiple <img> tags:
                $product_img_html = '';
                foreach ($imageArray as $base64Img) {
                    $imgBinary = base64_decode($base64Img);

                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->buffer($imgBinary);

                    $product_img_html .= '
                    <div class="swiper-slide h-100 text-center bg-dark">
                        <img class="rounded-1 object-fit-cover" 
                        src="data:' . htmlspecialchars($mimeType) . ';base64,' . htmlspecialchars($base64Img) . '" 
                        alt="" height="300" />
                    </div>';
                }
            } else {
                $product_img_html = '<div class="swiper-slide h-100"><img class="rounded-1 object-fit-cover" src="../../assets/img/def_img.png" alt="Default Image"></div>';
            }
        }

        $product_description = $row['description'];
        $product_brand = $row['brand_name'];
        $product_category = $row['category_name'];
        $item_location = $row['location_name'];
        $supplier_name = $row['supplier_name'];
        $warehouse_name = $row['warehouse_name'];
        $added_by = $row['user_fname'] . " " . $row['user_lname'];
        $capital = $row['capital'];
        $sold_amount = $row['price'];
        $batch_code = $row['batch_code'];
        $parent_barcode = $row['parent_barcode'];
        $warehouse_hashed_id = $row['hashed_id'];
        $delivery_date = $row['date'];
        $local_international = $row['local_international'];
        $item_status = $row['item_status'];
        if($local_international === "Local"){
            $local_international = '<span class="badge rounded-pill badge-subtle-primary">Local</span>';
        } else {
            $local_international = '<span class="badge rounded-pill badge-subtle-danger">International</span>';
        }

        if(!empty($item_location)){
            $item_location = $row['location_name'] . '<span class="badge rounded-pill badge-subtle-success"><span class="far fa-check-circle"></span></span>';
        } else {
            $item_location = 'For SKU <span class="badge rounded-pill badge-subtle-danger"><span class="far fa-window-close"></span></span>';
        }
        ?>
            <div class="row">
                <div class="col-auto">
                <?php
                $delivery_date2 = $row['date'];

                function formatDateDifference($delivery_date2) {
                    $startDate = new DateTime($delivery_date2); // Parse the datetime string
                    $endDate = new DateTime(); // Current date and time
                    $interval = $startDate->diff($endDate);

                    if ($interval->y > 0) {
                        return $interval->y . " year" . ($interval->y > 1 ? "s" : "") . 
                            ($interval->m > 0 ? " " . $interval->m . " month" . ($interval->m > 1 ? "s" : "") : "") .
                            ($interval->d > 0 ? " " . $interval->d . " day" . ($interval->d > 1 ? "s" : "") : "");
                    } elseif ($interval->m > 0) {
                        return $interval->m . " month" . ($interval->m > 1 ? "s" : "") . 
                            ($interval->d > 0 ? " " . $interval->d . " day" . ($interval->d > 1 ? "s" : "") : "");
                    } else {
                        return $interval->d . " day" . ($interval->d > 1 ? "s" : "");
                    }
                }

                function getBadgeClass($delivery_date2) {
                    $startDate = new DateTime($delivery_date2);
                    $endDate = new DateTime();
                    $interval = $startDate->diff($endDate);

                    if ($interval->m >= 3 || $interval->y > 0) {
                        return "bg-danger";
                    } elseif ($interval->m >= 1) {
                        return "bg-warning";
                    } else {
                        return "bg-primary";
                    }
                }

                $badgeClass = getBadgeClass($delivery_date2);
                $formattedDateDifference = formatDateDifference($delivery_date2);

                echo '<button class="btn btn-primary d-none" id="liveToastBtn" type="button">Show </button>
                <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 5">
                <div class="toast fade" id="liveToast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header ' . $badgeClass . ' text-white"><strong class="me-auto">IMS</strong>
                    <div data-bs-theme="dark"><button class="btn-close" type="button" data-bs-dismiss="toast" aria-label="Close"></button></div>
                    </div>
                    <div class="toast-body">This item has been on the inventory for ' . $formattedDateDifference . '</div>
                </div>
                </div>';
                ?>
                </div>
            </div>
            <div class="row">
                <div class="col-12 text-end my-3">
                    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#change-location-modal">Change item location</button>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-lg mg-3">
                <div class="card-body p-4">
                    <div class="row">
                    
                        <!-- Left Column: Product Image Slider -->
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <div class="product-slider" id="galleryTop">
                            <div class="swiper theme-slider border rounded" data-swiper='{"autoplay":true,"spaceBetween":5,"loop":true,"loopedSlides":5,"slideToClickedSlide":true}'>
                                <div class="swiper-wrapper h-100">
                                <?php echo $product_img_html; ?>
                                </div>
                                <div class="swiper-nav">
                                <div class="swiper-button-next swiper-button-white"></div>
                                <div class="swiper-button-prev swiper-button-white"></div>
                                </div>
                            </div>
                            </div>
                        </div>

                        <!-- Right Column: Product Details -->
                        <div class="col-lg-6">
                            <!-- Product Title & Category -->
                            <div class="mb-3">
                            <h3 class="fw-bold"><?php echo $product_description; ?></h3>
                            <p class="text-muted small mb-1"><?php echo $product_category; ?></p>
                            </div>

                            <!-- Brand and Basic Info -->
                            <div class="mb-4">
                            <h5 class="text-primary"><?php echo $product_brand; ?></h5>
                            <table class="table table-sm">
                                <thead class="table-light">
                                <tr>
                                    <th>Delivery Date</th>
                                    <th>Added By</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td><?php echo $delivery_date; ?></td>
                                    <td><?php echo $added_by; ?></td>
                                </tr>
                                </tbody>
                            </table>
                            </div>

                            <!-- Pricing & Capital Info -->
                            <div class="mb-4">
                            <h6 class="fw-semibold">Price Summary</h6>
                            <table class="table table-sm">
                                <?php if (!empty($row['outbound_id'])): ?>
                                <thead class="table-light">
                                    <tr>
                                    <th class="text-warning">Capital</th>
                                    <th class="text-success">Sold For</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                    <td class="text-warning"><?php echo $capital; ?></td>
                                    <td class="text-success"><?php echo $sold_amount; ?></td>
                                    </tr>
                                </tbody>
                                <?php else: ?>
                                <thead class="table-light">
                                    <tr><th class="text-warning">Capital</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td class="text-warning">₱<?php echo $capital; ?></td></tr>
                                </tbody>
                                <?php endif; ?>
                            </table>
                            </div>

                        

                        </div>
                        <div class="col-12">
                            <!-- Additional Product Metadata -->
                            <div class="border rounded p-3 bg-light">
                            <h6 class="mb-3">Product Details</h6>
                            <table class="table table-sm mb-0">
                                <tr>
                                <th>Supplier:</th>
                                <td><?php echo $supplier_name . " " . $local_international; ?></td>
                                </tr>
                                <tr>
                                <th>Location:</th>
                                <td><?php echo $item_location; ?></td>
                                </tr>
                                <tr>
                                <th>Batch:</th>
                                <td><?php echo $batch_code; ?></td>
                                </tr>
                                <tr>
                                <th>Barcode:</th>
                                <td><?php echo $unique_barcode; ?></td>
                                </tr>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        


            

            
            <div class="card mt-3">
                <div class="card-body px-sm-4 px-md-1 px-lg-1 px-xxl-8">
                    <div class="row my-3">
                        <div class="col-lg-12 text-center">
                            <h3>Product History</h3>
                        </div>
                    </div>
                    <div class="timeline-vertical">
                        <div class="timeline-item timeline-item-start">
                            <div class="timeline-icon icon-item icon-item-lg text-primary border-300">
                                <span class="fs-8 fas fa-mobile"></span>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 timeline-item-time">
                                    <div>
                                        <p class="fs-10 mb-0 fw-semi-bold"><?php echo $delivery_date2;?></p>
                                        <!-- <p class="fs-11 text-600">24 September</p> -->
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="timeline-item-content">
                                        <div class="timeline-item-card">
                                            <h5 class="mb-2">Inbound</h5>
                                            <p class="fs-10 mb-0">Item has been successfully inbounded.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php 
                        $first = false;
                        $timeline_query = "SELECT 
                                                u.user_fname, 
                                                u.user_lname, 
                                                st.title, 
                                                st.action, 
                                                st.date
                                            FROM stock_timeline st
                                            LEFT JOIN users u ON u.hashed_id = st.user_id
                                            WHERE st.unique_barcode = '$unique_barcode'
                                            ORDER BY st.date ASC";
                        $result = $conn->query($timeline_query);
                        if($result->num_rows>0){
                            while($row=$result->fetch_assoc()){
                                $did_by = $row['user_fname'] . " " . $row['user_lname'];
                                $title = $row['title'];
                                $action = $row['action'];
                                $action_date = $row['date'];
                                if($first === true){
                                ?>
                                <div class="timeline-item timeline-item-start">
                                    <div class="timeline-icon icon-item icon-item-lg text-primary border-300">
                                        <span class="fs-8 fas fa-mobile"></span>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 timeline-item-time">
                                            <div>
                                                <p class="fs-10 mb-0 fw-semi-bold"><?php echo $action_date;?></p>
                                                <!-- <p class="fs-11 text-600">24 September</p> -->
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="timeline-item-content">
                                                <div class="timeline-item-card">
                                                    <h5 class="mb-2"><?php echo $title;?></h5>
                                                    <p class="fs-10 mb-0"><?php echo $action;?></p>
                                                    <small class="fs-11 text-400"><?php echo $did_by;?></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                $first = false;
                                } else {
                                ?>
                                <div class="timeline-item timeline-item-end">
                                    <div class="timeline-icon icon-item icon-item-lg text-primary border-300">
                                        <span class="fs-8 fas fa-fire"></span>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 timeline-item-time">
                                            <div>
                                                <p class="fs-10 mb-0 fw-semi-bold"><?php echo $action_date;?></p>
                                                <!-- <p class="fs-11 text-600">03 April</p> -->
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="timeline-item-content">
                                                <div class="timeline-item-card">
                                                    <h5 class="mb-2"><?php echo $title;?></h5>
                                                    <p class="fs-10 mb-0"><?php echo $action;?></p>
                                                    <small class="fs-11 text-400"><?php echo $did_by;?></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                $first = true;
                                }
                                
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="change-location-modal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
                    <div class="modal-content position-relative p-3">
                        <form action="sample.php" method="POST">
                        <!-- Close Button -->
                        <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <!-- Modal Body -->
                        <div class="modal-body p-0">
                            <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
                                <h4 class="mb-1" id="modalExampleDemoLabel">Change Item Location</h4>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <label for="item_location">Select item location</label>
                                    <select class="form-select" id="item_location" name="item_location" id="" required>
                                        <?php 
                                        $item_location_query = "SELECT * FROM item_location WHERE warehouse = '$warehouse_hashed_id' ORDER BY location_name ASC";
                                        $item_res = $conn->query($item_location_query);

                                        if ($item_res->num_rows > 0) {
                                            while ($row = $item_res->fetch_assoc()) {
                                                $location_id = $row['id'];
                                                $location_name = $row['location_name'];
                                                if($item_location === $location_name){
                                                    echo '<option selected>' . $location_name . '</option>';
                                                } else {
                                                    echo '<option value="' . $location_id . '" >' . $location_name . '</option>';   
                                                }    
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
                            <button class="btn btn-primary" type="submit">Understood</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php
        if($item_status != 1){
        ?>

        <script>
            // Wait until the entire page (including images and assets) is fully loaded
            window.onload = function() {
                // Find the button by its ID
                const button = document.getElementById("liveToastBtn");
                
                // Trigger a click event on the button
                button.click();
            };
        </script>
        <?php
        }

        ?>
        <script>
            // Function to fetch data from the API
            async function fetchChartData() {
                try {
                    const uniqueBarcode = '1000992-1'; // Replace with a dynamic value if needed
                    const response = await fetch(`../config/total_outbound_specific_product.php?prod=${uniqueBarcode}`);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }

                    const data = await response.json();
                    console.log('API Response:', data); // Debugging log

                    const months = data.map(item => item.month); // Extract the month labels
                    const totals = data.map(item => item.total_outbound); // Extract the total outbound counts
                    
                    // Reverse the months and totals to display from the earliest to the latest
                    months.reverse();
                    totals.reverse();
                    
                    return { months, totals };
                } catch (error) {
                    console.error('Error fetching chart data:', error);
                    return { months: [], totals: [] }; // Return empty data in case of error
                }
            }

            async function initChart() {
                const { months, totals } = await fetchChartData();

                // Verify data before proceeding
                if (months.length === 0 || totals.length === 0) {
                    console.error('No data available for the chart.');
                    return;
                }

                const chart = echarts.init(document.getElementById('SpecificItemChart'));

                const options = {
                    title: {
                        text: 'Outbound Products Over Time',
                        left: 'center'
                    },
                    tooltip: {
                        trigger: 'axis',
                        formatter: '{b}: {c}'
                    },
                    xAxis: {
                        type: 'category',
                        data: months, // Now showing from earliest to latest month
                        name: 'Month'
                    },
                    yAxis: {
                        type: 'value',
                        name: 'Total Outbound'
                    },
                    series: [
                        {
                            name: 'Total Outbound',
                            type: 'line',
                            data: totals, // Now showing from earliest to latest total
                            smooth: true,
                            lineStyle: {
                                color: '#5470C6',
                                width: 2
                            },
                            itemStyle: {
                                color: '#5470C6'
                            }
                        }
                    ]
                };

                chart.setOption(options);
            }

            // Initialize the chart
            initChart();
        </script>
<?php
    } else {
        echo '
        <div class="alert alert-danger mt-3">
            <strong>Product cannot be found.</strong><br>
            Please check the barcode and try again.
        </div>';
    }
}

