<?php
$audit_id = $_SESSION['audit_id'];

$audit_position_query = "SELECT audit_position FROM audit_users WHERE hashed_id = '$user_id' AND audit_id = '$audit_id'";
$audit_position_result = $conn->query($audit_position_query);
if($audit_position_result->num_rows === 0){
    if($user_email !== "vp_ronadanesito@laptoppcoutlet.com" && $user_email !== "administrator@admin.admin"){
        echo "<div class='alert alert-danger'>You are not assigned to this audit.</div>";
        exit;
    }
}
$audit_position = $audit_position_result->fetch_assoc()['audit_position'] ?? null;

if($user_email === "vp_ronadanesito@laptoppcoutlet.com"){
    $audit_position = 1;
}
// Administrators and audit leads are the only users allowed to see monetary values.
if($user_email === "administrator@admin.admin"){
    $audit_position = 1;
}
$can_view_amounts = ($audit_position == 1);

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
    echo "<div class='alert alert-info'>Audit status: " . ucfirst($audit['audit_status']) . "</div>";
    exit;
}

$audit_status = $audit['audit_status'];

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


// AUDIT SUMMARY VALUES

$positive_variance_qty = 0;
$positive_variance_amount = 0;

$wrong_warehouse_qty = 0;
$wrong_warehouse_amount = 0;

$wrong_location_qty = 0;
$wrong_location_amount = 0;

$outbounded_variance_qty = 0;
$outbounded_variance_amount = 0;



$negative_variance_qty = 0;
$negative_variance_amount = 0;



$stmt_summary = "
    SELECT

    -- Expected Qty
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = $audit_id
        AND warehouse_origin = '$warehouse_id_audit'
        AND outbounded = 'no'
    ) AS total_expected_qty,

    -- Expected Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = $audit_id
        AND ita.warehouse_origin = '$warehouse_id_audit'
        AND ita.outbounded = 'no'
    ) AS total_expected_amount,

    -- Total Scanned
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = $audit_id
        AND audit_status IN ('scanned','approved')
    ) AS total_scanned,

    -- Total Scanned Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = $audit_id
        AND ita.audit_status IN ('scanned','approved')
    ) AS total_scanned_amount,

    -- Total Expected Scanned QTY
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = $audit_id
        AND audit_status IN ('scanned','approved')
        AND warehouse_origin = '$warehouse_id_audit'
        AND outbounded = 'no'
    ) AS total_expected_scanned_qty,

    -- Total Expected Scanned Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = $audit_id
        AND ita.warehouse_origin = '$warehouse_id_audit'
        AND ita.audit_status IN ('scanned','approved')
        AND ita.outbounded = 'no'
    ) AS total_expected_scanned_amount,


    -- Missing Qty
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = $audit_id
        AND audit_status = 'pending'
        AND warehouse_origin = '$warehouse_id_audit'
        AND outbounded = 'no'
    ) AS total_missing_qty,

    -- Missing Expected amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = $audit_id
        AND ita.audit_status = 'pending'
        AND ita.warehouse_origin = '$warehouse_id_audit'
        AND ita.outbounded = 'no'
    ) AS total_missing_amount,


    -- Positive Variance Outbounded
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = $audit_id
        AND audit_status IN ('scanned','approved')
        AND warehouse_origin = '$warehouse_id_audit'
        AND outbounded != 'no'
    ) AS total_scanned_outbounded_as_positive_variance_qty,

    -- Positive Variance Outbounded Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = $audit_id
        AND ita.audit_status IN ('scanned','approved')
        AND ita.warehouse_origin = '$warehouse_id_audit'
        AND ita.outbounded != 'no'
    ) AS total_scanned_outbounded_as_positive_variance_amount,

    -- Positive Variance Wrong Warehouse
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = $audit_id
        AND audit_status IN ('scanned','approved')
        AND warehouse_origin != '$warehouse_id_audit'
    ) AS total_scanned_wrong_warehouse_as_positive_variance_qty,

    -- Positive Variace Wrong Warehouse Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = $audit_id
        AND ita.audit_status IN ('scanned','approved')
        AND ita.warehouse_origin != '$warehouse_id_audit'
    ) AS total_scanned_wrong_warehouse_as_positive_variance_amount,

    -- Outbounded qty
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = $audit_id
        AND audit_status = 'outbounded'
        AND warehouse_origin = '$warehouse_id_audit'
    ) AS total_missing_outbounded_qty,

    -- Outbounded amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s 
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = $audit_id
        AND ita.audit_status = 'outbounded'
        AND ita.warehouse_origin = '$warehouse_id_audit'
    ) AS total_missing_outbounded_amount
";

$stmt_summary_result = $conn->prepare($stmt_summary);

if (!$stmt_summary_result) {
    die("Prepare failed: " . $conn->error);
}

$stmt_summary_result->execute();

$result = $stmt_summary_result->get_result();
$row = $result->fetch_assoc();

// Expected
$total_expected_qty = (int)$row['total_expected_qty'];
$total_expected_amount = (float)($row['total_expected_amount'] ?? 0);

// Scanned
$total_qty_scanned = (int)$row['total_scanned'];
$total_scanned_amount = (float)($row['total_scanned_amount'] ?? 0);

// Expected Scanned
$total_expected_scanned_qty = (int)$row['total_expected_scanned_qty'];
$total_expected_scanned_amount = (float)($row['total_expected_scanned_amount'] ?? 0);

// Missing
$negative_variance_qty = (int)$row['total_missing_qty'];
$negative_variance_amount = (float)($row['total_missing_amount'] ?? 0);

// Positive Variance - Outbounded
$total_scanned_outbounded_as_positive_variance_qty = (int)$row['total_scanned_outbounded_as_positive_variance_qty'];
$total_scanned_outbounded_as_positive_variance_amount = (float)($row['total_scanned_outbounded_as_positive_variance_amount'] ?? 0);

// Positive Variance - Wrong Warehouse
$wrong_warehouse_qty = $row['total_scanned_wrong_warehouse_as_positive_variance_qty'];
$total_scanned_wrong_warehouse_as_positive_variance_amount = (float)($row['total_scanned_wrong_warehouse_as_positive_variance_amount'] ?? 0);

//missing that was outbounded
$outbounded_qty_missing = $row['total_missing_outbounded_qty'];
$outbounded_amount_missing = $row['total_missing_outbounded_amount'];

$stmt_summary_result->close();

// Expected items found in a different warehouse are a resolved disposition,
// but are intentionally kept separate from "verified in correct warehouse".
$audited_on_other_wh_qty = 0;
$audited_on_other_wh_amount = 0;
$stmt_other_warehouse = $conn->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(s.capital), 0) AS total_amount FROM items_to_audit ita LEFT JOIN stocks s ON s.unique_barcode = ita.unique_barcode WHERE ita.audit_id = ? AND ita.audit_status = 'scanned_on_other' AND ita.warehouse_origin = ? AND ita.outbounded = 'no'");
$stmt_other_warehouse->bind_param("is", $audit_id, $warehouse_id_audit);
$stmt_other_warehouse->execute();
$other_warehouse_summary = $stmt_other_warehouse->get_result()->fetch_assoc();
$audited_on_other_wh_qty = (int)($other_warehouse_summary['total'] ?? 0);
$audited_on_other_wh_amount = (float)($other_warehouse_summary['total_amount'] ?? 0);
$stmt_other_warehouse->close();

$outbounded_variance_qty = $total_scanned_outbounded_as_positive_variance_qty;
$positive_variance_qty = $wrong_warehouse_qty + $total_scanned_outbounded_as_positive_variance_qty;
$positive_variance_amount = $total_scanned_wrong_warehouse_as_positive_variance_amount + $total_scanned_outbounded_as_positive_variance_amount;

$net_variance_qty =
    $positive_variance_qty -
    $negative_variance_qty;

