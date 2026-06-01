<?php
$audit_id = $_SESSION['audit_id'];


$audit_position_query = "SELECT audit_position FROM audit_users WHERE hashed_id = '$user_id'";
$audit_position_result = $conn->query($audit_position_query);
$audit_position = $audit_position_result->fetch_assoc()['audit_position'] ?? null;


$unfiltered_audit_assignment_id = $_GET['hash'];
preg_match('/^\d+/', $unfiltered_audit_assignment_id, $matches);
$audit_assignment_id = $matches[0] ?? '';

// LOCATION
$location_query = "
    SELECT item_location
    FROM audit_assignments
    WHERE id = '$audit_assignment_id'
    LIMIT 1
";
$location_result = mysqli_query($conn, $location_query);
$location_row = mysqli_fetch_assoc($location_result);
$location_id = $location_row['item_location'] ?? '';

// EXPECTED
$subtotal_query = "
    SELECT
        SUM(s.capital) AS total_expected_amount_forlocation,
        COUNT(ia.unique_barcode) AS total_scanned_qty_forlocation
    FROM items_to_audit ia
    LEFT JOIN stocks s ON s.unique_barcode = ia.unique_barcode
    WHERE ia.audit_assignment_id = '$audit_assignment_id' AND ia.outbounded = 'no'
";
$subtotal_result = mysqli_query($conn, $subtotal_query);
$subtotal_row = mysqli_fetch_assoc($subtotal_result);

$expected_amount = $subtotal_row['total_expected_amount_forlocation'] ?? 0;
$expected_qty    = $subtotal_row['total_scanned_qty_forlocation'] ?? 0;

// TOTALS
$total_scanned_amount = 0;
$total_scanned_qty = 0;

$total_outbounded_qty = 0;
$total_outbounded_amount = 0;

$total_wrong_warehouse_qty = 0;
$total_wrong_warehouse_amount = 0;

$total_wrong_location_qty = 0;
$total_wrong_location_amount = 0;

$total_positive_variance_qty = 0;
$total_positive_variance_amount = 0;

$total_pending_qty = 0;
$total_pending_amount = 0;

$pending_rows = [];
$rows = [];

// DATA
$scanned_query = "
    SELECT 
        p.description,
        b.brand_name,
        c.category_name,
        ia.warehouse_origin,
        ia.warehouse_onscanned,
        ia.item_location_origin,
        ia.item_location_onscanned,
        ia.unique_barcode,
        ia.scanned_date,
        ia.outbounded,
        s.capital
    FROM items_to_audit ia
    LEFT JOIN stocks s ON s.unique_barcode = ia.unique_barcode
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    WHERE ia.audit_assignment_id = '$audit_assignment_id'
";

$scanned_res = mysqli_query($conn, $scanned_query);

while ($r = mysqli_fetch_assoc($scanned_res)) {

    $capital = $r['capital'] ?? 0;

    $total_scanned_amount += $capital;
    $total_scanned_qty++;

    $is_outbounded = ($r['outbounded'] === "yes");
    $is_pending = empty($r['outbounded']) || $r['outbounded'] === 'pending';

    $is_wrong_warehouse = ($r['warehouse_origin'] !== $r['warehouse_onscanned']);
    $is_wrong_location  = ($r['item_location_origin'] !== $r['item_location_onscanned']);

    // OUTBOUNDED
    if ($is_outbounded) {
        $total_outbounded_qty++;
        $total_outbounded_amount += $capital;
    }

    // WRONG WAREHOUSE
    if (!$is_outbounded && $is_wrong_warehouse) {
        $total_wrong_warehouse_qty++;
        $total_wrong_warehouse_amount += $capital;
    }

    // WRONG LOCATION
    if (!$is_outbounded && $is_wrong_location) {
        $total_wrong_location_qty++;
        $total_wrong_location_amount += $capital;
    }

    // POSITIVE VARIANCE
    if ($is_outbounded || $is_wrong_warehouse || $is_wrong_location) {
        $total_positive_variance_qty++;
        $total_positive_variance_amount += $capital;
    }

    // PENDING
    if ($is_pending) {
        $total_pending_qty++;
        $total_pending_amount += $capital;
        $pending_rows[] = $r;
    }

    $rows[] = $r;
}

