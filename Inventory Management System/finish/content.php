<?php
$audit_id = $_SESSION['audit_id'];
$selected_area = $_GET['area'];
$staff_id = $_GET['user_id'] ?? $user_id;
$audit_assignment_id = $_GET['aa'] ?? null;

// echo $staff_id;

$additional_query = "";
if(isset($_GET['fi'])){
    $aas_id = $_GET['fi'];
    $additional_query = "AND id = '$aas_id'";
}

$audit_position_query = "SELECT audit_position FROM audit_users WHERE hashed_id = '$user_id' AND audit_id = '$audit_id'";
$audit_position_result = $conn->query($audit_position_query);
$audit_position = $audit_position_result->fetch_assoc()['audit_position'] ?? null;

// =========================
// AUDIT DETAILS
// =========================
$audit_query = "SELECT al.*, w.warehouse_name 
                FROM audit_logs al 
                LEFT JOIN warehouse w 
                ON al.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci 
                WHERE al.id = ?";

$stmt = $conn->prepare($audit_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$audit = $stmt->get_result()->fetch_assoc();
$stmt->close();

$today = date('Y-m-d');
$schedule_date = date('Y-m-d', strtotime($audit['schedule_date']));

if ($today < $schedule_date) {
    echo "<div class='alert alert-warning'>Audit is scheduled for " . date('M d, Y', strtotime($audit['schedule_date'])) . "</div>";
    exit;
}

if ($audit['audit_status'] == 'pending') {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const startModal = new bootstrap.Modal(document.getElementById('startAuditModal'));
            startModal.show();
        });
    </script>";
} elseif ($audit['audit_status'] !== 'active' &&
    $audit['audit_status'] !== 'partially_completed' &&
    $audit['audit_status'] !== 'completed') {
    echo "<div class='alert alert-info'>Audit status: " . ucfirst($audit['audit_status']) . "</div>";
    exit;
}

// =========================
// ASSIGNMENT
// =========================
if($audit_assignment_id === null){
    $audit_assignment_query = "SELECT id, warehouse FROM audit_assignments WHERE item_location = ? AND audit_id = ? LIMIT 1";
    $stmt = $conn->prepare($audit_assignment_query);
    $stmt->bind_param("ii", $selected_area, $audit_id);
    $stmt->execute();
    $assignment_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $audit_assignment_id = $assignment_data['id'] ?? null;
}
$warehouse_audit_id = $assignment_data['warehouse'] ?? null;
// echo $audit_assignment_id;

// ==========================
// FETCH audit staff status
// ==========================
$status = 'idle';

$status_query = "
    SELECT `status`, user_id, id
    FROM audit_assignment_staffs
    WHERE audit_assignments_id = ?
      AND user_id = ?
      AND (`status` = 'for_approval' OR `status` = 'rejected')
      $additional_query
      ORDER BY id DESC
    LIMIT 1
";

$stmt = $conn->prepare($status_query);
$stmt->bind_param("is", $audit_assignment_id, $staff_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    // No row found → insert default row
    $status_query2 = "
        SELECT `status`, user_id, id
        FROM audit_assignment_staffs
        WHERE audit_assignments_id = ?
        AND user_id = ?
        $additional_query
        ORDER BY id DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($status_query2);
    $stmt->bind_param("is", $audit_assignment_id, $staff_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) {
        // No row found → insert default row
        $insert_query = "
            INSERT INTO audit_assignment_staffs (audit_assignments_id, user_id, status, date_assigned)
            VALUES (?, ?, 'idle', NOW())
        ";

        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("is", $audit_assignment_id, $staff_id);
        $stmt->execute();
        $stmt->close();

        if ($stmt->execute()) {
            $audit_assignment_staff_id = $conn->insert_id; // inserted row ID
        }

        $status = 'idle';
        $staff_id = $staff_id;
    } else {
        $status = $res['status'];
        $staff_id = $res['user_id'];
        $audit_assignment_staff_id = $res['id'];
    }
} else {
    $status = $res['status'];
    $staff_id = $res['user_id'];
    $audit_assignment_staff_id = $res['id'];
}

