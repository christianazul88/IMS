<?php
// Include necessary files for database connection and session handling
include '../config/database.php';
include '../config/on_session.php';

$unique_key = $_SESSION['unique_key'];
$po_id = $_SESSION['inbound_po_id'];
$received_date = $_SESSION['inbound_received_date'];
$warehouse_inbound = $_SESSION['inbound_warehouse'];

$sql = "SELECT warehouse_name FROM warehouse WHERE hashed_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $warehouse_inbound);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $inbound_warehouse = $warehouse_inbound;
    $inbound_warehouse_name = $row['warehouse_name'];
}

$sql = "INSERT INTO inbound_logs SET po_id = ?, date_received = ?, user_id = ?, warehouse = ?, unique_key = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $po_id, $received_date, $user_id, $warehouse_inbound, $unique_key);
if ($stmt->execute()) {
    $inbound_id = $conn->insert_id;
}

$response = ['status' => 'error', 'message' => 'No data submitted.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csv_unique = $_POST['csv_unique'];
    $items = $_POST['item'];
    $keywords = $_POST['keyword'];
    $quantities = $_POST['qty'];
    $prices = $_POST['price'];
    $suppliers = $_POST['supplier'];
    $barcodes = $_POST['barcode'];
    $batches = $_POST['batch'];
    $brands = $_POST['brand'];
    $categories = $_POST['category'];
    $safeties = $_POST['safety'];

    $checkedCount = count($csv_unique);
    $success = 0;

    foreach ($csv_unique as $index) {
        $item = $items[$index - 1];
        $keyword = $keywords[$index - 1];
        $quantity = $quantities[$index - 1];
        $price = $prices[$index - 1];
        $supplier = $suppliers[$index - 1];
        $barcode = $barcodes[$index - 1];
        $batch = $batches[$index - 1];
        $brand = $brands[$index - 1];
        $category = $categories[$index - 1];
        $safety = $safeties[$index - 1];

        // Handle brand
        $sql = "SELECT * FROM brand WHERE brand_name = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $brand);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $sql = "INSERT INTO brand (brand_name, user_id, `date`) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $brand, $user_id, $currentDateTime);
            $stmt->execute();
            $brand_id = $conn->insert_id;
            $hashed_brand_id = hash('sha256', $brand_id);

            $sql = "UPDATE brand SET hashed_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_brand_id, $brand_id);
            $stmt->execute();
        } else {
            $row = $result->fetch_assoc();
            $hashed_brand_id = $row['hashed_id'];
        }

        // Handle category
        $sql = "SELECT * FROM category WHERE category_name = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $sql = "INSERT INTO category (category_name, user_id, `date`) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $category, $user_id, $currentDateTime);
            $stmt->execute();
            $category_id = $conn->insert_id;
            $hashed_category_id = hash('sha256', $category_id);

            $sql = "UPDATE category SET hashed_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_category_id, $category_id);
            $stmt->execute();
        } else {
            $row = $result->fetch_assoc();
            $hashed_category_id = $row['hashed_id'];
        }

        // Handle supplier
        $sql = "SELECT * FROM supplier WHERE supplier_name = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $supplier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $sql = "INSERT INTO supplier (supplier_name, `date`, user_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $supplier, $currentDateTime, $user_id);
            $stmt->execute();
            $supplier_id = $conn->insert_id;
            $hashed_supplier_id = hash('sha256', $supplier_id);

            $sql = "UPDATE supplier SET hashed_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_supplier_id, $supplier_id);
            $stmt->execute();
        } else {
            $row = $result->fetch_assoc();
            $hashed_supplier_id = $row['hashed_id'];
        }

        // Update inbound logs
        $sql = "UPDATE inbound_logs SET supplier = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_supplier_id, $inbound_id);
        $stmt->execute();

        // Handle product
        $sql = "SELECT * FROM product WHERE parent_barcode = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $product_id = $row['id'];
            $hashed_product_id = $row['hashed_id'];

            $sql = "UPDATE product SET hashed_id = ?, safety = ?, keyword = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $hashed_product_id, $safety, $keyword, $product_id);
            $stmt->execute();
        } else {
            $sql = "INSERT INTO product (description, brand, category, parent_barcode, `date`, user_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $item, $hashed_brand_id, $hashed_category_id, $barcode, $currentDateTime, $user_id);
            $stmt->execute();
            $product_id = $conn->insert_id;
            $hashed_product_id = hash('sha256', $product_id);

            $sql = "UPDATE product SET hashed_id = ?, safety = ?, keyword = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $hashed_product_id, $safety, $keyword, $product_id);
            $stmt->execute();
        }

        // Barcode generation
        $sql = "SELECT barcode_extension FROM stocks WHERE parent_barcode = ? ORDER BY barcode_extension DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result();
        $last_unique_suffix = 0;
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_unique_suffix = (int) $row['barcode_extension'];
        }

        for ($i = 1; $i <= $quantity; $i++) {
            $new_suffix = $last_unique_suffix + $i;
            $unique_barcode = $barcode . '-' . $new_suffix;

            $sql = "INSERT INTO stocks (unique_barcode, product_id, parent_barcode, batch_code, capital, warehouse, supplier, `date`, user_id, inbound_id, unique_key, barcode_extension, safety) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssssssss", $unique_barcode, $hashed_product_id, $barcode, $batch, $price, $warehouse_inbound, $hashed_supplier_id, $currentDateTime, $user_id, $inbound_id, $unique_key, $new_suffix, $safety);
            if ($stmt->execute()) {
                $stock_id = $conn->insert_id;
                $hash_stock = hash('sha256', $stock_id);

                $sql = "UPDATE stocks SET hashed_id = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $hash_stock, $stock_id);
                $stmt->execute();

                $sql = "INSERT INTO stock_timeline (unique_barcode, title, `action`, `date`, user_id) VALUES (?, 'INBOUND', ?, ?, ?)";
                $action_text = "Product was inbounded to $inbound_warehouse_name";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $unique_barcode, $action_text, $currentDateTime, $user_id);
                $stmt->execute();

                $sql = "INSERT INTO logs (title, `action`, `date`, user_id) VALUES ('INBOUND', ?, ?, ?)";
                $log_text = "Inbound Reference no. $inbound_id has been successfully inbounded to $inbound_warehouse_name.";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $log_text, $currentDateTime, $user_id);
                $stmt->execute();
            } else {
                error_log("Failed to insert stock: " . $conn->error);
            }
        }
        $success++;
    }

    if ($success > 0) {
        $response = [
            'status' => 'success',
            'message' => "$success out of $checkedCount records saved successfully."
        ];
    } else {
        $response = [
            'status' => 'info',
            'message' => 'No valid data to save.'
        ];
    }
}

echo json_encode($response);
$conn->close();
?>
