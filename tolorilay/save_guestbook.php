<?php
/**
 * save_guestbook.php
 * Receives { name, message } as JSON and appends it to guestbook.json.
 * Returns the updated list of entries as JSON.
 */

header('Content-Type: application/json');

$dataFile = __DIR__ . '/guestbook.json';

// Read existing entries (create the file if it somehow doesn't exist yet).
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, '[]');
}

$raw = file_get_contents($dataFile);
$entries = json_decode($raw, true);
if (!is_array($entries)) {
    $entries = [];
}

$input = json_decode(file_get_contents('php://input'), true);

$name = isset($input['name']) ? trim(strip_tags($input['name'])) : '';
$message = isset($input['message']) ? trim(strip_tags($input['message'])) : '';

if ($message === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'A message is required.']);
    exit;
}

if ($name === '') {
    $name = 'Anonymous';
}

// Keep things reasonable in length.
$name = mb_substr($name, 0, 60);
$message = mb_substr($message, 0, 600);

$entries[] = [
    'name' => $name,
    'message' => $message,
    'timestamp' => date('Y-m-d H:i:s'),
];

file_put_contents($dataFile, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['ok' => true, 'entries' => $entries]);
