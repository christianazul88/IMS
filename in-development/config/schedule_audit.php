<?php
include "database.php";
include "on_session.php";

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize the input
    $warehouse = filter_input(INPUT_POST, 'warehouse', FILTER_SANITIZE_STRING);
    $start_date = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);

    //my auto generated creation
    $audit_status = "pending";

    // Check if required fields are empty
    if (empty($warehouse) || empty($start_date)) {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
        exit;
    }

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO audit_logs (warehouse, schedule_date, date_created, created_by, audit_status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $warehouse, $start_date, $currentDateTime, $user_id, $audit_status);

    // Execute the query
    if ($stmt->execute()) {
        // Get the ID of the newly created row
        $last_id = $conn->insert_id;
        $audit_num = 1000 + $last_id;
        
        // Update the audit_num column
        $update_stmt = $conn->prepare("UPDATE audit_logs SET audit_num = ? WHERE id = ?");
        $update_stmt->bind_param("si", $audit_num, $last_id);
        
        if ($update_stmt->execute()) {
            header("Location: ../audit-automation-module/?success=true");
        } else {
            header("Location: ../audit-automation-module/?success=false");
        }
        
        $update_stmt->close();
    } else {
        header("Location: ../audit-automation-module/?success=false");
    }

    // Close connections
    $stmt->close();
    $conn->close();

} else {
    // Redirect back if accessed without POST
    header("Location: ../audit-automation-module.php");
    exit;
}