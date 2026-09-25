<?php
declare(strict_types=1);

// Activity is stored in MySQL/MariaDB instead of a JSON file.
function invite_db(): PDO {
    static $db;
    if ($db instanceof PDO) return $db;
    $host = (string)(getenv('INVITE_DB_HOST') ?: 'localhost');
    $port = (string)(getenv('INVITE_DB_PORT') ?: '3306');
    $name = (string)(getenv('INVITE_DB_NAME') ?: 'testonly123');
    $user = (string)(getenv('INVITE_DB_USER') ?: 'root');
    $pass = (string)(getenv('INVITE_DB_PASSWORD') ?: '');
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) throw new RuntimeException('Invalid database name.');
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    try {
        $db = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, $options);
    } catch (PDOException $error) {
        if ((int)$error->errorInfo[1] !== 1049) throw $error;
        $server = new PDO("mysql:host={$host};port={$port}", $user, $pass, $options);
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, $options);
    }
    invite_db_setup($db);
    return $db;
}
function invite_db_setup(PDO $db): void {
    static $ready = false;
    if ($ready) return;
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS invite_visitors (
    visitor_id CHAR(36) NOT NULL PRIMARY KEY, first_seen DATETIME(6) NOT NULL,
    last_seen DATETIME(6) NOT NULL, visits_count INT UNSIGNED NOT NULL DEFAULT 0,
    name_typed VARCHAR(80) NULL, device_label VARCHAR(120) NULL,
    browser VARCHAR(80) NULL, os_name VARCHAR(80) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS invite_visits (
    visit_id CHAR(36) NOT NULL PRIMARY KEY, visitor_id CHAR(36) NOT NULL,
    started_at DATETIME(6) NOT NULL, last_seen DATETIME(6) NOT NULL,
    name_typed VARCHAR(80) NULL, device_label VARCHAR(120) NULL,
    CONSTRAINT fk_invite_visits_visitor FOREIGN KEY (visitor_id) REFERENCES invite_visitors(visitor_id) ON DELETE CASCADE,
    INDEX idx_invite_visits_visitor (visitor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS invite_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, occurred_at DATETIME(6) NOT NULL,
    visitor_id CHAR(36) NOT NULL, visit_id CHAR(36) NOT NULL, event_type VARCHAR(40) NOT NULL,
    target VARCHAR(350) NOT NULL DEFAULT '', elapsed_ms INT UNSIGNED NOT NULL DEFAULT 0,
    name_typed VARCHAR(80) NULL, device_label VARCHAR(120) NULL,
    CONSTRAINT fk_invite_events_visitor FOREIGN KEY (visitor_id) REFERENCES invite_visitors(visitor_id) ON DELETE CASCADE,
    CONSTRAINT fk_invite_events_visit FOREIGN KEY (visit_id) REFERENCES invite_visits(visit_id) ON DELETE CASCADE,
    INDEX idx_invite_events_time (occurred_at), INDEX idx_invite_events_visitor (visitor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
    $ready = true;
}
function invite_now(): string { return gmdate('Y-m-d H:i:s.u'); }
function invite_db_reset(): void {
    $db = invite_db(); $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    $db->exec('TRUNCATE TABLE invite_events'); $db->exec('TRUNCATE TABLE invite_visits'); $db->exec('TRUNCATE TABLE invite_visitors');
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
}
function invite_device_label(string $ua): string {
    if (preg_match('/iPad/i', $ua)) return 'iPad';
    if (preg_match('/iPhone/i', $ua)) return 'iPhone';
    if (preg_match('/Android/i', $ua)) return 'Android phone/tablet';
    if (preg_match('/Windows Phone/i', $ua)) return 'Windows phone';
    if (preg_match('/Macintosh|Mac OS X/i', $ua)) return 'Mac';
    if (preg_match('/Windows/i', $ua)) return 'Windows PC';
    if (preg_match('/Linux/i', $ua)) return 'Linux device';
    return 'Unknown device';
}
function invite_browser_label(string $ua): string {
    foreach (['Edg'=>'Edge','OPR'=>'Opera','CriOS'=>'Chrome iOS','FxiOS'=>'Firefox iOS','Chrome'=>'Chrome','Firefox'=>'Firefox','Safari'=>'Safari'] as $needle => $label) if (stripos($ua, $needle) !== false) return $label;
    return 'Unknown browser';
}
function invite_os_label(string $ua): string {
    if (stripos($ua, 'iPhone OS') !== false || stripos($ua, 'iPad') !== false) return 'iOS';
    if (stripos($ua, 'Android') !== false) return 'Android';
    if (stripos($ua, 'Windows') !== false) return 'Windows';
    if (stripos($ua, 'Mac OS X') !== false) return 'macOS';
    if (stripos($ua, 'Linux') !== false) return 'Linux';
    return 'Unknown OS';
}
function invite_record_event(array $input): array {
    $db = invite_db(); $now = invite_now(); $visitor = $input['visitor']; $visit = $input['visit'];
    $name = trim((string)($input['name'] ?? '')); $name = $name === '' ? null : mb_substr($name, 0, 80);
    $ua = (string)($input['userAgent'] ?? ''); $device = invite_device_label($ua); $browser = invite_browser_label($ua); $os = invite_os_label($ua);
    $target = mb_substr((string)($input['target'] ?? ''), 0, 350); $elapsed = max(0, min(86400000, (int)($input['elapsedMs'] ?? 0)));
    $db->beginTransaction();
    try {
        $find = $db->prepare('SELECT visit_id FROM invite_visits WHERE visit_id = ? FOR UPDATE'); $find->execute([$visit]); $freshVisit = !$find->fetchColumn();
        $db->prepare('INSERT INTO invite_visitors (visitor_id, first_seen, last_seen, visits_count, name_typed, device_label, browser, os_name) VALUES (?, ?, ?, 0, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE last_seen = VALUES(last_seen), name_typed = COALESCE(VALUES(name_typed), name_typed), device_label = VALUES(device_label), browser = VALUES(browser), os_name = VALUES(os_name)')->execute([$visitor, $now, $now, $name, $device, $browser, $os]);
        if ($freshVisit) {
            $db->prepare('INSERT INTO invite_visits (visit_id, visitor_id, started_at, last_seen, name_typed, device_label) VALUES (?, ?, ?, ?, ?, ?)')->execute([$visit, $visitor, $now, $now, $name, $device]);
            $db->prepare('UPDATE invite_visitors SET visits_count = visits_count + 1 WHERE visitor_id = ?')->execute([$visitor]);
        } else {
            $db->prepare('UPDATE invite_visits SET last_seen = ?, name_typed = COALESCE(?, name_typed), device_label = ? WHERE visit_id = ?')->execute([$now, $name, $device, $visit]);
        }
        if ($input['type'] !== 'visit' || $freshVisit) $db->prepare('INSERT INTO invite_events (occurred_at, visitor_id, visit_id, event_type, target, elapsed_ms, name_typed, device_label) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')->execute([$now, $visitor, $visit, $input['type'], $target, $elapsed, $name, $device]);
        $db->commit(); $count = $db->prepare('SELECT visits_count FROM invite_visitors WHERE visitor_id = ?'); $count->execute([$visitor]);
        return ['ok' => true, 'visits' => (int)$count->fetchColumn()];
    } catch (Throwable $error) { if ($db->inTransaction()) $db->rollBack(); throw $error; }
}
