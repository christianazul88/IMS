<?php

include '../config/database.php';
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

$warehouse_id_audit = $audit['warehouse'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['location_assignment'])) {

        die("No assignments submitted.");
    }

    $assignments = $_POST['location_assignment'];

    // Track insert status
    $allInserted = true;

    $insert_query = "INSERT INTO audit_assignments
                    (
                        item_location,
                        user_id,
                        audit_id,
                        warehouse,
                        status,
                        date_assigned
                    )
                    VALUES (?, ?, ?, ?, 'pending', NOW())";

    $stmt = $conn->prepare($insert_query);

    foreach ($assignments as $location_name => $staff_name) {

        $stmt->bind_param(
            "isss",
            $location_name,
            $staff_name,
            $audit_id,
            $warehouse_id_audit
        );

        if (!$stmt->execute()) {

            $allInserted = false;
            break;
        }
    }

    $stmt->close();

    // Redirect if all successful
    if ($allInserted) {

        header("Location: index.php");
        exit();
    } else {

        echo "Failed to save some assignments.";
    }
}
?>