$net_variance_amount =
    $positive_variance_amount -
    $negative_variance_amount;

$expected_items_resolved_qty = $total_expected_scanned_qty + $outbounded_qty_missing + $audited_on_other_wh_qty;
$expected_items_resolved_amount = $total_expected_scanned_amount + $outbounded_amount_missing + $audited_on_other_wh_amount;

$audit_progress = 
    $total_expected_qty > 0
    ? ($expected_items_resolved_qty / $total_expected_qty) * 100
    : 0;



$variance_amount = $negative_variance_amount;

if($audit_position != 1){
    $variance_amount = 0;
    $net_variance_amount = 0;   
}

$audit_progress = max(0, min(100, $audit_progress));
$attention_count = $negative_variance_qty + $wrong_warehouse_qty + $total_scanned_outbounded_as_positive_variance_qty;
$audit_status_label = ucwords(str_replace('_', ' ', $audit_status));

// ==================================================================================================
// == UPDATE THE CURRENT AUDIT TO "COMPLETED" IF THERE WERE NO LONGER PENDING/MISSING ON THIS AUDIT==
// ==================================================================================================
$check_scanned_query = "
    SELECT id
    FROM items_to_audit
    WHERE audit_id = ?
      AND audit_status IN ('pending','scanned')
";

$stmt = $conn->prepare($check_scanned_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {

    $update_query = "
        UPDATE audit_logs
        SET audit_status = 'completed'
        WHERE id = ?
    ";

    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $audit_id);
    $update_stmt->execute();
    $update_stmt->close();
}

$stmt->close();
// ===========================================================================================================
// == END OF UPDATING THE CURRENT AUDIT TO "COMPLETED" IF THERE WERE NO LONGER PENDING/MISSING ON THIS AUDIT==
// ===========================================================================================================
?>


