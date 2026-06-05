<?php
declare(strict_types=1);
include "database.php";
include "on_session.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize the input
    $logisticName = trim(filter_input(INPUT_POST, 'logistic-name', FILTER_SANITIZE_STRING));
    if (empty($logisticName)) {
        echo "<script>alert('logistic name cannot be empty.'); window.history.back();</script>";
        exit;
    }

    try {
        $conn->begin_transaction();

        // Prepare and execute the insertion of the logistic
        $stmt = $conn->prepare("INSERT INTO logistic_partner (logistic_name, `date`, user_id) VALUES (?, ?, ?)");
        $currentDateTime = (new DateTime())->format('Y-m-d H:i:s'); // Current date and time
        $stmt->bind_param("sss", $logisticName, $currentDateTime, $user_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert logistic.");
        }

        // Retrieve the inserted ID and hash it securely
        $logistic_unhashid = (string)$conn->insert_id; // Convert to string for hash function
        $logistic_hashid = hash('sha256', $logistic_unhashid);

        // Update the logistic table with hashed ID
        $update_stmt = $conn->prepare("UPDATE logistic_partner SET hashed_id = ? WHERE id = ?");
        $update_stmt->bind_param("si", $logistic_hashid, $logistic_unhashid);

        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update hashed ID.");
        }

        // Commit the transaction
        $conn->commit();

        header("Location: ../logistic-partner/?success=true");
    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage()); // Log the error for debugging
        header("Location: ../logistic-partner/?success=false");
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
    header("Location: ../logistic-list.php");
    exit;
}
?>
