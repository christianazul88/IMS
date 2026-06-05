<?php
declare(strict_types=1);
include "database.php";
include "on_session.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize the input
    $supplierName = trim(filter_input(INPUT_POST, 'supplier-name', FILTER_SANITIZE_STRING));
    $locality = trim(filter_input(INPUT_POST, 'localInternational', FILTER_SANITIZE_STRING));
    if (empty($supplierName)) {
        echo "<script>alert('supplier name cannot be empty.'); window.history.back();</script>";
        exit;
    }

    try {
        $conn->begin_transaction();

        // Prepare and execute the insertion of the supplier
        $stmt = $conn->prepare("INSERT INTO supplier (supplier_name, `date`, user_id, local_international) VALUES (?, ?, ?, ?)");
        $currentDateTime = (new DateTime())->format('Y-m-d H:i:s'); // Current date and time
        $stmt->bind_param("ssss", $supplierName, $currentDateTime, $user_id, $locality);

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert supplier.");
        }

        // Retrieve the inserted ID and hash it securely
        $supplier_unhashid = (string)$conn->insert_id; // Convert to string for hash function
        $supplier_hashid = hash('sha256', $supplier_unhashid);

        // Update the supplier table with hashed ID
        $update_stmt = $conn->prepare("UPDATE supplier SET hashed_id = ? WHERE id = ?");
        $update_stmt->bind_param("si", $supplier_hashid, $supplier_unhashid);

        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update hashed ID.");
        }

        // Commit the transaction
        $conn->commit();

        header("Location: ../Suppliers/?success=true");
    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage()); // Log the error for debugging
        header("Location: ../Suppliers/?success=false");
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
    header("Location: ../supplier-list.php");
    exit;
}
?>
