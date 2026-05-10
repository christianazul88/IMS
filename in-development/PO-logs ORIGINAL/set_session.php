<?php
include "../config/database.php";
include "../config/on_session.php";

if (!isset($_POST['warehouse']) && !isset($_POST['date_order']) && empty($_POST['date_order'])) {
    header("Location: index?error=missingfields");
    exit;
}

$selected_warehouse_id = $_POST['warehouse'];

$query = "SELECT warehouse_name FROM warehouse WHERE hashed_id = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $selected_warehouse_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $_SESSION['selected_warehouse_id'] = $selected_warehouse_id;
    $_SESSION['po_order_date'] = $_POST['date_order'];
    $_SESSION['selected_warehouse_name'] = $row['warehouse_name'];
    header("Location: ../Supplier-selection/");
    exit;
} else {
    die("Error: Invalid Warehouse ID.");
}
?>
