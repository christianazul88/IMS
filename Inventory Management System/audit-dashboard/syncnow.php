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


$check_query = "SELECT * FROM audit_logs_timestamps WHERE audit_id = ? AND `status` = 'start' LIMIT 1";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();

$result = $stmt->get_result();
$audit_log_timestamp = $result->fetch_assoc();
$stmt->close();

if (!$audit_log_timestamp) {
    // $insert_query = "INSERT INTO audit_logs_timestamps (audit_id, `status`, date_time) VALUES (?, 'start', NOW())";
    // $stmt = $conn->prepare($insert_query);
    // $stmt->bind_param("i", $audit_id);
    // $stmt->execute();
    // $stmt->close();

    $last_status = 'end';
} else {
    // $audit_log_timestamp_id = $audit_log_timestamp['id'];
    $audit_log_last_status_query = "SELECT * FROM audit_logs_timestamps WHERE audit_id = ? ORDER BY date_time DESC LIMIT 1";
    $stmt = $conn->prepare($audit_log_last_status_query);
    $stmt->bind_param("i", $audit_id);
    $stmt->execute();
    $last_status = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $last_status = $last_status['status'] ?? '';
}

$warehouse_id_audit = $audit['warehouse'];



$location_query = "SELECT id FROM item_location WHERE warehouse = ?";
$stmt = $conn->prepare($location_query);
$stmt->bind_param("s", $warehouse_id_audit);
$stmt->execute();
$locations_result = $stmt->get_result();
while ($location = $locations_result->fetch_assoc()) {
    $location_id = $location['id'];
    $sub_expected_qty = 0;
    $sub_expected_amount = 0.00;


    $stocks_query = "SELECT capital FROM stocks WHERE warehouse = ? AND item_location = ? AND item_status = 0";
    $stmt = $conn->prepare($stocks_query);
    $stmt->bind_param("ss", $warehouse_id_audit, $location_id);
    $stmt->execute();
    $stocks_result = $stmt->get_result();
    while ($stock = $stocks_result->fetch_assoc()) {
        $sub_expected_amount += $stock['capital'];
        $sub_expected_qty += 1;
    }

    $insert_audit_assignments = "INSERT INTO audit_assignments (audit_id, warehouse, item_location, expected_qty, sub_total_expected_amount) VALUES (?, ?, ?, ?, ?)";
    $stmt_assignments = $conn->prepare($insert_audit_assignments);
    $stmt_assignments->bind_param("isiid", $audit_id, $warehouse_id_audit, $location_id, $sub_expected_qty, $sub_expected_amount);
    $stmt_assignments->execute();
    $stmt_assignments->close();

}

header("Location: ../audit-dashboard/");
