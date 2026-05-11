<?php
// Include database connection
include 'database.php'; // Assume this file contains $conn connection

$secretKey = 'your_secret_key'; // Use a secure key here

try {
    // Pagination and search handling
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // Adjusted query to include new columns and LEFT JOINs with MySQLi
    $query = "
        SELECT 
            p.id, 
            p.description AS product_name, 
            c.category_name AS category, 
            b.brand_name AS brand, 
            p.parent_barcode, 
            CONCAT(u.user_fname, ' ', u.user_lname) AS created_by, 
            p.date AS created_date /* This assumes 'date' exists; comment or remove if not */
        FROM product p
        LEFT JOIN brand b ON p.brand = b.hashed_id
        LEFT JOIN category c ON p.category = c.hashed_id
        LEFT JOIN users u ON p.user_id = u.hashed_id /* Use 'users' table and correct column names */
        WHERE p.description LIKE ? 
           OR b.brand_name LIKE ? 
           OR c.category_name LIKE ? 
           OR u.user_fname LIKE ? 
           OR u.user_lname LIKE ?
        ORDER BY p.id DESC
        LIMIT ? OFFSET ?
    ";

    // Prepare the SQL query
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    $searchTerm = "%$search%";
    $stmt->bind_param("sssssii", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset);
    
    // Execute the statement
    $stmt->execute();

    // Fetch the results
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    // Hash the product IDs
    foreach ($data as &$item) {
        $item['id'] = hash_hmac('sha256', $item['id'], $secretKey);
    }

    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(*) AS total
        FROM product p
        LEFT JOIN brand b ON p.brand = b.hashed_id
        LEFT JOIN category c ON p.category = c.hashed_id
        LEFT JOIN users u ON p.user_id = u.hashed_id
        WHERE p.description LIKE ? 
           OR b.brand_name LIKE ? 
           OR c.category_name LIKE ? 
           OR u.user_fname LIKE ? 
           OR u.user_lname LIKE ?
    ";

    // Prepare the count query
    $countStmt = $conn->prepare($countQuery);
    
    // Bind parameters for count query
    $countStmt->bind_param("sssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    
    // Execute the count statement
    $countStmt->execute();
    
    // Fetch the total count
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];

    // Return the response as JSON
    echo json_encode(['data' => $data, 'total' => $total]);

} catch (mysqli_sql_exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
