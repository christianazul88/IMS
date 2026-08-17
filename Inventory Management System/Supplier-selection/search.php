<?php
include '../config/database.php';
include '../config/on_session.php'; // must exit/redirect if user is not authenticated

header('Content-Type: application/json');

if (isset($_POST['query']) && trim($_POST['query']) !== '') {
    $search = trim($_POST['query']);

    // Escape LIKE wildcard characters the user might type, so a literal
    // "%" or "_" in the search box doesn't act as a wildcard.
    $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);

    $stmt = $conn->prepare("SELECT p.description, b.brand_name, c.category_name, p.parent_barcode
                            FROM product p
                            LEFT JOIN brand b ON b.hashed_id = p.brand
                            LEFT JOIN category c ON c.hashed_id = p.category
                            WHERE p.description LIKE ? OR b.brand_name LIKE ? OR c.category_name LIKE ?
                            LIMIT 10");

    $likeQuery = "%$escaped%";
    $stmt->bind_param("sss", $likeQuery, $likeQuery, $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();

    $output = [];
    while ($row = $result->fetch_assoc()) {
        $output[] = $row;
    }

    echo json_encode($output);
} else {
    echo json_encode([]);
}
?>
