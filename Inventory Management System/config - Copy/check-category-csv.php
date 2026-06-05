<?php
include('database.php'); // DB connection

// Check if 'categories' array is sent in POST request
if (isset($_POST['categories']) && is_array($_POST['categories'])) {
    $categories = $_POST['categories'];
    $response = [];

    foreach ($categories as $category_name) {
        // Prepare SQL to check if category exists
        $sql = "SELECT COUNT(*) AS count FROM category WHERE category_name = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $category_name);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            // Append the result for each category
            $response[] = [
                'category' => $category_name,
                'exists' => $count > 0
            ];
        } else {
            // In case of query failure
            $response[] = [
                'category' => $category_name,
                'exists' => false,
                'error' => 'Query failed'
            ];
        }
    }

    // Return JSON response
    echo json_encode($response);
} else {
    // Return error if no categories were provided
    echo json_encode(['error' => 'No categories provided']);
}
?>
