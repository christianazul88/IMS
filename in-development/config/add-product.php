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
