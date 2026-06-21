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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_POST['barcode'])) {
        die("No barcode provided.");
    }

    $barcode = trim($_POST['barcode']);

    if (empty($barcode)) {
        die("Barcode is empty.");
    }

    
    $selected_area = $_SESSION['selected_area'];

    $audit_assignment_query = "SELECT id FROM audit_assignments WHERE item_location = ? LIMIT 1";

    $stmt = $conn->prepare($audit_assignment_query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $selected_area);
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

    $check_scanned_query = "
        SELECT id
        FROM items_to_audit
        WHERE audit_id = ?
        AND unique_barcode = ?
        AND audit_status = 'scanned'
        LIMIT 1
    ";

    $stmt_check = $conn->prepare($check_scanned_query);
    $stmt_check->bind_param("is", $audit_id, $barcode);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $already_scanned = true;
    }

    $stmt_check->close();

    if ($already_scanned) {
        die("Barcode already scanned.");
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
        echo "Item dont exist on missing items: " . htmlspecialchars($barcode);
        $stmt_update->close();
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
    echo "Scan successful for barcode: " . htmlspecialchars($barcode);
    $stmt_update->close();
    
    
}

?>