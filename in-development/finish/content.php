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
?>

<style>
body {
    font-family: monospace;
    background: #f5f5f5;
    padding: 20px;
}

.receipt {
    background: #fff;
    max-width: 550px;
    margin: auto;
    padding: 20px;
    border: 1px dashed #000;
}

.center {
    text-align: center;
}

.line {
    border-top: 1px dashed #000;
    margin: 10px 0;
}

.group {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px dashed #ccc;
}

.header {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
}

.sequences {
    font-size: 12px;
    color: #333;
    margin-top: 3px;
}

.product-info {
    font-size: 12px;
    color: #666;
    margin-top: 3px;
}

.btns {
    width: 100%;
    padding: 10px;
    background: green;
    color: white;
    border: none;
    cursor: pointer;
    margin-bottom: 10px;
}

@media print {

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

    .btn {
        display: none !important;
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

    <button class="btns" onclick="window.print()">PRINT RECEIPT</button>

    <div class="center">
        <h3>AUDIT RECEIPT</h3>
        <p>Area: <?php echo htmlspecialchars($selected_area); ?></p>
    </div>

    <div class="line"></div>

    <?php if (empty($grouped)): ?>

        <p class="center">No scanned items yet.</p>

    <?php else: ?>

        <?php foreach ($grouped as $parent => $data): ?>

            <?php $info = $product_cache[$parent] ?? null; ?>
            <?php $seqList = implode(", ", $data["sequences"]); ?>

            <div class="group">

                <div class="header">
                    <span><?php echo htmlspecialchars($parent); ?></span>
                    <span><?php echo $data["qty"]; ?> pcs</span>
                </div>

                <div class="sequences">
                    Sequences: <?php echo htmlspecialchars($seqList); ?>
                </div>

                <div class="product-info">
                    <?php if ($info): ?>
                        <?php echo htmlspecialchars(
                            $info['description'] . " | " .
                            $info['category_name'] . " | " .
                            $info['brand_name'] . " | $" .
                            number_format($info['capital'], 2)
                        ); ?>
                    <?php else: ?>
                        No product info found
                    <?php endif; ?>
                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

    <div class="line"></div>

    <div class="header">
        <span>Total Items</span>
        <span><?php echo $total; ?></span>
    </div>

    <p class="center" style="font-size:12px;color:#666;">
        Live Audit System
    </p>

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