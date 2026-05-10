<?php
include "../config/database.php";
if(isset($_GET['id'])){
    $id = $_GET['id'];
}
$query = "SELECT supplier_name, local_international as `type` FROM supplier WHERe hashed_id = '$id' LIMIT 1";
$res = $conn->query($query);
$row=$res->fetch_assoc();
$supplier_name = $row['supplier_name'];
?>
<div class="mb-3">
    <label for="select">Local/International</label>
    <select name="type" class="form-select" id="" required>
        <option value=""></option>
        <?php 
        if($row['type'] === "Local"){
            echo '<option value="Hakot">Hakot</option>';
            echo '<option value="International">International</option>';
            echo '<option value="Local" selected>Local</option>';
        } elseif($row['type'] === "International"){
            echo '<option value="Hakot">Hakot</option>';
            echo '<option value="International" selected>International</option>';
            echo '<option value="Local">Local</option>';
        } elseif($row['type'] === "Hakot"){
            echo '<option value="Hakot" selected>Hakot</option>';
            echo '<option value="International">International</option>';
            echo '<option value="Local">Local</option>';
        } else {
            echo '<option value="Hakot">Hakot</option>';
            echo '<option value="International">International</option>';
            echo '<option value="Local">Local</option>';
        }
        ?>
    </select>
</div>
<div class="mb-3">
    <label class="col-form-label" for="recipient-name">supplier Name</label>
    <input class="form-control" name="supplier_name" id="recipient-name" type="text" value="<?php echo $supplier_name;?>"/>
    <input type="text" name="id" value="<?php echo $id; ?>"  hidden>
</div>