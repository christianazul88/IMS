<?php 
error_reporting(E_ALL);
ini_set('max_execution_time', 300);  // 5 minutes
ini_set('memory_limit', '4G');  // Set memory limit to 4 GB
ini_set('display_errors', 1); 
include "database.php";
include "on_session.php";

require_once '../../vendor/autoload.php'; // Load mPDF
use Picqer\Barcode\BarcodeGeneratorPNG;

// Set the header to display the image directly in the browser
header('Content-Type: image/png');

$start = $_GET['start'];
$end = $_GET['end'];
$barcode = $_GET['barcode'];
$response = array('success' => false, 'message' => 'Something went wrong.');

if (isset($_GET['success']) && $_GET['success'] == 0 && isset($_SESSION['unique_key'])) {
    $uniqueKey = $_SESSION['unique_key'];
    
    // Query to fetch required data
    $query = "
        SELECT 
            s.product_id,
            p.description AS product_description,
            b.brand_name,
            c.category_name
        FROM stocks s
        JOIN product p ON s.product_id = p.hashed_id
        JOIN brand b ON p.brand = b.hashed_id
        JOIN category c ON p.category = c.hashed_id
        WHERE s.unique_key = ? AND s.parent_barcode = ?
        GROUP BY s.product_id, s.unique_key";
  
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $uniqueKey, $barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    // Loop through the results and generate PDFs
    while ($row = $result->fetch_assoc()) {
        $productDescription = $row['product_description'];
        $brandName = $row['brand_name'];
        $categoryName = $row['category_name'];
        $product_id = $row['product_id'];
        $images = [];
        $unique_barcode_query = "SELECT unique_barcode, barcode_extension FROM stocks WHERE product_id = '$product_id' AND unique_key = '$uniqueKey' ";
        $res = $conn->query($unique_barcode_query);
        if($res->num_rows > 0){
            while($row = $res->fetch_assoc()){
                $unique_barcode = $row['unique_barcode'];
                $barcode_ext = $row['barcode_extension'];
                if($barcode_ext >= $start && $barcode_ext <= $end){
                    // Create an instance of the barcode generator
                    $generator = new BarcodeGeneratorPNG();

                    // === Adjust Barcode Line Thickness ===
                    // Scale factor affects the thickness (default = 2, increase for thicker bars)
                    $scaleFactor = 1;  // Higher = thicker lines

                    // Generate the barcode with adjusted thickness
                    $barcodeData = $generator->getBarcode($unique_barcode, $generator::TYPE_CODE_128, $scaleFactor);

                    // === Fixed Background Size (30mm x 7mm @ 300 DPI) ===
                    $bgWidth = 354;  // 30mm ≈ 354px
                    $bgHeight = 73;  // 7mm ≈ 83px

                    // === Create a White Background ===
                    $image = imagecreatetruecolor($bgWidth, $bgHeight);
                    $white = imagecolorallocate($image, 255, 255, 255); // Define white color
                    imagefill($image, 0, 0, $white); // Fill background with white

                    // === Create Barcode Image from Raw Data ===
                    $barcodeImage = imagecreatefromstring($barcodeData);

                    // === Resize Barcode to Fully Fit the Background ===
                    $resizedBarcode = imagecreatetruecolor($bgWidth, $bgHeight);
                    imagefill($resizedBarcode, 0, 0, $white); // Ensure background remains white
                    imagecopyresampled($resizedBarcode, $barcodeImage, 0, 0, 0, 0, $bgWidth, $bgHeight, imagesx($barcodeImage), imagesy($barcodeImage));

                    // === Place the Barcode Directly onto the Background ===
                    imagecopy($image, $resizedBarcode, 0, 0, 0, 0, $bgWidth, $bgHeight);

                    ob_start(); // Start output buffering
                    imagepng($image);
                    $barcodeImageData = ob_get_contents(); // Capture the image data
                    ob_end_clean(); // Clean the output buffer

                    // Convert image data to base64 for embedding in HTML
                    $barcodeBase64 = 'data:image/png;base64,' . base64_encode($barcodeImageData);

                    // Store the barcode image as an <img> tag in the array
                    $images[] = "<div class='container'><img src='{$barcodeBase64}' alt='{$unique_barcode}'><b><span>LPO {$unique_barcode}</span></b></div>";
                }
            }
        }
        
        // Generate HTML content for PDF
        $html = "
        <html>
        <head>
            <style>
                body {
                    text-align: center;
                }
                .container {
                    max-width: 100%;
                    max-height: 100%;
                    padding-left:5;
                    padding-right: 5;
                    letter-spacing: 1px;
                    text-align: center;
                    font-size: 8px;
                    object-fit: contain;
                }
                img {
                    max-width: 100%;
                }
            </style>
        </head>
        <body>
                "  . implode('', $images) .  "
        </body>
        </html>";

        // Initialize mPDF with custom paper size for thermal printer (58mm x 30mm) and no margins
        $mpdf = new \Mpdf\Mpdf([
            'format' => [30, 10], // 58mm width x 30mm height
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);

        $mpdf->WriteHTML($html);

        // Output the PDF for download
        $fileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', "$productDescription - $brandName - $categoryName") . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $mpdf->Output($fileName, 'D');
        exit; // Stop execution after sending the first PDF
    }

    $stmt->close();
}

echo json_encode($response); // Return JSON response if no PDF is generated
exit;
?>
