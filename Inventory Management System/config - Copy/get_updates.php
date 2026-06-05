<?php

session_start();

if(!isset($_SESSION['updates'])){
    $_SESSION['updates']=[];
}

echo json_encode($_SESSION['updates']);