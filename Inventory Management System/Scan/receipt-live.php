<?php
include "../config/database.php";
include "../config/on_session.php";

$audit_id = $_SESSION['audit_id'];
$selected_area = $_SESSION['selected_area'];
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    die("User not found in session.");
}

/* ---------------------------
   FETCH AUDIT DETAILS
----------------------------*/
$audit_query = "
    SELECT al.*, w.warehouse_name
    FROM audit_logs al
    LEFT JOIN warehouse w 
        ON al.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci
    WHERE al.id = ?
";

$stmt = $conn->prepare($audit_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$audit = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$audit) {
    die("Audit not found.");
}

$today = date('Y-m-d');
$schedule_date = date('Y-m-d', strtotime($audit['schedule_date']));

if ($today < $schedule_date) {
    echo "<div class='alert alert-warning'>
            Audit is scheduled for " . date('M d, Y', strtotime($audit['schedule_date'])) . ". You cannot start it today.
          </div>";
    exit;
}

if ($audit['audit_status'] == 'pending') {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const startModal = new bootstrap.Modal(document.getElementById('startAuditModal'));
            startModal.show();
        });
    </script>";
} elseif ($audit['audit_status'] != 'active' && $audit['audit_status'] != 'partially_completed') {
    echo "<div class='alert alert-info'>
            Audit status: " . ucfirst($audit['audit_status']) . "
          </div>";
    exit;
}

/* ---------------------------
   CHECK AUDIT TIMESTAMP
----------------------------*/
$check_query = "
    SELECT *
    FROM audit_logs_timestamps
    WHERE audit_id = ?
      AND `status` = 'start'
    LIMIT 1
";

$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$result = $stmt->get_result();
$audit_log_timestamp = $result->fetch_assoc();
$stmt->close();

if (!$audit_log_timestamp) {
    $last_status = 'end';
} else {
    $audit_log_last_status_query = "
        SELECT *
        FROM audit_logs_timestamps
        WHERE audit_id = ?
        ORDER BY date_time DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($audit_log_last_status_query);
    $stmt->bind_param("i", $audit_id);
    $stmt->execute();

    $last = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $last_status = $last['status'] ?? '';
}

/* ---------------------------
   GET AUDIT ASSIGNMENT
----------------------------*/
$audit_assignment_query = "
    SELECT id
    FROM audit_assignments
    WHERE item_location = ?
    AND audit_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($audit_assignment_query);
$stmt->bind_param("ii", $selected_area, $audit_id);
$stmt->execute();

$result = $stmt->get_result();
$assignment_data = $result->fetch_assoc();
$stmt->close();

$audit_assignment_id = $assignment_data['id'] ?? null;

/* ---------------------------
   FETCH SCANNED BARCODES
   (only the last 3 are rendered - see note below on why)
----------------------------*/
$barcodes = [];
$total_scanned = 0;

$RECEIPT_DISPLAY_LIMIT = 3;

if ($audit_assignment_id) {

    // Cheap count - this is what drives the "TOTAL" line and the
    // sequence numbers, without ever pulling the full scan history.
    $count_query = "
        SELECT COUNT(*) AS total
        FROM items_to_audit
        WHERE audit_id = ?
        AND audit_assignment_id = ?
        AND user_id = ?
    ";

    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("iis", $audit_id, $audit_assignment_id, $user_id);
    $stmt->execute();
    $total_scanned = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    // Only fetch the most recent RECEIPT_DISPLAY_LIMIT rows, newest first.
    $barcode_query = "
        SELECT
            id,
            unique_barcode AS barcode
        FROM items_to_audit
        WHERE audit_id = ?
        AND audit_assignment_id = ?
        AND user_id = ?
        ORDER BY scanned_date DESC, id DESC
        LIMIT " . $RECEIPT_DISPLAY_LIMIT . "
    ";

    $stmt = $conn->prepare($barcode_query);
    $stmt->bind_param("iis", $audit_id, $audit_assignment_id, $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $barcodes[] = $row;
    }

    $stmt->close();
}

?>

<style>
.receipt-box{
    background:#fff;
    border:1px dashed #999;
    padding:15px;
    font-family:monospace;
    max-height:500px;
    overflow-y:auto;
}

.receipt-title{
    text-align:center;
    font-size:18px;
    font-weight:bold;
}

.receipt-line{
    border-top:1px dashed #999;
    margin:10px 0;
}

.receipt-item{
    display:flex;
    justify-content:space-between;
    margin-bottom:5px;
    font-size:14px;
}

.receipt-footer{
    text-align:center;
    margin-top:10px;
    font-size:12px;
    color:#666;
}
</style>

<div class="receipt-box">

    <div class="receipt-title">
        AUDIT RECEIPT <?php echo $audit_assignment_id;  ?>
    </div>

    

    <div class="receipt-line"></div>

    <?php if (empty($barcodes)): ?>

        <div class="text-center text-muted">
            No scanned items yet.
        </div>

    <?php else: ?>

        <?php foreach ($barcodes as $index => $item): ?>

            <?php $sequence = $total_scanned - $index; ?>

            <div class="receipt-item" id="receipt-item-<?= $item['id']; ?>">

                <span>
                    <?php echo $sequence; ?>.
                    <?php echo htmlspecialchars($item['barcode']); ?>
                </span>

               <button
                    type="button"
                    class="btn btn-sm btn-outline-danger remove-item"
                    data-id="<?= $item['id']; ?>">
                    <i class="fas fa-times"></i>
                </button>

            </div>

        <?php endforeach; ?>

        <?php if ($total_scanned > $RECEIPT_DISPLAY_LIMIT): ?>
            <div class="text-center text-muted" style="font-size:11px; margin-top:4px;">
                Showing your last <?php echo $RECEIPT_DISPLAY_LIMIT; ?> scans only, to keep scanning fast for everyone.
                All <?php echo number_format($total_scanned); ?> items are saved — this list is just a live preview.
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <div class="receipt-line"></div>

    <div class="receipt-item">
        <strong>TOTAL</strong>
        <strong><?php echo number_format($total_scanned); ?></strong>
    </div>

    <div class="receipt-footer">
        Live Audit Monitoring
    </div>

</div>