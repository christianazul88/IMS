<?php
$audit_id = $_SESSION['audit_id'];

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

if ($audit['audit_status'] == 'pending') {
    // Show start modal
    echo "<script>document.addEventListener('DOMContentLoaded', function() { 
        const startModal = new bootstrap.Modal(document.getElementById('startAuditModal'));
        startModal.show();
    });</script>";
} elseif ($audit['audit_status'] != 'active') {
    echo "<div class='alert alert-info'>Audit status: " . ucfirst($audit['audit_status']) . "</div>";
    exit;
}


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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['area'])) {
        die("No area selected.");
    }

    $selected_area = $_POST['area'];
    $_SESSION['selected_area'] = $selected_area;



    $get_neccessary_info_query = "SELECT location_name FROM item_location WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($get_neccessary_info_query);
    $stmt->bind_param("i", $selected_area); 
    $stmt->execute();
    $area_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $selected_area_name = $area_info['location_name'];
}
?>

<div class="card bg-primary text-white mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-10">
                <h5 class="card-title">Audit Dashboard</h5>
                <p class="card-text">Audit #<?php echo $audit['audit_num']; ?> - for area <?php echo $selected_area_name; ?></p>
            </div>
        </div>
        
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">

        <!-- Success Alert -->
        <div id="scan-alert"></div>

        <!-- Scan Form -->
        <div id="scan-form"></div>

    </div>
</div>

<div class="card shadow-sm mt-3">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Live Scanned Items</h5>
            <span class="badge bg-primary" id="total-scanned">0</span>
        </div>

        <div id="receipt-container">

            <div class="text-center text-muted py-4">
                No scanned items yet.
            </div>

        </div>

    </div>
    <div class="card-footer text-end">
        <button type="button" id="btn-finish" class="btn btn-secondary">
            Area Complete
        </button>
    </div>
</div>


<script>

$(document).ready(function(){

    let lastCount = 0;

    loadSingleTransfer();
    loadReceipt();

    // =====================================================
    // HANDLE SCAN SUBMIT
    // =====================================================
    $(document).on("submit", "#scanner", function(e){

        e.preventDefault();

        $.post($(this).attr("action"), $(this).serialize(), function(response){

            // SUCCESS
            if(response.includes("Scan successful")){

                $("#scan-alert").html(`
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>SUCCESS:</strong> ${response}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);

            }

            // DENIED
            else if(response.includes("DENIED")){

                $("#scan-alert").html(`
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>DENIED:</strong> Barcode already scanned.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);

            }

            // OTHER
            else{

                $("#scan-alert").html(`
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        ${response}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `);

            }

            // RELOAD FORM
            loadSingleTransfer();

            // RELOAD RECEIPT
            loadReceipt();

            // AUTO HIDE ALERT
            setTimeout(function(){
                $(".alert").alert('close');
            }, 2000);

        });

    });

    // =====================================================
    // LOAD SCAN FORM
    // =====================================================
    function loadSingleTransfer(){

        $("#scan-form").load("scan-form.php", function(){

            $("#barcode").focus();

        });

    }

    // =====================================================
    // LOAD RECEIPT
    // =====================================================
    function loadReceipt(){

        $("#receipt-container").load("receipt-live.php");

    }

    // =====================================================
    // AUTO REFRESH RECEIPT EVERY 2 SECONDS
    // =====================================================
    setInterval(function(){

        $.getJSON("receipt-count.php", function(data){

            if(data.count != lastCount){

                lastCount = data.count;

                loadReceipt();

                $("#total-scanned").text(data.count);

            }

        });

    }, 2000);


    // =====================================================
    // AREA COMPLETE CONFIRMATION
    // =====================================================
    $(document).on("click", "#btn-finish", function () {

        Swal.fire({
            title: "Are you sure?",
            text: "This will mark the area as completed and you cannot scan more items.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, complete it",
            cancelButtonText: "Cancel",
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33"
        }).then((result) => {

            if (result.isConfirmed) {

                // redirect after confirmation
                window.location.href = "../finish/?audit_id=<?php echo $audit_id; ?>&area=<?php echo $selected_area; ?>";

            }

        });

    });

});

</script>