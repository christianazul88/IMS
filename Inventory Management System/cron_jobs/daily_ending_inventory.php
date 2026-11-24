<?php
/**
 * Current inventory snapshot, one CSV per warehouse. Run near the end of the
 * day in Asia/Manila. This is not a historical reconstruction.
 *
 * php daily_ending_inventory.php [--output-dir=/path/to/reports] [--local-only]
 * php daily_ending_inventory.php --upload-only=YYYY-MM-DD
 * Uses IMS_REPORT_DIR/upload.json for signed uploads to the Drive receiver.
 * Upload-only retries an already saved snapshot without recapturing inventory.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR, 'Ending inventory failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
});
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Manila');
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+08:00'");

$options = getopt('', ['output-dir:', 'local-only', 'upload-only:']);
$directory = $options['output-dir'] ?? getenv('IMS_REPORT_DIR');
if (!$directory) {
    $directory = PHP_OS_FAMILY === 'Windows'
        ? 'C:/xampp/IMS-private/IMS-daily-reports'
        : rtrim(getenv('HOME') ?: sys_get_temp_dir(), '/\\') . '/.local/share/ims-daily-reports';
}
if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
    throw new RuntimeException('Cannot create output directory: ' . $directory);
}
if (!is_writable($directory)) {
    throw new RuntimeException('Output directory is not writable: ' . $directory);
}

function ending_csv_row($file, array $fields): void
{
    // Text beginning with a spreadsheet formula character must not execute.
    $fields = array_map(function ($value): string {
        $value = (string) ($value ?? '');
        return preg_match('/^[\s]*[=+@-]/u', $value) ? "'" . $value : $value;
    }, $fields);
    if (fputcsv($file, $fields, ',', '"', '') === false) {
        throw new RuntimeException('Unable to write CSV row.');
    }
}

function ending_upload(string $path, string $configDirectory): array
{
    $configPath = rtrim($configDirectory, '/\\') . '/upload.json';
    if (!is_file($configPath)) {
        throw new RuntimeException('CSV saved; Drive upload config not found: ' . $configPath);
    }
    $config = json_decode(file_get_contents($configPath), true, 512, JSON_THROW_ON_ERROR);
    if (!preg_match('~^https://script\.google\.com/macros/s/[a-zA-Z0-9_-]+/exec$~', $config['url'] ?? '') || empty($config['secret'])) {
        throw new RuntimeException('Invalid Drive receiver configuration.');
    }
    $csv = file_get_contents($path);
    if ($csv === false) {
        throw new RuntimeException('Cannot read CSV: ' . $path);
    }
    $name = basename($path);
    $payload = json_encode(['name' => $name, 'csv' => $csv], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
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
    if ($status !== 200 || !is_array($uploaded) || ($uploaded['ok'] ?? false) !== true || empty($uploaded['id']) || ($uploaded['name'] ?? '') !== $name || ($uploaded['mimeType'] ?? '') !== 'text/csv' || ($uploaded['folder'] ?? '') !== 'ending_inventory') {
        throw new RuntimeException('CSV saved; Drive upload failed for ' . $name . ' (HTTP ' . $status . '). ' . $error . ' ' . (is_array($uploaded) ? ($uploaded['error'] ?? '') : ''));
    }
    return $uploaded;
}

$configDirectory = getenv('IMS_REPORT_DIR') ?: (PHP_OS_FAMILY === 'Windows'
    ? 'C:/xampp/IMS-private/IMS-daily-reports'
    : rtrim(getenv('HOME') ?: sys_get_temp_dir(), '/\\') . '/.local/share/ims-daily-reports');
if (isset($options['upload-only'])) {
    if (isset($options['local-only'])) {
        throw new InvalidArgumentException('--upload-only and --local-only cannot be combined.');
    }
    $retryDate = $options['upload-only'];
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $retryDate);
    if (!$date || $date->format('Y-m-d') !== $retryDate) {
        throw new InvalidArgumentException('Use --upload-only=YYYY-MM-DD');
    }
    $paths = glob(rtrim($directory, '/\\') . '/ending_inventory_' . $retryDate . '_*.csv');
    if (!$paths) {
        throw new RuntimeException('No saved ending inventory files for ' . $retryDate);
    }
    foreach ($paths as $path) {
        $uploaded = ending_upload($path, $configDirectory);
        echo json_encode(['path' => $path, 'drive_id' => $uploaded['id']], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    }
    exit(0);
}

$labels = [0 => 'Available', 2 => 'Enroute', 3 => 'For Enroute', 4 => 'Pending return to supplier'];
$capturedAt = new DateTimeImmutable('now');
$reportDate = $capturedAt->format('Y-m-d');
$staged = [];
$seen = [];
$total = 0;

try {
    // All warehouses come from one consistent database snapshot.
    $conn->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $conn->begin_transaction(MYSQLI_TRANS_START_READ_ONLY | MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);
    $warehouses = $conn->query('SELECT hashed_id, warehouse_name FROM warehouse ORDER BY warehouse_name, hashed_id')->fetch_all(MYSQLI_ASSOC);
    $orphan = $conn->query("SELECT COUNT(*) AS count FROM stocks s LEFT JOIN warehouse w ON w.hashed_id = s.warehouse WHERE s.item_status IN (0,2,3,4) AND w.hashed_id IS NULL")->fetch_assoc();
    if ((int) $orphan['count'] > 0) {
        throw new RuntimeException($orphan['count'] . ' eligible stock rows have no matching warehouse.');
    }
    $query = $conn->prepare('SELECT s.unique_barcode, s.item_status, p.description, b.brand_name, c.category_name
        FROM stocks s
        LEFT JOIN product p ON p.hashed_id = s.product_id
        LEFT JOIN brand b ON b.hashed_id = p.brand
        LEFT JOIN category c ON c.hashed_id = p.category
        WHERE s.warehouse = ? AND s.item_status IN (0,2,3,4)
        ORDER BY s.unique_barcode');

    foreach ($warehouses as $warehouse) {
        $warehouseId = $warehouse['hashed_id'];
        $warehouseName = $warehouse['warehouse_name'];
        $safeName = trim(preg_replace('/[^a-zA-Z0-9_-]+/', '_', $warehouseName), '_');
        $safeName = substr($safeName ?: 'warehouse', 0, 60);
        // The ID keeps filenames distinct even if warehouse names are identical.
        $filename = 'ending_inventory_' . $reportDate . '_' . $safeName . '_' . $warehouseId . '.csv';
        $temp = tempnam($directory, '.ending_inventory_');
        if ($temp === false) {
            throw new RuntimeException('Cannot create temporary CSV in ' . $directory);
        }
        $staged[] = ['temp' => $temp, 'path' => rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $filename, 'warehouse' => $warehouseName, 'rows' => 0];
        $index = count($staged) - 1;
        $file = fopen($temp, 'wb');
        if (!$file) {
            throw new RuntimeException('Cannot open temporary CSV.');
        }
        try {
            ending_csv_row($file, ['Barcode', 'Description', 'Brand', 'Category', 'Warehouse', 'Status', 'Snapshot Time (Asia/Manila)']);
            $query->bind_param('s', $warehouseId);
            $query->execute();
            $result = $query->get_result();
            while ($row = $result->fetch_assoc()) {
                $barcode = (string) ($row['unique_barcode'] ?? '');
                if (trim($barcode) === '') {
                    throw new RuntimeException('An eligible stock row has a blank barcode.');
                }
                // Refuse ambiguous data rather than assign a barcode to the wrong warehouse.
                if (isset($seen[$barcode])) {
                    throw new RuntimeException('Duplicate eligible barcode found: ' . $barcode);
                }
                $seen[$barcode] = true;
                ending_csv_row($file, [$barcode, $row['description'], $row['brand_name'], $row['category_name'], $warehouseName, $labels[(int) $row['item_status']], $capturedAt->format('Y-m-d H:i:s')]);
                ++$staged[$index]['rows'];
                ++$total;
            }
            $result->free();
        } finally {
            fclose($file);
        }
    }
    $query->close();
    $conn->commit();
    foreach ($staged as $report) {
        if (!rename($report['temp'], $report['path'])) {
            throw new RuntimeException('Cannot save CSV: ' . $report['path']);
        }
    }
    // Save the full snapshot first, so failed uploads can be retried unchanged.
    foreach ($staged as $report) {
        $result = ['warehouse' => $report['warehouse'], 'rows' => $report['rows'], 'path' => $report['path']];
        if (!isset($options['local-only'])) {
            $uploaded = ending_upload($report['path'], $configDirectory);
            $result['drive_id'] = $uploaded['id'];
        }
        echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
    }
    echo json_encode(['snapshot' => $capturedAt->format('Y-m-d H:i:s P'), 'warehouses' => count($staged), 'total_rows' => $total], JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $error) {
    $conn->rollback();
    foreach ($staged as $report) {
        if (is_file($report['temp'])) {
            unlink($report['temp']);
        }
    }
    throw $error;
}
