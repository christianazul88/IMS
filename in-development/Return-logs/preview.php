<?php 
include "../config/database.php";

if (isset($_GET['id'])) {
    $unique_barcode = $_GET['id'];
    $query = "SELECT reason, images FROM returns WHERE id = '$unique_barcode' ORDER BY id DESC LIMIT 1";
    $res = $conn->query($query);
    
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $reason = $row['reason'];
        $rts_images = $row['images']; // sample value: img_1, img_2, img_3
        $images_array = explode(',', $rts_images);
        // echo $rts_images;
    }
}
?>

<ul class="nav nav-pills" id="pill-myTab" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="pill-home-tab" data-bs-toggle="tab" href="#pill-tab-home" role="tab" aria-controls="pill-tab-home" aria-selected="true">Reason</a>
    </li>
    <?php 
    if (!empty($rts_images)) {
    ?>
    <li class="nav-item">
        <a class="nav-link" id="pill-profile-tab" data-bs-toggle="tab" href="#pill-tab-profile" role="tab" aria-controls="pill-tab-profile" aria-selected="false">Images</a>
    </li>
    <?php 
    }
    ?>
</ul>

<div class="tab-content border p-3 mt-3" id="pill-myTabContent">
    <div class="tab-pane fade show active" id="pill-tab-home" role="tabpanel" aria-labelledby="home-tab">
        <i><?php echo $reason; ?></i>
    </div>

    <div class="tab-pane fade" id="pill-tab-profile" role="tabpanel" aria-labelledby="profile-tab">
        <div class="swiper theme-slider">
            <div class="swiper-wrapper">
                <?php 
                foreach ($images_array as $image) {
                    echo '
                    <div class="swiper-slide bg-dark">
                        <div class="swiper-zoom-container">
                            <img class="rounded-1 img-fluid" src="../../assets/img_return/' . $unique_barcode . '/' . trim($image) . '" alt="" style="height: 500px;"/>
                        </div>
                    </div>
                    ';
                }
                ?>
            </div>
            <div class="swiper-nav">
                <div class="swiper-button-next swiper-button-white"></div>
                <div class="swiper-button-prev swiper-button-white"></div>
            </div>
        </div>
    </div>
</div>
