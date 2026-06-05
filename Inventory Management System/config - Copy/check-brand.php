<?php
include('database.php'); // DB connection

if (isset($_POST['brand-name'])) { // Match key with AJAX
    $brand_name = $_POST['brand-name'];

    $sql = "SELECT COUNT(*) AS count FROM brand WHERE brand_name = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $brand_name);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        echo json_encode(['exists' => $count > 0]);
    } else {
        echo json_encode(['exists' => false, 'error' => 'Query failed']);
    }
} else {
    echo json_encode(['exists' => false]);
}
?>
