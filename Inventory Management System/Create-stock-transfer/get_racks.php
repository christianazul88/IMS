<?php
include "../config/database.php";

if (!isset($_POST['warehouse'])) {
    exit;
}

$warehouse = $_POST['warehouse'];

$stmt = $conn->prepare("
    SELECT id, location_name
    FROM item_location
    WHERE warehouse = ?
    ORDER BY location_name ASC
");
$stmt->bind_param("s", $warehouse);
$stmt->execute();

$result = $stmt->get_result();

echo '<option value="">Select Rack</option>';
echo '<option value="0">For SKU</option>';

while ($row = $result->fetch_assoc()) {
    echo '<option value="'.$row['id'].'">'.$row['location_name'].'</option>';
}