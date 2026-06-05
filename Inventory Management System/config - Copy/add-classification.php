<?php
include "database.php";
include "on_session.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Trim and validate classification name
    $classification_name = trim($_POST['classification_name'] ?? '');
    if (empty($classification_name)) {
        echo "Classification name is required";
        exit;
    }

    // Get selected categories (checkbox array) safely
    $categories = isset($_POST['categories']) && is_array($_POST['categories']) ? $_POST['categories'] : [];

    // Start transaction to ensure atomicity
    mysqli_begin_transaction($conn);

    try {
        // Insert into classification using prepared statement
        $stmt = $conn->prepare("INSERT INTO classification (classification_name, status, date_added, user_id, hashed_id) 
                                VALUES (?, 1, NOW(), ?, MD5(NOW()))");
        $stmt->bind_param("ss", $classification_name, $_SESSION['user_id']);
        $stmt->execute();

        // Get last inserted ID
        $class_id = $stmt->insert_id;
        $classification_id = hash('sha256', $class_id);

        // Update hashed_id securely
        $updateStmt = $conn->prepare("UPDATE classification SET hashed_id = ? WHERE id = ?");
        $updateStmt->bind_param("si", $classification_id, $class_id);
        $updateStmt->execute();

        // If categories selected, update them
        if (!empty($categories)) {
            $updateCategoryStmt = $conn->prepare("UPDATE category SET classification_id = ? WHERE hashed_id = ?");
            foreach ($categories as $hashed_id) {
                // Validate hashed_id format (SHA256 = 64 hex chars)
                if (preg_match('/^[a-f0-9]{64}$/i', $hashed_id)) {
                    $updateCategoryStmt->bind_param("ss", $classification_id, $hashed_id);
                    $updateCategoryStmt->execute();
                }
            }
            $updateCategoryStmt->close();
        }

        // Commit transaction
        mysqli_commit($conn);

        header("Location: ../Classification/?success=true");

        // Close statements
        $stmt->close();
        $updateStmt->close();

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Error inserting classification: " . $e->getMessage());
        echo "Error inserting classification";
    }
}
?>