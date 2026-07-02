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

/*
|--------------------------------------------------------------------------
| CSV Headers
|--------------------------------------------------------------------------
*/

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $warehouse_audit_name . '-Area Codes.csv"');

/*
|--------------------------------------------------------------------------
| Output CSV
|--------------------------------------------------------------------------
*/

$output = fopen('php://output', 'w');

// CSV Header Row
fputcsv($output, ['Area Name', 'Area Code', 'QTY']);

/*
|--------------------------------------------------------------------------
| Get Locations
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        il.id,
        il.location_name,
        COUNT(ita.id) AS qty
    FROM item_location il
    LEFT JOIN items_to_audit ita
        ON ita.item_location_origin = il.id
        AND ita.audit_id = ?
    WHERE il.warehouse = ?
    GROUP BY il.id, il.location_name
    ORDER BY il.location_name ASC
");

$stmt->bind_param("is", $audit_id, $warehouse_audit_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    fputcsv($output, [
        $row['location_name'],
        $row['id'],
        $row['qty'] > 0 ? $row['qty'] : ''
    ]);

}

$stmt->close();
fclose($output);

exit;