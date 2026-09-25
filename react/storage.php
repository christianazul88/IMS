<?php
declare(strict_types=1);

// Keep visitor data outside the public website. Override when deploying elsewhere.
function invite_store_path(): string {
    return getenv('INVITE_DATA_FILE') ?: dirname(__DIR__, 3) . '/private/date-invite/activity.json';
}
function invite_store(callable $callback, bool $write = true): array {
    $path = invite_store_path();
    if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0700, true) && !is_dir(dirname($path))) {
        throw new RuntimeException('Cannot create activity directory.');
    }
    $file = fopen($path, 'c+');
    if (!$file || !flock($file, LOCK_EX)) throw new RuntimeException('Cannot lock activity file.');
    try {
        $raw = stream_get_contents($file);
        $data = $raw === '' ? ['version' => 1, 'visitors' => [], 'events' => []] : json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        $result = $callback($data);
        if ($write || $raw === '') {
            $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            rewind($file);
            if (fwrite($file, $encoded) !== strlen($encoded)) throw new RuntimeException('Activity write failed.');
            ftruncate($file, strlen($encoded));
            fflush($file);
        }
        return $result;
    } finally {
        flock($file, LOCK_UN);
        fclose($file);
    }
}
