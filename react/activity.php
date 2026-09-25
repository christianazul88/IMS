<?php
declare(strict_types=1);
require __DIR__ . '/storage.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('{"error":"POST required"}'); }
if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') { http_response_code(403); exit('{"error":"Same-origin requests only"}'); }
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 8192) { http_response_code(413); exit('{"error":"Payload too large"}'); }
try {
    $input = json_decode(file_get_contents('php://input', false, null, 0, 8193), true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new InvalidArgumentException('Invalid event');
    foreach (['visitor', 'visit'] as $key) if (!is_string($input[$key] ?? null) || !preg_match('/^[a-f0-9-]{36}$/D', $input[$key])) throw new InvalidArgumentException('Invalid identifier');
    $allowed = ['visit','screen','click','hold_start','hold_pause','hold_resume','hold_cancel','hold_reset','accepted','selection','confirmed','mascot','red_returned','leave','name_submitted'];
    if (!in_array($input['type'] ?? '', $allowed, true)) throw new InvalidArgumentException('Invalid event');
    $input['name'] = is_string($input['name'] ?? null) ? mb_substr($input['name'], 0, 80) : '';
    $input['userAgent'] = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    echo json_encode(invite_record_event($input), JSON_THROW_ON_ERROR);
} catch (JsonException | InvalidArgumentException $error) {
    http_response_code(400); echo '{"error":"Invalid event"}';
} catch (Throwable $error) {
    error_log('Invite activity: ' . $error->getMessage());
    http_response_code(500); echo '{"error":"Activity could not be saved"}';
}
