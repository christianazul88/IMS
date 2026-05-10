<?php
include "../config/database.php";
if(isset($_GET['id'])){
    $id = $_GET['id'];
}
$query = "SELECT courier_name FROM courier WHERe hashed_id = '$id' LIMIT 1";
$res = $conn->query($query);
$row=$res->fetch_assoc();
$courier_name = $row['courier_name'];
?>
<label class="col-form-label" for="recipient-name">courier Name</label>
<input class="form-control" name="courier_name" id="recipient-name" type="text" value="<?php echo $courier_name;?>"/>
<input type="text" name="id" value="<?php echo $id; ?>"  hidden>