$variance_qty = $total_positive_variance_qty;
$variance_amount = $total_scanned_amount - $expected_amount;
?>

<style>
:root {
    --primary: #4f46e5;
    --success: #16a34a;
    --warning: #f59e0b;
    --danger: #ef4444;
    --dark: #111827;
    --muted: #6b7280;
}

/* PAGE */
/* body {
    background: #f6f7fb;
} */

/* SCANNER */
.scanner-bar {
    position: sticky;
    top: 0; 
    z-index: 999;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    padding: 12px;
    border-bottom: 1px solid #eee;
}

/* CARDS */
.stat-card {
    border: none;
    border-radius: 14px;
    transition: 0.2s ease-in-out;
    box-shadow: 0 4px 14px rgba(0,0,0,0.05);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}

.stat-title {
    font-size: 12px;
    color: var(--muted);
    letter-spacing: .5px;
    text-transform: uppercase;
}

.stat-value {
    font-size: 22px;
    font-weight: 700;
}

.stat-sub {
    font-size: 13px;
}

/* GRADIENTS */
.bg-primary-soft { background: linear-gradient(135deg,#eef2ff,#e0e7ff); }
.bg-success-soft { background: linear-gradient(135deg,#ecfdf5,#d1fae5); }
.bg-warning-soft { background: linear-gradient(135deg,#fffbeb,#fef3c7); }
.bg-danger-soft { background: linear-gradient(135deg,#fef2f2,#fee2e2); }
.bg-dark-soft { background: linear-gradient(135deg,#f3f4f6,#e5e7eb); }

/* TABLE */
.table-modern {
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 4px 14px rgba(0,0,0,0.04);
}

.table-modern thead {
    background: #111827;
    color: #fff;
}

.table-modern tbody tr:hover {
    background: #f3f4f6;
}

/* STATUS BADGES */
.badge-status {
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}

.badge-ok { background: #dcfce7; color: #166534; }
.badge-warn { background: #fef3c7; color: #92400e; }
.badge-danger { background: #fee2e2; color: #991b1b; }

/* TABS */
.nav-tabs .nav-link {
    border-radius: 10px;
    margin-right: 5px;
    color: #374151;
}

.nav-tabs .nav-link.active {
    background: var(--primary);
    color: #fff;
    border: none;
}
</style>

<div class="container-fluid py-3">

<!-- SCANNER -->
<div class="scanner-bar shadow-sm mb-3 d-flex justify-content-end align-items-center">
    
    <input type="text" id="barcodeInput" class="form-control form-control-lg me-3"
        placeholder="Scan barcode..." autofocus hidden>
    <?php if($audit_position == 1 || $user_position_name === "Administrator" || $user_position_name === "Superadmin") { ?>
    <button id="closeAuditBtn" class="btn btn-danger px-4">
        Close Audit
    </button>
    <?php } ?>

</div>

<!-- SUMMARY -->
<div class="row g-3 mb-3">

<?php
function card($title,$value,$sub,$class){
    echo "
    <div class='col-md-3'>
        <div class='card stat-card $class'>
            <div class='card-body'>
                <div class='stat-title'>$title</div>
                <div class='stat-value'>$value</div>
                <div class='stat-sub'>$sub</div>
            </div>
        </div>
    </div>";
}
?>

<?php card("Expected",$expected_qty,"₱".number_format($expected_amount,2),"bg-primary-soft"); ?>
<?php card("Scanned",$total_scanned_qty,"₱".number_format($total_scanned_amount,2),"bg-dark-soft"); ?>
<?php card("Outbounded",$total_outbounded_qty,"₱".number_format($total_outbounded_amount,2),"bg-success-soft"); ?>
<?php card("Wrong Warehouse",$total_wrong_warehouse_qty,"₱".number_format($total_wrong_warehouse_amount,2),"bg-warning-soft"); ?>
<?php card("Wrong Location",$total_wrong_location_qty,"₱".number_format($total_wrong_location_amount,2),"bg-warning-soft"); ?>
<?php card("Pending",$total_pending_qty,"₱".number_format($total_pending_amount,2),"bg-dark-soft"); ?>
<?php card("Positive Variance",$total_positive_variance_qty,"₱".number_format($total_positive_variance_amount,2),"bg-danger-soft"); ?>
<?php card("Net Variance",$variance_qty,"₱".number_format($variance_amount,2),"bg-primary-soft"); ?>

</div>

<!-- CHART -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <canvas id="varianceChart" height="80"></canvas>
    </div>
</div>

<!-- TABS -->
<ul class="nav nav-tabs">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#scanned">
            Scanned
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pending">
            Pending <span class="badge bg-danger"><?= $total_pending_qty ?></span>
        </button>
    </li>
</ul>

<div class="tab-content mt-2">

<!-- SCANNED -->
<div class="tab-pane fade show active" id="scanned">

<input id="searchScanned" class="form-control mb-2" placeholder="Search scanned...">

<div class="table-responsive table-modern">
<table id="scannedTable" class="table table-hover mb-0">
<thead>
<tr>
<th>Barcode</th>
<th>Description</th>
<th>Brand</th>
<th>Status</th>
<th>Capital</th>
</tr>
</thead>
<tbody>

<?php foreach ($rows as $r): ?>

<?php
$status = "Normal";
$badge = "badge-ok";

if ($r['outbounded'] === "yes") {
    $status = "Outbounded";
    $badge = "badge-ok";
}
elseif ($r['warehouse_origin'] !== $r['warehouse_onscanned'] ||
        $r['item_location_origin'] !== $r['item_location_onscanned']) {
    $status = "Mismatch";
    $badge = "badge-warn";
}
?>

<tr>
<td><?= $r['unique_barcode'] ?></td>
<td><?= $r['description'] ?></td>
<td><?= $r['brand_name'] ?></td>
<td><span class="badge-status <?= $badge ?>"><?= $status ?></span></td>
<td>₱<?= number_format($r['capital'],2) ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>
</div>

</div>

<!-- PENDING -->
<div class="tab-pane fade" id="pending">

<input id="searchPending" class="form-control mb-2" placeholder="Search pending...">

<div class="table-responsive table-modern">
<table id="pendingTable" class="table table-hover mb-0">
<thead>
<tr>
<th>Barcode</th>
<th>Description</th>
<th>Location</th>
<th>Capital</th>
</tr>
</thead>
<tbody>

<?php foreach ($pending_rows as $r): ?>
<tr>
<td><?= $r['unique_barcode'] ?></td>
<td><?= $r['description'] ?></td>
<td><?= $r['item_location_origin'] ?></td>
<td>₱<?= number_format($r['capital'],2) ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>

</div>

</div>
</div>

<!-- JS (UNCHANGED LOGIC) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
new Chart(document.getElementById('varianceChart'), {
    type: 'bar',
    data: {
        labels: ['Expected','Scanned','Pending','Variance'],
        datasets: [{
            data: [
                <?= $expected_qty ?>,
                <?= $total_scanned_qty ?>,
                <?= $total_pending_qty ?>,
                <?= $variance_qty ?>
            ]
        }]
    }
});

// SEARCH
function filter(input, table) {
    document.getElementById(input).addEventListener('keyup', function(){
        let v = this.value.toLowerCase();
        document.querySelectorAll(table+" tbody tr").forEach(r=>{
            r.style.display = r.innerText.toLowerCase().includes(v) ? "" : "none";
        });
    });
}
filter("searchScanned","#scannedTable");
filter("searchPending","#pendingTable");

// SCAN (UNCHANGED)
document.getElementById('barcodeInput').addEventListener('keydown', function(e){
    if(e.key === "Enter"){
        fetch("ajax-scan.php", {
            method:"POST",
            headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body:`barcode=${this.value}&audit_id=<?= $audit_assignment_id ?>`
        }).then(r=>r.json()).then(d=>{
            location.reload();
        });
        this.value="";
    }
});

document.getElementById("closeAuditBtn").addEventListener("click", function () {

    Swal.fire({
        title: "Close Audit?",
        text: "This action will finalize the audit and cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Yes, close it",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "close-audit.php?audit_id=<?= $audit_assignment_id ?>";
        }
    });

});
</script>