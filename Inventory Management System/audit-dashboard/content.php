<?php
$audit_id = $_SESSION['audit_id'];

$audit_position_query = "SELECT audit_position FROM audit_users WHERE hashed_id = '$user_id' AND audit_id = '$audit_id'";
$audit_position_result = $conn->query($audit_position_query);
if($audit_position_result->num_rows === 0){
    echo "<div class='alert alert-danger'>You are not assigned to this audit.</div>";
    exit;
}
$audit_position = $audit_position_result->fetch_assoc()['audit_position'] ?? null;

if($user_email === "vp_ronadanesito@laptoppcoutlet.com"){
    $audit_position = 1;
}

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

$outbounded_variance_qty = $total_scanned_outbounded_as_positive_variance_amount;
$positive_variance_qty = $wrong_warehouse_qty + $total_scanned_outbounded_as_positive_variance_qty;
$positive_variance_amount = $total_scanned_wrong_warehouse_as_positive_variance_amount + $total_scanned_outbounded_as_positive_variance_amount;

$net_variance_qty =
    $positive_variance_qty -
    $negative_variance_qty;

$net_variance_amount =
    $positive_variance_amount -
    $negative_variance_amount;

$audit_progress = 
    $total_expected_qty > 0
    ? ($total_expected_scanned_qty / $total_expected_qty) * 100
    : 0;



$variance_amount = $negative_variance_amount;

if($audit_position != 1){
    $variance_amount = 0;
    $net_variance_amount = 0;   
}

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


