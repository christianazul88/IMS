<?php
include "../config/database.php";
if(isset($_GET['id'])){
    $id = $_GET['id'];
}
$query = "SELECT warehouse_name FROM warehouse WHERe hashed_id = '$id' LIMIT 1";
$res = $conn->query($query);
$row=$res->fetch_assoc();
$warehouse_name = $row['warehouse_name'];
?>
<label class="col-form-label" for="recipient-name">warehouse Name</label>
<input class="form-control" name="warehouse_name" id="recipient-name" type="text" value="<?php echo $warehouse_name;?>"/>
<input type="text" name="id" value="<?php echo $id; ?>"  hidden>