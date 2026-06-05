<?php
if (isset($_GET['barcode'])) {
    $unique_barcode = $_GET['barcode'];
    // Dynamic HTML content for the PDF
    $html = "<html><head><style>body { font-family: Arial, sans-serif; }</style></head>";
    $html .= "<body>";
    $html .= "<div class='barcode-container'>";
    $html .= "<img alt='testing' src='../../assets/barcode/barcode.php?codetype=Code128&size=50&text=" . $unique_barcode . "&print=true' />";
    $html .= "</div>";
    $html .= "</body></html>";

    // Include mPDF library
    require_once '../../vendor/autoload.php';

    // Create an instance of mPDF
    $mpdf = new \Mpdf\Mpdf();

    // Write the HTML content into the PDF
    $mpdf->WriteHTML($html);  // Use $html here instead of $data

    // Output the PDF and prompt the user to download it
    $pdfname = "product_details_$unique_barcode.pdf";  // You can modify the name as needed
    $mpdf->Output($pdfname, \Mpdf\Output\Destination::DOWNLOAD);  // This triggers the download
}
?>
