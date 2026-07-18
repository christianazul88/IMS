<?php
include "../config/database.php";

$audit_id = 38;

mysqli_begin_transaction($conn);

try {

    // Get all audit assignments for the audit
    $stmt = $conn->prepare("
        SELECT id
        FROM audit_assignments
        WHERE audit_id = ?
    ");
    $stmt->bind_param("i", $audit_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Prepare delete statements
    $deleteStaff = $conn->prepare("
        DELETE FROM audit_assignment_staffs
        WHERE audit_assignments_id = ?
    ");

    $deleteAssignment = $conn->prepare("
        DELETE FROM audit_assignments
        WHERE id = ?
    ");

    while ($row = $result->fetch_assoc()) {

        $assignment_id = $row['id'];

        // Delete staff assignments
        $deleteStaff->bind_param("i", $assignment_id);
        $deleteStaff->execute();

        // Delete audit assignment
        $deleteAssignment->bind_param("i", $assignment_id);
        $deleteAssignment->execute();
    }

    mysqli_commit($conn);

    echo "Successfully deleted all audit assignments and their staff assignments.";

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo "Error: " . $e->getMessage();
}
?>