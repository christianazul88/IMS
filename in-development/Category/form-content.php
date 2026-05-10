<?php
include "../config/database.php";
if(isset($_GET['id'])){
    $id = $_GET['id'];
}
$query = "SELECT category_name FROM category WHERe hashed_id = '$id' LIMIT 1";
$res = $conn->query($query);
$row=$res->fetch_assoc();
$category_name = $row['category_name'];
?>
<label class="col-form-label" for="recipient-name">Category Name</label>
<input class="form-control" name="category_name" id="recipient-name" type="text" value="<?php echo $category_name;?>"/>
<input type="text" name="id" value="<?php echo $id; ?>"  hidden>