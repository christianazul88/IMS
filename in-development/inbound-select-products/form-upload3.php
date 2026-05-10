<?php 
include "../config/database.php";
include "../config/on_session.php";
// include "../page_properties/header.php";
?>
<form id="form-reload3" enctype="multipart/form-data">
    <div class="row">
        <div class="col-10">
            <label for="">Upload CSV file</label>
            <input type="file" name="csv" class="form-control" accept=".csv" required>
        </div>
        <div class="col-2 pt-4">
            <input class="btn btn-primary" type="submit" name="submit" value="Upload CSV">
            <!-- <button class="btn btn-primary">Submit</button> -->
        </div>
    </div>
</form>
<?php 
// include "../page_properties/footer_main.php";
?>