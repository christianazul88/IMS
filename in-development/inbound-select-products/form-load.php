<?php 
include "../config/database.php";
include "../config/on_session.php";
// include "../page_properties/header.php";
?>
<form id="form-reload">
    <div class="row">
        <div class="col-6">
            <label for="">Parent Barcode</label>
            <input type="text" class="form-control" name="parent_barcode" id="parent_barcode">
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
// include "../page_properties/footer_main.php";
?>