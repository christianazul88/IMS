<?php
declare(strict_types=1);
require __DIR__ . '/storage.php';
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

/*
 * The dashboard used to allow localhost only. That works on a development
 * machine, but a VPS sees the visitor's real remote address and therefore
 * blocked every normal browser request. Local access remains passwordless;
 * remote access uses standard HTTP Basic Auth, which Apache/LAMPP supports.
 *
 * Credentials can be supplied through INVITE_ADMIN_USER and
 * INVITE_ADMIN_PASSWORD, or through admin-config.php. The config file is
 * deliberately outside the public web root when the usual project layout is
 * used, but a same-directory file is also supported for portable hosting.
 */
function invite_admin_config(): array {
    $paths = [];
    $configured = getenv('INVITE_ADMIN_CONFIG');
    if (is_string($configured) && $configured !== '') $paths[] = $configured;
    $paths[] = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'date-invite' . DIRECTORY_SEPARATOR . 'admin-config.php';
    $paths[] = __DIR__ . DIRECTORY_SEPARATOR . 'admin-config.php';
    foreach ($paths as $path) {
        if (is_file($path)) {
            $value = require $path;
            return is_array($value) ? $value : [];
        }
    }
    return [];
}

$adminConfig = invite_admin_config();
$adminUser = (string)(getenv('INVITE_ADMIN_USER') ?: ($adminConfig['username'] ?? ''));
$adminPassword = (string)(getenv('INVITE_ADMIN_PASSWORD') ?: ($adminConfig['password'] ?? ''));
$isLocalRequest = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

if (!$isLocalRequest) {
    if ($adminUser === '' || $adminPassword === '') {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        exit("Invite dashboard authentication is not configured. Set INVITE_ADMIN_USER and INVITE_ADMIN_PASSWORD in Apache/PHP, or create admin-config.php from admin-config.example.php.\n");
    }
    $providedUser = (string)($_SERVER['PHP_AUTH_USER'] ?? '');
    $providedPassword = (string)($_SERVER['PHP_AUTH_PW'] ?? '');
    if (!hash_equals($adminUser, $providedUser) || !hash_equals($adminPassword, $providedPassword)) {
        header('WWW-Authenticate: Basic realm="Invite dashboard", charset="UTF-8"');
        http_response_code(401);
        header('Content-Type: text/plain; charset=utf-8');
        exit("Authentication required.\n");
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    invite_store(function (&$data): void {
        $data = ['version' => 1, 'visitors' => [], 'events' => []];
    });
    header('Location: admin.php?reset=1', true, 303);
    exit;
}
$data = invite_store(fn (&$data) => $data, false);
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
$resetMessage = isset($_GET['reset']) ? 'Activity reset complete. All counters are back to 0.' : '';
$filter = is_string($_GET['visitor'] ?? null) ? $_GET['visitor'] : '';
$events = array_values(array_filter($data['events'], fn ($e) => $filter === '' || $e['visitor'] === $filter));
$visits = array_sum(array_map(fn ($v) => count($v['visits']), $data['visitors']));
$selections = [];
foreach ($events as $event) if ($event['type'] === 'selection') $selections[$event['target']] = ($selections[$event['target']] ?? 0) + 1;
arsort($selections);
?>
<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Invite activity</title>
<style>body{font:15px/1.6 system-ui;color:#252923;background:#fafbf8;margin:0}main{max-width:1050px;margin:50px auto;padding:0 24px}h1{font-size:32px;margin-bottom:0}small,p{color:#656b62}a{color:#2b6c4e}header{display:flex;align-items:center;justify-content:space-between}.stats{display:flex;gap:16px;margin:28px 0}.stats div{flex:1;background:white;padding:20px;border:1px solid #e1e6dd;border-radius:12px}.stats strong{display:block;font-size:28px}table{border-collapse:collapse;width:100%;background:white}td,th{padding:12px;text-align:left;border-bottom:1px solid #e5e8e1;overflow-wrap:anywhere}th{font-size:12px;text-transform:uppercase}.scroll{overflow:auto}select,button{padding:10px;border:1px solid #ccc;border-radius:7px;background:white}.choices span{display:inline-block;margin:5px;padding:7px 12px;background:#eaf1e7;border-radius:30px}.reset{margin-top:20px;border-color:#d98b82;color:#8f2e27;background:#fff4f2}.notice{padding:12px 14px;border-radius:8px;color:#24613f;background:#e8f7ee}code{overflow-wrap:anywhere}</style>
<main><header><div><h1>Invite activity</h1><p>Visits, choices, and the order of interactions.</p></div><a href="admin.php">Refresh</a></header>
<?php if ($resetMessage): ?><p class="notice"><?= h($resetMessage) ?></p><?php endif; ?>
<div class="stats"><div><strong><?= $visits ?></strong>Visits</div><div><strong><?= count($data['visitors']) ?></strong>Browsers</div><div><strong><?= count($data['events']) ?></strong>Actions</div></div>
<p>A visit is a page load. Repeat visits use a browser ID; this does not prove who visited. Clearing browser storage or using another device creates a new ID. No IP addresses or typed names are stored.</p>
<form><label>Show browser <select name="visitor"><option value="">All browsers</option><?php foreach ($data['visitors'] as $id => $visitor): ?><option value="<?= h($id) ?>" <?= $filter === $id ? 'selected' : '' ?>><?= h(substr($id, 0, 8)) ?> · <?= count($visitor['visits']) ?> visits</option><?php endforeach ?></select></label> <button>Apply</button></form>
<form method="post" onsubmit="return confirm('Reset every visit, browser, choice, and action? This cannot be undone.');"><input type="hidden" name="action" value="reset"><button class="reset" type="submit">Reset all activity to 0</button></form>
<h2>Choices tapped</h2><div class="choices"><?php foreach ($selections as $choice => $count): ?><span><?= h($choice) ?> · <?= $count ?></span><?php endforeach ?><?php if (!$selections): ?>No choices yet.<?php endif ?></div>
<h2>Recent actions</h2><p>Latest 500 shown. The JSON file keeps the full history. Times below are UTC.</p><div class="scroll"><table><thead><tr><th>Time</th><th>Browser / visit</th><th>Action</th><th>Target</th><th>Seconds in visit</th></tr></thead><tbody><?php foreach (array_slice(array_reverse($events), 0, 500) as $event): ?><tr><td><?= h($event['at']) ?></td><td><?= h(substr($event['visitor'], 0, 8)) ?><br><small><?= h(substr($event['visit'], 0, 8)) ?></small></td><td><?= h($event['type']) ?></td><td><?= h($event['target']) ?></td><td><?= round($event['elapsedMs'] / 1000, 1) ?></td></tr><?php endforeach ?></tbody></table></div>
<p>Private JSON: <code><?= h(invite_store_path()) ?></code></p>
<p>Music: <?= is_file(__DIR__ . '/assets/music.mp3') ? 'ready' : 'not added yet — place your MP3 in date-invite/assets/music.mp3, then refresh the invite' ?>.</p></main></html>
