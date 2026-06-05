<?php
// Include database connection
include('database.php');  // Make sure this file contains your DB connection

// Read the incoming JSON data
$input = json_decode(file_get_contents('php://input'), true);

// Check if we received the necessary data
if (isset($input['items']) && isset($input['brands']) && isset($input['categories'])) {
    $items = $input['items'];
    $brands = $input['brands'];
    $categories = $input['categories'];
    $response = [];

    // Loop through each row of data to check the existence
    foreach ($items as $index => $item) {
        $brand = $brands[$index];
        $category = $categories[$index];

        // Check for the brand existence
        $brand_sql = "SELECT * FROM brand WHERE brand_name = ? LIMIT 1";
        $stmt = $conn->prepare($brand_sql);
        $stmt->bind_param("s", $brand);
        $stmt->execute();
        $brand_res = $stmt->get_result();
        $stmt->close();

        // Check for the category existence
        $category_sql = "SELECT * FROM category WHERE category_name = ? LIMIT 1";
        $stmt = $conn->prepare($category_sql);
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $category_res = $stmt->get_result();
        $stmt->close();

        // If both brand and category exist, proceed to check if the product exists
        if ($brand_res->num_rows > 0 && $category_res->num_rows > 0) {
            // Get the brand and category IDs
            $brand_row = $brand_res->fetch_assoc();
            $category_row = $category_res->fetch_assoc();
            $brand_id = $brand_row['hashed_id'];
            $category_id = $category_row['hashed_id'];

            // Check if the product (item) already exists with the same brand and category
            $product_sql = "SELECT COUNT(*) AS count FROM product WHERE description = ? AND brand = ? AND category = ?";
            $stmt = $conn->prepare($product_sql);
            $stmt->bind_param("sss", $item, $brand_id, $category_id);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            // If the product exists, add to the response
            if ($count > 0) {
                $response[] = ['itemExists' => true];
            } else {
                $response[] = ['itemExists' => false];
            }
        } else {
            // If brand or category doesn't exist, consider the product as new
            $response[] = ['itemExists' => false];
        }
    }

    // Send the response as JSON
    echo json_encode($response);
} else {
    // If the required data is missing, return a response indicating an error
    echo json_encode(['error' => 'Invalid input data']);
}
?>
