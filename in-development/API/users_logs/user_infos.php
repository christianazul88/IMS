<?php 
include "../../config/database.php";

$sql = "SELECT u.* 
        FROM users u
        LEFT JOIN warehouse w ON w.id "