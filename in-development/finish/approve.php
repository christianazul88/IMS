<?php
include "../config/database.php";
include "../config/on_session.php";

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

$update_status_query = "UPDATE audit_assignments SET `status` = 'approved' WHERE audit_id = ? AND item_location = ?";
$stmt = $conn->prepare($update_status_query);
$stmt->bind_param("ii", $audit_id, $selected_area);
$stmt->execute();
$stmt->close();

header("Location: ../finish/?audit_id=" . $audit_id . "&area=" . $selected_area);
exit;
?>