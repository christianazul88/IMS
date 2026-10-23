<?php
// These entry points are scheduled CLI jobs, never public download endpoints.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');
set_exception_handler(function (Throwable $e) {
    fwrite(STDERR, 'Report failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
});
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/database.php';
$conn->set_charset('utf8mb4');
// IMS writes transaction timestamps in Manila time (UTC+08:00).
date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");
$options = getopt('', ['date:', 'local-only']);
$reportDate = $options['date'] ?? date('Y-m-d', strtotime('yesterday'));
$day = DateTimeImmutable::createFromFormat('!Y-m-d', $reportDate);
if (!$day || $day->format('Y-m-d') !== $reportDate) {
    throw new InvalidArgumentException('Use --date=YYYY-MM-DD');
}
$startDate = $day->format('Y-m-d H:i:s');
$endDate = $day->modify('+1 day')->format('Y-m-d H:i:s');
$storage = getenv('IMS_REPORT_DIR');
if (!$storage) {
    $storage = PHP_OS_FAMILY === 'Windows'
        ? 'C:/xampp/IMS-private/IMS-daily-reports'
        : rtrim(getenv('HOME') ?: sys_get_temp_dir(), '/\\') . '/.local/share/ims-daily-reports';
}
if (!$storage || !is_dir($storage) && !mkdir($storage, 0700, true)) {
    throw new RuntimeException('Cannot create private report directory.');
}

function report_query(string $sql): mysqli_result
{
    global $conn, $startDate, $endDate;
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $startDate, $endDate);
    $stmt->execute();
    return $stmt->get_result();
}

function report_output()
{
    $output = fopen('php://temp/maxmemory:5242880', 'w+');
    if (!$output) {
        throw new RuntimeException('Cannot open report stream.');
    }
    return $output;
}

function report_csv($output, array $fields): void
{
    // Prevent spreadsheet formula execution in untrusted text fields.
    $fields = array_map(function ($value) {
        $value = (string) ($value ?? '');
        return preg_match('/^[\s]*[=+@-]/u', $value) && !is_numeric($value) ? "'" . $value : $value;
    }, $fields);
    if (fputcsv($output, $fields, ',', '"', '') === false) {
        throw new RuntimeException('Cannot write CSV row.');
    }
}

function report_finish($output, string $kind, int $rows): void
{
    global $storage, $reportDate, $options;
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    $name = $kind . '_' . $reportDate . '.csv';
    $driveName = $kind . '_' . $reportDate . '.xlsx';
    $path = $storage . '/' . $name;
    if (file_put_contents($path . '.tmp', $csv, LOCK_EX) === false || !rename($path . '.tmp', $path)) {
        throw new RuntimeException('Cannot save local CSV.');
    }
    $result = ['report' => $kind, 'date' => $reportDate, 'rows' => $rows, 'path' => $path];
    if (!isset($options['local-only'])) {
        $configPath = $storage . '/upload.json';
        if (!is_file($configPath)) {
            throw new RuntimeException('Local CSV saved; Drive upload is not configured: ' . $configPath);
        }
        $config = json_decode(file_get_contents($configPath), true, 512, JSON_THROW_ON_ERROR);
        if (!preg_match('~^https://script\.google\.com/macros/s/[a-zA-Z0-9_-]+/exec$~', $config['url'] ?? '') || empty($config['secret'])) {
            throw new RuntimeException('Invalid Drive receiver configuration.');
        }
        // CSV is only the signed transport format. Apps Script converts it to a
        // formatted Excel workbook in its matching Drive report folder.
        $payload = json_encode(['name' => $driveName, 'csv' => $csv], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $body = json_encode(['payload' => $payload, 'signature' => hash_hmac('sha256', $payload, $config['secret'])], JSON_THROW_ON_ERROR);
        $ch = curl_init($config['url']);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 5, CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 180, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        $uploaded = $response === false ? null : json_decode($response, true);
        if ($status !== 200 || !is_array($uploaded) || ($uploaded['ok'] ?? false) !== true || empty($uploaded['id']) || ($uploaded['name'] ?? '') !== $driveName || ($uploaded['mimeType'] ?? '') !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            throw new RuntimeException('Local CSV saved; Drive upload failed (HTTP ' . $status . '). ' . $error . ' ' . (is_array($uploaded) ? ($uploaded['error'] ?? '') : ''));
        }
        $result['drive_id'] = $uploaded['id'];
    }
    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
}
