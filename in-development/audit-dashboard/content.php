<?php
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

if ($schedule_date != $today) {
    echo "<div class='alert alert-warning'>Audit is scheduled for " . date('M d, Y', strtotime($audit['schedule_date'])) . ". You cannot start it today.</div>";
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
$assignments_query = "SELECT aa.*, u.user_fname, u.user_lname FROM audit_assignments aa LEFT JOIN users u ON aa.user_id = u.hashed_id COLLATE utf8mb4_unicode_ci WHERE aa.audit_id = ?";
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
?>
?>

<div class="card bg-primary text-white mb-3">
    <div class="card-body">
        <h5 class="card-title">Audit Dashboard</h5>
        <p class="card-text">Real-time monitoring for Audit #<?php echo $audit['audit_num']; ?> - <?php echo $audit['warehouse_name']; ?></p>
    </div>
</div>

<!-- Overview Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-primary"><?php echo number_format($totals['total_expected'] ?? 0, 2); ?></h5>
                <p class="card-text">Expected Qty</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-success"><?php echo number_format($totals['total_scanned'] ?? 0, 2); ?></h5>
                <p class="card-text">Scanned Qty</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-warning"><?php echo number_format($totals['total_variance_qty'] ?? 0, 2); ?></h5>
                <p class="card-text">Variance Qty</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-danger"><?php echo number_format($totals['total_variance_value'] ?? 0, 2); ?></h5>
                <p class="card-text">Variance Value</p>
            </div>
        </div>
    </div>
</div>

<!-- Progress Bar -->
<div class="card mb-4">
    <div class="card-body">
        <h6 class="card-title">Audit Progress</h6>
        <?php
        $expected = $totals['total_expected'] ?? 0;
        $scanned = $totals['total_scanned'] ?? 0;
        $progress = $expected > 0 ? ($scanned / $expected) * 100 : 0;
        ?>
        <div class="progress">
            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo number_format($progress, 1); ?>%</div>
        </div>
        <small class="text-muted">Scanned: <?php echo number_format($scanned, 2); ?> / Expected: <?php echo number_format($expected, 2); ?></small>
    </div>
</div>

<!-- Assignments Table -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">Audit Assignments</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Warehouse</th>
                        <th>Item Location</th>
                        <th>Status</th>
                        <th>Assigned At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                    <tr>
                        <td><?php echo $assignment['user_fname'] . ' ' . $assignment['user_lname']; ?></td>
                        <td><?php echo $assignment['warehouse']; ?></td>
                        <td><?php echo $assignment['item_location']; ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $assignment['status'] == 'pending' ? 'secondary' : 
                                     ($assignment['status'] == 'in_progress' ? 'primary' : 
                                     ($assignment['status'] == 'approved' ? 'success' : 'warning')); 
                            ?>"><?php echo ucfirst(str_replace('_', ' ', $assignment['status'])); ?></span>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($assignment['date_assigned'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Scans -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Recent Scans</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Barcode</th>
                        <th>Status</th>
                        <th>Belongs to Other</th>
                        <th>Scanned At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($scans as $scan): ?>
                    <tr>
                        <td><?php echo $scan['unique_barcode']; ?></td>
                        <td><span class="badge bg-<?php echo $scan['audit_status'] == 'scanned' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($scan['audit_status']); ?></span></td>
                        <td><?php echo $scan['belong_to_other_location'] == 'yes' ? 'Yes' : 'No'; ?></td>
                        <td><?php echo $scan['scanned_date'] ? date('M d, Y H:i', strtotime($scan['scanned_date'])) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Start Audit Modal -->
<div class="modal fade" id="startAuditModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Start Audit</h5>
            </div>
            <div class="modal-body text-center">
                <p>Are you ready to start the audit for <strong><?php echo $audit['warehouse_name']; ?></strong>?</p>
                <p>This will sync all available items and activate the audit.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='../audit-automation-module/'">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="startAudit()">Start Audit Now</button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Syncing audit data...</p>
            </div>
        </div>
    </div>
</div>

<script>
// For real-time updates (static for now, but structure ready)
function updateDashboard() {
    // Fetch updated data via AJAX and update elements
    // For now, static
}

function startAudit() {
    if (confirm('Are you sure you want to start the audit? This action cannot be undone.')) {
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();
        
        fetch('start_audit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ audit_id: <?php echo $audit_id; ?> })
        })
        .then(response => response.json())
        .then(data => {
            loadingModal.hide();
            if (data.success) {
                alert('Audit started successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            loadingModal.hide();
            alert('Error starting audit: ' + error);
        });
    }
}

setInterval(updateDashboard, 30000); // Update every 30 seconds
</script>