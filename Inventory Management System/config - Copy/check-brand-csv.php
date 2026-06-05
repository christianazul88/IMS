<?php
include('database.php'); // DB connection

// Check if 'brands' array is sent in POST request
if (isset($_POST['brands']) && is_array($_POST['brands'])) {
    $brands = $_POST['brands'];
    $response = [];

    foreach ($brands as $brand_name) {
        // Prepare SQL to check if brand exists
        $sql = "SELECT COUNT(*) AS count FROM brand WHERE brand_name = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $brand_name);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            // Append the result for each brand
            $response[] = [
                'brand' => $brand_name,
                'exists' => $count > 0
            ];
        } else {
            // In case of query failure
            $response[] = [
                'brand' => $brand_name,
                'exists' => false,
                'error' => 'Query failed'
            ];
        }
    }

    // Return JSON response
    echo json_encode($response);
} else {
    // Return error if no brands were provided
    echo json_encode(['error' => 'No brands provided']);
}
?>
