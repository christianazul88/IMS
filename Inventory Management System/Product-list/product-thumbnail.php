<?php
// Serve one small table thumbnail on demand instead of embedding every image
// into the product-list HTML response.
include "../config/database.php";
include "../config/on_session.php";

if (strpos($access, "product_list") === false && $user_position_name !== "Administrator") {
    http_response_code(403);
    exit;
}

$fallback = "../../assets/img/def_img.png";
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if (!$productId) {
    header('Location: ' . $fallback, true, 302);
    exit;
}

$stmt = $conn->prepare("SELECT product_img FROM product WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

$images = !empty($row['product_img']) ? @unserialize($row['product_img']) : null;
$imageBase64 = is_array($images) && !empty($images[0]) ? $images[0] : null;
$imageBinary = $imageBase64 ? base64_decode($imageBase64, true) : false;

if ($imageBinary === false) {
    header('Location: ' . $fallback, true, 302);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->buffer($imageBinary);
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($mimeType, $allowedTypes, true)) {
    header('Location: ' . $fallback, true, 302);
    exit;
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . strlen($imageBinary));
header('Cache-Control: private, max-age=86400');
echo $imageBinary;
