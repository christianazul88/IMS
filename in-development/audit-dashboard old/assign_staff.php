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

    $staff_id = $_POST['staff_id'] ?? null;
    $location_id = $_POST['location_id'] ?? null;
    $new_location = trim($_POST['new_location'] ?? '');

    // =========================
    // VALIDATION
    // =========================
    if (empty($staff_id)) {
        die("Staff is required.");
    }

    if (empty($location_id)) {
        die("Please select a location.");
    }

    // =========================
    // HANDLE "OTHER LOCATION"
    // =========================
    if ($location_id === "other") {

        if (empty($new_location)) {
            die("Please enter a new location.");
        }

        // Insert new location
        $insertLoc = $conn->prepare("
            INSERT INTO item_location (location_name, warehouse)
            VALUES (?, ?)
        ");

        $insertLoc->bind_param("ss", $new_location, $warehouse_id_audit);

        if (!$insertLoc->execute()) {
            die("Failed to create new location.");
        }

        $location_id = $insertLoc->insert_id;

        $insertLoc->close();
    }

    // =========================
    // GET EXPECTED QTY
    // =========================
    $get_expected_qty = "
        SELECT COUNT(*) AS total_expected
        FROM stocks
        WHERE warehouse = ?
        AND item_location = ?
        AND item_status = 0
    ";

    $stmt_expected = $conn->prepare($get_expected_qty);

    $stmt_expected->bind_param(
        "si",
        $warehouse_id_audit,
        $location_id
    );

    $stmt_expected->execute();

    $expected_result = $stmt_expected->get_result()->fetch_assoc();

    $expected_qty = $expected_result['total_expected'] ?? 0;

    $stmt_expected->close();


    // =========================
    // INSERT ASSIGNMENT
    // =========================
    $insert_query = "
        INSERT INTO audit_assignments
        (
            item_location,
            user_id,
            audit_id,
            warehouse,
            expected_qty,
            status,
            date_assigned
        )
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ";

    $stmt = $conn->prepare($insert_query);

    $stmt->bind_param(
        "ssisi",
        $location_id,
        $staff_id,
        $audit_id,
        $warehouse_id_audit,
        $expected_qty
    );

    if ($stmt->execute()) {

        $stmt->close();

        header("Location: index.php");
        exit();

    } else {

        $stmt->close();

        echo "Failed to save assignment.";
    }
}
?>