<?php
include "database.php";
include "on_session.php";

ini_set('memory_limit', '512M');

require_once '../../vendor/autoload.php';

use Picqer\Barcode\BarcodeGeneratorPNG;

// Accept POST first (new method), fallback to GET
$unique_barcodes = [];

if (!empty($_POST['product_id'])) {
    $unique_barcodes = $_POST['product_id'];
} elseif (!empty($_GET['product_id'])) {
    $unique_barcodes = $_GET['product_id'];
}

if (!empty($unique_barcodes)) {

    // Ensure array
    if (!is_array($unique_barcodes)) {
        $unique_barcodes = [$unique_barcodes];
    }

    $barcode_images = [];

    foreach ($unique_barcodes as $unique_barcode) {

        $generator = new BarcodeGeneratorPNG();

        // Barcode thickness
        $scaleFactor = 1;

        $barcodeData = $generator->getBarcode(
            $unique_barcode,
            $generator::TYPE_CODE_128,
            $scaleFactor
        );

        // Background size (30mm x 7mm approx)
        $bgWidth = 354;
        $bgHeight = 73;

        // Create background
        $image = imagecreatetruecolor($bgWidth, $bgHeight);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        // Create barcode image
        $barcodeImage = imagecreatefromstring($barcodeData);

        // Resize barcode
        $resizedBarcode = imagecreatetruecolor($bgWidth, $bgHeight);
        imagefill($resizedBarcode, 0, 0, $white);

        imagecopyresampled(
            $resizedBarcode,
            $barcodeImage,
            0,
            0,
            0,
            0,
            $bgWidth,
            $bgHeight,
            imagesx($barcodeImage),
            imagesy($barcodeImage)
        );

        imagecopy($image, $resizedBarcode, 0, 0, 0, 0, $bgWidth, $bgHeight);

        ob_start();
        imagepng($image);
        $barcodeImageData = ob_get_contents();
        ob_end_clean();

        $barcodeBase64 = 'data:image/png;base64,' . base64_encode($barcodeImageData);

        $barcode_images[] = "
        <div class='container'>
            <img src='{$barcodeBase64}' alt='{$unique_barcode}'>
            <b><span>LPO {$unique_barcode}</span></b>
        </div>";
    }

    // Generate HTML
    $html = "
    <html>
    <head>
        <style>
            body {
                text-align:center;
            }

            .container {
                max-width:100%;
                max-height:100%;
                padding-left:5px;
                padding-right:5px;
                letter-spacing:1px;
                text-align:center;
                font-size:8px;
                object-fit:contain;
            }

            img {
                max-width:100%;
            }
        </style>
    </head>
    <body>
        " . implode('', $barcode_images) . "
    </body>
    </html>";

    $mpdf = new \Mpdf\Mpdf([
        'format' => [30, 10],
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_top' => 0,
        'margin_bottom' => 0
    ]);

    $mpdf->WriteHTML($html);

    $mpdf->Output('Product_Barcodes.pdf', 'D'); // PDF download with correct filename
    exit;
}

echo "No product IDs received.";
















//original code

// <?php
// include "database.php"; // Include database connection
// include "on_session.php"; // Include session handling
// ini_set('max_input_vars', '100000');
// ini_set('max_input_time', '300');
// ini_set('memory_limit', '512M');

// require_once '../../vendor/autoload.php'; // Load mPDF
// use Picqer\Barcode\BarcodeGeneratorPNG;

// // Set the header to display the image directly in the browser
// header('Content-Type: image/png');

// if (isset($_POST['product_id'])) {
//     $unique_barcodes = $_POST['product_id'];
    
//     // Ensure product_ids is an array
//     if (!is_array($unique_barcodes)) {
//         $unique_barcodes = [$unique_barcodes];
//     }

//     $barcode_images = []; // Initialize array

//     foreach ($unique_barcodes as $unique_barcode) {
//         // Create an instance of the barcode generator
//         $generator = new BarcodeGeneratorPNG();

//         // === Adjust Barcode Line Thickness ===
//         // Scale factor affects the thickness (default = 2, increase for thicker bars)
//         $scaleFactor = 1;  // Higher = thicker lines

//         // Generate the barcode with adjusted thickness
//         $barcodeData = $generator->getBarcode($unique_barcode, $generator::TYPE_CODE_128, $scaleFactor);

//         // === Fixed Background Size (30mm x 7mm @ 300 DPI) ===
//         $bgWidth = 354;  // 30mm ≈ 354px
//         $bgHeight = 73;  // 7mm ≈ 83px

//         // === Create a White Background ===
//         $image = imagecreatetruecolor($bgWidth, $bgHeight);
//         $white = imagecolorallocate($image, 255, 255, 255); // Define white color
//         imagefill($image, 0, 0, $white); // Fill background with white

//         // === Create Barcode Image from Raw Data ===
//         $barcodeImage = imagecreatefromstring($barcodeData);

//         // === Resize Barcode to Fully Fit the Background ===
//         $resizedBarcode = imagecreatetruecolor($bgWidth, $bgHeight);
//         imagefill($resizedBarcode, 0, 0, $white); // Ensure background remains white
//         imagecopyresampled($resizedBarcode, $barcodeImage, 0, 0, 0, 0, $bgWidth, $bgHeight, imagesx($barcodeImage), imagesy($barcodeImage));

//         // === Place the Barcode Directly onto the Background ===
//         imagecopy($image, $resizedBarcode, 0, 0, 0, 0, $bgWidth, $bgHeight);

//         ob_start(); // Start output buffering
//         imagepng($image);
//         $barcodeImageData = ob_get_contents(); // Capture the image data
//         ob_end_clean(); // Clean the output buffer

//         // Convert image data to base64 for embedding in HTML
//         $barcodeBase64 = 'data:image/png;base64,' . base64_encode($barcodeImageData);

//         // Store the barcode image as an <img> tag in the array
//         $barcode_images[] = "<div class='container'><img src='{$barcodeBase64}' alt='{$unique_barcode}'><b><span>LPO {$unique_barcode}</span></b></div>";

//     }
    

//     // Generate HTML for PDF
//     $html = "
//     <html>
//     <head>
//         <style>
//             body {
//                 text-align: center;
//             }
//             .container {
//                 max-width: 100%;
//                 max-height: 100%;
//                 padding-left:5;
//                 padding-right: 5;
//                 letter-spacing: 1px;
//                 text-align: center;
//                 font-size: 8px;
//                 object-fit: contain;
//             }
//             img {
//                 max-width: 100%;
//             }
//         </style>
//     </head>
//     <body>
//         " . implode('', $barcode_images) . "
//     </body>
//     </html>";

//     // Create mPDF instance
//     $mpdf = new \Mpdf\Mpdf([
//         'format' => [30, 10], // Custom paper size (58mm x 30mm)
//         'margin_left' => 0,
//         'margin_right' => 0,
//         'margin_top' => 0,
//         'margin_bottom' => 0,
//     ]);

//     $mpdf->WriteHTML($html);
//     $pdf_content = $mpdf->Output('', 'S'); // Generate PDF as string


//     // Output PDF for download
//     header('Content-Type: application/pdf');
//     header('Content-Disposition: attachment; filename="Product_Barcodes.pdf"');
//     echo $pdf_content;
//     exit;
// }

// echo "No product IDs received.";
