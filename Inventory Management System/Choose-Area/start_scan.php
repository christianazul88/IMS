<?php
include "../config/database.php";
include "../config/on_session.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['area'] ?? '') !== 'others') {
    header('Location: index.php');
    exit;
}

$audit_id = $_SESSION['audit_id'] ?? null;
$area_code = trim($_POST['area_code'] ?? '');
$rack = trim($_POST['rack'] ?? '');
$level = trim($_POST['level'] ?? '');
$box_num = trim($_POST['box_num'] ?? '');

if (!$audit_id || !$area_code || !$rack || !$level || !ctype_digit($box_num)) {
    $_SESSION['choose_area_error'] = 'Please select an area, rack, level, and a numeric box number.';
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare('SELECT warehouse FROM audit_logs WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $audit_id);
$stmt->execute();
$audit = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$audit) {
    $_SESSION['choose_area_error'] = 'The selected audit could not be found.';
    header('Location: index.php');
    exit;
}

$location_name = $area_code . '-' . $rack . '-' . $level . '-' . $box_num;

// Check the whole table: a location name can only be assigned once.
$stmt = $conn->prepare('SELECT id FROM item_location WHERE location_name = ? LIMIT 1');
$stmt->bind_param('s', $location_name);
$stmt->execute();
$existing_location = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing_location) {
    $_SESSION['choose_area_error'] = 'Location ' . $location_name . ' already exists. Please enter another number.';
    header('Location: index.php');
    exit;
}

$stmt = $conn->prepare('INSERT INTO item_location (location_name, warehouse) VALUES (?, ?)');
$stmt->bind_param('ss', $location_name, $audit['warehouse']);

if (!$stmt->execute()) {
    $_SESSION['choose_area_error'] = 'Unable to create the location. Please try again.';
    $stmt->close();
    header('Location: index.php');
    exit;
}

$item_location_id = $stmt->insert_id;
$stmt->close();

// Store the last successfully assigned numeric value. A refresh does not touch it.
$counter_file = __DIR__ . '/count.json';
$counter_fp = fopen($counter_file, 'c+');
if ($counter_fp && flock($counter_fp, LOCK_EX)) {
    rewind($counter_fp);
    $counter_data = json_decode(stream_get_contents($counter_fp), true);
    $last_assigned = (int) ($counter_data[0]['number'] ?? 0);
    $entered_number = (int) $box_num;

    if ($entered_number > $last_assigned) {
        rewind($counter_fp);
        ftruncate($counter_fp, 0);
        fwrite($counter_fp, json_encode([['number' => $entered_number]], JSON_PRETTY_PRINT));
        fflush($counter_fp);
    }
    flock($counter_fp, LOCK_UN);
}
if ($counter_fp) {
    fclose($counter_fp);
}

$_SESSION['selected_area'] = $item_location_id;
header('Location: ../Scan/index.php?area=' . urlencode((string) $item_location_id));
exit;