<style>
    .audit-hero { background: linear-gradient(135deg, #0b2545, #174a7c); color: #fff; border-radius: 16px; padding: 1.25rem 1.5rem; }
    .audit-hero .opacity-75 { color: rgba(255,255,255,.76) !important; }
    .metric-card { height: 100%; border-left: 4px solid var(--metric-color, #2c7be5) !important; }
    .metric-card .metric-value { font-size: 1.55rem; line-height: 1.15; font-weight: 700; color: #12263f; }
    .metric-card .metric-meta { min-height: 1.1rem; font-size: .78rem; color: #6e7891; }
    .attention-item { border-left: 3px solid #f5803e; padding-left: .75rem; }
    @media (min-width: 1200px) { .executive-kpis > .col-md-3 { width: 20%; } }

    /* Reconciliation card - composition bar, legend, and stepper */
    .bg-purple { background-color: #9b59b6 !important; }
    .bg-teal { background-color: #39afd1 !important; }
    .text-purple { color: #9b59b6 !important; }
    .text-teal { color: #39afd1 !important; }
    .bg-purple-subtle { background-color: rgba(155,89,182,.12) !important; }
    .bg-teal-subtle { background-color: rgba(57,175,209,.12) !important; }
    .bg-primary-subtle { background-color: rgba(44,123,229,.12) !important; }
    .bg-warning-subtle { background-color: rgba(245,128,62,.12) !important; }
    .bg-info-subtle { background-color: rgba(57,175,209,.12) !important; }
    .bg-danger-subtle { background-color: rgba(231,76,60,.12) !important; }
    .bg-success-subtle { background-color: rgba(0,167,111,.12) !important; }

    .recon-bar { display: flex; width: 100%; height: 14px; border-radius: 8px; overflow: hidden; background: #eef1f5; }
    .recon-bar-seg { height: 100%; transition: width .6s ease; }
    .legend-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }

    .recon-step { display: flex; align-items: flex-start; gap: .75rem; padding: .4rem 0; position: relative; }
    .recon-step:not(:last-child)::before {
        content: ''; position: absolute; left: 15px; top: 34px; bottom: -6px; width: 2px; background: #e9ecef;
    }
    .recon-icon {
        flex: 0 0 auto; width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-size: .8rem; z-index: 1;
    }
    .recon-step-result { opacity: .92; }
    .recon-step-final .recon-step-body { padding: .5rem .75rem; background: #f8fffb; border-radius: 8px; }
</style>

<div class="audit-header audit-hero mb-3">
    <div class="row align-items-center">
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="mb-0 fw-bold">Audit Command Center</h4>
                <span class="badge <?= $audit_status === 'completed' ? 'bg-success' : ($audit_status === 'active' ? 'bg-info' : 'bg-warning text-dark') ?>"><?= htmlspecialchars($audit_status_label) ?></span>
            </div>
            <div class="small opacity-75">
                Audit #<?= $audit['audit_num']; ?> • <?= $audit['warehouse_name']; ?>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="action-bar">

                <a href="../Choose-Area/"
                   class="btn btn-light btn-sm mb-2 <?php if( $audit_status === "completed"){ echo "d-none";}?> <?php if($last_status === 'pause' || $last_status === 'end') echo "d-none"; ?>">
                    Start Scanning barcode
                </a>
                <a href="../Scan-Area/"
                   class="btn btn-secondary btn-sm mb-2 <?php if( $audit_status === "completed"){ echo "d-none";}?> <?php if($last_status === 'pause' ) echo "d-none"; ?>">
                    Scan Barcode Area
                </a>

                <a href="../Outbound-form/?audit_id=<?php echo $audit_id; ?>&warehouse=<?php echo $warehouse_id_audit; ?>"
                    class="btn btn-info btn-sm mb-2 <?php if( $audit_status === "completed"){ echo "d-none";}?>">
                    Outbound items
                </a>
                <?php 
                if($audit_position == 1) {
                    if($audit_status !== "completed" && $last_status === 'start' || $last_status === 'resume'){ ?>
                    <a href="../audit-upload/" class="btn btn-outline-secondary fs-11 mb-2">CSV import</a>
                    <a href="area_codes.php" class="btn btn-info btn-sm mb-2 fs-11 <?php if( $audit_status === "completed"){ echo "d-none";}?>"><span class="fas fa-download"></span> Area Code</a>
                    <a href="pause_audit.php" class="btn btn-warning btn-sm mb-2 fs-11 <?php if( $audit_status === "completed"){ echo "d-none";}?>">Pause</a>
                    <a href="end.php" class="btn btn-danger btn-sm mb-2 fs-11 <?php if( $audit_status === "completed"){ echo "d-none";}?>">End Audit</a>

                <?php } elseif($audit_status !== "completed" && $last_status === 'pause' && $audit_position == 1){ ?>

                    <a href="resume_audit.php" class="btn btn-success btn-sm">Resume</a>
                    <a href="end.php" class="btn btn-danger btn-sm mb-2">End Audit</a>

                <?php } elseif ($audit_status !== "completed" && $last_status === 'end' && $audit_position == 1){ ?>

                    <a href="../Variance-look/?audit_id=<?php echo $audit_id; ?>"
                        class="btn btn-info btn-sm mb-2">
                        Find Variance
                    </a>

                    <a href="../Outbound-form/?audit_id=<?php echo $audit_id; ?>&warehouse=<?php echo $warehouse_id_audit; ?>"
                        class="btn btn-info btn-sm mb-2">
                        Outbound items
                    </a>

                    <a href="generate_missing.php?audit_id=<?php echo $audit_id; ?>"
                       class="btn btn-danger btn-sm mb-2">
                        Missing CSV
                    </a>

                    <a href="generate_scanned_onotherwh.php?audit_id=<?php echo $audit_id; ?>"
                       class="btn btn-danger btn-sm mb-2">
                        Scanned on other audit
                    </a>

                    <a href="generate_detailed_report.php?audit_id=<?php echo $audit_id; ?>"
                       class="btn btn-success btn-sm mb-2">
                        Scanned CSV
                    </a>

                    <a href="generate_summary_report.php?audit_id=<?php echo $audit_id; ?>"
                       class="btn btn-primary btn-sm mb-2">
                        CSV Summary
                    </a>

                    <a href="complete.php" class="btn btn-danger btn-sm mb-2">Complete Audit</a>

                    


                <?php 
                    } elseif($audit_status === "completed") {
                    ?>
                    <a href="generate_outbounded.php?audit_id=<?php echo $audit_id; ?>"
                        class="btn btn-warning btn-sm mb-2">
                    Outbounded CSV
                    </a>
                    <a href="generate_missing.php?audit_id=<?php echo $audit_id; ?>"
                       class="btn btn-danger btn-sm mb-2">
                        Missing CSV
                    </a>

                    <a href="generate_scanned_onotherwh.php?audit_id=<?php echo $audit_id; ?>"
                       class="btn btn-danger btn-sm mb-2">
                        Scanned on other audit
                    </a>
                    
                    <a href="generate_detailed_report.php?audit_id=<?php echo $audit_id; ?>"
                       class="btn btn-success btn-sm mb-2">
                        Scanned CSV
                    </a>

                    <a href="generate_summary_report.php?audit_id=<?php echo $audit_id; ?>"
                       class="btn btn-primary btn-sm mb-2">
                        CSV Summary
                    </a>

                    
                    <?php
                    }
                }
                ?>

            </div>
        </div>
    </div>
</div>

<?php if (!$can_view_amounts): ?>
    <div class="alert alert-light border d-flex align-items-center gap-2 py-2 mb-3" role="status">
        <span class="fas fa-lock text-secondary"></span>
        <small><strong>Quantity-only view.</strong> Monetary amounts are restricted to audit leads, administrators, and the VP account.</small>
    </div>
<?php endif; ?>

<?php
// ==================================================================
// Single source of truth for the reconciliation bridge + health check
// (previously duplicated/inconsistent between this file and the AJAX
// endpoint - now computed once and reused by every card below)
// ==================================================================
$reconciled_scanned_qty   = $total_qty_scanned - $positive_variance_qty;
$reconciled_scanned_amount = $total_scanned_amount - $positive_variance_amount;

$reconciled_total_qty    = $reconciled_scanned_qty + $negative_variance_qty + $outbounded_qty_missing + $audited_on_other_wh_qty;
$reconciled_total_amount = $reconciled_scanned_amount + $negative_variance_amount + $outbounded_amount_missing + $audited_on_other_wh_amount;

$qty_balance_diff    = $reconciled_total_qty - $total_expected_qty;
$amount_balance_diff = $reconciled_total_amount - $total_expected_amount;
$is_balanced         = ($qty_balance_diff == 0) && (!$can_view_amounts || $amount_balance_diff == 0);

// Composition of "Expected Inventory" as percentages, for the visual breakdown bar
$pct_verified   = $total_expected_qty > 0 ? ($total_expected_scanned_qty / $total_expected_qty) * 100 : 0;
$pct_outbounded = $total_expected_qty > 0 ? ($outbounded_qty_missing / $total_expected_qty) * 100 : 0;
$pct_elsewhere  = $total_expected_qty > 0 ? ($audited_on_other_wh_qty / $total_expected_qty) * 100 : 0;
$pct_missing    = $total_expected_qty > 0 ? ($negative_variance_qty / $total_expected_qty) * 100 : 0;
$pct_unexplained = max(0, 100 - $pct_verified - $pct_outbounded - $pct_elsewhere - $pct_missing);

// One-line, plain-English read of where the audit stands, for the card header
if ($total_expected_qty == 0) {
    $recon_headline = "No expected inventory recorded yet for this warehouse.";
} elseif ($negative_variance_qty == 0 && $is_balanced) {
    $recon_headline = "Every expected item is accounted for — nothing left to chase.";
} elseif ($negative_variance_qty > 0) {
    $recon_headline = number_format($pct_verified, 1) . "% of expected inventory is verified. "
        . number_format($negative_variance_qty) . " item" . ($negative_variance_qty == 1 ? '' : 's') . " ("
        . number_format($pct_missing, 1) . "%) still need physical verification.";
} else {
    $recon_headline = number_format($pct_verified, 1) . "% of expected inventory is verified.";
}
?>

<div class="row">
    <div class="col-md-12 col-sm-12">
        <!-- //executive dashboard -->
        <div class="row g-3 mb-4 executive-kpis">

            <div class="col-md-3 col-sm-6 d-flex">
                <div class="card metric-card border-primary shadow-sm w-100" style="--metric-color:#2c7be5;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <small class="text-uppercase fw-semibold text-muted">Expected inventory</small>
                            <span class="fas fa-boxes text-primary opacity-50"></span>
                        </div>
                        <?php if($audit_position == 1){?><a href="expected_items.php?wh=<?php echo $warehouse_id_audit;?>" class="text-decoration-none"><?php } ?>
                        <h3 class="metric-value mb-0" id="expected_summary"><?= number_format($total_expected_qty) ?></h3>
                        <?php if($audit_position == 1){?></a><?php } ?>
                        <div class="metric-meta"><?php if($can_view_amounts){ ?>₱ <?= number_format($total_expected_amount, 2) ?><?php } ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 d-flex">
                <div class="card metric-card border-primary shadow-sm w-100" style="--metric-color:#2c7be5;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <small class="text-uppercase fw-semibold text-muted">Verified in correct WH</small>
                            <span class="fas fa-check-circle text-primary opacity-50"></span>
                        </div>
                        <h3 class="metric-value mb-0" id="scanned_expected_summary"><?= number_format($total_expected_scanned_qty) ?></h3>
                        <div class="metric-meta"><?php if($can_view_amounts){ ?>₱ <?= number_format($total_expected_scanned_amount, 2) ?><?php } ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 d-flex">
                <div class="card metric-card border-success shadow-sm w-100" style="--metric-color:#00a76f;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <small class="text-uppercase fw-semibold text-muted">Total scans captured</small>
                            <span class="fas fa-barcode text-success opacity-50"></span>
                        </div>
                        <?php if($audit_position == 1){?><a href="generate_detailed_report.php?audit_id=<?php echo $audit_id; ?>" class="text-decoration-none"><?php } ?>
                        <h3 class="metric-value mb-0" id="total_scanned_summary"><?= number_format($total_qty_scanned) ?></h3>
                        <?php if($audit_position == 1){?></a><?php } ?>
                        <div class="metric-meta"><?php if($can_view_amounts){ ?>₱ <?= number_format($total_scanned_amount,2) ?><?php } ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 d-flex">
                <div class="card metric-card border-warning shadow-sm w-100" style="--metric-color:#f5803e;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <small class="text-uppercase fw-semibold text-muted">Still missing</small>
                            <span class="fas fa-triangle-exclamation text-warning opacity-50"></span>
                        </div>
                        <?php if($audit_position == 1){?><a href="generate_missing.php?audit_id=<?php echo $audit_id; ?>" class="text-decoration-none"><?php } ?>
                        <h3 class="metric-value mb-0" id="missing_summary"><?= number_format($negative_variance_qty) ?></h3>
                        <?php if($audit_position == 1){?></a><?php } ?>
                        <div class="metric-meta"><?php if($can_view_amounts){ ?>₱ <?= number_format($negative_variance_amount,2) ?><?php } ?></div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 d-flex">
                <div class="card metric-card border-info shadow-sm w-100" style="--metric-color:#39afd1;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <small class="text-uppercase fw-semibold text-muted">Expected items accounted for</small>
                            <span class="fas fa-layer-group text-info opacity-50"></span>
                        </div>
                        <h3 class="metric-value mb-1" id="expected_reconciled_card"><?= number_format($expected_items_resolved_qty) ?></h3>
                        <?php if ($can_view_amounts): ?>
                            <div class="metric-meta fw-semibold text-primary mb-1">₱ <span id="expected_reconciled_amount"><?= number_format($expected_items_resolved_amount, 2) ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
        <!-- //progress bar  -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between mb-2">
                    <span>Audit Progress</span>
                    <strong id="progress_text"><?= number_format($audit_progress,2) ?>%</strong>
                </div>

                <div class="progress" style="height:22px">
                    <div id="progress_bar" class="progress-bar bg-success" style="width:<?= $audit_progress ?>%">
                        <?= number_format($audit_progress,2) ?>%
                    </div>
                </div>

                <div class="mt-3 small text-muted">
                    <strong id="expected_reconciled_qty"><?= number_format($expected_items_resolved_qty) ?></strong> of
                    <strong id="expected_reconciled_total"><?= number_format($total_expected_qty) ?></strong> expected items reconciled
                    <span class="d-block mt-1">
                        <span id="expected_reconciled_verified"><?= number_format($total_expected_scanned_qty) ?></span> verified +
                        <span id="expected_reconciled_outbounded"><?= number_format($outbounded_qty_missing) ?></span> outbounded +
                        <span id="expected_reconciled_other"><?= number_format($audited_on_other_wh_qty) ?></span> elsewhere
                    </span>
                </div>

            </div>
        </div>

        <!-- //data integrity check - new, at-a-glance -->
        <div class="card shadow-sm mb-4 border-<?= $is_balanced ? 'success' : 'danger' ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fas fa-scale-balanced fs-4 text-<?= $is_balanced ? 'success' : 'danger' ?>"></span>
                        <div>
                            <h6 class="mb-0">Reconciliation Check</h6>
                            <small class="text-muted">Scanned + Missing vs. Expected</small>
                        </div>
                    </div>
                    <span id="reconciliation_status_badge" class="badge <?= $is_balanced ? 'bg-success' : 'bg-danger' ?>">
                        <?= $is_balanced ? 'Balanced' : 'Out of balance' ?>
                    </span>
                </div>
                <div class="small text-muted mt-2" id="reconciliation_status_detail">
                    <?php if ($is_balanced): ?>
                        Every expected item is accounted for in the totals below.
                    <?php else: ?>
                        Variance of <strong><?= number_format(abs($qty_balance_diff)) ?></strong> qty<?php if($can_view_amounts){ ?> / ₱<?= number_format(abs($amount_balance_diff),2) ?><?php } ?> between reconciled totals and expected inventory.
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- //management attention - now carries the exception detail the removed cards used to show -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="mb-0">Management attention</h6>
                        <small class="text-muted">Items needing a decision or follow-up</small>
                    </div>
                    <span class="badge <?= $attention_count > 0 ? 'bg-warning text-dark' : 'bg-success' ?>"><?= number_format($attention_count) ?> open</span>
                </div>

                <div class="attention-item mb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <strong id="missing_qty_att"><?= number_format($negative_variance_qty) ?></strong> missing items
                        <small class="text-muted d-block">Priority: physical verification</small>
                    </div>
                    <?php if($audit_position == 1){?><a href="generate_missing.php?audit_id=<?php echo $audit_id; ?>" class="small">CSV</a><?php } ?>
                </div>

                <div class="attention-item mb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <strong id="wrong_wh_summary"><?= number_format($wrong_warehouse_qty) ?></strong> scanned from another warehouse
                        <small class="text-muted d-block">Priority: validate transfer or location<?php if($can_view_amounts){ ?> &middot; ₱<?= number_format($total_scanned_wrong_warehouse_as_positive_variance_amount,2) ?><?php } ?></small>
                    </div>
                    <?php if($audit_position == 1){?><a href="generate_wrong_wh_report.php?audit_id=<?php echo $audit_id; ?>" class="small">CSV</a><?php } ?>
                </div>

                <div class="attention-item mb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <strong id="scanned_outbounded_summary"><?= number_format($total_scanned_outbounded_as_positive_variance_qty) ?></strong> outbounded items scanned
                        <small class="text-muted d-block">Priority: reconcile outbound record<?php if($can_view_amounts){ ?> &middot; ₱<?= number_format($total_scanned_outbounded_as_positive_variance_amount,2) ?><?php } ?></small>
                    </div>
                </div>

                <div class="attention-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong id="missing_outbounded_summary"><?= number_format($outbounded_qty_missing) ?></strong> missing items later outbounded
                        <small class="text-muted d-block">Resolved automatically<?php if($can_view_amounts){ ?> &middot; ₱<?= number_format($outbounded_amount_missing,2) ?><?php } ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8 col-md-12 col-sm-12">

        <!-- //charts row - bar chart kept, doughnut chart added for visual composition -->
        <div class="row g-3 mb-4">
            <div class="col-lg-7 col-md-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header">Audit Exception Analysis</div>
                    <div class="card-body">
                        <canvas id="auditChart" height="220"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 col-md-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header">Expected Items Resolution</div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="resolutionChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- //reconciliation - executive view: headline insight + composition bar + bridge -->
        <div class="card shadow-sm mb-4 recon-card">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Audit Reconciliation</h5>
                        <div class="text-muted" id="recon_headline"><?= htmlspecialchars($recon_headline) ?></div>
                    </div>
                    <span id="reconciliation_bridge_badge" class="badge fs-11 <?= $is_balanced ? 'bg-success' : 'bg-danger' ?>">
                        <span class="fas <?= $is_balanced ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> me-1"></span><?= $is_balanced ? 'Balanced' : 'Out of balance' ?>
                    </span>
                </div>

                <!-- Composition bar: where every expected item currently stands -->
                <div class="recon-bar" id="recon_bar" role="img" aria-label="Composition of expected inventory">
                    <div class="recon-bar-seg bg-success" id="recon_bar_verified" style="width:<?= $pct_verified ?>%" title="Verified"></div>
                    <div class="recon-bar-seg bg-purple" id="recon_bar_outbounded" style="width:<?= $pct_outbounded ?>%" title="Outbounded"></div>
                    <div class="recon-bar-seg bg-teal" id="recon_bar_elsewhere" style="width:<?= $pct_elsewhere ?>%" title="Elsewhere"></div>
                    <div class="recon-bar-seg bg-danger" id="recon_bar_missing" style="width:<?= $pct_missing ?>%" title="Missing"></div>
                    <?php if ($pct_unexplained > 0): ?>
                    <div class="recon-bar-seg bg-secondary" id="recon_bar_unexplained" style="width:<?= $pct_unexplained ?>%" title="Unexplained"></div>
                    <?php endif; ?>
                </div>

                <div class="d-flex flex-wrap gap-3 mt-2 mb-4 recon-legend">
                    <div class="d-flex align-items-center gap-1"><span class="legend-dot bg-success"></span><small class="text-muted">Verified <strong id="recon_legend_verified_pct"><?= number_format($pct_verified,1) ?></strong>%</small></div>
                    <div class="d-flex align-items-center gap-1"><span class="legend-dot bg-purple"></span><small class="text-muted">Outbounded <strong id="recon_legend_outbounded_pct"><?= number_format($pct_outbounded,1) ?></strong>%</small></div>
                    <div class="d-flex align-items-center gap-1"><span class="legend-dot bg-teal"></span><small class="text-muted">Elsewhere <strong id="recon_legend_elsewhere_pct"><?= number_format($pct_elsewhere,1) ?></strong>%</small></div>
                    <div class="d-flex align-items-center gap-1"><span class="legend-dot bg-danger"></span><small class="text-muted">Missing <strong id="recon_legend_missing_pct"><?= number_format($pct_missing,1) ?></strong>%</small></div>
                    <?php if (!$is_balanced): ?>
                    <div class="d-flex align-items-center gap-1"><span class="legend-dot bg-secondary"></span><small class="text-muted">Unexplained <strong id="recon_legend_unexplained_pct"><?= number_format($pct_unexplained,1) ?></strong>%</small></div>
                    <?php endif; ?>
                </div>

                <hr class="mb-4">

                <!-- Bridge: how Total Scanned reconciles down to Expected Total -->
                <div class="row">
                    <div class="col-md-6 recon-col">
                        <h6 class="text-muted text-uppercase fs-11 mb-3">Quantity</h6>

                        <div class="recon-step">
                            <span class="recon-icon bg-primary-subtle text-primary"><span class="fas fa-barcode"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Total Scanned</small>
                                <span class="fw-semibold" id="scanned_qty"><?= number_format($total_qty_scanned) ?></span>
                            </div>
                        </div>

                        <div class="recon-step">
                            <span class="recon-icon bg-warning-subtle text-warning"><span class="fas fa-minus"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Positive Variance <span class="text-nowrap">(Wrong WH <span id="wrong_wh_qty"><?= number_format($wrong_warehouse_qty) ?></span> + Outbounded <span id="scanned_outbounded_qty"><?= number_format($total_scanned_outbounded_as_positive_variance_qty) ?></span>)</span></small>
                                <span class="fw-semibold" id="positive_variance_qty">- <?= number_format($positive_variance_qty) ?></span>
                            </div>
                        </div>

                        <div class="recon-step recon-step-result">
                            <span class="recon-icon bg-info-subtle text-info"><span class="fas fa-equals"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Expected Scanned</small>
                                <span class="fw-bold" id="must_be_equal_expected_scanned_qty"><?= number_format($reconciled_scanned_qty) ?></span>
                            </div>
                        </div>

                        <div class="recon-step">
                            <span class="recon-icon bg-danger-subtle text-danger"><span class="fas fa-plus"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Missing</small>
                                <span class="fw-semibold" id="missing_qty"><?= number_format($negative_variance_qty) ?></span>
                            </div>
                        </div>

                        <div class="recon-step">
                            <span class="recon-icon bg-purple-subtle text-purple"><span class="fas fa-truck"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Outbounded (was missing)</small>
                                <span class="fw-semibold" id="missing_outbounded_qty"><?= number_format($outbounded_qty_missing) ?></span>
                            </div>
                        </div>

                        <div class="recon-step">
                            <span class="recon-icon bg-teal-subtle text-teal"><span class="fas fa-warehouse"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Scanned elsewhere</small>
                                <span class="fw-semibold" id="audited_on_other_wh_qty"><?= number_format($audited_on_other_wh_qty) ?></span>
                            </div>
                        </div>

                        <div class="recon-step recon-step-final">
                            <span class="recon-icon bg-success-subtle text-success"><span class="fas fa-flag-checkered"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Expected Total</small>
                                <span class="fw-bold fs-5" id="must_be_equal_expected_qty"><?= number_format($reconciled_total_qty) ?></span>
                                <div class="text-muted" style="font-size:.72rem;">vs. Expected Inventory: <?= number_format($total_expected_qty) ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($can_view_amounts): ?>
                    <div class="col-md-6 recon-col mt-4 mt-md-0">
                        <h6 class="text-muted text-uppercase fs-11 mb-3">Amount (₱)</h6>

                        <div class="recon-step">
                            <span class="recon-icon bg-primary-subtle text-primary"><span class="fas fa-barcode"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Total Scanned</small>
                                <span class="fw-semibold" id="scanned_amount"><?= number_format($total_scanned_amount, 2) ?></span>
                            </div>
                        </div>

                        <div class="recon-step">
                            <span class="recon-icon bg-warning-subtle text-warning"><span class="fas fa-minus"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Positive Variance <span class="text-nowrap">(Wrong WH <span id="wrong_wh_amount"><?= number_format($total_scanned_wrong_warehouse_as_positive_variance_amount, 2) ?></span> + Outbounded <span id="scanned_outbounded_amount"><?= number_format($total_scanned_outbounded_as_positive_variance_amount, 2) ?></span>)</span></small>
                                <span class="fw-semibold" id="positive_variance_amount">- <?= number_format($positive_variance_amount, 2) ?></span>
                            </div>
                        </div>

                        <div class="recon-step recon-step-result">
                            <span class="recon-icon bg-info-subtle text-info"><span class="fas fa-equals"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Expected Scanned</small>
                                <span class="fw-bold" id="must_be_equal_expected_scanned_amount"><?= number_format($reconciled_scanned_amount, 2) ?></span>
                            </div>
                        </div>

                        <div class="recon-step">
                            <span class="recon-icon bg-danger-subtle text-danger"><span class="fas fa-plus"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Missing</small>
                                <span class="fw-semibold" id="missing_amount"><?= number_format($negative_variance_amount, 2) ?></span>
                            </div>
                        </div>

                        <div class="recon-step">
                            <span class="recon-icon bg-purple-subtle text-purple"><span class="fas fa-truck"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Outbounded (was missing)</small>
                                <span class="fw-semibold" id="missing_outbounded_amount"><?= number_format($outbounded_amount_missing, 2) ?></span>
                            </div>
                        </div>

                        <div class="recon-step">
                            <span class="recon-icon bg-teal-subtle text-teal"><span class="fas fa-warehouse"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Scanned elsewhere</small>
                                <span class="fw-semibold" id="audited_on_other_wh_amount"><?= number_format($audited_on_other_wh_amount, 2) ?></span>
                            </div>
                        </div>

                        <div class="recon-step recon-step-final">
                            <span class="recon-icon bg-success-subtle text-success"><span class="fas fa-flag-checkered"></span></span>
                            <div class="recon-step-body">
                                <small class="text-muted d-block">Expected Total</small>
                                <span class="fw-bold fs-5" id="must_be_equal_expected_amount"><?= number_format($reconciled_total_amount, 2) ?></span>
                                <div class="text-muted" style="font-size:.72rem;">vs. Expected Inventory: ₱<?= number_format($total_expected_amount, 2) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <!-- Hidden holders so live AJAX updates never throw on missing targets for restricted roles -->
                        <span class="d-none" id="scanned_amount"></span>
                        <span class="d-none" id="wrong_wh_amount"></span>
                        <span class="d-none" id="scanned_outbounded_amount"></span>
                        <span class="d-none" id="positive_variance_amount"></span>
                        <span class="d-none" id="must_be_equal_expected_scanned_amount"></span>
                        <span class="d-none" id="missing_amount"></span>
                        <span class="d-none" id="missing_outbounded_amount"></span>
                        <span class="d-none" id="audited_on_other_wh_amount"></span>
                        <span class="d-none" id="must_be_equal_expected_amount"></span>
                    <?php endif; ?>
                </div>

                <?php if (!$is_balanced): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mt-4 mb-0 py-2" id="recon_variance_alert">
                    <span class="fas fa-triangle-exclamation"></span>
                    <small>
                        Variance of <strong id="recon_variance_qty"><?= number_format(abs($qty_balance_diff)) ?></strong> qty
                        <?php if($can_view_amounts){ ?>/ ₱<strong id="recon_variance_amount"><?= number_format(abs($amount_balance_diff),2) ?></strong><?php } ?>
                        between the reconciled total and Expected Inventory.
                        <?php if($audit_position == 1){?><a href="../Variance-look/?audit_id=<?= $audit_id ?>" class="alert-link">Investigate variance &rarr;</a><?php } ?>
                    </small>
                </div>
                <?php else: ?>
                <div class="alert alert-success d-flex align-items-center gap-2 mt-4 mb-0 py-2" id="recon_variance_alert">
                    <span class="fas fa-circle-check"></span>
                    <small>Reconciled totals match Expected Inventory exactly. No further action needed here.</small>
                </div>
                <?php endif; ?>

            </div>
        </div>


    </div>
    <!-- NOTE: outer .row intentionally stays open here on purpose -- the Missing,
         Recent Scans, and Assignments table sections further down this file render
         as siblings inside this same row, which is closed near the Assignments card. -->


    <style>
        .card {
            border: 0;
            border-radius: 14px;
        }

        .card-title {
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .table-modern {
            font-size: 0.85rem;
        }

        .table-modern thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #6c757d;
            border-bottom: 2px solid #e9ecef;
            white-space: nowrap;
        }

        .table-modern tbody tr {
            transition: all 0.15s ease-in-out;
        }

        .table-modern tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            font-weight: 500;
            padding: 6px 10px;
            border-radius: 10px;
            font-size: 0.7rem;
        }

        .fs-11 {
            font-size: 0.82rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: .75rem;
        }

        .soft-muted {
            color: #8a8f98;
            font-size: 0.8rem;
        }

        .btn-sm {
            border-radius: 10px;
        }

        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }

        .table-scroll {
            max-height: 420px;
            overflow-y: auto;
        }
    </style>

    <div class="col-md-9 col-sm-12 d-flex mb-3">
        <div class="card mb-3 h-100 w-100 shadow-sm">
            <div class="card-body">

                <div class="section-header">
                    <div>
                        <h5 class="card-title mb-0">Missing / Not Yet Scanned Items</h5>
                        <div class="soft-muted">Pending audit items that are not yet scanned</div>
                    </div>
                </div>

                <?php
                $recent_scans_query = "
                    SELECT  p.description,
                            b.brand_name,
                            c.category_name,
                            il.location_name,
                            w.warehouse_name,
                            ia.outbounded,
                            ia.warehouse_origin,
                            ia.warehouse_onscanned,
                            ia.item_location_origin,
                            ia.item_location_onscanned,
                            s.capital,
                            ia.audit_status,
                            COUNT(ia.unique_barcode) AS qty
                    FROM items_to_audit ia
                    LEFT JOIN stocks s ON s.unique_barcode = ia.unique_barcode
                    LEFT JOIN product p ON p.hashed_id = s.product_id
                    LEFT JOIN brand b ON b.hashed_id = p.brand
                    LEFT JOIN category c ON c.hashed_id = p.category
                    LEFT JOIN item_location il ON il.id = s.item_location
                    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
                    WHERE ia.audit_status = 'pending'
                    AND ia.audit_id = '$audit_id'
                    GROUP BY s.parent_barcode
                    ORDER BY s.capital DESC
                ";

                $recent_scans_result = mysqli_query($conn, $recent_scans_query);
                ?>
                <div class="table-scroll">
                    <div class="table-responsive">
                        <table class="table table-hover table-modern align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th>Brand</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>QTY</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php while ($row = mysqli_fetch_assoc($recent_scans_result)) {

                                    $product_description = htmlspecialchars($row['description']);
                                    $brand_name = htmlspecialchars($row['brand_name']);
                                    $category_name = htmlspecialchars($row['category_name']);
                                    $location_name = htmlspecialchars($row['location_name']);
                                    $outbounded_yes_no = $row['outbounded'];
                                    $qty = $row['qty'];
                                ?>
                                <tr>
                                    <td class="fw-medium"><?= $product_description ?></td>
                                    <td><?= $brand_name ?></td>
                                    <td><?= $category_name ?></td>
                                    <td class="text-muted"><?= $location_name ?? "For SKU" ?></td>
                                    <td>
                                        <?php echo $qty;?>
                                    </td>
                                </tr>
                                <?php } ?>

                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-12 d-flex mb-3">
        <div class="card mb-3 h-100">
            <div class="card-header">
                <h5 class="text-muted fs-11">Barcodes moved to another warehouse since the audit started</h5>
                <hr>
            </div>
            <div class="card-body">
                <div class="table-scroll">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover table-modern align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Barcode</th>
                                    <th>On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $naligaw_query = "
                                    SELECT
                                        ita.unique_barcode,
                                        w.warehouse_name
                                    FROM items_to_audit ita
                                    LEFT JOIN stocks s
                                        ON s.unique_barcode = ita.unique_barcode
                                    LEFT JOIN warehouse w
                                        ON w.hashed_id = s.warehouse
                                    WHERE ita.audit_status = 'pending'
                                    AND s.warehouse != '$warehouse_id_audit'
                                    AND ita.audit_id = '$audit_id'
                                ";
                                $naligaw_res = $conn->query($naligaw_query);
                                if($naligaw_res->num_rows>0){
                                    while($row=$naligaw_res->fetch_assoc()){
                                        $naligaw_na_barcode = $row['unique_barcode'];
                                        $on = $row['warehouse_name'];

                                        echo '<tr>
                                            <td class="text-light">' . $naligaw_na_barcode . '</td>
                                            <td class="text-light">' . $on . '</td>
                                        </tr>';
                                    }
                                } else {
                                    echo '<tr>
                                        <td class="text-light" colspan="2">None yet.</td>
                                    </tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="col-md-6 col-sm-12 d-flex mb-3">
        <div class="card mb-3 h-100 w-100 shadow-sm">
            <div class="card-body">

                <div class="section-header">
                    <div>
                        <h5 class="card-title mb-0">Recent Scans</h5>
                        <div class="soft-muted">Latest scanned items</div>
                    </div>
                </div>

                <?php
                $recent_scans_query = "
                    SELECT  p.description,
                            b.brand_name,
                            c.category_name,
                            il.location_name,
                            w.warehouse_name,
                            ia.outbounded,
                            ia.warehouse_origin,
                            ia.warehouse_onscanned,
                            ia.item_location_origin,
                            ia.item_location_onscanned,
                            s.capital,
                            ia.audit_status,
                            s.unique_barcode
                    FROM items_to_audit ia
                    LEFT JOIN stocks s ON s.unique_barcode = ia.unique_barcode
                    LEFT JOIN product p ON p.hashed_id = s.product_id
                    LEFT JOIN brand b ON b.hashed_id = p.brand
                    LEFT JOIN category c ON c.hashed_id = p.category
                    LEFT JOIN item_location il ON il.id = s.item_location
                    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
                    WHERE ia.audit_status = 'scanned'
                    AND ia.audit_id = '$audit_id'
                    ORDER BY ia.scanned_date DESC
                    LIMIT 10
                ";

                $recent_scans_result = mysqli_query($conn, $recent_scans_query);
                ?>
                <div class="table-scroll">
                    <div class="table-responsive">
                        <table class="table table-hover table-modern align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Barcode</th>
                                    <th>Outbounded?</th>
                                    <th>Location</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($recent_scans_result)) {

                                    $barcode = htmlspecialchars($row['unique_barcode']);
                                    $outbounded_yes_no = $row['outbounded'];
                                    $location_name = htmlspecialchars($row['location_name']);
                                ?>
                                <tr>
                                    <td class="fw-medium"><?= $barcode ?></td>
                                    <td>
                                        <?php if ($outbounded_yes_no === "yes") { ?>
                                            <span class="badge bg-primary">Yes</span>
                                        <?php } else { ?>
                                            <span class="badge bg-secondary">No</span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-muted"><?= $location_name ?? "For SKU" ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>

                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>


    


    <div class="col-md-6 col-sm-12">
        <div class="card shadow-sm">
            <div class="card-body">

                <div class="section-header">
                    <div>
                        <h5 class="card-title mb-0">Assignments</h5>
                        <div class="soft-muted">Staff assigned per location</div>
                    </div>
                </div>

                <?php
                $additional_query = "";

                
                $audit_assignment_staffs_query = "
                    SELECT 
                        u.user_fname,
                        u.user_lname,
                        aas.user_id,
                        aas.status AS staff_status,
                        aas.audit_assignments_id,
                        il.id AS location_id,
                        il.location_name,
                        aas.id AS fi,
                        aa.id AS audit_assignment_id
                    FROM audit_assignment_staffs aas
                    LEFT JOIN users u
                        ON u.hashed_id = aas.user_id
                    LEFT JOIN audit_assignments aa
                        ON aa.id = aas.audit_assignments_id
                    LEFT JOIN item_location il
                        ON il.id = aa.item_location
                    WHERE aa.audit_id = ?
                    $additional_query
                    ORDER BY aas.id ASC
                    LIMIT 100
                ";

                $stmt = $conn->prepare($audit_assignment_staffs_query);
                $stmt->bind_param("i", $audit_id);
                $stmt->execute();
                $audit_assignment_staffs_result = $stmt->get_result();
                ?>

                <div class="table-responsive">
                    <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
                        <thead class="table-light">
                            <tr>
                                <th>Staff</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($audit_assignment_staffs_result->num_rows > 0) {

                                while ($staff = $audit_assignment_staffs_result->fetch_assoc()) {

                                    $full_name = $staff['user_fname'] . ' ' . $staff['user_lname'];
                                    // if ($staff['staff_status'] !== 'idle') {
                        
                                    // } else {
                                        // if($user_id === $staff['user_id']){
                                            $check_qty_query = "SELECT
                                                COUNT(id) AS qty
                                            FROM items_to_audit
                                            WHERE audit_id = '$audit_id'
                                            AND audit_assignment_id = " . $staff['audit_assignments_id'] . "
                                            AND user_id = '" . $staff['user_id'] . "'";
                                            $result = $conn->query($check_qty_query);
                                            if($result->num_rows>0){
                                                $row=$result->fetch_assoc();
                                                $idle_qty = $row['qty'];
                                                // if($idle_qty != 0){
                                                ?>
                                                <!-- for idle -->
                                                <tr>
                                                    <td>
                                                        <div class="fw-medium">
                                                            <?php if($staff['staff_status'] === 'for_approval'){ echo "<span class='d-none'>0</span>";}?>
                                                            <?= htmlspecialchars($full_name) ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($staff['location_name']) ?> 
                                                        </small>
                                                    </td>

                                                    <td>
                                                        <?php
                                                        if ($staff['staff_status'] === 'for_approval') {

                                                            echo '<span class="badge bg-warning text-dark">For Approval (' . $idle_qty . ')</span>';

                                                        } elseif ($staff['staff_status'] === 'approved') {

                                                            echo '<span class="badge bg-success">Approved (' . $idle_qty . ')</span>';  

                                                        } elseif ($staff['staff_status'] === 'declined') {

                                                            echo '<span class="badge bg-danger">Declined (' . $idle_qty . ')</span>';

                                                        } elseif ($staff['staff_status'] === 'rejected') {

                                                            echo '<span class="badge bg-danger">Rejected (' . $idle_qty . ')</span>';
                                                        } elseif ($staff['staff_status'] === 'idle') {
                        
                                                                echo '<span class="badge bg-secondary">Idle (' . $idle_qty . ')</span>';
                                                        } else {

                                                            echo '<span class="badge bg-secondary">'
                                                                . htmlspecialchars($staff['staff_status'])
                                                                . '</span>';

                                                        }
                                                        ?>
                                                    </td>

                                                    <td class="text-end">
                                                        <?php if($staff['staff_status'] === 'for_approval' || $staff['staff_status'] === 'approved' || $staff['staff_status'] === 'rejected'){ ?>

                                                            <a href="../finish/?area=<?= $staff['location_id']; ?>&user_id=<?= $staff['user_id']; ?>&fi=<?= $staff['fi']; ?>&aa=<?= $staff['audit_assignments_id'] ?>"
                                                            class="btn btn-success btn-sm">
                                                                View
                                                            </a>
                                                        <?php } elseif($user_id === $staff['user_id'] && $staff['staff_status'] === 'idle'){?>    
                                                            <a href="../Scan/?area=others&loc_combo=<?= $staff['location_name']; ?>"
                                                            class="btn btn-success btn-sm">
                                                                View
                                                            </a>
                                                        <?php } ?>

                                                    </td>
                                                </tr>
                                                <?php
                                                // }
                                            }
                                        // }           
                                    // }
                                }
                            } else {
                            ?>

                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    No staff assignments found
                                </td>
                            </tr>

                            <?php } ?>

                        </tbody>
                    </table>
                </div>

                <?php $stmt->close(); ?>

            </div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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








const resolutionChart = new Chart(document.getElementById('resolutionChart'), {
    type: 'doughnut',

    data: {
        labels: [
            'Verified here',
            'Outbounded (was missing)',
            'Scanned elsewhere',
            'Still missing'
        ],

        datasets: [{
            data: [
                <?= $total_expected_scanned_qty ?>,
                <?= $outbounded_qty_missing ?>,
                <?= $audited_on_other_wh_qty ?>,
                <?= $negative_variance_qty ?>
            ],

            backgroundColor: [
                '#00a76f', // green
                '#9b59b6', // purple
                '#39afd1', // teal
                '#e74c3c'  // red
            ],

            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 6
        }]
    },

    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',

        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 10,
                    font: { size: 11 }
                }
            },

            tooltip: {
                backgroundColor: '#212529',
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const pct = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                        return context.label + ': ' + context.raw.toLocaleString() + ' (' + pct + '%)';
                    }
                }
            }
        }
    }
});

const auditChart = new Chart(document.getElementById('auditChart'), {
    type: 'bar',

    data: {
        labels: [
            'Wrong Warehouse',
            'Scanned',
            'Outbounded',
            'Missing'
        ],

        datasets: [{
            label: 'Items',
            data: [
                <?= $wrong_warehouse_qty ?>,
                <?= $wrong_location_qty ?>,
                <?= $outbounded_variance_qty ?>,
                <?= $negative_variance_qty ?>
            ],

            backgroundColor: [
                '#f39c12', // orange
                '#3498db', // blue
                '#9b59b6', // purple
                '#e74c3c'  // red
            ],

            borderColor: [
                '#d68910',
                '#2e86c1',
                '#884ea0',
                '#c0392b'
            ],

            borderWidth: 1,
            borderRadius: 10,
            borderSkipped: false,
            hoverBackgroundColor: [
                '#f5b041',
                '#5dade2',
                '#af7ac5',
                '#ec7063'
            ]
        }]
    },

    options: {
        responsive: true,
        maintainAspectRatio: false,

        animation: {
            duration: 1200,
            easing: 'easeOutQuart'
        },

        plugins: {
            legend: {
                display: false
            },

            title: {
                display: true,
                text: 'Audit Variance Summary',
                font: {
                    size: 18,
                    weight: 'bold'
                },
                padding: {
                    bottom: 20
                }
            },

            tooltip: {
                backgroundColor: '#212529',
                padding: 12,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return context.raw.toLocaleString() + ' item(s)';
                    }
                }
            }
        },

        scales: {

            y: {
                beginAtZero: true,

                ticks: {
                    precision: 0
                },

                grid: {
                    color: 'rgba(0,0,0,0.08)'
                },

                title: {
                    display: true,
                    text: 'Quantity'
                }
            },

            x: {

                grid: {
                    display: false
                },

                ticks: {
                    font: {
                        weight: 'bold'
                    }
                }
            }
        }
    }
});

function loadAuditSummary(){

    $.ajax({

        url: 'audit_summary_data.php',

        data:{
            audit_id: <?= $audit_id ?>
        },

        dataType:'json',

        success:function(data){
            // summary
            $("#expected_summary").text(data.expected_summary);
            $("#scanned_expected_summary").text(data.scanned_expected_summary);
            $("#total_scanned_summary").text(data.total_scanned_summary);
            $("#missing_summary").text(data.missing_summary);
            $("#wrong_wh_summary").text(data.wrong_wh_summary);
            $("#scanned_outbounded_summary").text(data.scanned_outbounded_summary);
            $("#missing_outbounded_summary").text(data.missing_outbounded_summary);
    
            $("#expected_qty").text(data.total_expected_qty);
            $("#expected_amount").text(data.total_expected_amount)
            $("#expected_scanned_qty").text(data.expected_scanned_qty);
            $("#expected_scanned_amount").text(data.expected_scanned_amount);
            $("#scanned_qty").text(data.total_scanned_qty);
            $("#scanned_amount").text(data.scanned_amount);
            $("#missing_qty").text(data.missing_qty);
            $("#missing_amount").text(data.missing_amount);
            $("#wrong_wh_qty").text(data.wrong_warehouse_qty);
            $("#wrong_wh_amount").text(data.wrong_warehouse_amount);
            $("#scanned_outbounded_qty").text(data.scanned_outbounded_qty);
            $("#scanned_outbounded_amount").text(data.scanned_outbounded_amount);
            $("#missing_outbounded_qty").text(data.missing_outbounded_qty);
            $("#missing_outbounded_amount").text(data.missing_outbounded_amount);
            $("#positive_variance_qty").text(data.positive_variance_qty);
            $("#positive_variance_amount").text(data.positive_variance_amount);
            $("#audited_on_other_wh_qty").text(data.audited_on_other_wh_qty);
            $("#audited_on_other_wh_amount").text(data.audited_on_other_wh_amount);
            $("#audited_on_other_wh").text(data.audited_on_other_wh);

            $("#must_be_equal_expected_scanned_amount").text(data.must_be_equal_expected_scanned_amount);
            $("#must_be_equal_expected_amount").text(data.must_be_equal_expected_amount);
            $("#must_be_equal_expected_scanned_qty").text(data.must_be_equal_expected_scanned_qty);
            $("#must_be_equal_expected_qty").text(data.must_be_equal_expected_qty);


            $("#progress_text").text(data.progress + "%");

            $("#progress_bar")
                .css("width", data.progress + "%")
                .text(data.progress + "%");

            $("#expected_reconciled_qty").text(data.expected_items_reconciled_qty);
            $("#expected_reconciled_total").text(data.expected_items_reconciled_total);
            $("#expected_reconciled_card").text(data.expected_items_reconciled_qty);
            $("#expected_reconciled_verified").text(data.expected_items_verified_here_qty);
            $("#expected_reconciled_outbounded").text(data.expected_items_outbounded_qty);
            $("#expected_reconciled_other").text(data.expected_items_scanned_elsewhere_qty);

            auditChart.data.datasets[0].data = [

                data.wrong_warehouse_qty,
                data.expected_scanned_qty,
                data.scanned_outbounded_qty,
                data.missing_qty

            ];

            auditChart.update();

            resolutionChart.data.datasets[0].data = [
                data.expected_scanned_qty,
                data.missing_outbounded_qty,
                data.audited_on_other_wh_qty,
                data.missing_qty
            ];

            resolutionChart.update();

            // Live reconciliation health check
            const toNum = v => parseFloat(String(v).replace(/,/g, '')) || 0;
            const qtyBalanced = toNum(data.must_be_equal_expected_qty) === toNum(data.total_expected_qty);
            const amountBalanced = !data.total_expected_amount || toNum(data.must_be_equal_expected_amount) === toNum(data.total_expected_amount);
            const isBalanced = qtyBalanced && amountBalanced;

            $("#reconciliation_status_badge")
                .toggleClass("bg-success", isBalanced)
                .toggleClass("bg-danger", !isBalanced)
                .text(isBalanced ? "Balanced" : "Out of balance");

            if (isBalanced) {
                $("#reconciliation_status_detail").text("Every expected item is accounted for in the totals below.");
            } else {
                const qtyDiff = Math.abs(toNum(data.must_be_equal_expected_qty) - toNum(data.total_expected_qty)).toLocaleString();
                $("#reconciliation_status_detail").text("Variance of " + qtyDiff + " qty between reconciled totals and expected inventory.");
            }

            // Update the Audit Reconciliation card: composition bar, legend, headline, badge, alert
            const expectedTotal = toNum(data.total_expected_qty);
            const verifiedQty = toNum(data.expected_scanned_qty);
            const outboundedQty = toNum(data.missing_outbounded_qty);
            const elsewhereQty = toNum(data.audited_on_other_wh_qty);
            const missingQty = toNum(data.missing_qty);

            const pct = n => expectedTotal > 0 ? (n / expectedTotal) * 100 : 0;
            const pctVerified = pct(verifiedQty);
            const pctOutbounded = pct(outboundedQty);
            const pctElsewhere = pct(elsewhereQty);
            const pctMissing = pct(missingQty);
            const pctUnexplained = Math.max(0, 100 - pctVerified - pctOutbounded - pctElsewhere - pctMissing);

            $("#recon_bar_verified").css("width", pctVerified + "%");
            $("#recon_bar_outbounded").css("width", pctOutbounded + "%");
            $("#recon_bar_elsewhere").css("width", pctElsewhere + "%");
            $("#recon_bar_missing").css("width", pctMissing + "%");
            $("#recon_bar_unexplained").css("width", pctUnexplained + "%").toggle(pctUnexplained > 0);

            $("#recon_legend_verified_pct").text(pctVerified.toFixed(1));
            $("#recon_legend_outbounded_pct").text(pctOutbounded.toFixed(1));
            $("#recon_legend_elsewhere_pct").text(pctElsewhere.toFixed(1));
            $("#recon_legend_missing_pct").text(pctMissing.toFixed(1));
            $("#recon_legend_unexplained_pct").text(pctUnexplained.toFixed(1));

            if (expectedTotal === 0) {
                $("#recon_headline").text("No expected inventory recorded yet for this warehouse.");
            } else if (missingQty === 0 && isBalanced) {
                $("#recon_headline").text("Every expected item is accounted for — nothing left to chase.");
            } else if (missingQty > 0) {
                $("#recon_headline").text(
                    pctVerified.toFixed(1) + "% of expected inventory is verified. " +
                    missingQty.toLocaleString() + (missingQty === 1 ? " item" : " items") +
                    " (" + pctMissing.toFixed(1) + "%) still need physical verification."
                );
            } else {
                $("#recon_headline").text(pctVerified.toFixed(1) + "% of expected inventory is verified.");
            }

            $("#reconciliation_bridge_badge")
                .toggleClass("bg-success", isBalanced)
                .toggleClass("bg-danger", !isBalanced)
                .html('<span class="fas ' + (isBalanced ? 'fa-circle-check' : 'fa-triangle-exclamation') + ' me-1"></span>' + (isBalanced ? 'Balanced' : 'Out of balance'));

            const $reconAlert = $("#recon_variance_alert");
            if (isBalanced) {
                $reconAlert.removeClass("alert-danger").addClass("alert-success")
                    .html('<span class="fas fa-circle-check"></span><small>Reconciled totals match Expected Inventory exactly. No further action needed here.</small>');
            } else {
                const qtyDiffAbs = Math.abs(toNum(data.must_be_equal_expected_qty) - expectedTotal).toLocaleString();
                let msg = 'Variance of <strong>' + qtyDiffAbs + '</strong> qty';
                if (data.total_expected_amount) {
                    const amtDiffAbs = Math.abs(toNum(data.must_be_equal_expected_amount) - toNum(data.total_expected_amount)).toFixed(2);
                    msg += ' / ₱<strong>' + amtDiffAbs + '</strong>';
                }
                msg += ' between the reconciled total and Expected Inventory.';
                $reconAlert.removeClass("alert-success").addClass("alert-danger")
                    .html('<span class="fas fa-triangle-exclamation"></span><small>' + msg + '</small>');
            }

            if(data.audit_status === "completed"){
                $(".scan-buttons").hide();
                $(".completed-buttons").removeClass("d-none");
            }

        }

    });

}

loadAuditSummary();

setInterval(loadAuditSummary, 8898);
</script>
