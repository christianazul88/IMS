<?php
// collapse.php
if (isset($_GET['pr'])) {
    $productId = $_GET['pr'];
    // Fetch product details from the database based on the product ID
    // Example response
    echo json_encode([
        'details' => 'Some product details for product ID: ' . $productId
    ]);
} else {
    echo json_encode(['error' => 'No product ID provided']);
}
?>
