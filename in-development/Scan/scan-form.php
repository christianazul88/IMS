<?php
session_start();
?>
<form id="scanner" action="update-scan.php">
    <input type="text" name="barcode" id="barcode" class="form-control" placeholder="Scan barcode here..." autofocus>
    <input type="text" value="<?php echo $_SESSION['selected_area']; ?>" name="selected_area" hidden>
    <button type="submit" class="btn btn-primary mt-3">Submit</button>
</form>