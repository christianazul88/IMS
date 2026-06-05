<?php
session_set_cookie_params(0);
session_start();
$user_position_id = $_SESSION['position_id'];
$user_id = $_SESSION['user_id'];
$user_fullname = $_SESSION['full_name'];
$user_fname = $_SESSION['first_name'];
$user_email = $_SESSION['email'];
$user_bday = $_SESSION['birth_date'];
$user_warehouse_ids = explode(",", $_SESSION['warehouse_ids']);
$user_position_name = $_SESSION['position_name'];
$access = $_SESSION['access'];
if(empty($_SESSION['pfp'])){
    $user_pfp = "def_pfp.png";
} else {
    $user_pfp = $_SESSION['pfp'];
}



if(!isset($user_id)){
    header("Location: ../");
}
// Trim any extra whitespace from each ID
$warehouse_ids_array = array_map('trim', $user_warehouse_ids);

// Initialize an array to store the select options
$warehouses = $_SESSION['warehouse_ids'];
$warehouse_options = [];
$warehouse_options2 = [];

foreach ($warehouse_ids_array as $warehouse_id) {
    // Create the SQL query by directly inserting the warehouse ID
    $sql = "SELECT hashed_id, warehouse_name FROM warehouse WHERE hashed_id = '$warehouse_id'";
    
    // Execute the query
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        $warehouse_options[] = '<option value="' . $row['warehouse_name'] . '">' . $row['warehouse_name'] . '</option>';
        $warehouse_options2[] = '<option value="' . $row['hashed_id'] . '">' . $row['warehouse_name'] . '</option>';
    }
}
// Get the current file name
$currentFile = basename($_SERVER['PHP_SELF']);

// Check if the current file is 'logout.php'
if ($currentFile === 'logout.php') {
   echo $currentFile;
} else {
    if(!isset($_SESSION['logged_in'])){
        $_SESSION['logged_in'] = true;
        $logged_in = $_SESSION['logged_in'];
    } else {
        unset($_SESSION['logged_in']);
        $logged_in = false;
    }
}

$user_warehouse_id = "'".implode("','", $warehouse_ids_array)."'"; // For arrays



if ($logged_in === true) {
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT `status` FROM users WHERE hashed_id = ? LIMIT 1");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if ($row['status'] == 0) {
            $_SESSION['account_status'] = 0;
            $logged_in = false;

            // Redirect to disabled account page
            header("Location: ../Account/?status=disabled");
            $stmt->close();
            $conn->close();
            exit;
        }
    }
    $stmt->close();
} else {
    
}


?>
