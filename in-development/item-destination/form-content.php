<?php
include "../config/database.php";
if(isset($_GET['id'])){
    $id = $_GET['id'];
}
$query = "SELECT location_name FROM item_location WHERe id = '$id' LIMIT 1";
$res = $conn->query($query);
$row=$res->fetch_assoc();
$location_name = $row['location_name'];
?>
<label class="col-form-label" for="recipient-name">location Name</label>
<input class="form-control" name="location_name" id="recipient-name" type="text" value="<?php echo $location_name;?>"/>
<input type="text" name="id" value="<?php echo $id; ?>"  hidden>