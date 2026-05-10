<?php
include "../config/database.php";
include "../config/on_session.php";

if (!isset($_GET['id'])) {
    die("Missing ID.");
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT filename, csv_file FROM csv_auditing WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    die("File not found or access denied.");
}

$stmt->bind_result($filename, $csvBlob);
$stmt->fetch();

header('Content-Description: File Transfer');
header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
echo $csvBlob;
exit;
?>
