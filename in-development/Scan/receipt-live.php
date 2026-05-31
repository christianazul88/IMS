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
} elseif ($audit['audit_status'] != 'active') {
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
    LIMIT 1
";

$stmt = $conn->prepare($audit_assignment_query);
$stmt->bind_param("i", $selected_area);
$stmt->execute();

$result = $stmt->get_result();
$assignment_data = $result->fetch_assoc();
$stmt->close();

$audit_assignment_id = $assignment_data['id'] ?? null;

/* ---------------------------
   FETCH SCANNED BARCODES
----------------------------*/
$barcodes = [];

if ($audit_assignment_id) {

    $barcode_query = "
        SELECT unique_barcode AS barcode
        FROM items_to_audit
        WHERE audit_id = ?
          AND audit_assignment_id = ?
          AND user_id = ?
        ORDER BY scanned_date ASC
    ";

    $stmt = $conn->prepare($barcode_query);
    $stmt->bind_param("iii", $audit_id, $audit_assignment_id, $user_id);
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
        AUDIT RECEIPT
    </div>

    

    <div class="receipt-line"></div>

    <?php if (empty($barcodes)): ?>

        <div class="text-center text-muted">
            No scanned items yet.
        </div>

    <?php else: ?>

        <?php foreach ($barcodes as $index => $item): ?>

            <div class="receipt-item">
                <span><?php echo count($barcodes) - $index; ?>.</span>
                <span><?php echo htmlspecialchars($item['barcode'] ?? ''); ?></span>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

    <div class="receipt-line"></div>

    <div class="receipt-item">
        <strong>TOTAL</strong>
        <strong><?php echo count($barcodes); ?></strong>
    </div>

    <div class="receipt-footer">
        Live Audit Monitoring
    </div>

</div>