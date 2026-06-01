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


// =====================
// AUDIT SUMMARY VALUES
// =====================

// expected values (already from audit query, but kept safe)
$total_expected_qty = (float)$audit['total_expected_qty'];
$total_expected_amount = (float)$audit['total_expected_amount'];

// total scanned qty
$scanned_qty_query = "
    SELECT COUNT(*) AS total_scanned
    FROM items_to_audit
    WHERE audit_id = ? AND audit_status = 'scanned'
";
$stmt = $conn->prepare($scanned_qty_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$total_qty_scanned = $stmt->get_result()->fetch_assoc()['total_scanned'] ?? 0;
$stmt->close();


// total outbounded qty
$outbounded_qty_query = "
    SELECT COUNT(*) AS total_outbounded
    FROM items_to_audit
    WHERE audit_id = ? AND outbounded = 'yes' AND audit_status = 'scanned'
";
$stmt = $conn->prepare($outbounded_qty_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$total_outbounded_qty = $stmt->get_result()->fetch_assoc()['total_outbounded'] ?? 0;
$stmt->close();


// total scanned amount
$scanned_amount_query = "
    SELECT SUM(s.capital) AS total
    FROM items_to_audit ia
    LEFT JOIN stocks s ON s.unique_barcode = ia.unique_barcode
    WHERE ia.audit_id = ? AND ia.audit_status = 'scanned'
";
$stmt = $conn->prepare($scanned_amount_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$total_amount_scanned = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();


// total outbounded amount
$outbounded_amount_query = "
    SELECT SUM(s.capital) AS total
    FROM items_to_audit ia
    LEFT JOIN stocks s ON s.unique_barcode = ia.unique_barcode
    WHERE ia.audit_id = ? 
    AND ia.audit_status = 'scanned' 
    AND ia.outbounded = 'yes'
";
$stmt = $conn->prepare($outbounded_amount_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$total_amount_outbounded = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();


// variance
$variance_qty = $total_expected_qty - $total_qty_scanned - $total_outbounded_qty ;

?>
<style>
:root {
    --audit-primary: #4f46e5;
    --audit-success: #16a34a;
    --audit-warning: #f59e0b;
    --audit-danger: #ef4444;
    --audit-muted: #6b7280;
    --audit-bg: #f8fafc;
}

body {
    background: var(--audit-bg);
}

/* DASHBOARD HEADER */
.audit-header {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: #fff;
    border-radius: 12px;
    padding: 20px;
}

/* KPI CARDS */
.kpi-card {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.05);
    border-radius: 12px;
    padding: 16px;
    transition: 0.2s ease;
    height: 100%;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.kpi-title {
    font-size: 12px;
    color: var(--audit-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.kpi-value {
    font-size: 22px;
    font-weight: 700;
    margin-top: 5px;
}

/* COLOR ACCENTS */
.text-kpi-primary { color: var(--audit-primary); }
.text-kpi-success { color: var(--audit-success); }
.text-kpi-warning { color: var(--audit-warning); }
.text-kpi-danger { color: var(--audit-danger); }

/* TABLES */
.table-modern {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
}

.table-modern thead {
    background: #111827;
    color: #fff;
}

.table-modern tbody tr:hover {
    background: #f1f5f9;
}

/* SECTION CARDS */
.section-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.05);
    margin-top: 20px;
}

/* ACTION BUTTON BAR */
.action-bar {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.badge-soft {
    padding: 6px 10px;
    border-radius: 20px;
    font-weight: 500;
}
</style>

<div class="audit-header mb-3">
    <div class="row align-items-center">
        <div class="col-lg-6">
            <h4 class="mb-1 fw-bold">Audit Dashboard</h4>
            <div class="small opacity-75">
                Audit #<?= $audit['audit_num']; ?> • <?= $audit['warehouse_name']; ?>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="action-bar">

                <a href="../Choose-Area/"
                   class="btn btn-light btn-sm <?php if($last_status === 'pause' || $last_status === 'end') echo "d-none"; ?>">
                    Start Scanning
                </a>

                <?php if ($last_status === 'start' || $last_status === 'resume'): ?>

                    <a href="pause_audit.php" class="btn btn-warning btn-sm">Pause</a>
                    <a href="end.php" class="btn btn-danger btn-sm">End Audit</a>

                <?php elseif ($last_status === 'pause'): ?>

                    <a href="resume_audit.php" class="btn btn-success btn-sm">Resume</a>
                    <a href="end.php" class="btn btn-danger btn-sm">End Audit</a>

                <?php elseif ($last_status === 'end'): ?>

                    <span class="badge bg-dark badge-soft">Audit Ended</span>

                    <a href="generate_detailed_report.php?audit_id=<?php echo $audit_id; ?>"
                       class="btn btn-success btn-sm">
                        CSV Detail
                    </a>

                    <a href="generate_summary_report.php?audit_id=<?php echo $audit_id; ?>"
                       class="btn btn-primary btn-sm">
                        CSV Summary
                    </a>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<div class="section-card p-3">

    <h5 class="mb-3 fw-semibold">Audit Summary</h5>

    <div class="row g-3">

        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-title">Expected Qty</div>
                <div class="kpi-value"><?= number_format($total_expected_qty); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-title">Scanned Qty</div>
                <div class="kpi-value text-kpi-primary"><?= number_format($total_qty_scanned); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-title">Outbounded Qty</div>
                <div class="kpi-value text-kpi-warning"><?= number_format($total_outbounded_qty); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-title">Variance Qty</div>
                <div class="kpi-value <?= ($variance_qty < 0) ? 'text-kpi-danger' : 'text-kpi-success'; ?>">
                    <?= number_format($variance_qty); ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="kpi-card">
                <div class="kpi-title">Expected Amount</div>
                <div class="kpi-value">₱ <?= number_format($total_expected_amount, 2); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-title">Scanned Amount</div>
                <div class="kpi-value text-kpi-primary">₱ <?= number_format($total_amount_scanned, 2); ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-title">Outbounded Amount</div>
                <div class="kpi-value text-kpi-warning">₱ <?= number_format($total_amount_outbounded, 2); ?></div>
            </div>
        </div>

    </div>
</div>




<!-- for a position that can manage assignments on rackins -->
<div class="card mt-3 mb-3">
    <div class="card-body">
        <h5 class="card-title">Audit Assignments</h5>
        <div class="table-responsive">
            <table class="table table-hover table-modern align-middle">
                <thead>
                    <tr>
                        <th>Warehouse</th>
                        <th>Item Location</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $assignments_query = "
                    SELECT
                        w.warehouse_name,
                        il.location_name,
                        il.id AS location_id,
                        aa.status,
                        aa.expected_qty,
                        aa.sub_total_expected_amount,
                        aa.id
                    FROM audit_assignments aa
                    LEFT JOIN warehouse w ON aa.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci
                    LEFT JOIN item_location il ON aa.item_location = il.id
                    WHERE aa.audit_id = ?";
                    $stmt = $conn->prepare($assignments_query);
                    $stmt->bind_param("i", $audit_id);
                    $stmt->execute();
                    $assignments_result = $stmt->get_result();
                    if ($assignments_result->num_rows === 0) {
                        ?>
                        <tr><td colspan="3" class="text-center"><a href="syncnow.php" class="btn btn-secondary">Sync Now</a></td></tr>
                        <?php
                    } else {
                        while ($row = $assignments_result->fetch_assoc()) {
                            $random = chr(rand(65, 90)); // First character: A-Z

                            for ($i = 1; $i < 95; $i++) {
                                $random .= substr('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', rand(0, 61), 1);
                            }

                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['warehouse_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['location_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td>
                                    <a class="btn btn-primary fs-11" href="../audit-data/?hash=<?php echo $row['id'] . $random; ?>">View audit data</a>
                                    <button class="btn btn-info btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#assign-modal" data-target-id="<?php echo $row['id']; ?>">Assign Staffs</button>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Assignments</h5>
        <div class="table-responsive">
            <table class="table table-hover table-modern align-middle">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $audit_assignment_staffs_query = "
                        SELECT 
                            u.user_fname,
                            u.user_lname,
                            aas.user_id,
                            aas.status,
                            aas.audit_assignments_id,
                            il.id AS location_id,
                            il.location_name
                        FROM audit_assignment_staffs aas
                        LEFT JOIN users u
                            ON u.hashed_id = aas.user_id
                        LEFT JOIN audit_assignments aa
                            ON aa.id = aas.audit_assignments_id
                        LEFT JOIN item_location il
                            ON il.id = aa.item_location
                        WHERE aa.audit_id = ?
                        ORDER BY il.location_name, u.user_fname, u.user_lname
                    ";

                    $stmt = $conn->prepare($audit_assignment_staffs_query);
                    $stmt->bind_param("i", $audit_id);
                    $stmt->execute();

                    $audit_assignment_staffs_result = $stmt->get_result();

                    if ($audit_assignment_staffs_result->num_rows > 0) {

                        while ($staff = $audit_assignment_staffs_result->fetch_assoc()) {

                            $full_name = $staff['user_fname'] . ' ' . $staff['user_lname'];
                            ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($full_name); ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($staff['location_name']); ?>
                                    </small>
                                </td>

                                <td>
                                    <?php
                                    switch ($staff['status']) {

                                        case 'for_approval':
                                            echo '<span class="badge bg-warning text-dark badge-soft">For Approval</span>';
                                            break;

                                        case 'approved':
                                            echo '<span class="badge bg-success">Approved</span>';
                                            break;

                                        case 'declined':
                                            echo '<span class="badge bg-danger">Declined</span>';
                                            break;

                                        default:
                                            echo '<span class="badge bg-secondary">' .
                                                htmlspecialchars($staff['status']) .
                                                '</span>';
                                    }
                                    ?>
                                </td>

                                <td>
                                    <?php if ($staff['status'] !== 'idle' || $staff['status'] !== 'pending') : ?>

                                        <a href="../finish/?area=<?php echo $staff['location_id'];?>&user_id=<?php echo $staff['user_id'];?>"
                                        class="btn btn-success btn-sm">
                                            View
                                        </a>


                                    <?php else : ?>

                                        <span class="text-muted">No action required</span>

                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }

                    } else {
                        ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">
                                No staff assignments found.
                            </td>
                        </tr>
                        <?php
                    }

                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-3 mt-3">
    <div class="card-body">
        <h5 class="card-title">Recent Scans</h5>
        <div class="table-responsive">
            <table class="table table-hover table-modern align-middle">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Rack/Location</th>
                        <th>Warehouse</th>
                        <th>Outbounded?</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- RECENT SCANS -->
                    <?php
                    $recent_scans_query = "
                        SELECT  p.description,
                                b.brand_name,
                                c.category_name,
                                il.location_name,
                                w.warehouse_name,
                                ia.outbounded
                        FROM items_to_audit ia
                        LEFT JOIN stocks s ON s.unique_barcode = ia.unique_barcode
                        LEFT JOIN product p ON p.hashed_id = s.product_id
                        LEFT JOIN brand b ON b.hashed_id = p.brand
                        LEFT JOIN category c ON c.hashed_id = p.category
                        LEFT JOIN item_location il ON il.hashed_id = s.item_location
                        LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
                        WHERE ia.audit_status = 'scanned'
                        AND ia.audit_id = '$audit_id'
                        ORDER BY ia.scanned_date DESC
                    ";

                    $recent_scans_result = mysqli_query($conn, $recent_scans_query);

                    while ($row = mysqli_fetch_assoc($recent_scans_result)) {
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['brand_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['location_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['warehouse_name']); ?></td>
                        <td><?php echo $row['outbounded']; ?></td>
                    </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">Currently Missing/ Not Scanned Yet</h5>
        <div class="table-responsive">
            <table class="table table-hover table-modern align-middle">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Rack/Location</th>
                        <th>Warehouse</th>
                        <th>Outbounded?</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $missing_items_query = "
                        SELECT  p.description,
                                b.brand_name,
                                c.category_name,
                                il.location_name,
                                w.warehouse_name,
                                ia.outbounded
                        FROM items_to_audit ia
                        LEFT JOIN stocks s ON s.unique_barcode = ia.unique_barcode
                        LEFT JOIN product p ON p.hashed_id = s.product_id
                        LEFT JOIN brand b ON b.hashed_id = p.brand
                        LEFT JOIN category c ON c.hashed_id = p.category
                        LEFT JOIN item_location il ON il.hashed_id = s.item_location
                        LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
                        WHERE ia.audit_status = 'pending'
                        AND ia.audit_id = '$audit_id'
                        ORDER BY s.capital DESC
                    ";

                    $missing_items_result = mysqli_query($conn, $missing_items_query);

                    while ($row = mysqli_fetch_assoc($missing_items_result)) {
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['brand_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['location_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['warehouse_name']); ?></td>
                        <td><?php echo $row['outbounded']; ?></td>
                    </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- ASSIGNING STAFFS -->
 <div class="modal fade" id="assign-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
    <div class="modal-content position-relative">
    <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">Assign Staff</h5>
    </div>
    <form id="assign-staff-form" method="POST" action="assign_staff.php">
      <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
        <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-2">
        <input type="hidden" id="location_id" name="location_id" >
        <label for="organizerMultiple">Select Staffs for the selected item location</label>
        <select class="form-select js-choice bg-dark" id="organizerMultiple" multiple="multiple" size="10" name="staffs[]" data-options='{"removeItemButton":true,"placeholder":true,color: #212529 !important}'>
        <option value="">Select staffs</option>
        <?php
        $users_query = "
            SELECT user_fname, user_lname, hashed_id
            FROM users
            WHERE FIND_IN_SET(?, warehouse_access) > 0
        ";

        $stmt = $conn->prepare($users_query);
        $stmt->bind_param("s", $warehouse_id_audit);
        $stmt->execute();

        $users_result = $stmt->get_result();

        while ($user = $users_result->fetch_assoc()) {
            echo "<option style='color:#000000;' value='" . htmlspecialchars($user['hashed_id']) . "'>" .
            htmlspecialchars($user['user_fname'] . ' ' . $user['user_lname']) .
            "</option>";
        }
        ?>
        </select>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" type="submit">Submit </button>
      </div>
    </form>
    </div>
  </div>
</div>

<!-- !!!!!!!!!IMPORTAN!!!!!!! -->
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
document.addEventListener('DOMContentLoaded', function () {

    const assignModal = document.getElementById('assign-modal');

    assignModal.addEventListener('show.bs.modal', function (event) {

        // Button that triggered the modal
        const button = event.relatedTarget;

        // Get target ID
        const targetId = button.getAttribute('data-target-id');

        // Populate input
        document.getElementById('location_id').value = targetId;

        console.log('Selected ID:', targetId);
    });

});


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
