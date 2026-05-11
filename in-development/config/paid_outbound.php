<?php
include('database.php');
include('on_session.php');

header('Content-Type: application/json');

// Get the ID from the request
if (!isset($_GET['name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

$id = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['name']);

// Update outbound_logs
$stmt1 = $conn->prepare("UPDATE outbound_logs SET status = 0, date_paid = ? WHERE hashed_id = ?");
$stmt1->bind_param("ss", $currentDateTime, $id);

if (!$stmt1->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to update outbound_logs: ' . $stmt1->error]);
    exit;
}

// Update outbound_content
$stmt2 = $conn->prepare("UPDATE outbound_content SET status = 0 WHERE hashed_id = ?");
$stmt2->bind_param("s", $id);

if (!$stmt2->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to update outbound_content: ' . $stmt2->error]);
    exit;
}

// Get barcodes
$stmt3 = $conn->prepare("SELECT unique_barcode FROM outbound_content WHERE hashed_id = ?");
$stmt3->bind_param("s", $id);
$stmt3->execute();
$result = $stmt3->get_result();

$action = "Was changed status to paid.";
$insertHist = $conn->prepare("INSERT INTO stock_timeline (unique_barcode, title, action, user_id, date) VALUES (?, 'Outbound Paid', ?, ?, ?)");

while ($row = $result->fetch_assoc()) {
    $barcode = $row['unique_barcode'];
    $insertHist->bind_param("ssss", $barcode, $action, $user_id, $currentDateTime);
    $insertHist->execute();
}

// Insert into logs
$log_action = 'Outbound #' . $id . ' has been successfully paid.';
$stmt4 = $conn->prepare("INSERT INTO logs (title, action, user_id, date) VALUES ('OUTBOUND PAID', ?, ?, ?)");
$stmt4->bind_param("sss", $log_action, $user_id, $currentDateTime);
$stmt4->execute();

echo json_encode(['success' => true, 'message' => 'Marked as paid']);

$conn->close();
?>
