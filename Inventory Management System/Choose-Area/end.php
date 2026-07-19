<?php
session_start();
unset($_SESSION['NEW_LOCATION_NAME']); 

header("Location: ../Choose-Area/");
exit;