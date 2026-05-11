<?php
include('database.php');
include('on_session.php'); 

$outbound_id = $_SESSION['outbound_id'];
$warehouse_for_outbound = $_SESSION['warehouse_outbound'];

$response = [
    'status' => 'error',
    'error' => 'Something went wrong.',
];

// Fetch warehouse hashed_id securely
$warehouse_sql = "SELECT hashed_id FROM warehouse WHERE warehouse_name = ? LIMIT 1";
$stmt = $conn->prepare($warehouse_sql);
$stmt->bind_param("s", $warehouse_for_outbound);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $warehouse = $row['hashed_id'];
} else {
    echo json_encode(['status' => 'error', 'error' => 'Invalid warehouse.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = $_POST['customer_name'] ?? '';
    $platform = $_POST['platform'] ?? '';
    $courier = $_POST['courier'] ?? '';
    $orderNo = $_POST['order_no'] ?? '';
    $orderLineID = $_POST['order_line_id'] ?? '';
    $processedBy = $_POST['processed_by'] ?? '';
    $barcodes = $_POST['barcode'] ?? [];
    $payment_method = $_POST['payment_method'];
    $sellingPrices = $_POST['selling'] ?? [];

    if (!$customerName || !$platform || !$courier || !$orderNo || !$orderLineID) {
        echo json_encode(['status' => 'error', 'error' => 'All fields are required.']);
        exit;
    }

    if (empty($barcodes) || empty($sellingPrices) || count($barcodes) !== count($sellingPrices)) {
        echo json_encode(['status' => 'error', 'error' => 'Barcodes and selling prices must match in number.']);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Insert order details
        $orderSql = "INSERT INTO outbound_logs (date_sent, warehouse, user_id, customer_fullname, courier, platform, order_num, order_line_id, hashed_id, status, payment_method) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 6, ?)";
        $stmt = $conn->prepare($orderSql);
        $stmt->bind_param("ssssssssss", $currentDateTime, $warehouse, $user_id, $customerName, $courier, $platform, $orderNo, $orderLineID, $outbound_id, $payment_method);
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert order: " . $stmt->error);
        }
        $orderID = $conn->insert_id;

        foreach ($barcodes as $key => $barcode) {
            $sellingPrice = $sellingPrices[$key];

            // Fetch product_id safely
            $product_infoQuery = "SELECT product_id FROM stocks WHERE unique_barcode = ? GROUP BY product_id";
            $stmt = $conn->prepare($product_infoQuery);
            $stmt->bind_param("s", $barcode);
            $stmt->execute();
            $product_infoRes = $stmt->get_result();
            $product_id = $product_infoRes->fetch_assoc()['product_id'] ?? null;

            // Fetch product quantity before update
            if ($product_id) {
                $product_quantity_before_query = "SELECT COUNT(unique_barcode) AS quantity FROM stocks WHERE product_id = ? AND item_status = 0 AND warehouse = ?";
                $stmt = $conn->prepare($product_quantity_before_query);
                $stmt->bind_param("ss", $product_id, $warehouse);
                $stmt->execute();
                $result = $stmt->get_result();
                $product_quantity_before = $result->fetch_assoc()['quantity'] ?? 0;
            }

            // Insert into outbound_content
            $itemSql = "INSERT INTO outbound_content (unique_barcode, sold_price, hashed_id, quantity_before, status) VALUES (?, ?, ?, ?, 6)";
            $stmt = $conn->prepare($itemSql);
            $stmt->bind_param("sdsi", $barcode, $sellingPrice, $outbound_id, $product_quantity_before);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert order item: " . $stmt->error);
            }
            $outbound_content_id = $conn->insert_id;

            // Update stock status
            $update_stock = "UPDATE stocks SET item_status = 1, outbound_id = ?, outbounded_by = ? WHERE unique_barcode = ?";
            $stmt = $conn->prepare($update_stock);
            $stmt->bind_param("sss", $outbound_id, $user_id, $barcode);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update stock: " . $stmt->error);
            }

            // Insert into stock timeline
            $action = "Outbounded to Customer: " . $customerName;
            $insert_to_item_history = "INSERT INTO stock_timeline (unique_barcode, title, action, user_id, date) VALUES (?, 'Outbound', ?, ?, ?)";
            $stmt = $conn->prepare($insert_to_item_history);
            $stmt->bind_param("ssss", $barcode, $action, $user_id, $currentDateTime);
            $stmt->execute();

            // Fetch product quantity after update
            $product_quantity_after_query = "SELECT COUNT(unique_barcode) AS quantity FROM stocks WHERE product_id = ? AND item_status = 0 AND warehouse = ?";
            $stmt = $conn->prepare($product_quantity_after_query);
            $stmt->bind_param("ss", $product_id, $warehouse);
            $stmt->execute();
            $result = $stmt->get_result();
            $product_quantity_after = $result->fetch_assoc()['quantity'] ?? 0;

            // Update outbound_content
            $update = "UPDATE outbound_content SET quantity_after = ? WHERE id = ?";
            $stmt = $conn->prepare($update);
            $stmt->bind_param("si", $product_quantity_after, $outbound_content_id);
            $stmt->execute();
        }

        // Insert log entry for outbound process completion
        $log_action = 'Outbound #' . $outbound_id . ' has been successfully processed.';
        $insert_logs = "INSERT INTO logs (title, action, user_id, date) VALUES ('OUTBOUND', ?, ?, ?)";
        $stmt = $conn->prepare($insert_logs);
        $stmt->bind_param("sss", $log_action, $user_id, $currentDateTime);
        $stmt->execute();

        $filePath = "../Outbound-form/" . $outbound_id . ".json"; // Adjust the path accordingly

        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                // File deleted successfully, now unset the session variables
                unset($_SESSION['warehouse_outbound']);
                unset($_SESSION['outbound_id']);
            } else {
                echo "Error deleting the file.";
            }
        } else {
            // File does not exist, still unset the session variables
            unset($_SESSION['warehouse_outbound']);
            unset($_SESSION['outbound_id']);
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Order saved successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'error' => 'Transaction failed: ' . $e->getMessage()]);
    }
}
?>
