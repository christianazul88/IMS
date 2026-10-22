<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
date_default_timezone_set('Asia/Manila');
$storage = getenv('IMS_REPORT_DIR');
if (!$storage) {
    $storage = PHP_OS_FAMILY === 'Windows'
        ? 'C:/xampp/IMS-private/IMS-daily-reports'
        : rtrim(getenv('HOME') ?: sys_get_temp_dir(), '/\\') . '/.local/share/ims-daily-reports';
}
if (!is_dir($storage) && !mkdir($storage, 0700, true)) { exit(1); }
$lock = fopen($storage . '/runner.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { exit(1); }
$lastDay = new DateTimeImmutable('yesterday midnight');
$failed = false;
for ($day = new DateTimeImmutable('2026-10-22'); $day <= $lastDay; $day = $day->modify('+1 day')) {
    $date = $day->format('Y-m-d');
    foreach (['inbound', 'outbound'] as $kind) {
        $receipt = $storage . '/' . $kind . '_' . $date . '.uploaded.json';
        if (is_file($receipt)) { continue; }
        try {
            $pipes = [];
            $process = proc_open([PHP_BINARY, __DIR__ . '/daily_' . $kind . '.php', '--date=' . $date],
                [0 => ['pipe', 'r'], 1 => ['file', $storage . '/last-result.json', 'w'],
                 2 => ['file', $storage . '/last-error.log', 'w']], $pipes, __DIR__, null, ['bypass_shell' => true]);
            if (!is_resource($process)) { throw new RuntimeException('Cannot start exporter'); }
            fclose($pipes[0]);
            $status = proc_close($process);
            if ($status !== 0) { throw new RuntimeException(trim(file_get_contents($storage . '/last-error.log')) ?: 'Exporter failed'); }
            $raw = file_get_contents($storage . '/last-result.json');
            $result = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (empty($result['drive_id']) || $result['date'] !== $date || $result['report'] !== $kind) {
                throw new RuntimeException('Missing verified upload receipt');
            }
            if (file_put_contents($receipt . '.tmp', $raw, LOCK_EX) === false || !rename($receipt . '.tmp', $receipt)) {
                throw new RuntimeException('Cannot save upload receipt');
            }
            file_put_contents($storage . '/daily.log', date('c') . " OK $kind $date rows=" . $result['rows'] . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Throwable $error) {
            $failed = true;
            file_put_contents($storage . '/daily.log', date('c') . " FAILED $kind $date " . $error->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }
}
flock($lock, LOCK_UN);
fclose($lock);
exit($failed ? 1 : 0);
