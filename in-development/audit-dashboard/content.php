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

<div class="card mt-3 border-0 shadow-sm">
    <div class="card-body">
        <h5 class="card-title mb-3">Audit Summary</h5>

        <div class="row g-3">

            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <small class="text-muted">Expected Qty</small>
                    <h4 class="mb-0"><?php echo number_format($total_expected_qty); ?></h4>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <small class="text-muted">Scanned Qty</small>
                    <h4 class="mb-0 text-primary"><?php echo number_format($total_qty_scanned); ?></h4>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <small class="text-muted">Outbounded Qty</small>
                    <h4 class="mb-0 text-warning"><?php echo number_format($total_outbounded_qty); ?></h4>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <small class="text-muted">Variance Qty</small>
                    <h4 class="mb-0 <?php echo ($variance_qty < 0) ? 'text-danger' : 'text-success'; ?>">
                        <?php echo number_format($variance_qty); ?>
                    </h4>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 bg-light rounded">
                    <small class="text-muted">Total Expected Amount</small>
                    <h4 class="mb-0">₱ <?php echo number_format($total_expected_amount, 2); ?></h4>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <small class="text-muted">Scanned Amount</small>
                    <h4 class="mb-0 text-primary">₱ <?php echo number_format($total_amount_scanned, 2); ?></h4>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3 bg-light rounded">
                    <small class="text-muted">Outbounded Amount</small>
                    <h4 class="mb-0 text-warning">₱ <?php echo number_format($total_amount_outbounded, 2); ?></h4>
                </div>
            </div>

        </div>
    </div>
</div>




<!-- for a position that can manage assignments on rackins -->
<div class="card mt-3 mb-3">
    <div class="card-body">
        <h5 class="card-title">Audit Assignments</h5>
        <div class="table-responsive">
            <table class="table bordered-table">
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
                                    <a class="btn btn-primary fs-11" href="../view-staffs/?hash=<?php echo $row['id'] . $random; ?>">View assigned staffs</a>
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
            <table>
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
                                            echo '<span class="badge bg-warning">For Approval</span>';
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
            <table>
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
            </table>
        </div>
    </div>
</div>


<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">Currently Missing/ Not Scanned Yet</h5>
        <div class="table-responsive">
            <table>
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
            </table>
        </div>
    </div>
</div>


<!-- ASSIGNING STAFFS -->
 <div class="modal fade" id="assign-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
    <div class="modal-content position-relative">
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
