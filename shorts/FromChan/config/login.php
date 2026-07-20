<?php
$validNames = [
    "lorelie",
    "lorilay",
    "lori",
    "lorie"
];

$validPasswords = [
    "alepacio",
    "apelacio"
];

if (isset($_POST['name']) && isset($_POST['pw'])) {

    $name = strtolower(trim($_POST['name']));
    $pw = strtolower(trim($_POST['pw']));

    if (in_array($name, $validNames) && in_array($pw, $validPasswords)) {
        echo "yown";
    } else {
        echo "Invalid credentials.";
    }
}