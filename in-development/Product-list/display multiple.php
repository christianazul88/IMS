<?php
include('../config/database.php');

$hashed_id = $_GET['hashed_id'] ?? '';

$product_list_query = "SELECT product_img FROM product WHERE hashed_id = '$hashed_id'";
$product_list_res = $conn->query($product_list_query);

if ($product_list_res->num_rows > 0) {
    while ($row = $product_list_res->fetch_assoc()) {
        $serializedImages = $row['product_img'];

        // Deserialize the base64 image array
        $imageArray = @unserialize($serializedImages); // use json_decode() if you stored it as JSON

        if ($imageArray && is_array($imageArray)) {
            foreach ($imageArray as $base64Img) {
                echo '<img src="data:image/jpeg;base64,' . $base64Img . '" style="max-width: 200px; margin: 10px;">';
            }
        } else {
            echo "No images found or failed to decode.";
        }
    }
} else {
    echo "No product found.";
}
?>
