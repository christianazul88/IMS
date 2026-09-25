<?php
declare(strict_types=1);
require __DIR__ . '/storage.php';
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

function invite_admin_config(): array {
    $paths = [];
    $configured = getenv('INVITE_ADMIN_CONFIG');
    if (is_string($configured) && $configured !== '') $paths[] = $configured;
    $paths[] = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'date-invite' . DIRECTORY_SEPARATOR . 'admin-config.php';
    $paths[] = __DIR__ . DIRECTORY_SEPARATOR . 'admin-config.php';
    foreach ($paths as $path) if (is_file($path)) { $value = require $path; return is_array($value) ? $value : []; }
    return [];
}
$adminConfig = invite_admin_config();
$adminUser = (string)(getenv('INVITE_ADMIN_USER') ?: ($adminConfig['username'] ?? ''));
$adminPassword = (string)(getenv('INVITE_ADMIN_PASSWORD') ?: ($adminConfig['password'] ?? ''));
$isLocalRequest = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
if (!$isLocalRequest) {
    if ($adminUser === '' || $adminPassword === '') { http_response_code(503); header('Content-Type: text/plain; charset=utf-8'); exit("Invite dashboard authentication is not configured.\n"); }
    if (!hash_equals($adminUser, (string)($_SERVER['PHP_AUTH_USER'] ?? '')) || !hash_equals($adminPassword, (string)($_SERVER['PHP_AUTH_PW'] ?? ''))) {
        header('WWW-Authenticate: Basic realm="Invite dashboard", charset="UTF-8"'); http_response_code(401); header('Content-Type: text/plain; charset=utf-8'); exit("Authentication required.\n");
    }
}
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
$db = invite_db();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    invite_db_reset(); header('Location: admin.php?reset=1', true, 303); exit;
}
$filter = is_string($_GET['visitor'] ?? null) ? $_GET['visitor'] : '';
$stats = $db->query('SELECT COALESCE(SUM(visits_count), 0) AS visits, COUNT(*) AS browsers FROM invite_visitors')->fetch();
$actionCount = (int)$db->query('SELECT COUNT(*) FROM invite_events')->fetchColumn();
$visitorRows = $db->query('SELECT visitor_id, visits_count, name_typed, device_label, browser, os_name FROM invite_visitors ORDER BY last_seen DESC')->fetchAll();
$selectionQuery = 'SELECT target, COUNT(*) AS total FROM invite_events WHERE event_type = "selection"' . ($filter !== '' ? ' AND visitor_id = :visitor' : '') . ' GROUP BY target ORDER BY total DESC';
$selectionStmt = $db->prepare($selectionQuery); if ($filter !== '') $selectionStmt->bindValue(':visitor', $filter); $selectionStmt->execute(); $selections = $selectionStmt->fetchAll();
$eventQuery = 'SELECT occurred_at, visitor_id, visit_id, event_type, target, elapsed_ms, name_typed, device_label FROM invite_events' . ($filter !== '' ? ' WHERE visitor_id = :visitor' : '') . ' ORDER BY id DESC LIMIT 500';
$eventStmt = $db->prepare($eventQuery); if ($filter !== '') $eventStmt->bindValue(':visitor', $filter); $eventStmt->execute(); $events = $eventStmt->fetchAll();
$resetMessage = isset($_GET['reset']) ? 'Activity reset complete. All counters are back to 0.' : '';
?>
<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Invite activity</title>
<style>body{font:15px/1.6 system-ui;color:#252923;background:#fafbf8;margin:0}main{max-width:1150px;margin:50px auto;padding:0 24px}h1{font-size:32px;margin-bottom:0}small,p{color:#656b62}a{color:#2b6c4e}header{display:flex;align-items:center;justify-content:space-between}.stats{display:flex;gap:16px;margin:28px 0}.stats div{flex:1;background:white;padding:20px;border:1px solid #e1e6dd;border-radius:12px}.stats strong{display:block;font-size:28px}table{border-collapse:collapse;width:100%;background:white}td,th{padding:12px;text-align:left;border-bottom:1px solid #e5e8e1;overflow-wrap:anywhere}th{font-size:12px;text-transform:uppercase}.scroll{overflow:auto}select,button{padding:10px;border:1px solid #ccc;border-radius:7px;background:white}.choices span{display:inline-block;margin:5px;padding:7px 12px;background:#eaf1e7;border-radius:30px}.reset{margin-top:20px;border-color:#d98b82;color:#8f2e27;background:#fff4f2}.notice{padding:12px 14px;border-radius:8px;color:#24613f;background:#e8f7ee}code{overflow-wrap:anywhere}</style>
<main><header><div><h1>Invite activity</h1><p>Visits, names, devices, choices, and interaction order.</p></div><a href="admin.php">Refresh</a></header>
<?php if ($resetMessage): ?><p class="notice"><?= h($resetMessage) ?></p><?php endif; ?>
<div class="stats"><div><strong><?= (int)$stats['visits'] ?></strong>Visits</div><div><strong><?= count($visitorRows) ?></strong>Browsers</div><div><strong><?= $actionCount ?></strong>Actions</div></div>
<p>Device labels come from the browser user agent. Browsers generally identify “iPhone” but do not reliably reveal an exact model such as iPhone 11.</p>
<form><label>Show browser <select name="visitor"><option value="">All browsers</option><?php foreach ($visitorRows as $visitor): ?><option value="<?= h($visitor['visitor_id']) ?>" <?= $filter === $visitor['visitor_id'] ? 'selected' : '' ?>><?= h(substr($visitor['visitor_id'], 0, 8)) ?> · <?= (int)$visitor['visits_count'] ?> visits · <?= h($visitor['device_label'] ?? 'Unknown device') ?></option><?php endforeach ?></select></label> <button>Apply</button></form>
<form method="post" onsubmit="return confirm('Reset every visit, browser, choice, and action? This cannot be undone.');"><input type="hidden" name="action" value="reset"><button class="reset" type="submit">Reset all activity to 0</button></form>
<h2>Browsers and names</h2><div class="scroll"><table><thead><tr><th>Browser ID</th><th>Visits</th><th>Name typed</th><th>Device</th><th>Browser / OS</th></tr></thead><tbody><?php foreach ($visitorRows as $visitor): ?><tr><td><?= h(substr($visitor['visitor_id'], 0, 8)) ?></td><td><?= (int)$visitor['visits_count'] ?></td><td><?= h($visitor['name_typed'] ?? '—') ?></td><td><?= h($visitor['device_label'] ?? 'Unknown') ?></td><td><?= h(($visitor['browser'] ?? 'Unknown') . ' / ' . ($visitor['os_name'] ?? 'Unknown')) ?></td></tr><?php endforeach ?><?php if (!$visitorRows): ?><tr><td colspan="5">No visits yet.</td></tr><?php endif ?></tbody></table></div>
<h2>Choices tapped</h2><div class="choices"><?php foreach ($selections as $choice): ?><span><?= h($choice['target']) ?> · <?= (int)$choice['total'] ?></span><?php endforeach ?><?php if (!$selections): ?>No choices yet.<?php endif ?></div>
<h2>Recent actions</h2><p>Latest 500 shown. Times below are UTC.</p><div class="scroll"><table><thead><tr><th>Time</th><th>Browser / visit</th><th>Name</th><th>Device</th><th>Action</th><th>Target</th><th>Seconds</th></tr></thead><tbody><?php foreach ($events as $event): ?><tr><td><?= h($event['occurred_at']) ?></td><td><?= h(substr($event['visitor_id'], 0, 8)) ?><br><small><?= h(substr($event['visit_id'], 0, 8)) ?></small></td><td><?= h($event['name_typed'] ?? '—') ?></td><td><?= h($event['device_label'] ?? 'Unknown') ?></td><td><?= h($event['event_type']) ?></td><td><?= h($event['target']) ?></td><td><?= round(((int)$event['elapsed_ms']) / 1000, 1) ?></td></tr><?php endforeach ?></tbody></table></div>
<p>Database: <code><?= h((string)(getenv('INVITE_DB_NAME') ?: 'testonly123')) ?></code></p></main></html>
