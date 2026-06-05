<?php
include('database.php'); // DB connection

if (isset($_POST['category-name'])) { // Match key with AJAX
    $category_name = $_POST['category-name'];

    $sql = "SELECT COUNT(*) AS count FROM category WHERE category_name = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $category_name);
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
