<?php 
include "../config/database.php";
include "../config/on_session.php";

if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    $stmt = $conn->prepare("SELECT product.id, product.description, product.product_img, product.safety, product.parent_barcode, category.category_name, brand.brand_name, category.hashed_id AS category_id, brand.hashed_id AS brand_id
                            FROM product
                            LEFT JOIN category ON category.hashed_id = product.category
                            LEFT JOIN brand ON brand.hashed_id = product.brand
                            WHERE product.id = ?
                            LIMIT 1");
    $stmt->bind_param("i", $product_id); // Assuming product_id is an integer
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $product_id = $row['id'];
        $product_img = empty($row['product_img']) ? 'def_img.png' : $row['product_img'];
        $product_category = $row['category_name'];
        $product_brand = $row['brand_name'];
        $product_des = $row['description'];
        $product_pbarcode = $row['parent_barcode'];
        $category_id = $row['category_id'];
        $brand_id = $row['brand_id'];
        $update_safety = $row['safety'];
?>
<input type="text" name="product_id" value="<?php echo $product_id ?>" hidden>
<div class="row">
    <div class="col-3">
            <div class="avatar avatar-5xl shadow-sm img-thumbnail mt-3 ms-3">
                <div class="h-100 w-100 overflow-hidden position-relative"> <img src="../../assets/img/<?php echo $product_img; ?>"  alt="" /><input class="d-none" name="product_image[]" id="profile-image" type="file" accept="image/png, image/jpeg, image/jpg" multiple/><label class="mb-0 overlay-icon d-flex flex-center" for="profile-image"><span class="bg-holder overlay overlay-0"></span><span class="z-1 text-white dark__text-white text-center fs-10"><span class="fas fa-camera"></span><span class="d-block">Update</span></span></label></div>
            </div>
    </div>
    <div class="col-9">
        <div class="row p-3">
            <div class="col-lg-8 mb-3">
                <label for="">Product Description</label>
                <input type="text" class="form-control" name="product_description" value="<?php echo $product_des;?>">
            </div>
            
            <div class="col-lg-4 mb-3">
            <label for="">Category</label>
            <select class="form-select" name="category" id="">
                <option value="">Select Category</option>
                <?php 
                $category_selection = "SELECT * FROM category ORDER BY category_name ASC";
                $category_result = $conn->query($category_selection);
                if($category_result->num_rows > 0) {
                while($row = $category_result->fetch_assoc()) {
                    if($row['hashed_id'] === $category_id){
                        echo '<option value="' . $row['hashed_id'] . '" selected>' . $row['category_name'] . '</option>';    
                    } else {
                        echo '<option value="' . $row['hashed_id'] . '">' . $row['category_name'] . '</option>';
                    }
                }
                } else {
                echo '<option value="">No category found</option>';
                }
                ?>
            </select>
            </div>
            <div class="col-lg-4 mb-3">
                <label for="">Brand</label>
                <select class="form-select" name="brand" id="">
                    <?php 
                    $brand_selection = "SELECT * FROM brand ORDER BY brand_name ASC";
                    $brand_result = $conn->query($brand_selection);
                    if($brand_result->num_rows > 0) {
                    while($row = $brand_result->fetch_assoc()) {
                        if($row['hashed_id'] === $brand_id){
                            echo '<option value="' . $row['hashed_id'] . '" selected>' . $row['brand_name'] . '</option>';
                        } else {
                            echo '<option value="' . $row['hashed_id'] . '">' . $row['brand_name'] . '</option>';
                        }
                    }
                    } else {
                    echo '<option value="">No brand found</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-lg-3 mb-3">
                <label for="safety">Safety(Skip if no edit)</label>
                <input type="number" name="safety" min="2" max="1000" class="form-control" value="">
            </div>
            <div class="col-lg-5 mb-3">
                <label for="">Safety for Warehouse</label>
                <select class="form-select" name="safety_for" id="safety_for">
                    <option value="" selected>Skip if no edit</option>
                    <?php 
                    foreach($warehouse_options2 AS $options){
                        echo $options;
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
</div>

<?php
    }
    $stmt->close();
}
