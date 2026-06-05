<?php
include '../config/database.php';
include '../config/on_session.php';

if (!isset($_POST['location_id']) || !isset($_POST['staffs'])) {
    die("Missing data.");
}

$audit_assignment_id = (int)$_POST['location_id'];
$staffs = $_POST['staffs'];

$check = $conn->prepare("
    SELECT id
    FROM audit_assignment_staffs
    WHERE audit_assignments_id = ?
    AND user_id = ?
");

$insert = $conn->prepare("
    INSERT INTO audit_assignment_staffs
    (
        audit_assignments_id,
        user_id,
        date_assigned,
        `status`
    )
    VALUES (?, ?, NOW(), 'idle')
");

foreach ($staffs as $staff_id) {

    if (empty($staff_id)) {
        continue;
    }

    $check->bind_param(
        "is",
        $audit_assignment_id,
        $staff_id
    );

    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        continue;
    }

    $insert->bind_param(
        "is",
        $audit_assignment_id,
        $staff_id
    );

    $insert->execute();
}

header("Location: ../audit-dashboard/?success=true");
exit;