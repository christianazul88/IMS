<?php
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '4G');
ini_set('display_errors', 1);
ini_set('pcre.backtrack_limit', '10000000');


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
$warehouse_name_audit = $audit['warehouse_name'];
?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="bi bi-upload me-2"></i>
            Barcode Injection Upload
        </h5>
    </div>

    <div class="card-body">

        <div class="alert alert-info">
            <strong>CSV Format Required:</strong>
            <br>
            <code>barcode, area_location, email</code>
            <br>
            Example:
            <pre class="mb-0 mt-2">109231-1,WS2_10,sample@email.com
109231-2,WS2_10,sample@email.com</pre>
        </div>

        <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <label class="form-label fw-semibold">
                    CSV File
                </label>
                <input
                    type="file"
                    name="csv_file"
                    class="form-control"
                    accept=".csv"
                    required
                >
                <small class="text-muted">
                    Upload a CSV file containing barcodes to be injected into the audit system.
                </small>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" name="upload" class="btn btn-primary">
                    <i class="bi bi-cloud-upload me-1"></i>
                    Upload CSV
                </button>
            </div>

        </form>

    </div>
</div>

<?php

$success = [];
$errors = [];
$warnings = [];




if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {

    $file = $_FILES['csv_file']['tmp_name'];

    $grouped = [];

    if (($handle = fopen($file, "r")) !== false) {

        // Skip header row
        fgetcsv($handle);

        while (($row = fgetcsv($handle, 1000, ",")) !== false) {

            $barcode       = trim($row[0]);
            $area_location = trim($row[1]);
            $email         = trim($row[2]);

            // Group by area location
            $grouped[$area_location][] = [
                'barcode' => $barcode,
                'email'   => $email
            ];
        }

        $staff_details_query = "
            SELECT 
                hashed_id AS staff_id
            FROM users
            WHERE email = '$email'
            LIMIT 1
        ";

        $staff_details_result = $conn->query($staff_details_query);
        if($staff_details_result->num_rows>0){
            $row=$staff_details_result->fetch_assoc();
            $staff_id = $row['staff_id'];
        }

        fclose($handle);

        // Sort area locations alphabetically
        ksort($grouped);

        // Process each area
        foreach ($grouped as $area_location => $items) {


            

            
            



            $get_neccessary_info_query = "SELECT id FROM item_location WHERE location_name = ? LIMIT 1";
            $stmt = $conn->prepare($get_neccessary_info_query);
            $stmt->bind_param("s", $area_location);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $stmt = $conn->prepare("
                        INSERT INTO item_location (
                            location_name,
                            warehouse
                        )
                        VALUES (?, ?)
                    ");
                    $stmt->bind_param(
                        "ss",
                        $area_location,
                        $warehouse_id_audit
                    );
                    $stmt->execute();

                    $selected_area_name = $area_location;
                    $item_location_id = $stmt->insert_id;
                    $selected_area = $item_location_id;
                    $_SESSION['selected_area'] = $selected_area;
                    $stmt->close();
            } else {
                $area_info = $result->fetch_assoc();
                 $selected_area_name = $area_location;
                $item_location_id = $area_info['id'];

                $selected_area = $item_location_id;
                $_SESSION['selected_area'] = $selected_area;
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
                $staff_id
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
                    VALUES (?, ?, 'for_approval')
                ");
                $stmt->bind_param(
                    "is",
                    $audit_assignment_id,
                    $staff_id
                );
                $stmt->execute();
                $stmt->close();
            } else {
                $audit_assignment_staff_id = $existing_staff['id'];
                $update_assignment_query = "
                    UPDATE audit_assignment_staffs
                    SET status = 'for_approval'
                    WHERE id = ?
                ";

                $stmt = $conn->prepare($update_assignment_query);
                $stmt->bind_param("i", $audit_assignment_staff_id);
                $stmt->execute();
                $stmt->close();
            }
            
                



           

            foreach ($items as $item) {

                $barcode = $item['barcode'];
                // ==============================================================================================
                // ==============================================================================================
                // == barcode insertion start ===================================================================
                // ==============================================================================================
                // ==============================================================================================

                if (empty($barcode)) {
                    $errors[] = [
                        'barcode' => '-',
                        'message' => 'Empty barcode.'
                    ];
                }

                
                $selected_area = $_SESSION['selected_area'];

                

                // =========================================================
                // GET BARCODE DETAILS FROM DATABASE
                // =========================================================
                $stmt = $conn->prepare("
                    SELECT 
                        item_location,
                        supplier,
                        item_status,
                        warehouse
                    FROM stocks
                    WHERE unique_barcode = ?
                    LIMIT 1
                ");

                $stmt->bind_param("s", $barcode);
                $stmt->execute();

                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    $errors[] = [
                        'barcode' => $barcode,
                        'message' => 'Barcode not found in stocks.'
                    ];
                    continue;
                }

                $stock_data = $result->fetch_assoc();

                // Assign each column to its own variable
                $stock_item_location = $stock_data['item_location'];
                $stock_supplier      = $stock_data['supplier'];
                $stock_item_status   = $stock_data['item_status'];
                $stock_warehouse     = $stock_data['warehouse'];
                // $additional_query = "";
                $outbounded_option = "no";

                
                // if($selected_area !== $stock_item_location){
                //     $additional_query .= ", item_location_origin = '$stock_item_location', item_location_onscanned = '$selected_area'";
                // } 

                // Check if barcode is already scanned in items_to_audit
                $already_scanned = false;
                $location_onscan = NULL;

                // check if na audit na yung item
                // $check_if_audited_query = "
                //     SELECT audit_id 
                //     FROM items_to_audit
                //     WHERE scanned_date >= NOW() - INTERVAL 30 DAY
                //     AND unique_barcode = ?
                //     AND (audit_status = 'scanned' OR audit_status = 'approved')
                //     LIMIT 1
                // ";

                // $stmt_checkaudit = $conn->prepare($check_if_audited_query);
                // $stmt_checkaudit ->bind_param("s", $barcode);
                // $stmt_checkaudit->execute();

                // $result = $stmt_checkaudit->get_result();

                // if($row = $result->fetch_assoc()) {
                //     $already_scanned = true;
                //     $audit_id_onscanned = $row['audit_id'];
                // }

                // $stmt_checkaudit->close();

                // if($already_scanned) {
                //     die("Barcode already audited on Audit # " . $audit_id_onscanned);
                // }

                // -----check if scanned na on the same audit
                $check_scanned_query = "
                    SELECT id, item_location_onscanned
                    FROM items_to_audit
                    WHERE audit_id = ?
                    AND unique_barcode = ?
                    AND audit_status IN ('scanned','approved','outbounded')
                ";


                $stmt_check = $conn->prepare($check_scanned_query);
                $stmt_check->bind_param("is", $audit_id, $barcode);
                $stmt_check->execute();

                $result = $stmt_check->get_result();

                if ($row = $result->fetch_assoc()) {
                    $already_scanned = true;
                    $location_onscan = $row['item_location_onscanned'];
                }

                $stmt_check->close();

                if ($already_scanned) {
                    $stmt_location = $conn->prepare("SELECT location_name FROM item_location WHERE id = ?");
                    $stmt_location->bind_param("i", $location_onscan);
                    $stmt_location->execute();
                    $location_result = $stmt_location->get_result();
                    $location_name = $location_result->fetch_assoc()['location_name'];
                    $warnings[] = [
                        'barcode' => $barcode,
                        'message' => 'Already scanned',
                        'location' => $location_name
                    ];
                    continue;

                }

                $needsInsert = false;

                if ($stock_item_status != 0) {
                    $outbounded_option = "yes";
                    $needsInsert = true;
                }

                if ($stock_warehouse !== $warehouse_id_audit) {
                    $needsInsert = true;
                }

                // If barcode doesn't exist in items_to_audit yet, create it
                $check_existing_query = "
                    SELECT id
                    FROM items_to_audit
                    WHERE audit_id = ?
                    AND unique_barcode = ?
                    LIMIT 1
                ";

                $stmt_existing = $conn->prepare($check_existing_query);
                $stmt_existing->bind_param("is", $audit_id, $barcode);
                $stmt_existing->execute();
                $stmt_existing->store_result();

                $exists = $stmt_existing->num_rows > 0;

                $stmt_existing->close();

                if (!$exists) {

                    $check_existing_barcode_query = "
                        SELECT id, audit_id, warehouse_origin
                        FROM items_to_audit
                        WHERE unique_barcode = ?
                        AND audit_status = 'pending'
                        ORDER BY id DESC
                        LIMIT 1
                    ";

                    $stmt_on_other_audit = $conn->prepare($check_existing_barcode_query);
                    $stmt_on_other_audit->bind_param("s", $barcode);
                    $stmt_on_other_audit->execute();
                    // $stmt_on_other_audit->get_result();

                    $result = $stmt_on_other_audit->get_result();

                    // $stmt_on_other_audit->close();

                    if($result->num_rows == 0){

                        $insert_warehouse = NULL;

                        if($stock_warehouse === $warehouse_id_audit){
                            $stock_transfer_query = "
                                SELECT
                                    st.from_warehouse
                                FROM stock_transfer st
                                LEFT JOIN stock_transfer_content stc
                                    ON stc.st_id = st.id
                                WHERE stc.unique_barcode = '$barcode'
                                AND st.to_warehouse = '$warehouse_id_audit'
                                ORDER BY st.id DESC
                                LIMIT 1
                            ";
                            $stock_transfer_result = $conn->query($stock_transfer_query);
                            if($stock_transfer_result->num_rows>0){
                                $row=$stock_transfer_result->fetch_assoc();
                                $insert_warehouse = $row['from_warehouse'];
                            } else {
                                $insert_warehouse = '59e19706d51d39f66711c2653cd7eb1291c94d9b55eb14bda74ce4dc636d015a';
                            }
                        }

                        $insert_items_to_audit = "
                            INSERT INTO items_to_audit (
                                audit_id,
                                unique_barcode,
                                warehouse_origin,
                                item_location_origin,
                                audit_status
                            )
                            VALUES (?, ?, ?, ?, 'scanned')
                        ";

                        $stmt_items = $conn->prepare($insert_items_to_audit);
                        $stmt_items->bind_param(
                            "issi",
                            $audit_id,
                            $barcode,
                            $insert_warehouse,
                            $selected_area
                        );
                        $stmt_items->execute();
                        $stmt_items->close();

                    } else {
                        $row = $result->fetch_assoc();
                        $id_of_barcode = $row['id'];


                        $previous_audit_id = $row['audit_id'];
                        $update_to_current_audit = "
                            UPDATE items_to_audit
                            SET
                                audit_assignment_id = '$audit_assignment_id',
                                user_id = '$staff_id',
                                audit_status = 'scanned_on_other',
                                scanned_date = NOW(),
                                item_location_onscanned = '$selected_area',
                                outbounded = '$outbounded_option',
                                warehouse_onscanned = '$warehouse_id_audit'

                            WHERE audit_id = '$previous_audit_id'
                            AND id = '$id_of_barcode'
                        ";

                        $stmt_update_prevaudit = $conn->prepare($update_to_current_audit);

                        if (!$stmt_update_prevaudit) {
                            die("Prepare failed: " . $conn->error);
                        }

                        if(!$stmt_update_prevaudit->execute()) {
                            die("Prepare failed: " . $conn->error);
                        }


                        //insert to current audit
                        $insert_warehouse = NULL;

                        if($stock_warehouse === $warehouse_id_audit){
                            $stock_transfer_query = "
                                SELECT
                                    st.from_warehouse
                                FROM stock_transfer st
                                LEFT JOIN stock_transfer_content stc
                                    ON stc.st_id = st.id
                                WHERE stc.unique_barcode = '$barcode'
                                AND st.to_warehouse = '$warehouse_id_audit'
                                ORDER BY st.id DESC
                                LIMIT 1
                            ";
                            $stock_transfer_result = $conn->query($stock_transfer_query);
                            if($stock_transfer_result->num_rows>0){
                                $row=$stock_transfer_result->fetch_assoc();
                                $insert_warehouse = $row['from_warehouse'];
                            } else {
                                $insert_warehouse = '59e19706d51d39f66711c2653cd7eb1291c94d9b55eb14bda74ce4dc636d015a';
                            }
                        }

                        $insert_items_to_audit = "
                            INSERT INTO items_to_audit (
                                audit_id,
                                unique_barcode,
                                warehouse_origin,
                                item_location_origin,
                                audit_status
                            )
                            VALUES (?, ?, ?, ?, 'pending')
                        ";

                        $stmt_items = $conn->prepare($insert_items_to_audit);
                        $stmt_items->bind_param(
                            "issi",
                            $audit_id,
                            $barcode,
                            $insert_warehouse,
                            $selected_area
                        );
                        $stmt_items->execute();
                        $stmt_items->close();
                    }


                    
                }

                $update_items_to_audit = "
                    UPDATE items_to_audit
                    SET 
                        audit_assignment_id = '$audit_assignment_id',
                        user_id = '$staff_id',
                        audit_status = 'scanned',
                        scanned_date = NOW(), 
                        item_location_origin = '$stock_item_location', 
                        item_location_onscanned = '$selected_area', 
                        outbounded = '$outbounded_option', 
                        warehouse_onscanned = '$warehouse_id_audit'

                    WHERE audit_id = '$audit_id'
                    AND unique_barcode = '$barcode'
                ";

                $stmt_update = $conn->prepare($update_items_to_audit);

                if (!$stmt_update) {
                    die("Prepare failed: " . $conn->error);
                }

                if (!$stmt_update->execute()) {
                    die("Update failed: " . $stmt_update->error);
                }

                // Insert into stock_timeline
                $timeline_title = "Audited";
                $audit_date = date("M j, Y");
                $timeline_action = "Item was audited on WH: " . $warehouse_name_audit . " (date: {$audit_date}) by {$user_fullname}.";

                $timeline_stmt = $conn->prepare("
                    INSERT INTO stock_timeline (
                        unique_barcode,
                        title,
                        action,
                        date,
                        user_id
                    ) VALUES (?, ?, ?, NOW(), ?)
                ");

                if (!$timeline_stmt) {
                    die("Timeline prepare failed: " . $conn->error);
                }

                $timeline_stmt->bind_param(
                    "ssss",
                    $barcode,
                    $timeline_title,
                    $timeline_action,
                    $staff_id
                );

                if (!$timeline_stmt->execute()) {
                    die("Timeline insert failed: " . $timeline_stmt->error);
                }

                $timeline_stmt->close();

                
                $success[] = [
                    'barcode' => $barcode,
                    'location' => $selected_area_name
                ];
                $stmt_update->close();

                // ==============================================================================================
                // ==============================================================================================
                // == END OF barcode insertion===================================================================
                // ==============================================================================================
                // ==============================================================================================
            }
            // echo "<br><hr>";
        }
        ?>


        <div class="card shadow mt-4 border-0">

            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-clipboard-check me-2"></i>
                    Upload Results
                </h5>

                <div>
                    <span class="badge bg-success">
                        <?= count($success) ?> Success
                    </span>

                    <span class="badge bg-warning text-dark">
                        <?= count($warnings) ?> Warnings
                    </span>

                    <span class="badge bg-danger">
                        <?= count($errors) ?> Errors
                    </span>
                </div>
            </div>

            <div class="card-body">

                <?php if(count($success)){ ?>

                    <div class="alert alert-success">
                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            Successfully Imported
                        </h6>

                        <div class="table-responsive">
                            <table class="table table-sm table-success table-striped align-middle">
                                <thead>
                                <tr>
                                    <th width="40">#</th>
                                    <th>Barcode</th>
                                    <th>Assigned Location</th>
                                    <th>Status</th>
                                </tr>
                                </thead>

                                <tbody>

                                <?php foreach($success as $i=>$row){ ?>

                                    <tr>
                                        <td><?= $i+1 ?></td>

                                        <td>
                                            <code><?= htmlspecialchars($row['barcode']) ?></code>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($row['location']) ?>
                                        </td>

                                        <td>
                                            <span class="badge bg-success">
                                                Imported
                                            </span>
                                        </td>
                                    </tr>

                                <?php } ?>

                                </tbody>
                            </table>
                        </div>

                    </div>

                <?php } ?>


                <?php if(count($warnings)){ ?>

                    <div class="alert alert-warning">

                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Already Scanned
                        </h6>

                        <div class="table-responsive">

                            <table class="table table-sm table-warning table-striped">

                                <thead>

                                <tr>
                                    <th width="40">#</th>
                                    <th>Barcode</th>
                                    <th>Status</th>
                                    <th>Current Location</th>
                                </tr>

                                </thead>

                                <tbody>

                                <?php foreach($warnings as $i=>$row){ ?>

                                    <tr>

                                        <td><?= $i+1 ?></td>

                                        <td>
                                            <code><?= htmlspecialchars($row['barcode']) ?></code>
                                        </td>

                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                Already Scanned
                                            </span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($row['location']) ?>
                                        </td>

                                    </tr>

                                <?php } ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                <?php } ?>


                <?php if(count($errors)){ ?>

                    <div class="alert alert-danger">

                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-x-circle-fill me-1"></i>
                            Failed Imports
                        </h6>

                        <div class="table-responsive">

                            <table class="table table-sm table-danger table-striped">

                                <thead>

                                <tr>
                                    <th width="40">#</th>
                                    <th>Barcode</th>
                                    <th>Reason</th>
                                </tr>

                                </thead>

                                <tbody>

                                <?php foreach($errors as $i=>$row){ ?>

                                    <tr>

                                        <td><?= $i+1 ?></td>

                                        <td>
                                            <code><?= htmlspecialchars($row['barcode']) ?></code>
                                        </td>

                                        <td><?= htmlspecialchars($row['message']) ?></td>

                                    </tr>

                                <?php } ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                <?php } ?>

            </div>

        </div>
        <?php
        
    } else {
        echo "Unable to open CSV file.";
    }
}
?>