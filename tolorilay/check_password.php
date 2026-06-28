<?php

session_start();

$password =
strtolower(
trim($_POST['password'] ?? '')
);

if($password === 'spanishlatte'){

    $_SESSION['password_verified'] = true;

    echo json_encode([
        'success' => true
    ]);

    exit;
}

echo json_encode([
    'success' => false
]);