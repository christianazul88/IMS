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

if ($location_status === "pending" || $location_status === "in_progress") {

    $update_status_query = "
        UPDATE audit_assignments 
        SET status = 'for_approval' 
        WHERE audit_id = ? 
        AND item_location = ?
    ";

    $stmt = $conn->prepare($update_status_query);
    $stmt->bind_param("ii", $audit_id, $selected_area);
    $stmt->execute();
    $stmt->close();
}

$check_audit_assignment_logs_query = "
    SELECT * 
    FROM audit_assignment_logs 
    WHERE audit_assignment_id = ? 
    AND status = 'end' 
    LIMIT 1
";

$stmt = $conn->prepare($check_audit_assignment_logs_query);
$stmt->bind_param("i", $audit_assignment_id);
$stmt->execute();
$assignment_log = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$assignment_log) {

    $insert_log_query = "
        INSERT INTO audit_assignment_logs 
        (
            audit_assignment_id, 
            status, 
            date_time, 
            user_id
        ) 
        VALUES (?, 'end', NOW(), ?)
    ";

    $stmt = $conn->prepare($insert_log_query);
    $stmt->bind_param("is", $audit_assignment_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

$total = count($barcodes);

// ======================================================
// GET EXPECTED QTY FROM AUDIT ASSIGNMENTS
// ======================================================

$expected_qty = 0;

$get_expected_query = "
    SELECT expected_qty
    FROM audit_assignments
    WHERE audit_id = ?
    AND warehouse = ?
    AND item_location = ?
    LIMIT 1
";

$stmt = $conn->prepare($get_expected_query);

$stmt->bind_param(
    "isi",
    $audit_id,
    $audit['warehouse'],
    $selected_area
);

$stmt->execute();

$expected_result = $stmt->get_result()->fetch_assoc();

$stmt->close();

if ($expected_result) {
    $expected_qty = $expected_result['expected_qty'] ?? 0;
}

// ======================================================
// COMPUTE VARIANCE
// ======================================================

$variance_qty = $total - $expected_qty;

if ($variance_qty > 0) {

    $variance_label = "POSITIVE";
    $variance_badge = "#198754";

} elseif ($variance_qty < 0) {

    $variance_label = "NEGATIVE";
    $variance_badge = "#dc3545";

} else {

    $variance_label = "BALANCED";
    $variance_badge = "#6c757d";
}

// ======================================================
// DETAILED BARCODE SUMMARY
// ======================================================

$detailed_summary = [];

foreach ($barcodes as $row) {

    $barcode = $row['barcode'] ?? '';

    if (empty($barcode)) {
        continue;
    }

    $current_location_id = $row['item_location'] ?? '';
    $current_item_status = $row['item_status'] ?? 0;
    $current_warehouse_id = $row['warehouse'] ?? '';

    $outbounded = false;
    $belong_to_other_warehouse = false;
    $belong_to_other_location = false;
    $dont_belong_to_system_stocks = false;

    // CHECK IF EXISTS IN STOCKS

    $stock_query = "
        SELECT unique_barcode
        FROM stocks
        WHERE unique_barcode = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($stock_query);
    $stmt->bind_param("s", $barcode);
    $stmt->execute();

    $stock_result = $stmt->get_result();

    if ($stock_result->num_rows === 0) {
        $dont_belong_to_system_stocks = true;
    }

    $stmt->close();

    // OUTBOUNDED

    if (
        $current_item_status != 0 &&
        $dont_belong_to_system_stocks === false
    ) {
        $outbounded = true;
    }

    // OTHER WAREHOUSE

    if (
        $current_warehouse_id !== $audit['warehouse'] &&
        $dont_belong_to_system_stocks === false
    ) {
        $belong_to_other_warehouse = true;
    }

    // OTHER LOCATION

    if (
        $current_location_id != $selected_area &&
        $dont_belong_to_system_stocks === false
    ) {
        $belong_to_other_location = true;
    }

    $detailed_summary[] = [

        "barcode" => $barcode,

        "system_stock" => $dont_belong_to_system_stocks
            ? "NO"
            : "YES",

        "outbounded" => $outbounded
            ? "YES"
            : "NO",

        "other_warehouse" => $belong_to_other_warehouse
            ? "YES"
            : "NO",

        "other_location" => $belong_to_other_location
            ? "YES"
            : "NO"
    ];
}

// ======================================================
// GROUP BY PARENT BARCODE
// ======================================================

$grouped = [];

foreach ($barcodes as $item) {

    $code = $item['barcode'] ?? '';

    if (empty($code)) {
        continue;
    }

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
// FETCH PRODUCT INFO
// ======================================================

$product_cache = [];

foreach ($grouped as $parent => $data) {

    $barcode_details_query = "
        SELECT 
            p.description, 
            c.category_name, 
            b.brand_name, 
            s.capital 
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

    if ($status === 'start' || $status === 'resume') {

        $active_start = $time;

        if ($pause_start !== null) {

            $total_pause_seconds += ($time - $pause_start);
            $pause_start = null;
        }
    }

    elseif ($status === 'pause') {

        if ($active_start !== null) {

            $total_active_seconds += ($time - $active_start);
            $active_start = null;
        }

        $pause_start = $time;
    }

    elseif ($status === 'end') {

        if ($active_start !== null) {

            $total_active_seconds += ($time - $active_start);
            $active_start = null;
        }
    }
}

$total_minutes = floor($total_active_seconds / 60);

$hours = floor($total_minutes / 60);
$minutes = $total_minutes % 60;

$formatted_time = "";

if ($hours > 0) {
    $formatted_time .= $hours . " hr ";
}

$formatted_time .= $minutes . " min";

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
    max-width: 900px;
    margin: auto;
    padding: 25px;
    border-radius: 10px;
    border: 2px dashed #000;
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
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
}

.timeline-item {
    display: flex;
    justify-content: space-between;
    border-bottom: 1px dashed #ddd;
    padding: 6px 0;
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

@media print {

    body * {
        visibility: hidden;
    }

    #print-area,
    #print-area * {
        visibility: visible;
    }

    .print-btn,
    .btn {
        display: none !important;
    }
}

</style>

<div class="row mb-3">
    <div class="col-3">
        <a href="../audit-dashboard/" class="btn btn-primary">
            Back to Dashboard
        </a>
    </div>
</div>

<div id="print-area">

<div class="receipt">

<button class="print-btn" onclick="window.print()">
    PRINT RECEIPT
</button>

<h2 style="text-align:center;">
    AUDIT RECEIPT
</h2>

<div class="line"></div>

<div class="summary-grid">

    <div class="summary-card">
        <small>Expected Qty</small>
        <strong>
            <?php echo number_format($expected_qty); ?>
        </strong>
    </div>

    <div class="summary-card">
        <small>Variance</small>

        <strong style="color: <?php echo $variance_badge; ?>;">

            <?php
            if ($variance_qty > 0) {
                echo "+" . number_format($variance_qty);
            } else {
                echo number_format($variance_qty);
            }
            ?>

        </strong>

        <div style="margin-top:5px; font-size:12px;">
            <?php echo $variance_label; ?>
        </div>
    </div>

    <div class="summary-card">
        <small>Total Scanned</small><br>
        <strong><?php echo number_format($total); ?></strong>
    </div>

    <div class="summary-card">
        <small>Unique Products</small><br>
        <strong><?php echo number_format(count($grouped)); ?></strong>
    </div>

    <div class="summary-card">
        <small>Active Audit Time</small><br>
        <strong><?php echo $formatted_time; ?></strong>
    </div>

    <div class="summary-card">
        <small>Paused Time</small><br>
        <strong><?php echo $formatted_pause; ?></strong>
    </div>

</div>

<div class="line"></div>

<h4 style="margin-bottom:15px;">
    BARCODE DETAILED SUMMARY
</h4>

<div style="overflow-x:auto; margin-bottom:20px;">

<table style="
    width:100%;
    border-collapse: collapse;
    font-size: 12px;
">

<thead>

<tr style="background:#f1f1f1;">

    <th style="border:1px solid #ccc; padding:8px;">
        Barcode
    </th>

    <th style="border:1px solid #ccc; padding:8px;">
        In System
    </th>

    <th style="border:1px solid #ccc; padding:8px;">
        Outbounded
    </th>

    <th style="border:1px solid #ccc; padding:8px;">
        Other WH
    </th>

    <th style="border:1px solid #ccc; padding:8px;">
        Other Location
    </th>

</tr>

</thead>

<tbody>

<?php foreach ($detailed_summary as $summary): ?>

<tr>

    <td style="border:1px solid #ccc; padding:8px;">
        <?php echo htmlspecialchars($summary['barcode']); ?>
    </td>

    <td style="border:1px solid #ccc; padding:8px; text-align:center;">
        <?php echo $summary['system_stock']; ?>
    </td>

    <td style="border:1px solid #ccc; padding:8px; text-align:center;">
        <?php echo $summary['outbounded']; ?>
    </td>

    <td style="border:1px solid #ccc; padding:8px; text-align:center;">
        <?php echo $summary['other_warehouse']; ?>
    </td>

    <td style="border:1px solid #ccc; padding:8px; text-align:center;">
        <?php echo $summary['other_location']; ?>
    </td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<div class="line"></div>

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

    <div style="margin-top:8px;">
        <strong>Sequences:</strong>
        <?php echo htmlspecialchars($seqList); ?>
    </div>

    <?php if ($info): ?>

    <div style="margin-top:8px; font-size:12px;">

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

    </div>

    <?php endif; ?>

</div>

<?php endforeach; ?>

<div class="line"></div>

<h4>
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

<?php 
if($location_status === "for_approval"){
?>
<div class="row mt-3">
    <div class="col-12 text-center">
        <a href="approve.php" id="btnApprove" class="btn btn-success">Approved</a>
        <a href="#" id="btnReject" class="btn btn-danger ms-3">Reject</a>
    </div>
</div>
<?php
}
?>

</div>