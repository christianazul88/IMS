<?php
declare(strict_types=1);
require __DIR__ . '/storage.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('{"error":"POST required"}'); }
if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') { http_response_code(403); exit('{"error":"Same-origin requests only"}'); }
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 4096) { http_response_code(413); exit('{"error":"Payload too large"}'); }
try {
    $input = json_decode(file_get_contents('php://input', false, null, 0, 4097), true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new InvalidArgumentException('Invalid event');
    foreach (['visitor', 'visit'] as $key) {
        if (!is_string($input[$key] ?? null) || !preg_match('/^[a-f0-9-]{36}$/D', $input[$key])) throw new InvalidArgumentException('Invalid identifier');
    }
    $allowed = ['visit', 'screen', 'click', 'hold_start', 'hold_pause', 'hold_resume', 'hold_cancel', 'hold_reset', 'accepted', 'selection', 'confirmed', 'mascot', 'red_returned', 'leave'];
    if (!in_array($input['type'] ?? '', $allowed, true)) throw new InvalidArgumentException('Invalid event');
    $target = is_string($input['target'] ?? null) ? mb_substr($input['target'], 0, 350) : '';
    $result = invite_store(function (&$data) use ($input, $target) {
        $id = $input['visitor'];
        $now = gmdate('c');
        if (!isset($data['visitors'][$id])) $data['visitors'][$id] = ['firstSeen' => $now, 'lastSeen' => $now, 'visits' => []];
        $visitor = &$data['visitors'][$id];
        $visitor['lastSeen'] = $now;
        $fresh = !isset($visitor['visits'][$input['visit']]);
        $visitor['visits'][$input['visit']] = $visitor['visits'][$input['visit']] ?? $now;
        if ($input['type'] !== 'visit' || $fresh) {
            $data['events'][] = ['at' => $now, 'visitor' => $id, 'visit' => $input['visit'], 'type' => $input['type'], 'target' => $target, 'elapsedMs' => max(0, min(86400000, (int)($input['elapsedMs'] ?? 0)))];
        }
        return ['ok' => true, 'visits' => count($visitor['visits'])];
    });
    echo json_encode($result);
} catch (JsonException | InvalidArgumentException $error) {
    http_response_code(400); echo '{"error":"Invalid event"}';
} catch (Throwable $error) {
    error_log('Invite activity: ' . $error->getMessage());
    http_response_code(500); echo '{"error":"Activity could not be saved"}';
}
