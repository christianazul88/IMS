<?php
// Database connection credentials
$host = 'localhost';        // Your database host (e.g., localhost)
$db = 'u680032315_dmp_db';  // Your database name
$user = 'root';             // Your database username
$pass = '';                 // Your database password

try {
    // Create a new PDO instance for MySQL connection
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);

    // Set the PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optionally log the connection success
    // echo "Connected successfully!";
} catch (PDOException $e) {
    // If connection fails, output the error
    die("Database connection failed: " . $e->getMessage());
}
?>
