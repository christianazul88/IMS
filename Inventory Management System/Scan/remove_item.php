<?php
include "../config/database.php";
include "../config/on_session.php";

if (!isset($_POST['id'])) {
    exit("Missing item ID.");
}

$id = (int)$_POST['id'];

$stmt = $conn->prepare("
    UPDATE items_to_audit
    SET
        audit_status = 'pending',
        user_id = NULL,
        audit_assignment_id = NULL,
        warehouse_onscanned = NULL,
        item_location_onscanned = NULL,
        scanned_date = NULL
    WHERE id = ?
");

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "successfully removed";
} else {
    echo "Failed to update item.";
}

$stmt->close();
$conn->close();