<?php
include '../config/database.php';

if (isset($_POST['query'])) {
    $search = $_POST['query'];
    
    $stmt = $conn->prepare("SELECT p.description, b.brand_name, c.category_name, p.parent_barcode 
                            FROM product p 
                            LEFT JOIN brand b ON b.hashed_id = p.brand 
                            LEFT JOIN category c ON c.hashed_id = p.category 
                            WHERE p.description LIKE ? OR b.brand_name LIKE ? OR c.category_name LIKE ? 
                            LIMIT 10");
    
    $likeQuery = "%$search%";
    $stmt->bind_param("sss", $likeQuery, $likeQuery, $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();

    $output = [];
    while ($row = $result->fetch_assoc()) {
        $output[] = $row;
    }

    echo json_encode($output);
}
?>