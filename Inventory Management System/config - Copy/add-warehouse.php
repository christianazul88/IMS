<?php
declare(strict_types=1);
include "database.php";
include "on_session.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize the input
    $warehouseName = trim(filter_input(INPUT_POST, 'warehouse-name', FILTER_SANITIZE_STRING));

    if (empty($warehouseName)) {
        echo "<script>alert('Warehouse name cannot be empty.'); window.history.back();</script>";
        exit;
    }

    try {
        $conn->begin_transaction();

        // Prepare and execute the insertion of the warehouse
        $stmt = $conn->prepare("INSERT INTO warehouse (warehouse_name, `date`, user_id) VALUES (?, ?, ?)");
        $currentDateTime = (new DateTime())->format('Y-m-d H:i:s'); // Current date and time
        $stmt->bind_param("ssi", $warehouseName, $currentDateTime, $user_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert warehouse.");
        }

        // Retrieve the inserted ID and hash it securely
        $warehouse_unhashid = (string)$conn->insert_id; // Convert to string for hash function
        $warehouse_hashid = hash('sha256', $warehouse_unhashid);

        // Update the warehouse table with hashed ID
        $update_stmt = $conn->prepare("UPDATE warehouse SET hashed_id = ? WHERE id = ?");
        $update_stmt->bind_param("si", $warehouse_hashid, $warehouse_unhashid);

        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update hashed ID.");
        }

        // Commit the transaction
        $conn->commit();

        header("Location: ../Warehouses/?success=true");
    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage()); // Log the error for debugging
        header("Location: ../Warehouses/?success=false");
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
    header("Location: ../warehouse-list.php");
    exit;
}
?>
