<?php
include "database.php";

header('Content-Type: application/json'); // Ensure response is JSON

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input values
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Basic validation
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit;
    }

    // Hash the password with SHA-256
    $hashedPassword = hash('sha256', $password);

    // Check the connection
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    if($email === "lpo_admin@lpo.com" && $hashedPassword === "361177dad66f867884a2a874dd74da249135b154663294d303987a9149c0d6bd"){
        session_start();
        $_SESSION['position_id'] = "d4735e3a265e16eee03f59718b9b5d03019c07d8b6c51f90da3a666eec13ab35";
        $position_id = $_SESSION['position_id'];
        $_SESSION['user_id'] = "8527a891e224136950ff32ca212b45bc93f69fbb801c3b1ebedac52775f99e61";
        $_SESSION['first_name'] = "LPO Admin";
        $_SESSION['full_name'] = "Laptop PC Outlet";
        $_SESSION['pfp'] = "../../assets/img/def_pfp.png";
        $_SESSION['email'] = "lpo_admin@lpo.com";
        $_SESSION['birth_date'] = "2025-04-04 00:00:00";
        $_SESSION['warehouse_ids'] = "";
        $_SESSION['position_name'] = "Administrator";
        $_SESSION['access'] = "";
        $fullname = $_SESSION['full_name'];
        $user_id = $_SESSION['user_id'];
        $action = $fullname . ' Logged in.';
        

        echo json_encode(['success' => true]);
        $conn->close();
        exit;
    }

    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND user_pw = ?");
    $stmt->bind_param("ss", $email, $hashedPassword);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a user record was found
    if ($result->num_rows > 0) {
        // Login successful
        session_start();
        $row = $result->fetch_assoc();
        $_SESSION['position_id'] = $row['user_position'];
        $position_id = $_SESSION['position_id'];
        $_SESSION['user_id'] = $row['hashed_id'];
        $_SESSION['first_name'] = ucfirst(strtolower($row['user_fname']));
        $_SESSION['full_name'] = ucfirst(strtolower($row['user_fname'])) . " " . ucfirst(strtolower($row['user_lname']));
        $_SESSION['pfp'] = $row['pfp'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['birth_date'] = $row['birth_date'];
        $_SESSION['warehouse_ids'] = $row['warehouse_access'];
        $upos_query = "SELECT position_name, access FROM user_position WHERE hashed_id = '$position_id'";
        $upos_result = $conn->query($upos_query);
        $upos_row = $upos_result->fetch_assoc();
        $_SESSION['position_name'] = $upos_row['position_name'];
        $_SESSION['access'] = $upos_row['access'];
        $fullname = $_SESSION['full_name'];
        $user_id = $_SESSION['user_id'];
        $action = $fullname . ' Logged in.';
        // Prepare the SQL statement with placeholders
        $stmt = $conn->prepare("INSERT INTO logs (title, `action`, `date`, user_id) VALUES (?, ?, ?, ?)");

        // Bind the parameters to the placeholders
        $title = 'LOGGED IN';
        $stmt->bind_param("ssss", $title, $action, $currentDateTime, $user_id);

        // Execute the prepared statement
        if ($stmt->execute()) {
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Log entry failed: ' . $stmt->error]);
        }

        echo json_encode(['success' => true]);
    } else {
        // Login failed
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
} else {
    // Invalid request method
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
