<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include "../config/database.php";
include "../config/on_session.php";

header('Content-Type: application/json');

$audit_id = $_GET['audit_id'] ?? null;

if (!$audit_id) {
    echo json_encode(["error" => "Missing audit_id"]);
    exit;
}

/* =========================
   TOTALS
========================= */
$totals_query = "SELECT 
    SUM(expected_qty) as total_expected,
    SUM(scanned_qty) as total_scanned,
    SUM(scanned_outbounded_qty) as total_scanned_outbounded_qty,
    SUM(scanned_belong_to_other_wh) as total_scanned_belong_to_other_wh,
    SUM(scanned_belong_to_other_location) as total_scanned_belong_to_other_location,
    SUM(variance_qty) as total_variance_qty,
    SUM(variance_value) as total_variance_value
FROM audit_items 
WHERE audit_id = ?";

$stmt = $conn->prepare($totals_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();
$stmt->close();


$variance_query = "SELECT 
    SUM(CASE WHEN variance_qty > 0 THEN variance_qty ELSE 0 END) as positive_variance,
    SUM(CASE WHEN variance_qty < 0 THEN ABS(variance_qty) ELSE 0 END) as negative_variance,
    SUM(CASE WHEN variance_qty = 0 THEN 1 ELSE 0 END) as zero_variance
FROM audit_items 
WHERE audit_id = ?";

$stmt = $conn->prepare($variance_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$variance = $stmt->get_result()->fetch_assoc();
$stmt->close();



/* =========================
   ASSIGNMENTS
========================= */
$assignments_query = "SELECT aa.*, u.user_fname, u.user_lname, w.warehouse_name, il.location_name
FROM audit_assignments aa 
LEFT JOIN users u ON aa.user_id = u.hashed_id COLLATE utf8mb4_unicode_ci 
LEFT JOIN warehouse w ON aa.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci 
LEFT JOIN item_location il ON aa.item_location = il.id  
WHERE aa.audit_id = ?";

$stmt = $conn->prepare($assignments_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$res = $stmt->get_result();

$assignments = [];
while ($row = $res->fetch_assoc()) {
    $assignments[] = $row;
}
$stmt->close();

/* =========================
   RECENT SCANS
========================= */
$scans_query = "SELECT ita.*, ai.parent_barcode 
FROM items_to_audit ita 
LEFT JOIN audit_items ai 
ON ita.audit_id = ai.audit_id 
AND ita.unique_barcode = ai.parent_barcode 
WHERE ita.audit_id = ? 
ORDER BY ita.scanned_date DESC 
LIMIT 10";

$stmt = $conn->prepare($scans_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$res = $stmt->get_result();

$scans = [];
while ($row = $res->fetch_assoc()) {
    $scans[] = $row;
}
$stmt->close();

/* =========================
   OUTPUT JSON
========================= */
echo json_encode([
    "totals" => $totals,
    "assignments" => $assignments,
    "recent_scans" => $scans,
    "variance" => $variance
]);