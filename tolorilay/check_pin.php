<?php

session_start();

$pin = trim($_POST['pin'] ?? '');

if ($pin === '050444') {

    $_SESSION['pin_verified'] = true;

    echo json_encode([
        'success' => true
    ]);

    exit;
}

echo json_encode([
    'success' => false
]);