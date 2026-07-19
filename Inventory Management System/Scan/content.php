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
} elseif ($audit['audit_status'] != 'active' && $audit['audit_status'] != 'partially_completed') {
    echo "<div class='alert alert-info'>Audit status: " . ucfirst($audit['audit_status']) . "</div>";
    exit;
}




$warehouse_id_audit = $audit['warehouse'];

if ((isset($_POST['area']) && $_POST['area'] === 'others') || (isset($_GET['area']) && $_GET['area'] == 'others')) {
    if(isset($_SESSION['NEW_LOCATION_NAME'])){
        $location_combo = $_SESSION['NEW_LOCATION_NAME'];
    }elseif(isset($_GET['loc_combo'])){
        $location_combo = $_GET['loc_combo'];
    } else {
        $location_combo = $_POST['area_code'] . "-" . $_POST['rack'] . "-" . $_POST['level'] . "-" . $_POST['box_num'];
    }

    $location_name = trim($location_combo);
    

    if ($location_name == '') {
        die('Location name is required.');
    }

    // Check if item location already exists
    $stmt = $conn->prepare("
        SELECT id
        FROM item_location
        WHERE location_name = ? AND warehouse = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $location_name, $warehouse_id_audit);
    $stmt->execute();
    $existing_location = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing_location) {

        $item_location_id = $existing_location['id'];

    } else {

        $stmt = $conn->prepare("
            INSERT INTO item_location (
                location_name,
                warehouse
            )
            VALUES (?, ?)
        ");
        $stmt->bind_param("ss", $location_name, $warehouse_id_audit);
        $stmt->execute();

        $item_location_id = $stmt->insert_id;
        $stmt->close();
    }

    // Check if audit assignment already exists
    $stmt = $conn->prepare("
        SELECT id
        FROM audit_assignments
        WHERE audit_id = ?
        AND item_location = ?
        AND warehouse = ?
        LIMIT 1
    ");
    $stmt->bind_param(
        "iis",
        $audit_id,
        $item_location_id,
        $warehouse_id_audit
    );
    $stmt->execute();
    $existing_assignment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing_assignment) {

        $audit_assignment_id = $existing_assignment['id'];

        

    } else {

        $stmt = $conn->prepare("
            INSERT INTO audit_assignments (
                audit_id,
                item_location,
                warehouse
            )
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param(
            "iis",
            $audit_id,
            $item_location_id,
            $warehouse_id_audit
        );
        $stmt->execute();

        $audit_assignment_id = $stmt->insert_id;
        $stmt->close();
    }

    // Check if staff is already assigned
    $stmt = $conn->prepare("
        SELECT id
        FROM audit_assignment_staffs
        WHERE audit_assignments_id = ?
        AND user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param(
        "is",
        $audit_assignment_id,
        $user_id
    );
    $stmt->execute();
    $existing_staff = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing_staff) {

        $stmt = $conn->prepare("
            INSERT INTO audit_assignment_staffs (
                audit_assignments_id,
                user_id,
                status
            )
            VALUES (?, ?, 'idle')
        ");
        $stmt->bind_param(
            "is",
            $audit_assignment_id,
            $user_id
        );
        $stmt->execute();
        $stmt->close();
    }
    

    $selected_area = $item_location_id;

} else {

    $selected_area = $_POST['area'];


}
    

    
    $_SESSION['selected_area'] = $selected_area;



    $get_neccessary_info_query = "SELECT location_name FROM item_location WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($get_neccessary_info_query);
    $stmt->bind_param("i", $selected_area);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        
    } else {
        $area_info = $result->fetch_assoc();
        $selected_area_name = $area_info['location_name'];
        $item_location_id = $selected_area;


        // Check if audit assignment already exists
        $stmt = $conn->prepare("
            SELECT id
            FROM audit_assignments
            WHERE audit_id = ?
            AND item_location = ?
            AND warehouse = ?
            LIMIT 1
        ");
        $stmt->bind_param(
            "iis",
            $audit_id,
            $item_location_id,
            $warehouse_id_audit
        );
        $stmt->execute();
        $existing_assignment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing_assignment) {

            $audit_assignment_id = $existing_assignment['id'];

            

        } else {

            $stmt = $conn->prepare("
                INSERT INTO audit_assignments (
                    audit_id,
                    item_location,
                    warehouse
                )
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param(
                "iis",
                $audit_id,
                $item_location_id,
                $warehouse_id_audit
            );
            $stmt->execute();

            $audit_assignment_id = $stmt->insert_id;
            $stmt->close();
        }

        // Check if staff is already assigned
        $stmt = $conn->prepare("
            SELECT id
            FROM audit_assignment_staffs
            WHERE audit_assignments_id = ?
            AND user_id = ?
            LIMIT 1
        ");
        $stmt->bind_param(
            "is",
            $audit_assignment_id,
            $user_id
        );
        $stmt->execute();
        $existing_staff = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$existing_staff) {

            $stmt = $conn->prepare("
                INSERT INTO audit_assignment_staffs (
                    audit_assignments_id,
                    user_id,
                    status
                )
                VALUES (?, ?, 'idle')
            ");
            $stmt->bind_param(
                "is",
                $audit_assignment_id,
                $user_id
            );
            $stmt->execute();
            $stmt->close();
        } else {
            $audit_assignment_staff_id = $existing_staff['id'];
            $update_assignment_query = "
                UPDATE audit_assignment_staffs
                SET status = 'idle'
                WHERE id = ?
            ";

            $stmt = $conn->prepare($update_assignment_query);
            $stmt->bind_param("i", $audit_assignment_staff_id);
            $stmt->execute();
            $stmt->close();
        }
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

    // echo $audit_assignment_id;
?>

<div class="card bg-primary text-white mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-10">
                <h5 class="card-title">Audit Dashboard</h5>
                <p class="card-text" id="title">Audit #<?php echo $audit['audit_num']; ?> - for area <?php echo $selected_area_name; ?></p>
            </div>
            <div class="col-2 text-end">
                <button class="btn btn-light fs-11" type="button" data-bs-toggle="modal" data-bs-target="#error-modal">Update Location</button>
                <button class="btn btn-info fs-11" type="button" data-bs-toggle="modal" data-bs-target="#batch-modal">Batch Scan</button>
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



<div class="modal fade" id="error-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
    <div class="modal-content position-relative">
        <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="update-location-form" method="POST" action="update_location.php">
        <div class="modal-body p-0">
            <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
            <h4 class="mb-1" id="modalExampleDemoLabel">Update Location/Area Name of <?php echo htmlspecialchars($selected_area_name); ?></h4>
            </div>
            <div class="p-4 pb-0">
                <div class="mb-3">
                    <label class="col-form-label" for="recipient-name">Location/ Area Name:</label>
                    <input class="form-control"
                    id="location-name"
                    name="location_name"
                    type="text"
                    value="<?php echo htmlspecialchars($selected_area_name); ?>" />
                    <input type="hidden" name="location_id" value="<?= $selected_area ?>">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
            <button class="btn btn-primary" type="submit">Update Location</button>
        </div>
        
        </form>
    </div>
  </div>
</div>





<!-- batch scan modal -->
 <div class="modal fade" id="batch-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
    <div class="modal-content position-relative">
        <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
            <button class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="batch-scan" method="POST" action="batch-scan.php">
        <div class="modal-body p-0">
            <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
            <h4 class="mb-1" id="modalExampleDemoLabel">Scan batch of barcodes</h4>
            </div>
            <div class="row p-4 pb-0">
                <div class="col-12 mb-3">
                    <label class="col-form-label" for="recipient-name">Mother/Parent Barcode</label>
                    <input class="form-control"
                    id="parent-barcode"
                    name="parent-barcode"
                    type="text"
                    />
                    <input type="hidden" name="location_id" value="<?= $selected_area ?>">
                    <input type="hidden" name="audit_assignment_id" value="<?= $audit_assignment_id ?>">
                </div>
                <div class="col-6 mb-3">
                    <label for="from_sequence">From</label>
                    <input type="number" class="form-control" id="from_sequence" name="from_sequence">
                </div>
                <div class="col-6 mb-3">
                    <label for="to_sequence">To</label>
                    <input type="number" class="form-control" id="to_sequence" name="to_sequence">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
            <button class="btn btn-primary" type="submit">Submit</button>
        </div>
        
        </form>
    </div>
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
    // REMOVE ITEM
    // =====================================================
    $(document).on("click", ".remove-item", function(){

        let button = $(this);
        let itemId = button.data("id");

        $.ajax({
            url: "remove_item.php",
            type: "POST",
            data: {
                id: itemId
            },
            success: function(response){

                console.log(response);

                if(response.trim() === "successfully removed"){

                    // Option 1 - reload the receipt
                    loadReceipt();

                    // refresh count
                    $.getJSON("receipt-count.php", function(data){
                        lastCount = data.count;
                        $("#total-scanned").text(data.count);
                    });

                }else{

                    alert(response);

                }

            },
            error: function(){

                alert("Unable to contact server.");

            }

        });

    });


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



    document.getElementById('batch-scan').addEventListener('submit', async function(e) {

        e.preventDefault();

        const form = this;
        const formData = new FormData(form);

        const parentBarcode = document.getElementById('parent-barcode').value.trim();
        const fromSequence = document.getElementById('from_sequence').value.trim();
        const toSequence = document.getElementById('to_sequence').value.trim();

        if (!parentBarcode) {
            Swal.fire({
                icon: 'warning',
                title: 'Parent/Mother barcode is required!',
                text: 'Please enter a valid parent/mother barcode'
            });
            return;
        }

        if(!fromSequence){
            Swal.fire({
                icon: 'warning',
                title: 'Starting sequence is required!',
                text: 'Please enter a starting sequence'
            });
            return;
        }

        if(!toSequence){
            Swal.fire({
                icon: 'warning',
                title: 'Ending sequence is required!',
                text: 'Please enter an ending sequence'
            });
            return;
        }

        // First confirmation
        const firstConfirmation = await Swal.fire({
            title: 'Batch Scan Confirmation',
            html: `
                You are about to mark ${parentBarcode}-${fromSequence} <br><br>
                up to ${toSequence} as scanned. 
                This may affect audit records.
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false
        });

        if (!firstConfirmation.isConfirmed) {
            return;
        }

        // Second confirmation
        const secondConfirmation = await Swal.fire({
            title: 'Are you absolutely sure?',
            html: `
                any outbounded items from this batch will be returned automatically. 
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No'
        });

        if (!secondConfirmation.isConfirmed) {
            return;
        }

        Swal.fire({
            title: 'Submitting form...',
            text: 'Please wait.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const result = await response.text();
            loadReceipt();

            Swal.fire({
                icon: response.ok ? 'success' : 'error',
                title: response.ok ? 'Batch scanned successfully' : 'failed to submit form',
                html: result
            });

        

        } catch (error) {

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Unable to communicate with the server.'
            });

            console.error(error);
        }

    });

});

</script>


<script>
document.getElementById('update-location-form').addEventListener('submit', async function(e) {

    e.preventDefault();

    const form = this;
    const formData = new FormData(form);

    const oldLocation = <?php echo json_encode($selected_area_name); ?>;
    const newLocation = document.getElementById('location-name').value.trim();

    if (!newLocation) {
        Swal.fire({
            icon: 'warning',
            title: 'Location name required',
            text: 'Please enter a location name.'
        });
        return;
    }

    // First confirmation
    const firstConfirm = await Swal.fire({
        title: 'Update Location?',
        html: `
            You are about to rename:<br><br>
            <b>${oldLocation}</b><br>
            to<br>
            <b>${newLocation}</b>
            <br><br>
            This may affect audit records and staff assignments that reference this location.
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Update Location',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false
    });

    if (!firstConfirm.isConfirmed) {
        return;
    }

    // Second confirmation
    const secondConfirm = await Swal.fire({
        title: 'Are you absolutely sure?',
        html: `
            The location name <b>${oldLocation}</b> will be changed to
            <b>${newLocation}</b>.
            <br><br>
            Please confirm this is the correct location name.
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Save Changes',
        cancelButtonText: 'No'
    });

    if (!secondConfirm.isConfirmed) {
        return;
    }

    Swal.fire({
        title: 'Updating Location...',
        text: 'Please wait.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {

        const response = await fetch(form.action, {
            method: 'POST',
            body: formData
        });

        const result = await response.text();

        Swal.fire({
            icon: response.ok ? 'success' : 'error',
            title: response.ok ? 'Location Updated' : 'Update Failed',
            html: result
        });

        // Optional: update title in modal
        document.querySelector('#modalExampleDemoLabel').innerHTML =
            `Update Location/Area Name of ${newLocation}`;
        document.querySelector('#title').innerHTML = `Audit # - for area ${newLocation}`;

    } catch (error) {

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Unable to communicate with the server.'
        });

        console.error(error);
    }

});
</script>




<?php
if (isset($_SESSION['NEW_LOCATION_NAME'])) {
    $location = htmlspecialchars($_SESSION['NEW_LOCATION_NAME'], ENT_QUOTES, 'UTF-8');
?>
<script>
document.addEventListener("DOMContentLoaded", function () {

    Swal.fire({
        icon: 'warning',
        title: 'Audit Scanning in Progress',
        html: `
            <p>An audit scanning session is currently in progress.</p>
            <p><strong>Current Scanning Location:</strong><br>
            <span class="text-primary"><?= $location ?></span></p>
            <hr>
            <p>Please verify that you are physically at this location before continuing.</p>
            <p><strong>Are you currently at this location?</strong></p>
        `,
        showCancelButton: true,
        confirmButtonText: 'Yes, Continue Scanning',
        cancelButtonText: 'No, Go Back',
        allowOutsideClick: false,
        allowEscapeKey: false,
        reverseButtons: true
    }).then((result) => {
        if (!result.isConfirmed) {
            window.location.href = "../audit-dashboard/";
        }
    });

});
</script>
<?php
    $_SESSION['NEW_LOCATION_NAME'] = $location_name;
}
?>