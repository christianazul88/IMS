<?php
include "../config/database.php";
include "../config/on_session.php";

$data = json_decode(file_get_contents('php://input'), true);
$audit_id = $data['audit_id'] ?? 0;

if (!$audit_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid audit ID']);
    exit;
}

// Update audit status to active
$update_audit = "UPDATE audit_logs SET audit_status = 'active', updated_at = NOW(), updated_by = ? WHERE id = ?";
$stmt = $conn->prepare($update_audit);
$stmt->bind_param("si", $user_id, $audit_id);
$stmt->execute();
$stmt->close();

// Insert timestamp
$insert_timestamp = "INSERT INTO audit_logs_timestamps (audit_id, date_time, status) VALUES (?, NOW(), 'start')";
$stmt = $conn->prepare($insert_timestamp);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$stmt->close();

// Get audit warehouse
$audit_query = "SELECT warehouse FROM audit_logs WHERE id = ?";
$stmt = $conn->prepare($audit_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$audit = $stmt->get_result()->fetch_assoc();
$warehouse = $audit['warehouse'];
$stmt->close();

// Get items from stocks
$stocks_query = "SELECT parent_barcode, unique_barcode, capital FROM stocks WHERE warehouse = ? AND item_status = 0";
$stmt = $conn->prepare($stocks_query);
$stmt->bind_param("s", $warehouse);
$stmt->execute();
$stocks_result = $stmt->get_result();

// Prepare inserts
$insert_items_to_audit = "INSERT INTO items_to_audit (audit_id, unique_barcode, audit_status) VALUES (?, ?, 'pending')";
$stmt_items = $conn->prepare($insert_items_to_audit);

$audit_items_data = [];
while ($stock = $stocks_result->fetch_assoc()) {
    // Insert to items_to_audit
    $stmt_items->bind_param("is", $audit_id, $stock['unique_barcode']);
    $stmt_items->execute();
    
    // Collect for audit_items
    $barcode = $stock['parent_barcode'];
    if (!isset($audit_items_data[$barcode])) {
        $audit_items_data[$barcode] = ['qty' => 0, 'cost' => $stock['capital']];
    }
    $audit_items_data[$barcode]['qty'] += 1; // Assuming qty is 1 per unique_barcode
}
$stmt_items->close();

// Insert to audit_items
$insert_audit_items = "INSERT INTO audit_items (audit_id, parent_barcode, expected_qty, unit_cost) VALUES (?, ?, ?, ?)";
$stmt_audit = $conn->prepare($insert_audit_items);
foreach ($audit_items_data as $barcode => $data) {
    $stmt_audit->bind_param("isdd", $audit_id, $barcode, $data['qty'], $data['cost']);
    $stmt_audit->execute();
}
$stmt_audit->close();

echo json_encode(['success' => true]);
$conn->close();
?>