<?php
include "../config/database.php";

$barcode = $_POST['barcode'];
$audit_id = $_POST['audit_id'];

$check = mysqli_query($conn, "
    SELECT * FROM items_to_audit
    WHERE unique_barcode = '$barcode'
    AND audit_assignment_id = '$audit_id'
");

if (mysqli_num_rows($check) > 0) {

    mysqli_query($conn, "
        UPDATE items_to_audit
        SET outbounded = 'yes', scanned_date = NOW()
        WHERE unique_barcode = '$barcode'
    ");

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Barcode not found"]);
}
?>