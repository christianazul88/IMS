<?php
include "database.php";
include "on_session.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $audit_id = filter_input(INPUT_POST, 'audit_id', FILTER_VALIDATE_INT);
    $schedule_date = filter_input(INPUT_POST, 'schedule_date', FILTER_SANITIZE_STRING);

    if (!$audit_id || empty($schedule_date)) {
        header('Location: ../audit-automation-module/?success=false');
        exit;
    }

    // Convert schedule_date to a datetime string if only date was provided
    $schedule_date = date('Y-m-d H:i:s', strtotime($schedule_date));

    $stmt = $conn->prepare("UPDATE audit_logs SET schedule_date = ?, updated_at = ?, updated_by = ? WHERE id = ?");
    $stmt->bind_param("sssi", $schedule_date, $currentDateTime, $user_id, $audit_id);

    if ($stmt->execute()) {
        header('Location: ../audit-automation-module/?success=true');
    } else {
        header('Location: ../audit-automation-module/?success=false');
    }

    $stmt->close();
    $conn->close();
    exit;
}

header('Location: ../audit-automation-module/?success=false');
exit;
