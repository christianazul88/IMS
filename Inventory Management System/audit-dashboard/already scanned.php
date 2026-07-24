<?php

include "../config/database.php";
include "../config/on_session.php";

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

// --- Pending items (now parameterized) ---
$pending_queries = "
    SELECT
        ita.unique_barcode,
        s.item_status,
        w.warehouse_name
    FROM items_to_audit ita
    LEFT JOIN stocks s
        ON s.unique_barcode = ita.unique_barcode
    LEFT JOIN warehouse w
        ON w.hashed_id = s.warehouse
    WHERE ita.audit_id = ?
    AND ita.audit_status = 'pending'
    AND ita.warehouse_origin = ?
";
$stmt = $conn->prepare($pending_queries);
$stmt->bind_param("ss", $audit_id, $warehouse_id_audit);
$stmt->execute();
$pending_result = $stmt->get_result();
$stmt->close();
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
        <div>
            <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($warehouse_name_audit ?? 'Unknown Warehouse') ?></h5>
            <small class="text-muted">Audit #<?= htmlspecialchars($audit_id) ?> &middot; Status:
                <span class="badge rounded-pill text-bg-<?=
                    match($audit_status) {
                        'active' => 'success',
                        'partially_completed' => 'warning',
                        'completed' => 'secondary',
                        default => 'light text-dark'
                    }
                ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $audit_status))) ?></span>
            </small>
        </div>
        <span class="text-muted small">
            <i class="bi bi-box-seam"></i> <?= $pending_result->num_rows ?> item<?= $pending_result->num_rows === 1 ? '' : 's' ?> pending
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Barcode</th>
                    <th>Warehouse (Database)</th>
                    <th>Product Availability</th>
                    <th>On Audit #</th>
                    <th>Scanned On Warehouse</th>
                    <th>On Audit Status</th>
                    <th>Scanned Date</th>
                </tr>
            </thead>
            <tbody>
<?php
if ($pending_result->num_rows > 0):
    while ($row = $pending_result->fetch_assoc()):
        $barcode = $row['unique_barcode'];
        $warehouse_recorded_on_database = $row['warehouse_name'];

        $availability = match((int)$row['item_status']) {
            0 => ['label' => 'Available', 'class' => 'success'],
            1, 6 => ['label' => 'Outbounded', 'class' => 'secondary'],
            3 => ['label' => 'For Enroute / About to be Delivered', 'class' => 'warning'],
            default => ['label' => 'Unknown', 'class' => 'light text-dark'],
        };

        $check_if_already_audited_query = "
            SELECT ita.audit_id, w.warehouse_name, ita.audit_status, ita.scanned_date
            FROM items_to_audit ita
            INNER JOIN warehouse w
                ON w.hashed_id = ita.warehouse_onscanned
            WHERE ita.unique_barcode = ?
            AND ita.audit_status != 'pending'
            LIMIT 1
        ";
        $stmt2 = $conn->prepare($check_if_already_audited_query);
        $stmt2->bind_param("s", $barcode);
        $stmt2->execute();
        $check_if_already_audited_res = $stmt2->get_result();
        $stmt2->close();

        if ($check_if_already_audited_res->num_rows > 0) {
            $audited_row = $check_if_already_audited_res->fetch_assoc();
            $on_audit = $audited_row['audit_id'];
            $on_warehouse = $audited_row['warehouse_name'];
            $on_audit_status = $audited_row['audit_status'];
            $scanned_date = $audited_row['scanned_date'] ? date('M d, Y g:i A', strtotime($audited_row['scanned_date'])) : '';
        } else {
            $on_audit = '';
            $on_warehouse = '';
            $on_audit_status = '';
            $scanned_date = '';
        }

        $status_badge_class = match($on_audit_status) {
            'completed' => 'success',
            'partially_completed' => 'warning',
            'active' => 'info',
            '' => 'light text-dark',
            default => 'secondary'
        };
?>
                <tr>
                    <td><code><?= htmlspecialchars($barcode) ?></code></td>
                    <td><?= htmlspecialchars($warehouse_recorded_on_database ?? '—') ?></td>
                    <td><span class="badge rounded-pill text-bg-<?= $availability['class'] ?>"><?= htmlspecialchars($availability['label']) ?></span></td>
                    <td><?= $on_audit !== '' ? htmlspecialchars($on_audit) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= $on_warehouse !== '' ? htmlspecialchars($on_warehouse) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= $on_audit_status !== ''
                            ? '<span class="badge rounded-pill text-bg-' . $status_badge_class . '">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $on_audit_status))) . '</span>'
                            : '<span class="text-muted">—</span>' ?></td>
                    <td><?= $scanned_date !== '' ? htmlspecialchars($scanned_date) : '<span class="text-muted">—</span>' ?></td>
                </tr>
<?php
    endwhile;
else:
?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-check2-circle fs-4 d-block mb-1"></i>
                        No pending items for this warehouse.
                    </td>
                </tr>
<?php endif; ?>
            </tbody>
        </table>
    </div>
</div>