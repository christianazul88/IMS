<?php
include "database.php";
include "on_session.php";

$parent = $_POST['parent_barcode'];
$ext = $_POST['sequence'];
$amount = $_POST['new_amount'];

$sql = "
UPDATE stocks 
SET capital='$amount'
WHERE parent_barcode='$parent'
AND barcode_extension='$ext'
";

mysqli_query($conn,$sql);

$rows = mysqli_affected_rows($conn);


$action = $user_fullname . ' Updated the amount(' . $amount . ') of barcode ' . $parent . '-' . $ext . '.';
// Prepare the SQL statement with placeholders
$stmt = $conn->prepare("INSERT INTO logs (title, `action`, `date`, user_id) VALUES (?, ?, ?, ?)");

// Bind the parameters to the placeholders
$title = 'AMOUNT UPDATE';
$stmt->bind_param("ssss", $title, $action, $currentDateTime, $user_id);

// Execute the prepared statement
if ($stmt->execute()) {
    
} else {
    echo json_encode(['success' => false, 'message' => 'Log entry failed: ' . $stmt->error]);
}


$_SESSION['updates'][] = [
"parent"=>$parent,
"ext"=>$ext,
"amount"=>$amount,
"rows"=>$rows
];

echo json_encode([
"parent"=>$parent,
"ext"=>$ext,
"amount"=>$amount,
"rows"=>$rows
]);