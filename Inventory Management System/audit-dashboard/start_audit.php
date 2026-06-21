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
$stocks_query = "SELECT parent_barcode, unique_barcode, capital, warehouse, item_location FROM stocks WHERE warehouse = ? AND item_status = 0";
$stmt = $conn->prepare($stocks_query);
$stmt->bind_param("s", $warehouse);
$stmt->execute();
$stocks_result = $stmt->get_result();

// Prepare inserts
$insert_items_to_audit = "
            INSERT IGNORE INTO items_to_audit
            (
                audit_id,
                unique_barcode,
                warehouse_origin,
                item_location_origin,
                audit_status
            )
            VALUES (?, ?, ?, ?, 'pending')
        ";
$stmt_items = $conn->prepare($insert_items_to_audit);

$total_expected_qty = 0;
$total_expected_amount = 0.00;
$audit_items_data = [];
while ($stock = $stocks_result->fetch_assoc()) {
    // Insert to items_to_audit
    $stmt_items->bind_param("issi", $audit_id, $stock['unique_barcode'], $stock['warehouse'], $stock['item_location']);
    $stmt_items->execute();

    $total_expected_qty += 1; // Assuming qty is 1 per unique_barcode
    $total_expected_amount += $stock['capital'];
    
}
$stmt_items->close();

// UPDATE AUDIT LOGS
$insert_audit_items = "UPDATE audit_logs SET total_expected_qty = ?, total_expected_amount = ? WHERE id = ? ";
$stmt_audit = $conn->prepare($insert_audit_items);
$stmt_audit->bind_param("idd", $total_expected_qty, $total_expected_amount, $audit_id);
$stmt_audit->execute();

$stmt_audit->close();

echo json_encode(['success' => true]);
$conn->close();
?>