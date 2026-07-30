<?php

include "../config/database.php";
include "../config/on_session.php";

// Force CSV download
$filename = "outbounded_barcodes_" . date("Ymd_His") . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$audit_id = $_SESSION['audit_id'];

// --- Permission check (now using a prepared statement) ---
$audit_position_query = "SELECT audit_position FROM audit_users WHERE hashed_id = ? AND audit_id = ?";
$stmt = $conn->prepare($audit_position_query);
$stmt->bind_param("si", $user_id, $audit_id);
$stmt->execute();
$audit_position_result = $stmt->get_result();
$stmt->close();

if ($audit_position_result->num_rows === 0) {
    if ($user_email !== "vp_ronadanesito@laptoppcoutlet.com" && $user_email !== "administrator@admin.admin") {
        echo "<div class='alert alert-danger d-flex align-items-center gap-2'>
                <i class='bi bi-exclamation-triangle-fill'></i>
                You are not assigned to this audit.
              </div>";
        exit;
    }
}
$audit_position = $audit_position_result->fetch_assoc()['audit_position'] ?? null;

if ($user_email === "vp_ronadanesito@laptoppcoutlet.com") {
    $audit_position = 1;
}

// --- Fetch audit details ---
$audit_query = "SELECT al.*, w.warehouse_name FROM audit_logs al
                 LEFT JOIN warehouse w ON al.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci
                 WHERE al.id = ?";
$stmt = $conn->prepare($audit_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$audit = $stmt->get_result()->fetch_assoc();
$stmt->close();

$today = date('Y-m-d');
$schedule_date = date('Y-m-d', strtotime($audit['schedule_date']));

if ($today < $schedule_date) {
    echo "<div class='alert alert-warning d-flex align-items-center gap-2'>
            <i class='bi bi-clock-history'></i>
            Audit is scheduled for <strong>" . date('M d, Y', strtotime($audit['schedule_date'])) . "</strong>. You cannot start it today.
          </div>";
    exit;
}

if ($audit['audit_status'] == 'pending' && $audit_position == 1) {
    // Show start modal
    echo "<script>document.addEventListener('DOMContentLoaded', function() {
        const startModal = new bootstrap.Modal(document.getElementById('startAuditModal'));
        startModal.show();
    });</script>";
} elseif (
    $audit['audit_status'] !== 'active' &&
    $audit['audit_status'] !== 'partially_completed' &&
    $audit['audit_status'] !== 'completed'
) {
    echo "<div class='alert alert-info d-flex align-items-center gap-2'>
            <i class='bi bi-info-circle'></i>
            Audit status: <strong>" . ucfirst($audit['audit_status']) . "</strong>
          </div>";
    exit;
}

$audit_status = $audit['audit_status'];

$check_query = "SELECT * FROM audit_logs_timestamps WHERE audit_id = ? AND `status` = 'start' LIMIT 1";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$audit_log_timestamp = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$audit_log_timestamp) {
    $last_status = 'end';
} else {
    $audit_log_last_status_query = "SELECT * FROM audit_logs_timestamps WHERE audit_id = ? ORDER BY date_time DESC LIMIT 1";
    $stmt = $conn->prepare($audit_log_last_status_query);
    $stmt->bind_param("i", $audit_id);
    $stmt->execute();
    $last_status_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $last_status = $last_status_row['status'] ?? '';
}

$warehouse_id_audit = $audit['warehouse'];
$warehouse_name_audit = $audit['warehouse_name'];

// CSV Header
fputcsv($output, [
    "Audit ID",
    "Barcode",
    "Order Number",
    "Item Status"
]);

$query_pending_items = "
    SELECT 
        ita.audit_id,
        ita.unique_barcode, 
        s.item_status,
        ol.order_num
    FROM items_to_audit ita
    INNER JOIN stocks s 
        ON s.unique_barcode = ita.unique_barcode
    LEFT JOIN audit_logs al
        ON al.id = ita.audit_id
    LEFT JOIN outbound_content oc
        ON oc.unique_barcode = ita.unique_barcode
    LEFT JOIN outbound_logs ol
        ON ol.hashed_id = oc.hashed_id
    WHERE ita.audit_status = 'pending'
      AND al.audit_status != 'completed'
      AND s.item_status != 0
";

$result_pending_items = $conn->query($query_pending_items);

if ($result_pending_items->num_rows > 0) {

    while ($row = $result_pending_items->fetch_assoc()) {

        $query_audit_id    = $row['audit_id'];
        $barcode           = $row['unique_barcode'];
        $query_availability = $row['item_status'];
        $order_number      = $row['order_num'] ?? 'NA';

        if ($query_availability == 1 || $query_availability == 6) {

            // Save to CSV
            fputcsv($output, [
                $query_audit_id,
                $barcode,
                $order_number,
                $query_availability
            ]);

            // Update database
            $stmt = $conn->prepare("
                UPDATE items_to_audit
                SET audit_status = 'outbounded',
                    order_num = ?
                WHERE unique_barcode = ?
                  AND audit_status = 'pending'
            ");

            $stmt->bind_param("ss", $order_number, $barcode);
            $stmt->execute();
            $stmt->close();
        }
    }
}

fclose($output);
exit;