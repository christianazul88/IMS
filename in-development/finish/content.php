<?php
$audit_id = $_SESSION['audit_id'];

// Fetch audit details
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
    echo "<div class='alert alert-warning'>
        Audit is scheduled for " . date('M d, Y', strtotime($audit['schedule_date'])) . ".
    </div>";
    exit;
}

$selected_area = $_SESSION['selected_area'];

$json_file = "../audit_json/" . $audit_id . "-" . $selected_area . ".json";

$barcodes = [];

if (file_exists($json_file)) {
    $data = json_decode(file_get_contents($json_file), true);
    if (is_array($data)) {
        $barcodes = array_reverse($data);
    }
}

$audit_info_query = "SELECT * FROM audit_assignments WHERE audit_id = ? AND item_location = ?";
$stmt = $conn->prepare($audit_info_query);
$stmt->bind_param("ii", $audit_id, $selected_area);
$stmt->execute();
$audit_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$location_status = $audit_info['status'];
$audit_assignment_id = $audit_info['id'];

if($location_status === "pending" || $location_status === "in_progress"){
    $update_status_query = "UPDATE audit_assignments SET `status` = 'for_approval' WHERE audit_id = ? AND item_location = ?";
    $stmt = $conn->prepare($update_status_query);
    $stmt->bind_param("ii", $audit_id, $selected_area);
    $stmt->execute();
    $stmt->close();
}

$check_audit_assignment_logs_query = "SELECT * FROM audit_assignment_logs WHERE audit_assignment_id = ? AND `status` = 'end' LIMIT 1";
$stmt = $conn->prepare($check_audit_assignment_logs_query);
$stmt->bind_param("i", $audit_assignment_id);
$stmt->execute();
$assignment_log = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$assignment_log){
    $insert_log_query = "INSERT INTO audit_assignment_logs (audit_assignment_id, `status`, date_time, user_id) VALUES (?, 'end', NOW(), ?)";
    $stmt = $conn->prepare($insert_log_query);
    $stmt->bind_param("is", $audit_assignment_id, $user_id);
    $stmt->execute();
    $stmt->close();
}



$total = count($barcodes);

// ======================================================
// GROUP BY PARENT BARCODE
// ======================================================
$grouped = [];

foreach ($barcodes as $code) {

    $parts = explode("-", $code);
    $parent = $parts[0];
    $seq = $parts[1] ?? '';

    if (!isset($grouped[$parent])) {
        $grouped[$parent] = [
            "qty" => 0,
            "sequences" => []
        ];
    }

    $grouped[$parent]["qty"]++;
    $grouped[$parent]["sequences"][] = $seq;
}

// ======================================================
// FETCH PRODUCT INFO ONCE PER PARENT
// ======================================================
$product_cache = [];

