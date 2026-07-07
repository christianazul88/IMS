<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate audit_id
    if (!isset($_POST['audit_id']) || !is_numeric($_POST['audit_id'])) {
        die("Invalid audit ID.");
    }

    $audit_id = (int) $_POST['audit_id'];

    /**
     * Assign a user to an audit if not already assigned.
     */
    function assignUser($conn, $hashed_id, $audit_position, $audit_id)
    {
        // Check if user is already assigned to this audit
        $check = $conn->prepare("
            SELECT 1
            FROM audit_users
            WHERE hashed_id = ?
              AND audit_id = ?
            LIMIT 1
        ");

        $check->bind_param("si", $hashed_id, $audit_id);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        // Skip if already exists
        if ($exists) {
            return;
        }

        // Insert new assignment
        $insert = $conn->prepare("
            INSERT INTO audit_users (
                hashed_id,
                audit_position,
                audit_id
            ) VALUES (?, ?, ?)
        ");

        $insert->bind_param("sii", $hashed_id, $audit_position, $audit_id);
        $insert->execute();
        $insert->close();
    }

    // Management (audit_position = 1)
    if (!empty($_POST['manage']) && is_array($_POST['manage'])) {
        foreach ($_POST['manage'] as $hashed_id) {
            $hashed_id = trim($hashed_id);

            if ($hashed_id !== '') {
                assignUser($conn, $hashed_id, 1, $audit_id);
            }
        }
    }

    // Staff (audit_position = 2)
    if (!empty($_POST['staff']) && is_array($_POST['staff'])) {
        foreach ($_POST['staff'] as $hashed_id) {
            $hashed_id = trim($hashed_id);

            if ($hashed_id !== '') {
                assignUser($conn, $hashed_id, 2, $audit_id);
            }
        }
    }

    header("Location: ../audit-dashboard/?audit_id=$audit_id");
    exit;
}