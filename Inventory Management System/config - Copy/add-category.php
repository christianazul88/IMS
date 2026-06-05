<?php
include "database.php";
include "on_session.php";

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Sanitize the category name
    $categoryName = filter_input(INPUT_POST, 'category_name', FILTER_SANITIZE_STRING);

    // Check if category name is empty
    if (empty($categoryName)) {
        echo "<script>alert('category name cannot be empty.'); window.history.back();</script>";
        exit;
    }


    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO category (category_name, `date`, user_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $categoryName, $currentDateTime, $user_id);

    // Execute the query
    if ($stmt->execute()) {
        $category_id = $conn->insert_id;
        $hashed_id = hash('sha256', $category_id);
        $update = "UPDATE category SET hashed_id ='$hashed_id' WHERE id = '$category_id'";
        if($conn->query($update)===TRUE){
            header("Location: ../Category/?success=true");
        }
    } else {
        header("Location: ../Category/?success=false");
    }

    // Close connections
    $stmt->close();
    $conn->close();

} else {
    // Redirect back if accessed without POST
    header("Location: ../Category.php");
    exit;
}
?>
