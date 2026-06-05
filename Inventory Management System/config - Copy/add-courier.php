<?php
declare(strict_types=1);
include "database.php";
include "on_session.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize the input
    $courierName = trim(filter_input(INPUT_POST, 'courier-name', FILTER_SANITIZE_STRING));
    if (empty($courierName)) {
        echo "<script>alert('courier name cannot be empty.'); window.history.back();</script>";
        exit;
    }

    try {
        $conn->begin_transaction();

        // Prepare and execute the insertion of the courier
        $stmt = $conn->prepare("INSERT INTO courier (courier_name, `date`, user_id) VALUES (?, ?, ?)");
        $currentDateTime = (new DateTime())->format('Y-m-d H:i:s'); // Current date and time
        $stmt->bind_param("sss", $courierName, $currentDateTime, $user_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert courier.");
        }

        // Retrieve the inserted ID and hash it securely
        $courier_unhashid = (string)$conn->insert_id; // Convert to string for hash function
        $courier_hashid = hash('sha256', $courier_unhashid);

        // Update the courier table with hashed ID
        $update_stmt = $conn->prepare("UPDATE courier SET hashed_id = ? WHERE id = ?");
        $update_stmt->bind_param("si", $courier_hashid, $courier_unhashid);

        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update hashed ID.");
        }

        // Commit the transaction
        $conn->commit();

        header("Location: ../Courier/?success=true");
    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage()); // Log the error for debugging
        header("Location: ../Courier/?success=false");
    } finally {
        // Close statements and connection
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($update_stmt)) {
            $update_stmt->close();
        }
        $conn->close();
    }
} else {
    // Redirect if accessed without POST
    header("Location: ../courier-list.php");
    exit;
}
?>
