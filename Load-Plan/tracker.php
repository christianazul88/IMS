<?php
require __DIR__.'/tracker-config.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
if (isset($_SERVER['HTTP_SEC_FETCH_SITE']) && $_SERVER['HTTP_SEC_FETCH_SITE'] === 'cross-site') { http_response_code(403); exit; }
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 2048) { http_response_code(413); exit; }
$d=json_decode(file_get_contents('php://input'),true);
$valid='/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i';
if (!is_array($d) || !preg_match($valid,$d['visit'] ?? '') || !preg_match($valid,$d['device'] ?? '') || !isset($d['seconds']) || !is_numeric($d['seconds'])) { http_response_code(400); exit; }
$seconds=max(0,min(604800,(int)$d['seconds']));
try {
 $db=tracking_db();
 $q=$db->prepare('INSERT IGNORE INTO scene_visits (visit_id,device_id) VALUES (?,?)');
 $q->execute([$d['visit'],$d['device']]);
 $q=$db->prepare('UPDATE scene_visits SET active_seconds=GREATEST(active_seconds,LEAST(?,TIMESTAMPDIFF(SECOND,started_at,CURRENT_TIMESTAMP)+15)),last_seen=CURRENT_TIMESTAMP WHERE visit_id=? AND device_id=?');
 $q->execute([$seconds,$d['visit'],$d['device']]);
 echo '{"ok":true}';
} catch(Throwable $e) { http_response_code(503); echo '{"ok":false}'; }
