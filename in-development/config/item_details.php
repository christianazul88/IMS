<?php
include "../config/database.php";

if(isset($_GET['id'])){
    $id = $_GET['id'];
    $query = "SELECT * FROM stocks WHERE product_id = '$id'";
    $result = mysqli_query($conn, $query);
    while($row=$result->fetch_assoc()){
        echo $row['unique_barcode'];
    }
} else {
    echo "burat";
}
