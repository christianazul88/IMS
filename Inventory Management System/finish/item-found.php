<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '4G');

include "../config/database.php";
include "../config/on_session.php";
$audit_id = $_SESSION['audit_id'];

$audit_assignment_id = $_GET['audit_assignment_id'] ?? '';
$area = $_GET['location'] ?? '';
$barcode = $_GET['barcode'] ?? '';
$staff_id = $_GET['staff_id'] ?? '';
$warehouse_onscaned = $_GET['warehouse'] ?? '';

// echo "Audit Assignment ID: " . $audit_assignment_id . "<br>";
// echo "Location: " . $area . "<br>";
// echo "Barcode: " . $barcode . "<br>";
// echo "Staff ID: " . $staff_id . "<br>";
// echo "Warehouse: " . $warehouse_onscaned . "<br>";

$update_query = "UPDATE items_to_audit SET audit_status = 'scanned', user_id = ?, warehouse_origin = ?, warehouse_onscanned = ?, item_location_origin = ?, item_location_onscanned = ?, scanned_date = NOW(), audit_assignment_id = ? WHERE unique_barcode = ? AND audit_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("sssiiisi", $staff_id, $warehouse_onscaned, $warehouse_onscaned, $area, $area, $audit_assignment_id, $barcode, $audit_id);
if ($stmt->execute()) {
    echo "Item successfully marked as scanned.";
} else {
    http_response_code(500);
    echo "Error updating item: " . $stmt->error;
}
$stmt->close();
