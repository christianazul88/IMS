<?php 
include "../config/database.php";

if (isset($_GET['id'])) {
    $rts_id = $_GET['id'];

    // Get the 'for' value from rts_logs
    $for_query = "SELECT `for`, `status`, proof, front, back, warranty, reason FROM rts_logs WHERE id = '$rts_id' LIMIT 1";
    $for_res = $conn->query($for_query);
    $for = "";
    if ($for_res->num_rows > 0) {
        $row = $for_res->fetch_assoc();
        $for = $row['for'];
        $rts_log_status = $row['status'];
        $reason = $row['reason'];
        $rts_proof = $row['proof'];
        $rts_front = $row['front']; 
        $rts_back = $row['back'];
        $rts_warranty = $row['warranty'];
    }
    if($rts_log_status == 0){
    if ($for === 'return and replace') {
        $linked = '<a id="replace-btn" href="../config/returned.php?type=replace&d=' . $rts_id . '" class="btn btn-primary mb-3 fs-10 shadow-lg refund-btn" data-bs-toggle="tooltip" data-bs-placement="left" title="click if item already replaced!">
            <span class="fas fa-recycle"></span> Replace
        </a>';
        } else {
        $linked = '<a id="replace-btn" href="../config/returned.php?type=refund&d=' . $rts_id . '" class="btn btn-primary mb-3 fs-10 shadow-lg refund-btn" data-bs-toggle="tooltip" data-bs-placement="left" title="click if item already refunded!">
            <span class="far fa-money-bill-alt"></span> Refund
        </a>';
        }
    }
?>
<div class="p-4 pb-0">

    <form>
        <div class="row">
            <?php 
            $col = 6;
            if(!empty($rts_proof)){
                $col = 6;
            ?>
            <div class="col-lg-6 text-start">
                <button class="btn btn-info ms-sm-2 mb-3 fs-10" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">View Images</button>
            </div>
            <?php 
            } else {
                $col = 12;
            }
                if($rts_log_status == 0){
            ?>
            <div class="col-lg-<?php echo $col;?> text-end">
                <?php echo $linked;?> 
                <a class="btn btn-success ms-sm-2 mb-3 fs-10" href="download.php?id=<?php echo $rts_id;?>">Download</a>
            </div>
            <?php 
                }
            ?>
            <div class="col-lg-12 mb-3">
                <h5>Reason:</h5>
                <p class="fs-11"><i><?php echo $reason;?></i></p>
            </div>
            <div class="col-lg-12">
                <div id="tableExample" data-list='{"valueNames":["name","email","age"],"page":5,"pagination":true}'>
                    <div class="table-responsive scrollbar">
                        <table class="table table-bordered table-striped fs-10 mb-0">
                            <thead class="bg-info">
                                <tr>
                                    <th>#</th>
                                    <th class="text-900 sort" data-sort="name" style="width: 200px;">Item</th>
                                    <th class="text-900 sort" data-sort="name">Brand</th>
                                    <th class="text-900 sort" data-sort="name">Category</th>
                                    <th class="text-900 sort" data-sort="name">Fulfillment Status</th>
                                    <th class="text-900 sort" data-sort="name" style="width: 200px;">Barcode</th>
                                    <th class="text-900 sort" data-sort="name">Returned Date</th>
                                </tr>
                            </thead>
                            <tbody class="list">
                                <?php
                                // Fetch the RTS content
                                $sql = "SELECT DISTINCT p.description, b.brand_name, c.category_name, rts.unique_barcode, rts.status, rts.returned_date
                                        FROM rts_content rts
                                        LEFT JOIN stocks s ON s.unique_barcode = rts.unique_barcode
                                        LEFT JOIN product p ON p.hashed_id = s.product_id
                                        LEFT JOIN brand b ON b.hashed_id = p.brand
                                        LEFT JOIN category c ON c.hashed_id = p.category
                                        WHERE rts.rts_id = '$rts_id'";
                                $result = $conn->query($sql);

                                if ($result->num_rows > 0) {
                                    $number = 0;
                                    while ($row = $result->fetch_assoc()) {
                                        $number++;
                                        $product_description = $row['description'];
                                        $brand_name = $row['brand_name'];
                                        $category_name = $row['category_name'];
                                        $barcode = $row['unique_barcode'];
                                        $returned_date = $row['returned_date'];

                                        // Determine RTS status badge
                                        switch ($row['status']) {
                                            case 0:
                                                $rts_status = '<span class="badge rounded-pill bg-primary">Returned</span>';
                                                break;
                                            case 1:
                                                $rts_status = '<span class="badge rounded-pill bg-success">Returned and Refunded</span>';
                                                break;
                                            default:
                                                $rts_status = '<span class="badge rounded-pill bg-success">Returned and Replaced</span>';
                                        }
                                ?>
                                <tr>
                                    <td><?php echo $number; ?></td>
                                    <td class="text-900"><?php echo $product_description; ?></td>
                                    <td class="text-900"><?php echo $brand_name; ?></td>
                                    <td class="text-900"><?php echo $category_name; ?></td>
                                    <td class="text-900"><?php echo $rts_status; ?></td>
                                    <td class="text-900"><?php echo $barcode; ?></td>
                                    <td class="">
                                        <?php 
                                        if ($row['status'] == 0) {
                                            
                                        } else {
                                            echo $returned_date;
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div> <!-- table-responsive -->
                </div> <!-- tableExample -->
            </div> <!-- col-lg-12 -->
            <div class="col-lg-12 mt-3">
            <div class="collapse" id="collapseExample">
                <div class="swiper theme-slider">
                <div class="swiper-wrapper">
                    <div class="swiper-slide bg-dark">
                        <div class="swiper-zoom-container">
                        <img class="rounded-1 img-fluid" src="../../assets/img_rts/<?php echo $rts_id;?>/<?php echo trim($rts_proof); ?>" alt="" style="height: 500px;"/>
                        </div>
                    </div>
                    <div class="swiper-slide bg-dark">
                        <div class="swiper-zoom-container">
                        <img class="rounded-1 img-fluid" src="../../assets/img_rts/<?php echo $rts_id;?>/<?php echo trim($rts_front); ?>" alt="" style="height: 500px;"/>
                        </div>
                    </div>
                    <div class="swiper-slide bg-dark">
                        <div class="swiper-zoom-container">
                        <img class="rounded-1 img-fluid" src="../../assets/img_rts/<?php echo $rts_id;?>/<?php echo trim($rts_back); ?>" alt="" style="height: 500px;"/>
                        </div>
                    </div>
                    <div class="swiper-slide bg-dark">
                        <div class="swiper-zoom-container">
                        <img class="rounded-1 img-fluid" src="../../assets/img_rts/<?php echo $rts_id;?>/<?php echo trim($rts_warranty); ?>" alt="" style="height: 500px;"/>
                        </div>
                    </div>
                </div>
                <div class="swiper-button-next swiper-button-white"></div>
                <div class="swiper-button-prev swiper-button-white"></div>
                </div>
            </div>
            </div>
        </div> <!-- row -->
    </form>
</div>
<?php 
} // if isset
?>
