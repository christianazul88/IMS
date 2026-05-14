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
    SUM(scanned_outbounded_qty) as total_scanned_outbounded_qty,
    SUM(scanned_belong_to_other_wh) as total_scanned_belong_to_other_wh,
    SUM(scanned_belong_to_other_location) as total_scanned_belong_to_other_location,
    SUM(variance_qty) as total_variance_qty,
    SUM(variance_value) as total_variance_value
FROM audit_items 
WHERE audit_id = ?";
$stmt = $conn->prepare($totals_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();
$stmt->close();

$variance_query = "SELECT 
    SUM(CASE WHEN variance_qty > 0 THEN variance_qty ELSE 0 END) as positive_variance,
    SUM(CASE WHEN variance_qty < 0 THEN ABS(variance_qty) ELSE 0 END) as negative_variance,
    SUM(CASE WHEN variance_qty = 0 THEN 1 ELSE 0 END) as zero_variance
FROM audit_items 
WHERE audit_id = ?";

$stmt = $conn->prepare($variance_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$variance = $stmt->get_result()->fetch_assoc();
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

$check_query = "SELECT * FROM audit_logs_timestamps WHERE audit_id = ? AND `status` = 'start' LIMIT 1";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();

$result = $stmt->get_result();
$audit_log_timestamp = $result->fetch_assoc();
$stmt->close();

if (!$audit_log_timestamp) {
    $insert_query = "INSERT INTO audit_logs_timestamps (audit_id, `status`, date_time) VALUES (?, 'start', NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("i", $audit_id);
    $stmt->execute();
    $stmt->close();

    $last_status = 'start';
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
?>

<div class="card bg-warning text-white mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-4">
                <h5 class="card-title">Audit Dashboard</h5>
                <p class="card-text">Real-time monitoring for Audit #<?php echo $audit['audit_num']; ?> - <?php echo $audit['warehouse_name']; ?></p>
            </div>
            <div class="col-8 text-end">
                <a href="../Choose-Area/" class="btn btn-light <?php if($last_status === 'pause' || $last_status === 'end') echo "d-none"; ?>">
                    Start Scanning
                </a>

                <?php if ($last_status === 'start' || $last_status === 'resume'): ?>

                    <a href="pause_audit.php" class="btn btn-warning">Pause</a>
                    <a href="end.php" class="btn btn-danger">End Audit</a>

                <?php elseif ($last_status === 'pause'): ?>

                    <a href="resume_audit.php" class="btn btn-success">Resume</a>
                    <a href="end.php" class="btn btn-danger">End Audit</a>

                <?php elseif ($last_status === 'end'): ?>

                    <span class="badge bg-secondary">Audit Ended</span>

                    <a href="generate_detailed_report.php?audit_id=<?php echo $audit_id; ?>"
                    class="btn btn-success">
                    Download Detailed CSV
                    </a>

                    <a href="generate_summary_report.php?audit_id=<?php echo $audit_id; ?>"
                    class="btn btn-primary">
                    Download Summary CSV
                    </a>

                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<?php
$warehouse_id_audit = $audit['warehouse'];
?>

<div class="row mb-4">

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Scan vs Expected Overview</div>
            <div class="card-body">
                <div style="height: 250px;">
                    <canvas id="scanChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Variance Distribution</div>
            <div class="card-body">
            <div style="height: 250px;">
                <canvas id="varianceChart"></canvas>
            </div>
        </div>
        </div>
    </div>

</div>

<!-- Overview Cards -->
<div class="row mb-4">

    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-primary">
                    <?php echo number_format($totals['total_expected'] ?? 0, 2); ?>
                </h5>
                <p class="card-text">Expected Qty</p>
            </div>
        </div>
    </div>

    <div class="col-md-4 mt-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-success">
                    <?php echo number_format($totals['total_scanned'] ?? 0, 2); ?>
                </h5>
                <p class="card-text">Scanned Qty</p>
            </div>
        </div>
    </div>

    <div class="col-md-4 mt-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-info">
                    <?php echo number_format($totals['total_scanned_outbounded_qty'] ?? 0, 2); ?>
                </h5>
                <p class="card-text">Outbounded</p>
            </div>
        </div>
    </div>

    <div class="col-md-2 mt-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-secondary">
                    <?php echo number_format($totals['total_scanned_belong_to_other_wh'] ?? 0, 2); ?>
                </h5>
                <p class="card-text">Other WH</p>
            </div>
        </div>
    </div>

    <div class="col-md-2 mt-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-dark">
                    <?php echo number_format($totals['total_scanned_belong_to_other_location'] ?? 0, 2); ?>
                </h5>
                <p class="card-text">Other Location</p>
            </div>
        </div>
    </div>

    <div class="col-md-4 mt-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-warning">
                    <?php echo number_format($totals['total_variance_qty'] ?? 0, 2); ?>
                </h5>
                <p class="card-text">Variance Qty</p>
            </div>
        </div>
    </div>

    <div class="col-md-4 mt-2">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title text-danger">
                    <?php echo number_format($totals['total_variance_value'] ?? 0, 2); ?>
                </h5>
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
        <div class="row">
            <div class="col-md-12 text-end mt-2">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">Assign Staff</button>
            </div>
        </div>
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
                <tbody id="assignmentsTableBody">
                <!-- <tbody>
                    <?php // foreach ($assignments as $assignment): ?>
                    <tr>
                        <td><?php // echo $assignment['user_fname'] . ' ' . $assignment['user_lname']; ?></td>
                        <td><?php //echo $assignment['warehouse_name']; ?></td>
                        <td>
                            <?php 
                            //if($assignment['status'] == 'pending' || $assignment['status'] == 'in_progress') {
                              //  echo $assignment['location_name'];
                            //} else {
                            ?>
                            <a href="../finish/?audit_id=<?php// echo $audit_id; ?>&area=<?php// echo $assignment['item_location'];?>"><?php echo $assignment['location_name']; ?></a>
                            <?php
                            //}
                            ?>
                            
                        </td>
                        <td>
                            <span class="badge bg-<?php 
                              //  echo $assignment['status'] == 'pending' ? 'secondary' : 
                                //     ($assignment['status'] == 'in_progress' ? 'primary' : 
                                  //   ($assignment['status'] == 'approved' ? 'success' : 'warning')); 
                            ?>"><?php// echo ucfirst(str_replace('_', ' ', $assignment['status'])); ?></span>
                        </td>
                        <td><?php //echo date('M d, Y H:i', strtotime($assignment['date_assigned'])); ?></td>
                    </tr>
                    <?php// endforeach; ?>
                </tbody> -->
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
                <tbody id="recentScansTableBody">
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


<!-- assign staff modal -->
<div class="modal fade" id="assignModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-sm border-0">

      <!-- ✅ Header -->
      <div class="modal-header bg-primary text-white">
        <div>
          <h5 class="modal-title mb-0">Assign Staff</h5>
          <small class="opacity-75">Assign staff to each warehouse location</small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- ✅ Body -->
      <div class="modal-body p-4">

        <!-- Instruction -->
        <div class="alert alert-info">
          Select a staff member for each location. All locations must be assigned before submitting.
        </div>

        <!-- <form id="assignStaffForm" action="assign_staff.php" method="post"> -->
        <form id="assignStaffForm" action="assign_staff.php" method="post">

            <div class="mb-3">
                <label class="form-label">Staff</label>
                <select name="staff_id" class="form-select" required>
                    <option value="">-- Select Staff --</option>

                    <?php
                    $users_Sql = "SELECT hashed_id, user_fname, user_lname
                                FROM users
                                WHERE FIND_IN_SET(?, warehouse_access)
                                AND status IN ('', 1)";

                    $stmt_users = $conn->prepare($users_Sql);
                    $stmt_users->bind_param("s", $warehouse_id_audit);
                    $stmt_users->execute();
                    $users_result = $stmt_users->get_result();

                    while ($staff = $users_result->fetch_assoc()) {
                        $staff_name = $staff['user_fname'] . ' ' . $staff['user_lname'];
                    ?>
                        <option value="<?php echo htmlspecialchars($staff['hashed_id']); ?>">
                            <?php echo htmlspecialchars($staff_name); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Item Location</label>
                <select name="location_id" id="locationSelect" class="form-select" required>
                    <option value="">-- Select Location --</option>

                    <?php
                    $locations_Sql = "SELECT id, location_name
                          FROM item_location
                          WHERE warehouse = ?";

                    $stmt_locations = $conn->prepare($locations_Sql);
                    $stmt_locations->bind_param("s", $warehouse_id_audit);
                    $stmt_locations->execute();
                    $locations_result = $stmt_locations->get_result();
                    while ($location = $locations_result->fetch_assoc()) {
                    ?>
                        <option value="<?php echo htmlspecialchars($location['id']); ?>">
                            <?php echo htmlspecialchars($location['location_name']); ?>
                        </option>
                    <?php } ?>

                    <!-- 🔥 IMPORTANT -->
                    <option value="other">+ Add New Location</option>
                </select>
            </div>

            <!-- 🔥 Optional new location -->
            <div class="mb-3">
                <label class="form-label">New Location</label>
                <input
                    type="text"
                    name="new_location"
                    id="newLocationInput"
                    class="form-control"
                    placeholder="Type new location"
                    disabled
                >
            </div>

            <button type="submit" class="btn btn-success">
                Save Assignment
            </button>

        </form>
      </div>

      <!-- ✅ Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          Cancel
        </button>
        <!-- <button type="button" class="btn btn-success px-4" onclick="assignStaff()">
          Assign Staff
        </button> -->
      </div>

    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const locationSelect = document.getElementById("locationSelect");
    const newLocationInput = document.getElementById("newLocationInput");

    locationSelect.addEventListener("change", function () {

        if (this.value === "other") {
            newLocationInput.disabled = false;
            newLocationInput.required = true;
            newLocationInput.focus();
        } else {
            newLocationInput.disabled = true;
            newLocationInput.required = false;
            newLocationInput.value = "";
        }

    });
});
</script>

<script>
function updateDashboard() {


    fetch('audit_dashboard_data.php?audit_id=<?php echo $audit_id; ?>')
        .then(res => res.json())
        .then(data => {


        // UPDATE SCAN CHART
        scanChart.data.datasets[0].data = [
            Number(data.totals.total_expected || 0),
            Number(data.totals.total_scanned || 0)
        ];
        scanChart.update();

        // UPDATE VARIANCE CHART (if you include variance in JSON)
        varianceChart.data.datasets[0].data = [
            Number(data.variance.positive_variance || 0),
            Number(data.variance.negative_variance || 0),
            Number(data.variance.zero_variance || 0)
        ];
        varianceChart.update();
            
        const t = data.totals;

        /* =========================
            UPDATE CARDS
        ========================= */

        document.querySelector(".card-title.text-primary").innerText =
            Number(t.total_expected || 0).toFixed(2);

        document.querySelector(".card-title.text-success").innerText =
            Number(t.total_scanned || 0).toFixed(2);

        document.querySelector(".card-title.text-info").innerText =
            Number(t.total_scanned_outbounded_qty || 0).toFixed(2);

        document.querySelector(".card-title.text-secondary").innerText =
            Number(t.total_scanned_belong_to_other_wh || 0).toFixed(2);

        document.querySelector(".card-title.text-dark").innerText =
            Number(t.total_scanned_belong_to_other_location || 0).toFixed(2);

        document.querySelector(".card-title.text-warning").innerText =
            Number(t.total_variance_qty || 0).toFixed(2);

        document.querySelector(".card-title.text-danger").innerText =
            Number(t.total_variance_value || 0).toFixed(2);

        /* =========================
            UPDATE PROGRESS BAR
        ========================= */

        let expected = Number(t.total_expected || 0);
        let scanned = Number(t.total_scanned || 0);
        let progress = expected > 0 ? (scanned / expected) * 100 : 0;

        const progressBar = document.querySelector(".progress-bar");

        progressBar.style.width = progress + "%";
        progressBar.innerText = progress.toFixed(1) + "%";
        progressBar.setAttribute("aria-valuenow", progress);

        /* =========================
            UPDATE ASSIGNMENTS TABLE
        ========================= */

        if (data.assignments) {

            let assignmentsHTML = '';

            data.assignments.forEach(assignment => {

                let badgeClass = 'secondary';

                if (assignment.status === 'in_progress') {
                    badgeClass = 'primary';
                } else if (assignment.status === 'approved') {
                    badgeClass = 'success';
                } else if (assignment.status === 'rejected') {
                    badgeClass = 'warning';
                }

                let locationHTML = '';

                if (
                    assignment.status === 'pending' ||
                    assignment.status === 'in_progress'
                ) {

                    locationHTML = assignment.location_name;

                } else {

                    locationHTML =
                        `<a href="../finish/?audit_id=<?php echo $audit_id; ?>&area=${assignment.item_location}">
                            ${assignment.location_name}
                        </a>`;
                }

                assignmentsHTML += `
                    <tr>
                        <td>
                            ${assignment.user_fname} ${assignment.user_lname}
                        </td>

                        <td>
                            ${assignment.warehouse_name}
                        </td>

                        <td>
                            ${locationHTML}
                        </td>

                        <td>
                            <span class="badge bg-${badgeClass}">
                                ${assignment.status.replace('_', ' ')}
                            </span>
                        </td>

                        <td>
                            ${formatDate(assignment.date_assigned)}
                        </td>
                    </tr>
                `;
            });

            document.getElementById('assignmentsTableBody').innerHTML =
                assignmentsHTML;
            }

            /* =========================
               UPDATE RECENT SCANS
            ========================= */

            if (data.recent_scans) {

                let scansHTML = '';

                data.recent_scans.forEach(scan => {

                    let badgeClass =
                        scan.audit_status === 'scanned'
                            ? 'success'
                            : 'secondary';

                    scansHTML += `
                        <tr>

                            <td>
                                ${scan.unique_barcode}
                            </td>

                            <td>
                                <span class="badge bg-${badgeClass}">
                                    ${capitalize(scan.audit_status)}
                                </span>
                            </td>

                            <td>
                                ${scan.belong_to_other_location === 'yes'
                                    ? 'Yes'
                                    : 'No'}
                            </td>

                            <td>
                                ${scan.scanned_date
                                    ? formatDate(scan.scanned_date)
                                    : '-'}
                            </td>

                        </tr>
                    `;
                });

                document.getElementById('recentScansTableBody').innerHTML =
                    scansHTML;
            }

        })
        .catch(err => {
            console.error("Dashboard update failed:", err);
        });


}

/* =========================
   HELPERS
========================= */

function capitalize(text) {

    if (!text) return '';

    return text.charAt(0).toUpperCase() + text.slice(1);
}

function formatDate(dateString) {

    const date = new Date(dateString);

    return date.toLocaleString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/* =========================
   AUTO REFRESH
========================= */

updateDashboard();




setInterval(updateDashboard, 10000);
</script>

<script>
function startAudit() {

    if (!confirm('Are you sure you want to start the audit?')) {
        return;
    }

    const loadingModal = new bootstrap.Modal(
        document.getElementById('loadingModal')
    );

    loadingModal.show();

    fetch('start_audit.php', {

        method: 'POST',

        headers: {
            'Content-Type': 'application/json'
        },

        body: JSON.stringify({
            audit_id: <?php echo $audit_id; ?>
        })

    })
    .then(response => response.json())
    .then(data => {

        loadingModal.hide();

        if (data.success) {

            alert('Audit started successfully.');

            location.reload();

        } else {

            alert(data.message || 'Failed to start audit.');

        }

    })
    .catch(error => {

        loadingModal.hide();

        console.error(error);

        alert('Error starting audit.');

    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const scanCtx = document.getElementById('scanChart').getContext('2d');

const scanChart = new Chart(scanCtx, {
    type: 'bar',
    data: {
        labels: ['Expected', 'Scanned'],
        datasets: [{
            label: 'Quantity',
            data: [
                <?php echo (float)($totals['total_expected'] ?? 0); ?>,
                <?php echo (float)($totals['total_scanned'] ?? 0); ?>
            ],
            backgroundColor: [
                '#0d6efd', // expected - blue
                '#198754'  // scanned - green
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>


<script>
const varianceCtx = document.getElementById('varianceChart').getContext('2d');

const varianceChart = new Chart(varianceCtx, {
    type: 'pie',
    data: {
        labels: ['Positive Variance', 'Negative Variance', 'No Variance'],
        datasets: [{
            data: [
                <?php echo (float)($variance['positive_variance'] ?? 0); ?>,
                <?php echo (float)($variance['negative_variance'] ?? 0); ?>,
                <?php echo (float)($variance['zero_variance'] ?? 0); ?>
            ],
            backgroundColor: [
                '#ffc107', // yellow
                '#dc3545', // red
                '#198754'  // green
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>


<!-- below are the databases samples:
audit_logs
id,audit_num,warehouse,audit_status,schedule_date,created_by,updated_by,date_created,updated_at,deleted_at,
1,1001,6f4b6612125fb3a0daecd2799dfd6c9c299424fd920f9b3081...,active,2026-05-11 00:00:00,6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c0...,6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c0...,2026-05-11 00:42:52,2026-05-11 00:42:59,NULL

audit_assignments
id,audit_id,user_id,warehouse,item_location,statusdate_assigned,approved_by,approved_at,remarks
1,1,6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c0...,6f4b6612125fb3a0daecd2799dfd6c9c299424fd920f9b3081...,259,approved,2026-05-11 00:43:09,NULL,NULL,NULL,


audit_assignment_logs
id, user_id, audit_assignment_id, date_time, status, remarks
1, hss, 1, 2026-05-11 00:43:28, start, null
2, hss, 1, 2026-05-11 01:15:42, pause, null
3, hss, 1, 2026-05-11 01:20:10, resume, null
4, hss, 1, 2026-05-11 02:00:00, pause, null
5, hss, 1, 2026-05-11 02:10:00, resume, null
6, hss, 1, 2026-05-11 03:00:00, end, null

audit_items
id,audit_id,parent_barcode,expected_qty,scanned_qty,variance_qty,scanned_outbounded_qt,variance_value,unit_cost,scanned_value,item_location,last_scanned_at,
1,1,6386029,3.00,3.00,0.00,0.00,0.00,55.00,165.00,NULL,NULL,

items_to_audit
id,audit_id,unique_barcode,audit_status,belong_to_other_location,belong_to_type,belong_to_value,scanned_date,
1,1,6386029-3,scanned,yes,item_location,2026-05-11 00:50:56
2,1,6386029-4,pending,yes,item_location,2026-05-11 00:51:09
3,1,6386029-5,scanned,yes,item_location,2026-05-11 00:51:13
4,1,6386029-2,scanned,NULL,NULL,NULL,2026-05-11 00:43:51


audit_logs_timestamps
id,audit_id,date_time,status
1,1,2026-05-11 00:42:52,start
2,1,2026-05-11 01:15:42,pause
3,1,2026-05-11 01:20:10,resume
4,1,2026-05-11 02:00:00,pause
5,1,2026-05-11 02:10:00,resume
6,1,2026-05-11 03:00:00,end -->