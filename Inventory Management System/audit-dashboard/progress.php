<?php
session_start();

$audit_id = $_GET['audit_id'] ?? 0;

$p = $_SESSION['csv_progress'][$audit_id] ?? null;

if (!$p) {
    echo json_encode([
        "progress" => 0,
        "message" => "Idle..."
    ]);
    exit;
}

$percent = ($p['total'] > 0)
    ? round(($p['done'] / $p['total']) * 100, 2)
    : 0;

echo json_encode([
    "progress" => $percent,
    "done" => $p['done'],
    "total" => $p['total'],
    "stage" => $p['stage'],
    "message" => $p['message']
]);