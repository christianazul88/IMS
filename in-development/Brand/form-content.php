<?php
include "../config/database.php";
if(isset($_GET['id'])){
    $id = $_GET['id'];
}
$query = "SELECT brand_name FROM brand WHERe hashed_id = '$id' LIMIT 1";
$res = $conn->query($query);
$row=$res->fetch_assoc();
$brand_name = $row['brand_name'];
?>
<label class="col-form-label" for="recipient-name">brand Name</label>
<input class="form-control" name="brand_name" id="recipient-name" type="text" value="<?php echo $brand_name;?>"/>
<input type="text" name="id" value="<?php echo $id; ?>"  hidden>