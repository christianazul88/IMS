<?php
include "../config/database.php";
include "../config/on_session.php";

$audit_id = $_SESSION['audit_id'];

// Fetch audit details
$audit_query = "SELECT al.*, w.warehouse_name FROM audit_logs al LEFT JOIN warehouse w ON al.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci WHERE al.id = ?";
$stmt = $conn->prepare($audit_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$audit = $stmt->get_result()->fetch_assoc();
$stmt->close();

$today = date('Y-m-d');
$schedule_date = date('Y-m-d', strtotime($audit['schedule_date']));

if ($today < $schedule_date) {
    echo "<div class='alert alert-warning'>
            Audit is scheduled for " . date('M d, Y', strtotime($audit['schedule_date'])) . ". You cannot start it today.
          </div>";
    exit;
}

if ($audit['audit_status'] == 'pending') {
    // Show start modal
    echo "<script>document.addEventListener('DOMContentLoaded', function() { 
        const startModal = new bootstrap.Modal(document.getElementById('startAuditModal'));
        startModal.show();
    });</script>";
} elseif ($audit['audit_status'] != 'active') {
    echo "<div class='alert alert-info'>Audit status: " . ucfirst($audit['audit_status']) . "</div>";
    exit;
}

// Rest of the code only if active

// Fetch totals from audit_items
$totals_query = "SELECT 
    SUM(expected_qty) as total_expected,
    SUM(scanned_qty) as total_scanned,
    SUM(variance_qty) as total_variance_qty,
    SUM(variance_value) as total_variance_value
FROM audit_items WHERE audit_id = ?";
$stmt = $conn->prepare($totals_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch assignments
$assignments_query = "SELECT aa.*, u.user_fname, u.user_lname, w.warehouse_name, il.location_name FROM audit_assignments aa LEFT JOIN users u ON aa.user_id = u.hashed_id COLLATE utf8mb4_unicode_ci LEFT JOIN warehouse w ON aa.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci LEFT JOIN item_location il ON aa.item_location = il.id  WHERE aa.audit_id = ?";
$stmt = $conn->prepare($assignments_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$assignments_result = $stmt->get_result();
$assignments = [];
while ($row = $assignments_result->fetch_assoc()) {
    $assignments[] = $row;
}
$stmt->close();

// Fetch recent scans (last 10)
$scans_query = "SELECT ita.*, ai.parent_barcode FROM items_to_audit ita LEFT JOIN audit_items ai ON ita.audit_id = ai.audit_id AND ita.unique_barcode = ai.parent_barcode WHERE ita.audit_id = ? ORDER BY ita.scanned_date DESC LIMIT 10";
$stmt = $conn->prepare($scans_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$scans_result = $stmt->get_result();
$scans = [];
while ($row = $scans_result->fetch_assoc()) {
    $scans[] = $row;
}
$stmt->close();

$insert_query = "INSERT INTO audit_logs_timestamps (audit_id, `status`, date_time) VALUES (?, 'end', NOW())";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$stmt->close();

header("Location: ../audit-dashboard/index");

?>