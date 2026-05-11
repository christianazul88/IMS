<?php
// Include your database connection file
include 'database.php';
include "on_session.php";


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve the form data
    $firstName = mysqli_real_escape_string($conn, $_POST['fname']);
    $middleName = mysqli_real_escape_string($conn, $_POST['mname']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $birthDate = mysqli_real_escape_string($conn, $_POST['bday']);
    $positionId = mysqli_real_escape_string($conn, $_POST['organizerSingle']);
    $warehouseAccessArray = isset($_POST['warehouses']) ? $_POST['warehouses'] : [];

    // Generate a random 6-digit code
    // $random_code = rand(100000, 999999);
    $random_code = 123;
    // Generate default password
    $password = $random_code;
    $hashedPassword = hash('sha256', $password);

    // Check if at least one checkbox is selected
    if (!empty($warehouseAccessArray)) {
        // Convert array of selected warehouse IDs to a comma-separated string
        $warehouseAccess = implode(",", $warehouseAccessArray);
    } else {
        $warehouseAccess = ""; // No access selected
    }

    // Prepare the SQL insert statement
    $sql = "INSERT INTO users (user_fname, user_mname, user_lname, email, birth_date, user_position, warehouse_access,user_pw) 
            VALUES ('$firstName', '$middleName', '$lastName', '$email', '$birthDate', '$positionId', '$warehouseAccess', '$hashedPassword')";

    // Execute the query and check for success
    if (mysqli_query($conn, $sql)) {
        // Redirect or display a success message
        // echo "Employee added successfully.";
        // Optionally, redirect to another page:
        $employee_userid = $conn->insert_id;
        $hashEmp_userid = hash('sha256', $employee_userid);
        $update_employee = "UPDATE users SET hashed_id = '$hashEmp_userid' WHERE id = '$employee_userid'";
        if($conn->query($update_employee) === TRUE ){
            header("Location: ../Users/?success=true");
            exit();
        }
        
    } else {
        // Display an error message if something goes wrong
        $error = "Error: " . mysqli_error($conn);
        header("Location: ../Users/?success=false&pr=$error");
    }

    // Close the database connection
    mysqli_close($conn);
} else {
    // If the request method is not POST, redirect to the form page
    header("Location: ../form_page.php"); // Adjust to the actual form page path
    exit();
}
?>
