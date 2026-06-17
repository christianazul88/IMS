<?php

include "../config/database.php";
include "../config/on_session.php";

$audit_id = $_SESSION['audit_id'] ?? 0;

if (!$audit_id) {
    die("Invalid audit ID.");
}

/*
|--------------------------------------------------------------------------
| Get User Position
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT audit_position
    FROM audit_users
    WHERE hashed_id = ?
");
$stmt->bind_param("s", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$audit_position = $result->fetch_assoc()['audit_position'] ?? null;

$stmt->close();

/*
|--------------------------------------------------------------------------
| Fetch Audit Details
|--------------------------------------------------------------------------
*/

$audit_query = "
    SELECT
        al.*,
        w.warehouse_name
    FROM audit_logs al
    LEFT JOIN warehouse w
        ON al.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci
    WHERE al.id = ?
";

$stmt = $conn->prepare($audit_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();

$audit = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$audit) {
    die("Audit not found.");
}

$warehouse_audit_id = $audit['warehouse'];
$warehouse_audit_name = $audit['warehouse_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['areacode'])) {

    $area_code = trim($_POST['areacode']);

    // Validate input
    if (empty($area_code)) {
        die('Please enter an area code.');
    }

    // If area codes are numeric IDs
    if (!ctype_digit($area_code)) {
        die('Invalid area code.');
    }

    // Verify area code exists
    $stmt = $conn->prepare("
        SELECT id, location_name
        FROM item_location
        WHERE id = ?
        AND warehouse = ?
        LIMIT 1
    ");

    $stmt->bind_param("is", $area_code, $warehouse_audit_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die('Area code does not exist.');
    }

    $location = $result->fetch_assoc();

    // Store verified area code in session
    $_SESSION['area_code'] = $location['id'];
    $_SESSION['location_name'] = $location['location_name'];

    $item_location_id = $location['id'];

    // Check if audit assignment already exists
    $stmt = $conn->prepare("
        SELECT id
        FROM audit_assignments
        WHERE audit_id = ?
        AND item_location = ?
        AND warehouse = ?
        LIMIT 1
    ");
    $stmt->bind_param(
        "iis",
        $audit_id,
        $item_location_id,
        $warehouse_audit_id
    );
    $stmt->execute();
    $existing_assignment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing_assignment) {

        $audit_assignment_id = $existing_assignment['id'];

    } else {

        $stmt = $conn->prepare("
            INSERT INTO audit_assignments (
                audit_id,
                item_location,
                warehouse
            )
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param(
            "iis",
            $audit_id,
            $item_location_id,
            $warehouse_audit_id
        );
        $stmt->execute();

        $audit_assignment_id = $stmt->insert_id;
        $stmt->close();
    }



    // Check if staff is already assigned
    $stmt = $conn->prepare("
        SELECT id
        FROM audit_assignment_staffs
        WHERE audit_assignments_id = ?
        AND user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param(
        "is",
        $audit_assignment_id,
        $user_id
    );
    $stmt->execute();
    $existing_staff = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing_staff) {

        $stmt = $conn->prepare("
            INSERT INTO audit_assignment_staffs (
                audit_assignments_id,
                user_id,
                status
            )
            VALUES (?, ?, 'idle')
        ");
        $stmt->bind_param(
            "is",
            $audit_assignment_id,
            $user_id
        );
        $stmt->execute();
        $stmt->close();
    }

    $_SESSION['audit_assignment_id'] = $audit_assignment_id;

    header("Location: ../validate-items/");

} else {
    die('Invalid request.');
    header("Location: ../Enter-Area/?invalid=true");
}