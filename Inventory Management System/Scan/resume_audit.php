<?php
include "../config/database.php";
include "../config/on_session.php";

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

$audit_info_query = "SELECT * FROM audit_assignments WHERE audit_id = ? AND item_location = ?";
$stmt = $conn->prepare($audit_info_query);
$stmt->bind_param("ii", $audit_id, $selected_area);
$stmt->execute();
$audit_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$location_status = $audit_info['status'];
$audit_assignment_id = $audit_info['id'];

if($location_status === "pending"){
    $update_status_query = "UPDATE audit_assignments SET `status` = 'in_progress' WHERE audit_id = ? AND item_location = ?";
    $stmt = $conn->prepare($update_status_query);
    $stmt->bind_param("ii", $audit_id, $selected_area);
    $stmt->execute();
    $stmt->close();
}


$check_audit_assignment_logs_query = "SELECT * FROM audit_assignment_logs WHERE audit_assignment_id = ? AND `status` = 'start' LIMIT 1";
$stmt = $conn->prepare($check_audit_assignment_logs_query);
$stmt->bind_param("i", $audit_assignment_id);
$stmt->execute();
$assignment_log = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(num_rows($assignment_log) === 0){
    $insert_log_query = "INSERT INTO audit_assignment_logs (audit_assignment_id, `status`, date_time, user_id) VALUES (?, 'start', NOW(), ?)";
    $stmt = $conn->prepare($insert_log_query);
    $stmt->bind_param("is", $audit_assignment_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

if(SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resume_audit'])){
    $resume_query = "INSERT INTO audit_assignment_logs (audit_assignment_id, `status`, date_time, user_id) VALUES (?, 'resume', NOW(), ?)";
    $stmt = $conn->prepare($resume_query);
    $stmt->bind_param("is", $audit_assignment_id, $user_id);
    $stmt->execute();
    $stmt->close();

    echo "resumed successfully";
}

?>