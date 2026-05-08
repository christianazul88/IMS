<?php

session_start();

$audit_id = $_SESSION['audit_id'];
$selected_area = $_SESSION['selected_area'];

$json_file = "../audit_json/" . $audit_id . "-" . $selected_area . ".json";

$count = 0;

if(file_exists($json_file)){

    $data = json_decode(file_get_contents($json_file), true);

    if(is_array($data)){
        $count = count($data);
    }

}

echo json_encode([
    "count" => $count
]);