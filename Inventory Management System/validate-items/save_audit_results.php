<?php
include "../config/database.php";
include "../config/on_session.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request.');
}

$audit_id = $_SESSION['audit_id'] ?? null;
$audit_assignment_id = $_SESSION['audit_assignment_id'] ?? null;
$area_code = $_SESSION['area_code'] ?? null;


// Fetch audit details
$audit_query = "SELECT al.*, w.warehouse_name FROM audit_logs al LEFT JOIN warehouse w ON al.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci WHERE al.id = ?";
$stmt = $conn->prepare($audit_query);
$stmt->bind_param("i", $audit_id);
$stmt->execute();
$audit = $stmt->get_result()->fetch_assoc();
$stmt->close();

$today = date('Y-m-d');
$schedule_date = date('Y-m-d', strtotime($audit['schedule_date']));

if ($today < $schedule_date) {
    echo "<div class='alert alert-warning'>
            Audit is scheduled for " . date('M d, Y', strtotime($audit['schedule_date'])) . ". You cannot start it today.
          </div>";
    exit;
}





$warehouse_id_audit = $audit['warehouse'];

if (!$audit_id || !$audit_assignment_id || !$area_code) {
    http_response_code(403);
    exit('Required audit session variables are missing.');
}

if (
    !isset($_POST['status']) ||
    !is_array($_POST['status'])
) {
    exit('No audit data received.');
}

try {

    $conn->begin_transaction();

    /*
    STEP 1
    Mark EVERYTHING in this area as scanned.
    Also update assignment/location fields.
    warehouse_onscanned gets warehouse_origin.
    */

    $stmt = $conn->prepare("
        UPDATE items_to_audit
        SET
            audit_status = 'scanned',
            audit_assignment_id = ?,
            warehouse_onscanned = ?,
            item_location_onscanned = ?,
            user_id = ?
        WHERE audit_id = ?
        AND item_location_origin = ?
        AND audit_status = 'pending'
    ");

    $stmt->bind_param(
        "isisis",
        $audit_assignment_id,
        $warehouse_id_audit,
        $area_code,
        $user_id,
        $audit_id,
        $area_code
    );

    $stmt->execute();
    $stmt->close();

    /*
    STEP 2
    Collect missing barcodes.
    */

    $missingBarcodes = [];

    foreach ($_POST['status'] as $barcode => $status) {

        if ($status === 'missing') {

            $missingBarcodes[] =
                "'" . $conn->real_escape_string($barcode) . "'";
        }
    }

    /*
    STEP 3
    Revert missing items.
    */

    if (!empty($missingBarcodes)) {

        $sql = "
            UPDATE items_to_audit
            SET
                audit_status = 'pending',
                audit_assignment_id = NULL,
                warehouse_onscanned = NULL,
                item_location_onscanned = NULL
            WHERE audit_id = '" . $conn->real_escape_string($audit_id) . "'
            AND unique_barcode IN (" . implode(',', $missingBarcodes) . ")
        ";

        $conn->query($sql);
    }

    $conn->commit();

    // echo "success";
    header("Location: ../finish/?area=$area_code&user_id=$user_id");

} catch (Throwable $e) {

    $conn->rollback();

    http_response_code(500);

    echo $e->getMessage();
}

