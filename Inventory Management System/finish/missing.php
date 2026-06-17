<?php
include "../config/database.php";
include "../config/on_session.php";

$audit_id = $_SESSION['audit_id'] ?? null;

if (!$audit_id) {
    http_response_code(403);
    exit("Unauthorized");
}

if (isset($_GET['barcode'])) {

    $unique_barcode = $_GET['barcode'];

    $sql = "
        UPDATE items_to_audit
        SET
            audit_status = 'pending',
            audit_assignment_id = NULL,
            warehouse_onscanned = NULL,
            item_location_onscanned = NULL
        WHERE audit_id = ?
        AND unique_barcode = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        http_response_code(500);
        exit("Prepare failed");
    }

    $stmt->bind_param("is", $audit_id, $unique_barcode);

    if ($stmt->execute()) {
        echo "SUCCESSFULLY UPDATED";
    } else {
        http_response_code(500);
        echo "UPDATE FAILED";
    }

    $stmt->close();
}
?>