foreach ($grouped as $parent => $data) {

    $barcode_details_query = "
        SELECT p.description, c.category_name, b.brand_name, s.capital 
        FROM product p
        LEFT JOIN category c 
            ON p.category = c.hashed_id COLLATE utf8mb4_unicode_ci
        LEFT JOIN brand b 
            ON p.brand = b.hashed_id COLLATE utf8mb4_unicode_ci
        LEFT JOIN stocks s 
            ON p.hashed_id = s.product_id COLLATE utf8mb4_unicode_ci
        WHERE s.unique_barcode LIKE ? 
        LIMIT 1
    ";

    $like = $parent . "-%";

    $stmt = $conn->prepare($barcode_details_query);
    $stmt->bind_param("s", $like);
    $stmt->execute();

    $product_cache[$parent] = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$total_time = 0;
// id, user_id, audit_assignment_id, date_time, status, remarks
// 1, hss, 14, 2026-05-11 00:43:28, start, null
// 2, hss, 14, 2026-05-11 01:15:42, pause, null
// 3, hss, 14, 2026-05-11 01:20:10, resume, null
// 4, hss, 14, 2026-05-11 02:00:00, pause, null
// 5, hss, 14, 2026-05-11 02:10:00, resume, null
// 6, hss, 14, 2026-05-11 03:00:00, end, null


// ======================================================
// GET AUDIT LOGS
// ======================================================

$get_logs_query = "
    SELECT *
    FROM audit_assignment_logs
    WHERE audit_assignment_id = ?
    ORDER BY date_time ASC
";

$stmt = $conn->prepare($get_logs_query);
$stmt->bind_param("i", $audit_assignment_id);
$stmt->execute();

$result = $stmt->get_result();

$logs = [];

while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

$stmt->close();

// ======================================================
// CALCULATE TOTAL ACTIVE TIME
// ======================================================

$total_active_seconds = 0;
$total_pause_seconds = 0;

$active_start = null;
$pause_start = null;

foreach ($logs as $log) {

    $status = $log['status'];
    $time = strtotime($log['date_time']);

    // START / RESUME
    if ($status === 'start' || $status === 'resume') {

        $active_start = $time;

        // END PAUSE TIMER
        if ($pause_start !== null) {

            $total_pause_seconds += ($time - $pause_start);
            $pause_start = null;
        }
    }

    // PAUSE
    elseif ($status === 'pause') {

        if ($active_start !== null) {

            $total_active_seconds += ($time - $active_start);
            $active_start = null;
        }

        $pause_start = $time;
    }

    // END
    elseif ($status === 'end') {

        if ($active_start !== null) {

            $total_active_seconds += ($time - $active_start);
            $active_start = null;
        }
    }
}

// ======================================================
// FORMAT TIME
// ======================================================

$total_minutes = floor($total_active_seconds / 60);

$hours = floor($total_minutes / 60);
$minutes = $total_minutes % 60;

$formatted_time = "";

if ($hours > 0) {
    $formatted_time .= $hours . " hr ";
}

$formatted_time .= $minutes . " min";

// TOTAL PAUSE

$total_pause_minutes = floor($total_pause_seconds / 60);

$pause_hours = floor($total_pause_minutes / 60);
$pause_minutes = $total_pause_minutes % 60;

$formatted_pause = "";

if ($pause_hours > 0) {
    $formatted_pause .= $pause_hours . " hr ";
}

$formatted_pause .= $pause_minutes . " min";
?>

<style>

body {
    background: #eceff1;
    font-family: 'Courier New', monospace;
    padding: 20px;
}

.receipt {
    background: #fff;
    max-width: 700px;
    margin: auto;
    padding: 25px;
    border-radius: 10px;
    border: 2px dashed #000;
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

.receipt-header {
    text-align: center;
}

.receipt-header h2 {
    margin: 0;
    font-size: 28px;
    letter-spacing: 2px;
}

.receipt-header p {
    margin: 3px 0;
    font-size: 13px;
    color: #666;
}

.line {
    border-top: 1px dashed #777;
    margin: 15px 0;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}

.summary-card {
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 12px;
    background: #fafafa;
}

.summary-card small {
    display: block;
    color: #777;
    margin-bottom: 5px;
}

.summary-card strong {
    font-size: 18px;
}

.group {
    border: 1px dashed #ccc;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 15px;
    background: #fcfcfc;
}

.header {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    font-size: 15px;
}

.sequences {
    margin-top: 6px;
    font-size: 12px;
    color: #444;
    word-break: break-word;
    line-height: 1.5;
}

.product-info {
    margin-top: 8px;
    font-size: 12px;
    color: #666;
    line-height: 1.5;
}

.timeline {
    margin-top: 20px;
}

.timeline-item {
    display: flex;
    justify-content: space-between;
    border-bottom: 1px dashed #ddd;
    padding: 6px 0;
    font-size: 13px;
}

.print-btn {
    width: 100%;
    padding: 12px;
    border: none;
    background: #198754;
    color: white;
    font-weight: bold;
    border-radius: 8px;
    margin-bottom: 20px;
    cursor: pointer;
}

.print-btn:hover {
    opacity: 0.9;
}

.footer-note {
    text-align: center;
    font-size: 12px;
    color: #777;
    margin-top: 20px;
}

@media print {

    body {
        background: white;
        padding: 0;
    }

    body * {
        visibility: hidden;
    }

    #print-area,
    #print-area * {
        visibility: visible;
    }

    #print-area {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }

    .btn,
    .print-btn {
        display: none !important;
    }

    .receipt {
        box-shadow: none;
        border-radius: 0;
    }
}

</style>


<div class="row mb-3">
    <div class="col-3">
        <a href="../audit-dashboard/" class="btn btn-primary">Back to Dashboard</a>
    </div>
</div>
<div id="print-area">

<div class="receipt">

    <button class="print-btn" onclick="window.print()">
        PRINT RECEIPT
    </button>

    <div class="receipt-header">
        <h2>AUDIT RECEIPT</h2>

        <p>
            Warehouse:
            <?php echo htmlspecialchars($audit['warehouse_name']); ?>
        </p>

        <p>
            Area:
            <?php echo htmlspecialchars($selected_area); ?>
        </p>

        <p>
            Date:
            <?php echo date('M d, Y h:i A'); ?>
        </p>
    </div>

    <div class="line"></div>

    <div class="summary-grid">

        <div class="summary-card">
            <small>Total Scanned</small>
            <strong><?php echo number_format($total); ?></strong>
        </div>

        <div class="summary-card">
            <small>Unique Products</small>
            <strong><?php echo number_format(count($grouped)); ?></strong>
        </div>

        <div class="summary-card">
            <small>Active Audit Time</small>
            <strong><?php echo $formatted_time; ?></strong>
        </div>

        <div class="summary-card">
            <small>Paused Time</small>
            <strong><?php echo $formatted_pause; ?></strong>
        </div>

    </div>

    <div class="line"></div>

    <?php if (empty($grouped)): ?>

        <p style="text-align:center;">
            No scanned items found.
        </p>

    <?php else: ?>

        <?php foreach ($grouped as $parent => $data): ?>

            <?php
            $info = $product_cache[$parent] ?? null;
            $seqList = implode(", ", $data["sequences"]);
            ?>

            <div class="group">

                <div class="header">
                    <span><?php echo htmlspecialchars($parent); ?></span>
                    <span><?php echo $data["qty"]; ?> pcs</span>
                </div>

                <div class="sequences">
                    <strong>Sequences:</strong>
                    <?php echo htmlspecialchars($seqList); ?>
                </div>

                <div class="product-info">

                    <?php if ($info): ?>

                        <div>
                            <strong>Description:</strong>
                            <?php echo htmlspecialchars($info['description']); ?>
                        </div>

                        <div>
                            <strong>Category:</strong>
                            <?php echo htmlspecialchars($info['category_name']); ?>
                        </div>

                        <div>
                            <strong>Brand:</strong>
                            <?php echo htmlspecialchars($info['brand_name']); ?>
                        </div>

                        <div>
                            <strong>Capital:</strong>
                            ₱<?php echo number_format($info['capital'], 2); ?>
                        </div>

                    <?php else: ?>

                        Product information unavailable.

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

    <div class="line"></div>

    <div class="timeline">

        <h4 style="margin-bottom:10px;">
            AUDIT TIMELINE
        </h4>

        <?php foreach ($logs as $log): ?>

            <div class="timeline-item">

                <span>
                    <?php echo strtoupper($log['status']); ?>
                </span>

                <span>
                    <?php echo date('M d, Y h:i:s A', strtotime($log['date_time'])); ?>
                </span>

            </div>

        <?php endforeach; ?>

    </div>

    <div class="footer-note">
        Live Audit System<br>
        Generated Receipt
    </div>

</div>

</div>

<?php 
if($location_status === "for_approval"){
?>
<div class="row mt-3">
    <div class="col-12 text-center">
        <a href="approve.php" id="btnApprove" class="btn btn-success">Approved</a>
        <a href="#" id="btnReject" class="btn btn-danger ms-3">Reject</a>
    </div>
</div>

<script>
document.getElementById('btnApprove').addEventListener('click', function(e) {
    e.preventDefault();

    const url = this.getAttribute('href');

    Swal.fire({
        title: 'Confirm Approval?',
        text: "Are you sure you want to approve this record?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Approve'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
});

document.getElementById('btnReject').addEventListener('click', function(e) {
    e.preventDefault();

    const url = this.getAttribute('href');

    Swal.fire({
        title: 'Confirm Rejection?',
        text: "Are you sure you want to reject this record?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Reject'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
});
</script>
<?php
}
?>