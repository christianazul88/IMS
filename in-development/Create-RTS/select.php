<?php 
include "../config/database.php";
include "../config/on_session.php";
if(isset($_SESSION['return_supplier'])){
    $supplier = $_SESSION['return_supplier'];
    $sql = "SELECT supplier_name FROM supplier WHERE hashed_id = '$supplier' LIMIT 1";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    $supplier_name = $row['supplier_name'];
    ?>
    <select class="form-select bg-info" name="supplier" readonly>
        <option value="<?php echo $supplier; ?>" selected><?php echo $supplier_name;?></option>
    </select>
    <?php
} else {
    ?>
    <select class="form-select" name="to_supplier" id="to_supplier" disabled>
        <option value="" selected>Supplier</option>
    </select>
    <?php
}
?>