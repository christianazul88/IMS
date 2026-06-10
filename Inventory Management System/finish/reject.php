<?php
include "../config/database.php";
include "../config/on_session.php";

$audit_id = $_SESSION['audit_id'];
$audit_assignment_id = $_GET['id'];
$staff_id = $_GET['user'];
$selected_area = $_GET['area'];
$barcodes = [];

$barcode_query = "
    SELECT  unique_barcode AS barcode,
            warehouse_origin,
            warehouse_onscanned,
            item_location_origin,
            item_location_onscanned,
            outbounded
    FROM items_to_audit
    WHERE audit_id = ?
        AND audit_assignment_id = ?
        AND user_id = ?
    ORDER BY scanned_date ASC
";

$stmt = $conn->prepare($barcode_query);
$stmt->bind_param("iii", $audit_id, $audit_assignment_id, $staff_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $barcodes[] = $row;
}

$stmt->close();

foreach ($barcodes as $row) {

    $unique_barcode = $row['barcode'];
    $warehouse_origin = $row['warehouse_origin'];
    $item_location_origin = $row['item_location_origin'];
    $outbounded = $row['outbounded'];

    $update_items_to_audit = "
        UPDATE items_to_audit
        SET
            audit_assignment_id = NULL,
            user_id = NULL,
            warehouse_onscanned = NULL,
            item_location_onscanned = NULL,
            warehouse_origin = ?,
            item_location_origin = ?,
            audit_status = 'pending',
            scanned_date = NULL
        WHERE unique_barcode = ?
        AND audit_id = ?
    ";

    $stmt = $conn->prepare($update_items_to_audit);
    $stmt->bind_param(
        "sssi",
        $warehouse_origin,
        $item_location_origin,
        $unique_barcode,
        $audit_id
    );
    $stmt->execute();
    $stmt->close();
}

$update_audit_assignment_staffs = "
    UPDATE audit_assignment_staffs
    SET `status` = 'rejected'
    WHERE audit_assignments_id = ?
    AND user_id = ?
";

$stmt = $conn->prepare($update_audit_assignment_staffs);
$stmt->bind_param("ii", $audit_assignment_id, $staff_id);
$stmt->execute();
$stmt->close();

$get_location_name_query = "SELECT location_name FROM item_location WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($get_location_name_query);
$stmt->bind_param("i", $selected_area);
$stmt->execute();
$result = $stmt->get_result();
$location_name = "N/A";
if ($row = $result->fetch_assoc()) {
    $location_name = $row['location_name'];
}


$title = "Audit Rejected for " . htmlspecialchars($location_name);
$message = "Your audit for " . htmlspecialchars($location_name) . " has been rejected. Please approach your supervisor for more details. rejected by " . htmlspecialchars($user_fullname) . ".";

$insert_notification = "INSERT INTO notification (to_userid, title, `message`, `date`, `status`) VALUES (?, ?, ?, NOW(), 0)";
$stmt = $conn->prepare($insert_notification);
$stmt->bind_param("sss", $staff_id, $title, $message);
$stmt->execute();
$stmt->close();

header("Location: ../finish/?audit_id=" . $audit_id . "&area=" . $selected_area . "&user_id=" . $staff_id);
exit;
?>