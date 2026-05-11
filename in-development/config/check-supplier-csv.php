<?php
include('database.php'); // DB connection

// Check if 'suppliers' array is sent in POST request
if (isset($_POST['suppliers']) && is_array($_POST['suppliers'])) {
    $suppliers = $_POST['suppliers'];
    $response = [];

    foreach ($suppliers as $supplier_name) {
        // Prepare SQL to check if supplier exists
        $sql = "SELECT COUNT(*) AS count FROM supplier WHERE supplier_name = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $supplier_name);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            // Append the result for each supplier
            $response[] = [
                'supplier' => $supplier_name,
                'exists' => $count > 0
            ];
        } else {
            // In case of query failure
            $response[] = [
                'supplier' => $supplier_name,
                'exists' => false,
                'error' => 'Query failed'
            ];
        }
    }

    // Return JSON response
    echo json_encode($response);
} else {
    // Return error if no suppliers were provided
    echo json_encode(['error' => 'No suppliers provided']);
}
?>
