<?php
include "../config/database.php";
if(isset($_GET['id'])){
    $id = $_GET['id'];
}
$query = "SELECT logistic_name FROM logistic_partner WHERe hashed_id = '$id' LIMIT 1";
$res = $conn->query($query);
$row=$res->fetch_assoc();
$logistic_name = $row['logistic_name'];
?>
<label class="col-form-label" for="recipient-name">Logistic Name</label>
<input class="form-control" name="logistic_name" id="recipient-name" type="text" value="<?php echo $logistic_name;?>"/>
<input type="text" name="id" value="<?php echo $id; ?>"  hidden>