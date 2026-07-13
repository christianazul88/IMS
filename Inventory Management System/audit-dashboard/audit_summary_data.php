<?php
include "../config/database.php";
include "../config/on_session.php";

header('Content-Type: application/json');

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


$total_expected_scanned_qty_with_outbounded_missing = $total_expected_scanned_qty + $outbounded_qty_missing;
$net_variance_qty =
    $positive_variance_qty -
    $negative_variance_qty;

$net_variance_amount =
    $positive_variance_amount -
    $negative_variance_amount;

$audit_progress = 
    $total_expected_qty > 0
    ? ($total_expected_scanned_qty_with_outbounded_missing / $total_expected_qty) * 100
    : 0;



$variance_amount = $negative_variance_amount;

if($audit_position != 1){
    $variance_amount = 0;
    $net_variance_amount = 0;   
}

$expected_summary = $total_expected_qty. ' (₱ ' . number_format($total_expected_amount,2) . ')';
$scanned_summary_expected = $total_expected_scanned_qty . ' (₱ ' . number_format($total_expected_scanned_amount, 2) . ')';


$total_scanned_summary = $total_qty_scanned . ' (₱ ' . number_format($total_scanned_amount,2) . ')';
$missing_summary = $negative_variance_qty . ' (₱ ' . number_format($negative_variance_amount,2) . ')';
$wrong_wh_summary = $wrong_warehouse_qty . ' (₱ ' . number_format($total_scanned_wrong_warehouse_as_positive_variance_amount,2) . ')';
$scanned_outbounded_summary = $total_scanned_outbounded_as_positive_variance_qty . ' (₱ ' . number_format($total_scanned_outbounded_as_positive_variance_amount,2) . ')';
$missing_outbounded_summary = $outbounded_qty_missing . ' (₱ ' . number_format($outbounded_amount_missing,2) . ')';

$must_be_equal_expected_scanned_amount = $total_scanned_amount - $positive_variance_amount;
$must_be_equal_expected_amount = $must_be_equal_expected_scanned_amount + $negative_variance_amount + $outbounded_amount_missing;

$must_be_equal_expected_scanned_qty = $total_qty_scanned - $positive_variance_qty;
$must_be_equal_expected_qty = $must_be_equal_expected_scanned_qty + $negative_variance_qty + $outbounded_qty_missing;

echo json_encode([
    'expected_summary' => $expected_summary,
    'scanned_expected_summary' => $scanned_summary_expected,
    'total_scanned_summary' => $total_scanned_summary,
    'missing_summary' => $missing_summary ,
    'wrong_wh_summary' => $wrong_wh_summary,
    'scanned_outbounded_summary' => $scanned_outbounded_summary,
    'missing_outbounded_summary' => $missing_outbounded_summary,

    'must_be_equal_expected_scanned_amount' => $must_be_equal_expected_scanned_amount,
    'must_be_equal_expected_amount' => number_format($must_be_equal_expected_amount, 2),
    'must_be_equal_expected_scanned_qty' => $must_be_equal_expected_scanned_qty,
    'must_be_equal_expected_qty' => $must_be_equal_expected_qty,


    'total_expected_qty' => $total_expected_qty,
    'total_expected_amount' => number_format($total_expected_amount,2),

    'expected_scanned_qty' => $total_expected_scanned_qty,
    'expected_scanned_amount' => number_format($total_expected_scanned_amount,2),

    'total_scanned_qty' => $total_qty_scanned,
    'scanned_amount' => number_format($total_scanned_amount,2),

    'missing_qty' => $negative_variance_qty,
    'missing_amount' => number_format($negative_variance_amount,2),

    'wrong_warehouse_qty' => $wrong_warehouse_qty,
    'wrong_warehouse_amount' => number_format($total_scanned_wrong_warehouse_as_positive_variance_amount,2),

    'scanned_outbounded_qty' => 500,
    'scanned_outbounded_amount' => number_format($total_scanned_outbounded_as_positive_variance_amount,2),

    'missing_outbounded_qty' => $outbounded_qty_missing,
    'missing_outbounded_amount' => $outbounded_amount_missing,

    'positive_variance_qty' => $positive_variance_qty,
    'positive_variance_amount' => $positive_variance_amount,

    'progress' => round($audit_progress,2),

    'audit_status' => $audit_status
]);