$status = $status;
// =========================
// FETCH SCANNED ITEMS
// =========================
$barcodes = [];

if($status === "for_approval"){
    $barcode_query = "
        SELECT 
            ia.unique_barcode AS barcode,
            p.description,
            b.brand_name,
            c.category_name,
            il.location_name,
            ia.outbounded
        FROM items_to_audit ia
        LEFT JOIN stocks s ON s.unique_barcode = ia.unique_barcode
        LEFT JOIN product p ON p.hashed_id = s.product_id
        LEFT JOIN brand b ON b.hashed_id = p.brand
        LEFT JOIN category c ON c.hashed_id = p.category
        LEFT JOIN item_location il ON il.id = s.item_location
        WHERE ia.audit_id = ?
        AND ia.audit_assignment_id = ?
        AND ia.user_id = ?
        AND ia.audit_status = 'scanned'
        ORDER BY ia.scanned_date ASC
    ";
} else {
    $barcode_query = "
        SELECT 
            ia.unique_barcode AS barcode,
            p.description,
            b.brand_name,
            c.category_name,
            il.location_name,
            ia.outbounded
        FROM items_to_audit ia
        LEFT JOIN stocks s ON s.unique_barcode = ia.unique_barcode
        LEFT JOIN product p ON p.hashed_id = s.product_id
        LEFT JOIN brand b ON b.hashed_id = p.brand
        LEFT JOIN category c ON c.hashed_id = p.category
        LEFT JOIN item_location il ON il.id = s.item_location
        WHERE ia.audit_id = ?
        AND ia.audit_assignment_id = ?
        AND ia.user_id = ?
        AND ia.audit_status IN ('approved','scanned')
        ORDER BY ia.scanned_date ASC
    ";
}

$stmt = $conn->prepare($barcode_query);
$stmt->bind_param("iis", $audit_id, $audit_assignment_id, $staff_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $barcodes[] = $row;
}
$stmt->close();

// =========================
// GROUP BARCODES
// =========================
$grouped = [];

foreach ($barcodes as $row) {

    $parts = explode('-', $row['barcode']);
    $parent = $parts[0];
    $sequence = $parts[1] ?? 0;

    $grouped[$parent][] = [
        'full' => $row['barcode'],
        'sequence' => $sequence,
        'description' => $row['description'],
        'brand_name' => $row['brand_name'],
        'category_name' => $row['category_name'],
        'location_name' => $row['location_name'],
        'outbounded' => $row['outbounded']
    ];
}

// =========================
// TOTALS
// =========================
$total_scanned = count($barcodes);

$_SESSION['total_scanned'] = $total_scanned;

// expected values
$total_expected_qty = (float)$audit['total_expected_qty'];
$total_expected_amount = (float)$audit['total_expected_amount'];

// scanned amount
$amount_query = "
    SELECT SUM(s.capital) AS total_amount
    FROM items_to_audit ia
    LEFT JOIN stocks s ON s.unique_barcode = ia.unique_barcode
    WHERE ia.audit_id = ?
      AND ia.audit_assignment_id = ?
      AND ia.user_id = ?
      AND ia.audit_status = 'scanned'
";
$stmt = $conn->prepare($amount_query);
$stmt->bind_param("iis", $audit_id, $audit_assignment_id, $staff_id);
$stmt->execute();
$total_scanned_amount = $stmt->get_result()->fetch_assoc()['total_amount'] ?? 0;
$stmt->close();

// outbounded qty
$out_qty_query = "
    SELECT COUNT(*) AS total
    FROM items_to_audit
    WHERE audit_id = ? AND outbounded = 'yes'
