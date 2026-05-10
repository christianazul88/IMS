<?php 
include "../config/database.php";
include "../config/on_session.php";
include "../page_properties/header.php";
?>
<form id="form-reload2" action="../config/add_product_inbound.php" method="POST">
    <div class="row">
        <div class="col-6">
            <label for="">Product</label>
            <select class="form-select js-choice" name="product_id" id="organizerSingle" size="1" data-options='{"removeItemButton":true,"placeholder":true}'>
                <option value="">Select Product</option>
                <?php 
                $option_product_sql = "SELECT p.*, c.category_name, b.brand_name 
                                        FROM product p
                                        LEFT JOIN category c ON c.id = p.category
                                        LEFT JOIN brand b ON b.id = p.brand
                                        ORDER BY p.description ASC";
                $option_product_res = $conn -> query($option_product_sql);
                if($option_product_res->num_rows>0){
                    while($row = $option_product_res->fetch_assoc()){
                        $option_product_id = $row['id'];
                        $option_product_Desc = $row['description'];
                        $option_category_name = $row['category_name'];
                        $option_brand_name = $row['brand_name'];
                        echo '<option value="' . $option_product_id . '">' . $option_product_Desc . ' - ' . $option_brand_name . '</option>';
                    }
                }
                ?>
            </select>
        </div>
        <div class="col-4">
            <label for="">Quantity</label>
            <input class="form-control" type="number" name="rcvd_qty" id="rcvd_qty">
        </div>
        <div class="col-2 pt-4">
            <button class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>


<?php 
include "../page_properties/footer_main.php";
?>