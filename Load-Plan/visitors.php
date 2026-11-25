<?php
session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Strict','cookie_secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off']);
header('Cache-Control: no-store');
require __DIR__.'/tracker-config.php';
$hash=getenv('TRACK_ADMIN_PASSWORD_HASH');
$local=in_array($_SERVER['REMOTE_ADDR'] ?? '',['127.0.0.1','::1'],true);
if (isset($_POST['password']) && $hash && password_verify($_POST['password'],$hash)) { session_regenerate_id(true); $_SESSION['tracker_admin']=true; }
$authorized=($hash && !empty($_SESSION['tracker_admin'])) || (!$hash && $local);
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Scene visitors</title>
<style>body{margin:0;background:#100d16;color:#f4e8ee;font:15px system-ui;padding:clamp(20px,5vw,70px)}main{max-width:1100px;margin:auto}h1{font-size:38px;letter-spacing:-1px}p{color:#b6a5b4}a{color:#f2a6c3}.cards{display:flex;gap:16px;flex-wrap:wrap;margin:30px 0}.card{background:#231a29;border:1px solid #493047;border-radius:18px;padding:24px;min-width:170px}.card strong{display:block;font-size:32px;margin-top:10px}.table{overflow:auto}table{width:100%;border-collapse:collapse;white-space:nowrap}td,th{text-align:left;padding:16px;border-bottom:1px solid #352738}th{color:#d4a0b9}input,button{padding:13px;border-radius:8px;border:1px solid #73516b;background:#281e2e;color:white}button{cursor:pointer}small{color:#b6a5b4}</style><main>
<h1>Scene visitors</h1>
<?php if(!$authorized): ?>
<p>Owner access only.</p><?php if($hash): ?><form method="post"><input type="password" name="password" required autocomplete="current-password" placeholder="Dashboard password"><button>Sign in</button></form><?php else: ?><p>Set TRACK_ADMIN_PASSWORD_HASH on the server to enable remote dashboard access.</p><?php endif; ?>
<?php else: ?>
<p>Anonymous browser visits and time with the scene visible. All timestamps are UTC. <a href="visitors.php">Refresh</a></p>
<?php try { $db=tracking_db(); $tot=$db->query('SELECT COUNT(*) visits,COUNT(DISTINCT device_id) devices,COALESCE(SUM(active_seconds),0) seconds FROM scene_visits')->fetch(); ?>
<div class="cards"><?php foreach(['devices'=>'Browsers','visits'=>'Visits','seconds'=>'Active seconds'] as $key=>$label): ?><div class="card"><?=h($label)?><strong><?=number_format($tot[$key])?></strong></div><?php endforeach; ?></div>
<div class="table"><table><thead><tr><th>Browser ID</th><th>Visits</th><th>Active time</th><th>First visit</th><th>Last seen</th></tr></thead><tbody>
<?php foreach($db->query('SELECT device_id,COUNT(*) visits,SUM(active_seconds) seconds,MIN(started_at) first_visit,MAX(last_seen) last_seen FROM scene_visits GROUP BY device_id ORDER BY last_seen DESC LIMIT 500') as $row): ?>
<tr><td title="<?=h($row['device_id'])?>"><?=h(substr($row['device_id'],0,8))?></td><td><?=h($row['visits'])?></td><td><?=h(floor($row['seconds']/60))?>m <?=h($row['seconds']%60)?>s</td><td><?=h($row['first_visit'])?></td><td><?=h($row['last_seen'])?></td></tr>
<?php endforeach; ?></tbody></table></div><p><small>Latest 500 browsers. Reloading counts as a new visit. Clearing browser storage creates a new browser ID. Concurrent tabs count separately. No names, IP addresses, or browser fingerprints are stored by this tracker.</small></p>
<?php } catch(Throwable $e) { echo '<p>Database unavailable. Check MySQL credentials and CREATE permission for sampletest; setup runs automatically.</p>'; } ?>
<?php endif; ?></main></html>