<div class="audit-header mb-3">
    <div class="row align-items-center">
        <div class="col-lg-6 col-md-6 col-sm-12">
            <h4 class="mb-1 fw-bold">Audit Dashboard </h4>
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
                    class="btn btn-info btn-sm mb-2">
                    Outbound items
                </a>
                <?php 
                if($audit_position == 1) {
                    if($audit_status !== "completed" && $last_status === 'start' || $last_status === 'resume'){ ?>
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

                    <a href="generate_detailed_report.php?audit_id=<?php echo $audit_id; ?>"
                       class="btn btn-success btn-sm mb-2">
                        Scanned CSV
                    </a>

                    <a href="generate_summary_report.php?audit_id=<?php echo $audit_id; ?>"
                       class="btn btn-primary btn-sm mb-2">
                        CSV Summary
                    </a>

                    


                <?php 
                    } elseif($audit_status === "completed") {
                    ?>
                    <a href="generate_missing.php?audit_id=<?php echo $audit_id; ?>"
                       class="btn btn-danger btn-sm mb-2">
                        Missing CSV
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

<div class="row">
    <div class="col-md-12 col-sm-12">
        <!-- //executive dashboard -->
        <div class="row g-3 mb-4">

            <div class="col-md-3 col-sm-12">
                <div class="card border-primary shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Expected Qty</small>
                        <h3 id="expected_summary">
                            <?= number_format($total_expected_qty) ?>
                        </h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-12">
                <div class="card border-primary shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Scanned Expected Qty</small>
                        <h3 id="scanned_expected_summary">
                            <?= number_format($total_expected_scanned_qty) ?> <?php if($audit_position == 1){?><span class="fs-11 text-muted">(₱ <?= number_format($total_expected_scanned_amount, 2) ?>)</span> <?php } ?>
                        </h3>
                        
                    </div>
                </div>
            </div>



            <div class="col-md-3 col-sm-12">
                <div class="card border-success shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Scanned Qty</small>
                        <h3 id="total_scanned_summary"><?= number_format($total_qty_scanned) ?> <?php if($audit_position == 1){?><span class="text-muted fs-11">( ₱ <?= number_format($total_scanned_amount,2) ?> )</span><?php } ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-12">
                <div class="card border-warning shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Missing Qty</small>
                        <h3 id="missing_summary"><?= number_format($negative_variance_qty) ?> <?php if($audit_position == 1){?><span class="fs-11 text-muted">(₱ <?= number_format($negative_variance_amount,2) ?>)</span> <?php } ?></h3>
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

                    <strong id="progress_text">
                        <?= number_format($audit_progress,2) ?>%
                    </strong>

                </div>

                <div class="progress" style="height:25px">

                    <div
                        id="progress_bar"
                        class="progress-bar bg-success"
                        style="width:<?= $audit_progress ?>%"
                    >
                        <?= number_format($audit_progress,2) ?>%
                    </div>

                </div>

            </div>
        </div>

        <!-- charts -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <div class="card shadow-sm mb-4">
            <div class="card-header">
                Audit Exception Analysis
            </div>

            <div class="card-body">
                <canvas id="auditChart"></canvas>
            </div>
        </div>

        
    </div>

    <div class="col-md-8 col-sm-12">
        <!-- problems requiring investigation -->
        <div class="row g-3 mb-4">

            <div class="col-md-4 col-sm-12">
                <div class="card border-primary shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Wrong/From other Warehouse(s)</small>
                        <h5 id="wrong_wh_summary"><?= number_format($wrong_warehouse_qty,2) ?> <?php if($audit_position == 1){?><span>(₱ <?= number_format($total_scanned_wrong_warehouse_as_positive_variance_amount, 2) ?> )</span><?php } ?></h5>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-12">
                <div class="card border-success shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Outbounded Scanned</small>
                        <h5 id="scanned_outbounded_summary"><?= $total_scanned_outbounded_as_positive_variance_qty ?> <?php if($audit_position == 1){?><span>( ₱<?= number_format($total_scanned_outbounded_as_positive_variance_amount,2) ?> )</span> <?php } ?></h5>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-12">
                <div class="card border-sucess shadow-sm">
                    <div class="card-body">
                        <small class="text-muted">Outbounded from missing</small>
                        <h5 id="missing_outbounded_summary"><?= $outbounded_qty_missing ?> <?php if($audit_position == 1){?><span>( ₱<?= number_format($outbounded_amount_missing,2) ?> )</span> <?php } ?></h5>
                    </div>
                </div>
            </div>

            <div class="col-md-12 col-sm-12">
                <div class="card border-warning shadow-sm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6 col-md-6 col-xs-12">
                                <?php if($audit_position == 1){?>
                                    <div class="text-start">
                                        <small>Positive Variance (Wrong WH + Outbounded)</small>
                                    </div>

                                    <div class="text-end">
                                        <small id="wrong_wh_amount"><?php echo number_format($total_scanned_wrong_warehouse_as_positive_variance_amount, 2);?></small>
                                        <small> + </small>
                                        <small id="scanned_outbounded_amount"><?php echo number_format($total_scanned_outbounded_as_positive_variance_amount, 2);?></small> 
                                        <small> = </small>
                                        <small id="positive_variance_amount"><?php echo number_format($positive_variance_amount, 2);?></small>
                                    </div>

                                    <div class="text-start">
                                        <b><small>Calculation</small></b>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6 text-start">
                                            <small>Total Scanned</small>
                                        </div>
                                        <div class="col-sm-6 text-end"><small id="scanned_amount"><?php echo number_format($total_scanned_amount, 2);?></small></div>
                                        <div class="col-sm-6 text-start">
                                            <small>Positive Variance</small>
                                        </div>
                                        <div class="col-sm-6 text-end">
                                            <small>-   </small>
                                            <small id="positive_variance_amount"> <?php echo number_format($positive_variance_amount, 2); ?></small>
                                        </div> 
                                        <hr>
                                        <div class="col-sm-6">
                                            <small>must be equal to 'Expected Scanned'</small>
                                        </div>
                                        <div class="col-sm-6 text-end">
                                            <small id="must_be_equal_expected_scanned_amount"><?php 
                                                $expected_formula_amountscanned = $total_scanned_amount - $positive_variance_amount;
                                                echo number_format($expected_formula_amountscanned, 2);
                                            ?></small>
                                        </div>
                                        <div class="col-sm-6 text-start">
                                            <small>Missing</small>
                                        </div>
                                        <div class="col-sm-6 text-end">
                                            <small> +   </small>
                                            <small id="missing_amount"> <?php echo number_format($negative_variance_amount, 2);?></small>
                                        </div>
                                        <div class="col-sm-6 text-start">
                                            <small>Outbounded</small>
                                        </div>
                                        <div class="col-sm-6 text-end">
                                            <small id="missing_outbounded_amount">0</small>
                                        </div>
                                        <hr>
                                        <div class="col-sm-6 text-start">
                                            <small>must be equal to 'Expected'</small>
                                        </div>
                                        <div class="col-sm-6 text-end">
                                            <small id="must_be_equal_expected_amount"><?php 
                                            $expected_amount_formula = $expected_formula_amountscanned + $negative_variance_amount;
                                            echo number_format($expected_amount_formula, 2);
                                            ?></small>
                                        </div>
                                        
                                        
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-6 text-end">
                                <div class="text-start">
                                    <small>Positive Variance (Wrong WH + Outbounded)</small>
                                </div>

                                <div class="text-end">
                                    <small id="wrong_wh_qty"><?php echo number_format($wrong_warehouse_qty);?></small>
                                    <small> + </small>
                                    <small id="scanned_outbounded_qty"><?php echo number_format($total_scanned_outbounded_as_positive_variance_qty);?></small> 
                                    <small> = </small>
                                    <small id="positive_variance_qty"><?php echo $wrong_warehouse_qty + $total_scanned_outbounded_as_positive_variance_qty;?></small>
                                </div>

                                <div class="text-start">
                                    <b><small>Calculation</small></b>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6 text-start">
                                        <small>Total Scanned</small>
                                    </div>
                                    <div class="col-sm-6 text-end"><small id="scanned_qty"><?php echo number_format($total_qty_scanned);?></small></div>
                                    <div class="col-sm-6 text-start">
                                        <small>Positive Variance</small>
                                    </div>
                                    <div class="col-sm-6 text-end">
                                        <small>- </small>
                                        <small id="positive_variance_qty"> <?php echo $wrong_warehouse_qty + $total_scanned_outbounded_as_positive_variance_qty; ?></small></div> 
                                    <hr>
                                    <div class="col-sm-6 text-start">
                                        <small>must be equal to 'Expected Scanned'</small>
                                    </div>
                                    <div class="col-sm-6 text-end">
                                        <small id="must_be_equal_expected_scanned_qty"><?php 
                                            
                                            $expected_formula_qtyscanned = $total_qty_scanned - $positive_variance_qty;
                                            echo number_format($expected_formula_qtyscanned);
                                        ?></small>
                                    </div>
                                    <div class="col-sm-6 text-start">
                                        <small>Missing</small>
                                    </div>
                                    <div class="col-sm-6 text-end">
                                        <small>+ </small>
                                        <small id="missing_qty"><?php echo number_format($negative_variance_qty); ?></small>
                                    </div>
                                    <div class="col-sm-6 text-start">
                                        <small>Outbounded</small>
                                    </div>
                                    <div class="col-sm-6 text-end">
                                        <small> + </small>
                                        <small id="missing_outbounded_qty">0</small>
                                    </div>
                                    <hr>
                                    <div class="col-sm-6 text-start">
                                        <small>must be equal to 'Expected'</small>
                                    </div>
                                    <div class="col-sm-6 text-end">
                                        <small id="must_be_equal_expected_qty"><?php 
                                        $expected_qty_formula = $expected_formula_qtyscanned + $negative_variance_qty;
                                        echo number_format($expected_qty_formula);
                                        ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            

        </div>
    </div>

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

    <div class="col-md-12 col-sm-12 d-flex mb-3">
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
                $audit_assignment_staffs_query = "
                    SELECT 
                        u.user_fname,
                        u.user_lname,
                        aas.user_id,
                        aas.status AS staff_status,
                        aas.audit_assignments_id,
                        il.id AS location_id,
                        il.location_name,
                        aas.id AS fi
                    FROM audit_assignment_staffs aas
                    LEFT JOIN users u
                        ON u.hashed_id = aas.user_id
                    LEFT JOIN audit_assignments aa
                        ON aa.id = aas.audit_assignments_id
                    LEFT JOIN item_location il
                        ON il.id = aa.item_location
                    WHERE aa.audit_id = ?
                    ORDER BY aas.id DESC
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
                                    if ($staff['staff_status'] !== 'idle') {
                            ?>
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

                                        echo '<span class="badge bg-warning text-dark">For Approval</span>';

                                    } elseif ($staff['staff_status'] === 'approved') {

                                        echo '<span class="badge bg-success">Approved</span>';  

                                    } elseif ($staff['staff_status'] === 'declined') {

                                        echo '<span class="badge bg-danger">Declined</span>';

                                    } elseif ($staff['staff_status'] === 'rejected') {

                                        echo '<span class="badge bg-danger">Rejected</span>';
                                    } elseif ($staff['staff_status'] === 'idle') {
    
                                            echo '<span class="badge bg-secondary">Idle</span>';
                                    } else {

                                        echo '<span class="badge bg-secondary">'
                                            . htmlspecialchars($staff['staff_status'])
                                            . '</span>';

                                    }
                                    ?>
                                </td>

                                <td class="text-end">

                                    <?php if ($staff['staff_status'] === 'for_approval' || $staff['staff_status'] === 'approved' || $staff['staff_status'] === 'rejected') : ?>

                                        <a href="../finish/?area=<?= $staff['location_id']; ?>&user_id=<?= $staff['user_id']; ?>&fi=<?= $staff['fi']; ?>"
                                        class="btn btn-success btn-sm">
                                            View
                                        </a>

                                    <?php else : ?>

                                        <span class="text-muted small">No action</span>

                                    <?php endif; ?>

                                </td>
                            </tr>
                            <?php
                                    }
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








const auditChart = new Chart(document.getElementById('auditChart'), {

    type:'bar',

    data:{
        labels:[
            'Wrong Warehouse',
            'Wrong Location',
            'Outbounded',
            'Missing'
        ],
        datasets:[{
            data:[
                <?= $wrong_warehouse_qty ?>,
                <?= $wrong_location_qty ?>,
                <?= $outbounded_variance_qty ?>,
                <?= $negative_variance_qty ?>
            ]
        }]
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

            $("#must_be_equal_expected_scanned_amount").text(data.must_be_equal_expected_scanned_amount);
            $("#must_be_equal_expected_amount").text(data.must_be_equal_expected_amount);
            $("#must_be_equal_expected_scanned_qty").text(data.must_be_equal_expected_scanned_qty);
            $("#must_be_equal_expected_qty").text(data.must_be_equal_expected_qty);


            $("#progress_text").text(data.progress + "%");

            $("#progress_bar")
                .css("width", data.progress + "%")
                .text(data.progress + "%");

            auditChart.data.datasets[0].data = [

                data.wrong_warehouse_qty,
                data.wrong_location_qty,
                data.scanned_outbounded_qty,
                data.missing_qty

            ];

            auditChart.update();

            if(data.audit_status === "completed"){
                $(".scan-buttons").hide();
                $(".completed-buttons").removeClass("d-none");
            }

        }

    });

}

loadAuditSummary();

setInterval(loadAuditSummary, 30000);
</script>
