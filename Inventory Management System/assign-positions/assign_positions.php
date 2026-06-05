<?php
require_once '../config/database.php'; // your connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Reusable function
    function assignUser($conn, $hashed_id, $audit_position)
    {
        // Check if already exists
        $check = $conn->prepare("
            SELECT 1
            FROM audit_users
            WHERE hashed_id = ?
            LIMIT 1
        ");
        $check->bind_param("s", $hashed_id);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        // Skip if exists
        if ($exists) {
            return;
        }

        // Insert new record
        $insert = $conn->prepare("
            INSERT INTO audit_users (
                hashed_id,
                audit_position
            ) VALUES (?, ?)
        ");
        $insert->bind_param("si", $hashed_id, $audit_position);
        $insert->execute();
        $insert->close();
    }

    // Management (audit_position = 1)
    if (!empty($_POST['manage']) && is_array($_POST['manage'])) {
        foreach ($_POST['manage'] as $hashed_id) {
            assignUser($conn, trim($hashed_id), 1);
        }
    }

    // Staff (audit_position = 2)
    if (!empty($_POST['staff']) && is_array($_POST['staff'])) {
        foreach ($_POST['staff'] as $hashed_id) {
            assignUser($conn, trim($hashed_id), 2);
        }
    }

    header("Location: ../audit-automation-module?successfully_assigned=true");
    exit;
}