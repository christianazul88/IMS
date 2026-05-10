<?php
include "../config/database.php"; // Ensure this includes a $conn object for MySQLi
include "../config/on_session.php";
$warehouse_for_outbound = $_SESSION['warehouse_outbound'];
$warehouse_sql = "SELECT hashed_id FROM warehouse WHERE warehouse_name = '$warehouse_for_outbound' LIMIT 1";
$res = $conn->query($warehouse_sql);
if($res->num_rows>0){
    $row = $res->fetch_assoc();
    $warehouse_for_outbound = $row['hashed_id'];
}
$outbound_id = $_SESSION['outbound_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['barcode'])) {
    $barcode = $_POST['barcode'];

    // Use MySQLi's ? placeholder
    $stmt = $conn->prepare("
        SELECT 
            product.description, 
            brand.brand_name, 
            category.category_name, 
            stocks.batch_code, 
            stocks.capital
        FROM 
            product
        JOIN brand ON product.brand = brand.hashed_id
        JOIN category ON product.category = category.hashed_id
        JOIN stocks ON product.hashed_id = stocks.product_id
        WHERE stocks.unique_barcode = ? AND stocks.item_status = 0 AND stocks.warehouse = '$warehouse_for_outbound'
    ");

    if (!$stmt) {
        echo json_encode(["error" => "Failed to prepare statement: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("s", $barcode); // Bind the barcode to the query
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product) {
        $jsonFile = $outbound_id . '.json';

        // Ensure the JSON file exists or create it
        if (!file_exists($jsonFile)) {
            file_put_contents($jsonFile, json_encode([], JSON_PRETTY_PRINT));
        }

        // Read existing data
        $existingData = json_decode(file_get_contents($jsonFile), true) ?: [];

        // Check if barcode already exists
        $barcodeExists = false;
        foreach ($existingData as $existingProduct) {
            if ($existingProduct['barcode'] === $barcode) {
                $barcodeExists = true;
                break;
            }
        }

        if ($barcodeExists) {
            echo json_encode(["error" => "Barcode already listed."]);
        } else {
            // Append the new product if barcode doesn't exist
            $data = [
                "barcode" => $barcode,
                "product_description" => $product['description'] ?? '',
                "batch_num" => $product['batch_code'] ?? '',
                "brand_name" => $product['brand_name'] ?? '',
                "category_name" => $product['category_name'] ?? '',
                "capital" => $product['capital'] ?? ''
            ];

            $existingData[] = $data;

            // Save updated data back to the JSON file
            file_put_contents($jsonFile, json_encode($existingData, JSON_PRETTY_PRINT));

            echo json_encode($existingData); // Return updated JSON data
        }
    } else {
        echo json_encode(["error" => "No product found for the given barcode."]);
    }

    $stmt->close();
} else {
    echo json_encode(["error" => "Invalid request."]);
}

$conn->close();
?>