";
$stmt = $conn->prepare($out_qty_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$total_outbounded_qty = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// variance
$variance_qty = $total_scanned - $total_outbounded_qty - $total_expected_qty;
$variance_amount = $total_scanned_amount - $total_expected_amount;

// avg value
$avg_value = $total_scanned ? ($total_scanned_amount / $total_scanned) : 0;

if($audit_position != 1){
    $avg_value = 0;
    $total_scanned_amount = 0;
    $total_expected_amount = 0;
    $variance_amount = 0;   
}


// =========================
// OUTBOUNDED COUNT (FILTERED)
// =========================
$outbounded_filtered_query = "
    SELECT COUNT(*) AS total_outbounded
    FROM items_to_audit
    WHERE audit_id = ?
      AND audit_assignment_id = ?
      AND user_id = ?
      AND outbounded = 'yes'
      AND audit_status = 'scanned'
";

$stmt = $conn->prepare($outbounded_filtered_query);
$stmt->bind_param("iis", $audit_id, $audit_assignment_id, $staff_id);
$stmt->execute();
$total_outbounded_filtered = $stmt->get_result()->fetch_assoc()['total_outbounded'] ?? 0;
$stmt->close();

if($status === 'idle' || $status === 'in_progress' || $status === 'pending'){
    // =========================
    // UPDATE STATUS TO FOR APPROVAL
    // =========================
    $update_query = "
        UPDATE audit_assignment_staffs
        SET `status` = 'for_approval'
        WHERE audit_assignments_id = ?
        AND user_id = ?
        AND `status` != 'approved'
    ";

    $stmt = $conn->prepare($update_query);

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("is", $audit_assignment_id, $staff_id);

    if ($stmt->execute()) {
        $stmt->close();
        // header("Location: ../finish/?area=success=true");
        // exit;
    } else {
        $stmt->close();
        die("Update failed: " . $conn->error);
    }
}

// if($status === 'rejected' && $staff_id === $user_id){
//     // =========================
//     // UPDATE STATUS TO FOR APPROVAL
//     // =========================
//     $update_query = "
//         UPDATE audit_assignment_staffs
//         SET `status` = 'for_approval'
//         WHERE audit_assignments_id = ?
//         AND user_id = ?
//     ";

//     $stmt = $conn->prepare($update_query);

//     if (!$stmt) {
//         die("Prepare failed: " . $conn->error);
//     }

//     $stmt->bind_param("is", $audit_assignment_id, $user_id);

//     if ($stmt->execute()) {
//         $stmt->close();
//         // header("Location: ../finish/?area=success=true");
//         // exit;
//     } else {
//         $stmt->close();
//         die("Update failed: " . $conn->error);
//     }
// }


$missing_query = "SELECT p.description, b.brand_name, c.category_name, s.unique_barcode
                    FROM items_to_audit ia
                    LEFT JOIN stocks s ON s.unique_barcode = ia.unique_barcode
                    LEFT JOIN product p ON p.hashed_id = s.product_id
                    LEFT JOIN brand b ON b.hashed_id = p.brand
                    LEFT JOIN category c ON c.hashed_id = p.category
                    WHERE ia.audit_status = 'pending'
                    AND s.item_location = '$selected_area' 
                    AND ia.audit_id = '$audit_id'
                    AND ia.audit_status = 'scanned'";
$missing_result = $conn->query($missing_query);
$missing_items = [];

?>
<div class="container-fluid">

    <!-- HEADER -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center">

                <!-- LEFT: TITLE -->
                <div>
                    <h5 class="fw-bold mb-0">AUDIT RECEIPT DASHBOARD <?php// echo $audit_assignment_id; ?></h5>
                    <div class="text-muted small">
                        Warehouse: <?= htmlspecialchars($audit['warehouse_name'] ?? 'N/A') ?>
                    </div>
                    <div class="text-muted small">
                        Audit #: <?= $audit['audit_num'] ?>
                    </div>
                </div>

                <!-- RIGHT: ACTIONS -->
                <div class="text-end">

                    <a href="../audit-dashboard/" class="btn btn-outline-dark btn-sm mb-2">
                        ← Back to Dashboard <?php echo $audit_assignment_id;?>
                    </a>

                    <br>

                    <span class="badge bg-<?= $audit['audit_status'] === 'active' ? 'success' : 'secondary' ?> fs-6">
                        <?= strtoupper($audit['audit_status']) ?>
                    </span>

                </div>

            </div>

        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">

        <div class="card-body d-flex justify-content-between align-items-center">

            <div>
                <div class="fw-semibold">Assignment Status Control</div>
                <div class="text-muted small">
                    Current Status: 
                    <span class="fw-bold"><?= strtoupper($status) ?></span>
                </div>
            </div>

            <div class="text-end">
                <?php 
                // if($audit_position == 1 || $user_position_name === "Administrator" || $user_position_name === "Superadmin") {
                ?>
                <?php if ($status === 'for_approval' && $audit_position == 1): ?>

                    <!-- APPROVE / REJECT -->
                    <a href="approve.php?id=<?= $audit_assignment_id ?>&user=<?= $staff_id ?>&area=<?php echo $selected_area;?>"
                    class="btn btn-success btn-sm">
                        Approve
                    </a>

                    <a href="reject.php?id=<?= $audit_assignment_id ?>&user=<?= $staff_id ?>&area=<?php echo $selected_area;?>"
                    class="btn btn-danger btn-sm">
                        Decline
                    </a>

                    

                <?php elseif ($status === 'approved'): ?>

                    <!-- APPROVED (LOCKED) -->
                    <button class="btn btn-success btn-sm" disabled>
                        ✔ Approved & Closed
                    </button>

                    <a href="download_qrcode.php?audit_id=<?= $audit_id ?>&area=<?= $selected_area ?>&audit_assignment_id=<?= $audit_assignment_id ?>&user_id=<?= $staff_id ?>&fi=<?= $audit_assignment_staff_id; ?>"
                    class="btn btn-primary btn-sm">
                        <i class="bi bi-qr-code"></i> Download QR Code
                    </a>

                <?php elseif ($status === 'rejected'): ?>
                    <?php
                    // echo $user_id;
                    if($staff_id === $user_id) {
                    ?>
                    <!-- RESTART SCANNING -->
                    <a href="../Choose-Area/"
                    class="btn btn-warning btn-sm">
                        Restart Scanning
                    </a>

                    <?php
                    } else {
                        echo "<div class='text-muted'>Waiting for staff to restart the scanning process.</div>";
                    }
                    ?>

                <?php else: ?>


                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- ANALYTICS -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">

            <div class="row text-center g-2">

                <div class="col-6 col-md-3">
                    <div class="border rounded p-2">
                        <div class="text-muted small">SCANNED</div>
                        <div class="fw-bold fs-5"><?= $total_scanned ?></div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="border rounded p-2">
                        <div class="text-muted small">AMOUNT</div>
                        <div class="fw-bold text-success">
                            ₱ <?= number_format($total_scanned_amount, 2) ?>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="border rounded p-2">
                        <div class="text-muted small">OUTBOUNDED SCANNED</div>
                        <div class="fw-bold fs-5 text-danger">
                            <?= $total_outbounded_filtered ?>
                        </div>
                    </div>
                </div>

                <div class="col-3 col-md-3">
                    <div class="border rounded p-2">
                        <div class="text-muted small">VARIANCE QTY</div>
                        <div class="fw-bold <?= $variance_qty < 0 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format($variance_qty) ?>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <div class="border rounded p-2">
                        <div class="text-muted small">AVG VALUE</div>
                        <div class="fw-bold">
                            ₱ <?= number_format($avg_value, 2) ?>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3 mt-2">
                    <div class="border rounded p-2">
                        <div class="text-muted small">EXPECTED AMOUNT</div>
                        <div class="fw-bold">
                            ₱ <?= number_format($total_expected_amount, 2) ?>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-6 mt-2">
                    <div class="border rounded p-2">
                        <div class="text-muted small">AMOUNT VARIANCE</div>
                        <div class="fw-bold <?= $variance_amount < 0 ? 'text-danger' : 'text-success' ?>">
                            ₱ <?= number_format($variance_amount, 2) ?>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- GROUPED RECEIPT -->
    <div class="card shadow-sm border-0">

        <div class="card-header bg-white d-flex justify-content-between">
            <span class="fw-semibold">SCANNED ITEMS (GROUPED RECEIPT)</span>
            <span class="badge bg-dark"><?= $total_scanned ?> ITEMS</span>
        </div>

        <div class="card-body">

            <?php if (empty($grouped)): ?>

                <div class="text-center text-muted py-5">
                    No scanned items yet
                </div>

            <?php else: ?>

                <button class="btn btn-outline-dark btn-sm mb-3"
                        data-bs-toggle="collapse"
                        data-bs-target=".group-collapse">
                    Toggle All
                </button>

                <?php foreach ($grouped as $parent => $items): ?>

                    <?php $id = "g_" . $parent; ?>

                    <div class="border rounded mb-2">

                        <div class="p-2 bg-light d-flex justify-content-between">

                            <button class="btn btn-link p-0 fw-bold text-decoration-none"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#<?= $id ?>">
                                <?= $parent ?> (<?= count($items) ?>)
                            </button>

                            <small class="text-muted">Parent</small>

                        </div>

                        <div class="collapse group-collapse" id="<?= $id ?>">

                            <div class="p-2 font-monospace small">

                                <?php foreach ($items as $item): ?>

                                    <div class="border-bottom py-2">
                                        <?php 
                                        if($status !== 'approved' && $status !== 'rejected' && $audit_position == 1){
                                        ?>
                                        <!-- added button -->
                                        <div class="text-end mt-2 mb-3 fs-11">
                                            <a href="missing.php?barcode=<?= urlencode($item['full']) ?>"
                                            class="btn btn-outline-danger btn-sm mark-missing-btn fs-11">
                                                Mark as Missing
                                            </a>
                                        </div>
                                        <?php 
                                        }
                                        ?>

                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold"><?= $item['full'] ?></span>

                                            <?php if ($item['outbounded'] === 'yes'): ?>
                                                <span class="badge bg-danger">OUT</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">IN</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="text-muted">
                                            <?= htmlspecialchars($item['description'] ?? 'N/A') ?>
                                        </div>

                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>
                                                <?= $item['brand_name'] ?> / <?= $item['category_name'] ?>
                                            </span>
                                            <span>Seq <?= $item['sequence'] ?></span>
                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </div>


    <!-- MISSING ITEMS CAN ONLY BE SEEN BY MANAGERS AND ABOVE -->
    <?php if ($audit_position == 1 || $user_position_name === "Administrator" || $user_position_name === "Superadmin"): ?>
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white">
                <span class="fw-semibold">MISSING ITEMS</span>
                <span class="badge bg-danger"><?= $missing_result->num_rows ?> ITEMS</span>
            </div>

            <div class="card-body">

                <?php if ($missing_result->num_rows > 0): ?>

                    <?php while ($row = $missing_result->fetch_assoc()): ?>
                        <div class="row">
                            <div class="col-9">
                                <div class="border-bottom py-2">
                                    
                                    <div class="fw-bold">
                                        <?= htmlspecialchars($row['description'] ?? 'N/A') ?>
                                    </div>

                                    <div class="text-muted">
                                        <?= htmlspecialchars($row['brand_name'] ?? 'N/A') ?>
                                        /
                                        <?= htmlspecialchars($row['category_name'] ?? 'N/A') ?>
                                    </div>

                                    <div class="text-muted small">
                                        Barcode: <?= htmlspecialchars($row['unique_barcode'] ?? 'N/A') ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3">
                                <?php 
                                    if($status !== 'approved' && $status !== 'rejected' && $audit_position == 1){
                                ?>
                                <div class="text-end">
                                    <a class="btn btn-outline-primary fs-11 item-found-btn"
                                        href="item-found.php?barcode=<?= htmlspecialchars($row['unique_barcode'] ?? 'N/A') ?>&location=<?= $selected_area ?>&audit_assignment_id=<?= $audit_assignment_id ?>&staff_id=<?= $staff_id ?>&warehouse=<?= $warehouse_audit_id ?>">
                                        Item Found By Me
                                    </a>
                                </div>
                                <?php }?>
                            </div>
                        </div>
                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="text-center text-success py-3">
                        ✔ No missing items found. if the item location has just been added, this may be because the system has not yet generated the expected items for that location. 
                    </div>

                <?php endif; ?>

            </div>
        </div>
    <?php endif; ?>

</div>



<script>
document.addEventListener('click', async function(e) {

    const btn = e.target.closest('.item-found-btn');
    if (!btn) return;

    e.preventDefault();

    // First confirmation
    const firstConfirm = await Swal.fire({
        title: 'Approve this ticket?',
        text: 'If this ticket is later rejected, you may need to start the audit process again.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false
    });

    if (!firstConfirm.isConfirmed) {
        return;
    }

    // Second confirmation
    const secondConfirm = await Swal.fire({
        title: 'Are you really sure?',
        text: 'This action cannot be easily undone.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, I am sure',
        cancelButtonText: 'No'
    });

    if (!secondConfirm.isConfirmed) {
        return;
    }

    // Loading indicator
    Swal.fire({
        title: 'Processing...',
        text: 'Please wait.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {

        const response = await fetch(btn.href);
        const result = await response.text();

        Swal.fire({
            title: 'Server Response',
            html: result,
            icon: 'success'
        });

        // Optional: disable button after success
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
        btn.innerHTML = '✓ Item Found';
        btn.style.pointerEvents = 'none';

    } catch(error) {

        Swal.fire({
            title: 'Error',
            text: 'Failed to process request.',
            icon: 'error'
        });

        console.error(error);
    }

});

document.addEventListener('click', async function (e) {

    const btn = e.target.closest('.mark-missing-btn');
    if (!btn) return;

    e.preventDefault();

    // STEP 1
    const step1 = await Swal.fire({
        title: 'Mark this item as MISSING?',
        text: 'This will flag the item for audit discrepancy review.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, continue',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false
    });

    if (!step1.isConfirmed) return;

    // STEP 2
    const step2 = await Swal.fire({
        title: 'You cannot undo this',
        text: 'If you continue, this item will be treated as missing in the audit records.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'I understand',
        cancelButtonText: 'Go back'
    });

    if (!step2.isConfirmed) return;

    // STEP 3 (FINAL WARNING)
    const step3 = await Swal.fire({
        title: 'FINAL CONFIRMATION',
        html: `
            <b>This action is irreversible.</b><br><br>
            You cannot undo this, and you may need the developer to correct this later.<br><br>
            Are you absolutely sure you want to proceed?
        `,
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Yes, mark as missing',
        cancelButtonText: 'Cancel'
    });

    if (!step3.isConfirmed) return;

    // LOADING
    Swal.fire({
        title: 'Processing...',
        text: 'Updating audit records.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch(btn.href);
        const result = await response.text();

        Swal.fire({
            title: 'Completed',
            text: 'Item has been marked as missing.',
            icon: 'success'
        });

        // Optional UI update
        btn.classList.remove('btn-outline-danger');
        btn.classList.add('btn-secondary');
        btn.innerHTML = '✔ Missing';
        btn.style.pointerEvents = 'none';

    } catch (err) {
        Swal.fire({
            title: 'Error',
            text: 'Failed to mark item as missing.',
            icon: 'error'
        });

        console.error(err);
    }
});

</script>