<?php

include "../config/database.php";
include "../config/on_session.php";

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
$warehouse_name_audit = $audit['warehouse_name'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_POST['barcode'])) {
        die("No barcode provided.");
    }

    $barcode = trim($_POST['barcode']);

    if (empty($barcode)) {
        die("Barcode is empty.");
    }

    
    $selected_area = $_SESSION['selected_area'];

    $audit_assignment_query = "SELECT id FROM audit_assignments WHERE item_location = ? AND audit_id = ? ORDER BY id DESC LIMIT 1";

    $stmt = $conn->prepare($audit_assignment_query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $selected_area, $audit_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $assignment_data = $result->fetch_assoc();

    $stmt->close();

    $audit_assignment_id = $assignment_data['id'] ?? null;

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
        die("Barcode not found in stocks.");
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
        AND (audit_status = 'scanned' OR audit_status = 'approved')
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
        die("Barcode already scanned. Location: " . $location_name);
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
            $warehouse_id_audit,
            $selected_area
        );
        $stmt_items->execute();
        $stmt_items->close();
    }

    $update_items_to_audit = "
        UPDATE items_to_audit
        SET 
            audit_assignment_id = '$audit_assignment_id',
            user_id = '$user_id',
            audit_status = 'scanned',
            scanned_date = NOW(), 
            item_location_origin = '$stock_item_location', 
            item_location_onscanned = '$selected_area', 
            outbounded = '$outbounded_option', 
            warehouse_origin = '$stock_warehouse', 
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
    $timeline_action = "Item was audited (date: {$audit_date}) by {$user_fullname}.";

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
        "sssi",
        $barcode,
        $timeline_title,
        $timeline_action,
        $user_id
    );

    if (!$timeline_stmt->execute()) {
        die("Timeline insert failed: " . $timeline_stmt->error);
    }

    $timeline_stmt->close();

    
    echo "Scan successful for barcode: " . htmlspecialchars($barcode);
    $stmt_update->close();


    
    
    
}

?>