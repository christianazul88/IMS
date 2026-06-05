<?php
include "../config/database.php";

$audit_id = $_GET['audit_id'];

header('Content-Type: application/json');

// scanned
$scanned = $conn->query("
    SELECT COUNT(*) total 
    FROM items_to_audit 
    WHERE audit_id='$audit_id' AND audit_status='scanned'
")->fetch_assoc()['total'];

// outbounded
$out = $conn->query("
    SELECT COUNT(*) total 
    FROM items_to_audit 
    WHERE audit_id='$audit_id' 
    AND outbounded='yes' 
    AND audit_status='scanned'
")->fetch_assoc()['total'];

// expected
$expected = $conn->query("
    SELECT total_expected_qty 
    FROM audit_logs 
    WHERE id='$audit_id'
")->fetch_assoc()['total_expected_qty'];

echo json_encode([
    "scanned" => $scanned,
    "outbounded" => $out,
    "expected" => $expected
]);