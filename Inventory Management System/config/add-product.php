<?php
// Include the database connection file
include('database.php');
include('on_session.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $productDescription = $_POST['product_description'] ?? '';
    $category = $_POST['category'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $parentBarcode = $_POST['parent_barcode'] ?? '';
    $safety = $_POST['safety'];

    $voltage  = $_POST['volt'] ?? '';
    $amperage = $_POST['amp'] ?? '';
    $pin_size = $_POST['pin'] ?? '';
 
    /**
     * Makes sure a lookup table exists, then inserts the given value
     * into it only if that value isn't already present.
     *
     * @param mysqli $conn
     * @param string $tableName   e.g. 'voltage'
     * @param string $columnName  e.g. 'volt_value'
     * @param string $value       the value coming from the form
     */
    function ensureLookupValue($conn, $tableName, $columnName, $value) {
        // Nothing to store if the field was left empty
        if ($value === null || $value === '') {
            return;
        }
 
        // Create the table if it doesn't exist yet
        $createSql = "CREATE TABLE IF NOT EXISTS `$tableName` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `$columnName` VARCHAR(20) NULL
        )";
        $conn->query($createSql);
 
        // Check whether the value already exists
        $checkSql = "SELECT `id` FROM `$tableName` WHERE `$columnName` = ? LIMIT 1";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("s", $value);
        $stmt->execute();
        $stmt->store_result();
        $alreadyExists = $stmt->num_rows > 0;
        $stmt->close();
 
        // Only insert if it doesn't exist yet
        if (!$alreadyExists) {
            $insertSql = "INSERT INTO `$tableName` (`$columnName`) VALUES (?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("s", $value);
            $insertStmt->execute();
            $insertStmt->close();
        }
    }
 
    // Apply the same "create table if needed, insert if new" logic
    // for each of the three lookup fields.
    ensureLookupValue($conn, 'voltage', 'volt_value', $voltage);
    ensureLookupValue($conn, 'amperage', 'amp_value', $amperage);
    ensureLookupValue($conn, 'pin', 'pin_size_value', $pin_size);

    function generateUniqueBarcode($conn) {
        do {
            $barcode = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            $query = "SELECT COUNT(*) FROM product WHERE parent_barcode = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $barcode);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
        } while ($count > 0);
        return $barcode;
    }

    if (empty($parentBarcode)) {
        $parentBarcode = generateUniqueBarcode($conn);
    }

    // Process multiple image uploads
    $productImages = $_FILES['product_image'] ?? null;
    $imageBlobs = [];

    if ($productImages && is_array($productImages['tmp_name'])) {
        $totalImages = count($productImages['tmp_name']);

        if ($totalImages > 10) {
            header("Location: ../Product-list/?success=false&err=max_img");
            exit;
        }

        for ($i = 0; $i < $totalImages; $i++) {
            if ($productImages['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $productImages['tmp_name'][$i];
                $imageData = file_get_contents($tmpName);
                $imageBlobs[] = base64_encode($imageData); // Store as base64 for safe serialization
            }
        }
    }

    // Convert array of base64-encoded images to a single string for BLOB storage
    $finalBlobData = serialize($imageBlobs); // Or json_encode($imageBlobs)

    $currentDateTime = date('Y-m-d H:i:s'); // Add this if not already defined

    // Prepare the SQL statement to insert product data
    $sql = "INSERT INTO product (`description`, category, brand, parent_barcode, product_img, `date`, `user_id`, `safety`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $productDescription, $category, $brand, $parentBarcode, $finalBlobData, $currentDateTime, $user_id, $safety);

    if ($stmt->execute()) {
        $product_id = $stmt->insert_id;
        $hashed_product_id = hash('sha256', $product_id);
        $update = "UPDATE product SET hashed_id = '$hashed_product_id' WHERE id = '$product_id'";
        if ($conn->query($update) === TRUE) {
            header("Location: ../Product-list/?success=true");
        }
    } else {
        $error_message = "Error: " . $stmt->error;
        header("Location: ../Product-list/?success=false&err=$error_message");
    }

    $stmt->close();
    $conn->close();
}
?>
