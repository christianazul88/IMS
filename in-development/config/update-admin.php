<?php
include "database.php";
include "on_session.php";

if(isset($_GET['type'])){
    $type = $_GET['type'];

    if($type === "category"){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $category_name = trim($_POST['category_name']);
            $category_id = trim($_POST['id']);

            // Validate input
            if (empty($category_name) || empty($category_id)) {
                echo json_encode(["status" => "error", "message" => "All fields are required."]);
                exit;
            }

            // Fetch the current category name
            $query = "SELECT category_name FROM category WHERE hashed_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $category_id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($current_name);
            $stmt->fetch();

            if ($stmt->num_rows == 0) {
                echo json_encode(["status" => "error", "message" => "Category not found."]);
                exit;
            }
            $stmt->close();

            // If the new name is the same as the current name, return "No changes"
            if ($current_name === $category_name) {
                echo json_encode(["status" => "no_change", "message" => "No changes detected."]);
                exit;
            }

            // Update the category
            $update_query = "UPDATE category SET category_name = ?, date = NOW() WHERE hashed_id = ?";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("ss", $category_name, $category_id);

            if ($stmt_update->execute()) {
                // Log the update
                $log_query = "INSERT INTO logs (`title`, `action`, user_id, `date`) VALUES (?, ?, ?, ?)";
                $stmt_log = $conn->prepare($log_query);
                $log_title = "UPDATE CATEGORY";
                $log_action = "Updated category name from '$current_name' to '$category_name'";
                $stmt_log->bind_param("ssss", $log_title, $log_action, $user_id, $currentDateTime);
                $stmt_log->execute();
                $stmt_log->close();

                echo json_encode(["status" => "success", "message" => "Category updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update category."]);
            }

            $stmt_update->close();
        }
    } elseif ($type === "brand"){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $brand_name = trim($_POST['brand_name']);
            $brand_id = trim($_POST['id']);

            // Validate input
            if (empty($brand_name) || empty($brand_id)) {
                echo json_encode(["status" => "error", "message" => "All fields are required."]);
                exit;
            }

            // Fetch the current brand name
            $query = "SELECT brand_name FROM brand WHERE hashed_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $brand_id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($current_name);
            $stmt->fetch();

            if ($stmt->num_rows == 0) {
                echo json_encode(["status" => "error", "message" => "Brand not found."]);
                exit;
            }
            $stmt->close();

            // If the new name is the same as the current name, return "No changes"
            if ($current_name === $brand_name) {
                echo json_encode(["status" => "no_change", "message" => "No changes detected."]);
                exit;
            }

            // Update the brand
            $update_query = "UPDATE brand SET brand_name = ?, date = NOW() WHERE hashed_id = ?";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("ss", $brand_name, $brand_id);

            if ($stmt_update->execute()) {
                // Log the update
                $log_query = "INSERT INTO logs (`title`, `action`, user_id, `date`) VALUES (?, ?, ?, ?)";
                $stmt_log = $conn->prepare($log_query);
                $log_title = "UPDATE BRAND";
                $log_action = "Updated brand name from '$current_name' to '$brand_name'";
                $stmt_log->bind_param("ssss", $log_title, $log_action, $user_id, $currentDateTime);
                $stmt_log->execute();
                $stmt_log->close();

                echo json_encode(["status" => "success", "message" => "Brand updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update brand."]);
            }

            $stmt_update->close();
        }
    } elseif($type === "warehouse"){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $warehouse_name = trim($_POST['warehouse_name']);
            $warehouse_id = trim($_POST['id']);

            // Validate input
            if (empty($warehouse_name) || empty($warehouse_id)) {
                echo json_encode(["status" => "error", "message" => "All fields are required."]);
                exit;
            }

            // Fetch the current warehouse name
            $query = "SELECT warehouse_name FROM warehouse WHERE hashed_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $warehouse_id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($current_name);
            $stmt->fetch();

            if ($stmt->num_rows == 0) {
                echo json_encode(["status" => "error", "message" => "warehouse not found."]);
                exit;
            }
            $stmt->close();

            // If the new name is the same as the current name, return "No changes"
            if ($current_name === $warehouse_name) {
                echo json_encode(["status" => "no_change", "message" => "No changes detected."]);
                exit;
            }

            // Update the warehouse
            $update_query = "UPDATE warehouse SET warehouse_name = ?, date = NOW() WHERE hashed_id = ?";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("ss", $warehouse_name, $warehouse_id);

            if ($stmt_update->execute()) {
                // Log the update
                $log_query = "INSERT INTO logs (`title`, `action`, user_id, `date`) VALUES (?, ?, ?, ?)";
                $stmt_log = $conn->prepare($log_query);
                $log_title = "UPDATE warehouse";
                $log_action = "Updated warehouse name from '$current_name' to '$warehouse_name'";
                $stmt_log->bind_param("ssss", $log_title, $log_action, $user_id, $currentDateTime);
                $stmt_log->execute();
                $stmt_log->close();

                echo json_encode(["status" => "success", "message" => "warehouse updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update warehouse."]);
            }

            $stmt_update->close();
        }
    } elseif($type === "supplier"){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $supplier_name = trim($_POST['supplier_name']);
            $supplier_id = $_POST['id'];
            $supplier_type = $_POST['type'];

            // Validate input
            if (empty($supplier_name) || empty($supplier_id)) {
                echo json_encode(["status" => "error", "message" => "All fields are required."]);
                exit;
            }

            // Fetch the current supplier name
            $query = "SELECT supplier_name FROM supplier WHERE hashed_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $supplier_id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($current_name);
            $stmt->fetch();

            if ($stmt->num_rows == 0) {
                echo json_encode(["status" => "error", "message" => "supplier not found."]);
                exit;
            }
            $stmt->close();

            

            // Update the supplier
            $update_query = "UPDATE supplier SET supplier_name = ?, `date` = ?, local_international = ? WHERE hashed_id = ?";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("ssss", $supplier_name, $currentDateTime, $supplier_type, $supplier_id);

            if ($stmt_update->execute()) {
                // Log the update
                $log_query = "INSERT INTO logs (`title`, `action`, user_id, `date`) VALUES (?, ?, ?, ?)";
                $stmt_log = $conn->prepare($log_query);
                $log_title = "UPDATE supplier";
                $log_action = "Updated supplier name from '$current_name' to '$supplier_name' and set the supplier type to '$supplier_type'";
                $stmt_log->bind_param("ssss", $log_title, $log_action, $user_id, $currentDateTime);
                $stmt_log->execute();
                $stmt_log->close();

                echo json_encode(["status" => "success", "message" => "supplier updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update supplier."]);
            }

            $stmt_update->close();
        }
    } elseif($type === "logistic"){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $logistic_name = trim($_POST['logistic_name']);
            $logistic_id = trim($_POST['id']);

            // Validate input
            if (empty($logistic_name) || empty($logistic_id)) {
                echo json_encode(["status" => "error", "message" => "All fields are required."]);
                exit;
            }

            // Fetch the current logistic name
            $query = "SELECT logistic_name FROM logistic_partner WHERE hashed_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $logistic_id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($current_name);
            $stmt->fetch();

            if ($stmt->num_rows == 0) {
                echo json_encode(["status" => "error", "message" => "logistic not found."]);
                exit;
            }
            $stmt->close();

            // If the new name is the same as the current name, return "No changes"
            if ($current_name === $logistic_name) {
                echo json_encode(["status" => "no_change", "message" => "No changes detected."]);
                exit;
            }

            // Update the logistic
            $update_query = "UPDATE logistic_partner SET logistic_name = ?, date = NOW() WHERE hashed_id = ?";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("ss", $logistic_name, $logistic_id);

            if ($stmt_update->execute()) {
                // Log the update
                $log_query = "INSERT INTO logs (`title`, `action`, user_id, `date`) VALUES (?, ?, ?, ?)";
                $stmt_log = $conn->prepare($log_query);
                $log_title = "UPDATE logistic";
                $log_action = "Updated logistic name from '$current_name' to '$logistic_name'";
                $stmt_log->bind_param("ssss", $log_title, $log_action, $user_id, $currentDateTime);
                $stmt_log->execute();
                $stmt_log->close();

                echo json_encode(["status" => "success", "message" => "logistic updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update logistic."]);
            }

            $stmt_update->close();
        }
    } elseif($type === "courier"){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $courier_name = trim($_POST['courier_name']);
            $courier_id = trim($_POST['id']);

            // Validate input
            if (empty($courier_name) || empty($courier_id)) {
                echo json_encode(["status" => "error", "message" => "All fields are required."]);
                exit;
            }

            // Fetch the current courier name
            $query = "SELECT courier_name FROM courier WHERE hashed_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $courier_id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($current_name);
            $stmt->fetch();

            if ($stmt->num_rows == 0) {
                echo json_encode(["status" => "error", "message" => "courier not found."]);
                exit;
            }
            $stmt->close();

            // If the new name is the same as the current name, return "No changes"
            if ($current_name === $courier_name) {
                echo json_encode(["status" => "no_change", "message" => "No changes detected."]);
                exit;
            }

            // Update the courier
            $update_query = "UPDATE courier SET courier_name = ?, date = NOW() WHERE hashed_id = ?";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("ss", $courier_name, $courier_id);

            if ($stmt_update->execute()) {
                // Log the update
                $log_query = "INSERT INTO logs (`title`, `action`, user_id, `date`) VALUES (?, ?, ?, ?)";
                $stmt_log = $conn->prepare($log_query);
                $log_title = "UPDATE courier";
                $log_action = "Updated courier name from '$current_name' to '$courier_name'";
                $stmt_log->bind_param("ssss", $log_title, $log_action, $user_id, $currentDateTime);
                $stmt_log->execute();
                $stmt_log->close();

                echo json_encode(["status" => "success", "message" => "courier updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update courier."]);
            }

            $stmt_update->close();
        }
    } elseif($type === "location"){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $location_name = trim($_POST['location_name']);
            $location_id = trim($_POST['id']);

            // Validate input
            if (empty($location_name) || empty($location_id)) {
                echo json_encode(["status" => "error", "message" => "All fields are required."]);
                exit;
            }

            // Fetch the current location name
            $query = "SELECT location_name FROM item_location WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $location_id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($current_name);
            $stmt->fetch();

            if ($stmt->num_rows == 0) {
                echo json_encode(["status" => "error", "message" => "location not found."]);
                exit;
            }
            $stmt->close();

            // If the new name is the same as the current name, return "No changes"
            if ($current_name === $location_name) {
                echo json_encode(["status" => "no_change", "message" => "No changes detected."]);
                exit;
            }

            // Update the location
            $update_query = "UPDATE item_location SET location_name = ? WHERE id = ?";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("ss", $location_name, $location_id);

            if ($stmt_update->execute()) {
                // Log the update
                $log_query = "INSERT INTO logs (`title`, `action`, user_id, `date`) VALUES (?, ?, ?, ?)";
                $stmt_log = $conn->prepare($log_query);
                $log_title = "UPDATE location";
                $log_action = "Updated location name from '$current_name' to '$location_name'";
                $stmt_log->bind_param("ssss", $log_title, $log_action, $user_id, $currentDateTime);
                $stmt_log->execute();
                $stmt_log->close();

                echo json_encode(["status" => "success", "message" => "location updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update location."]);
            }

            $stmt_update->close();
        }
    }
}

$conn->close();
?>
