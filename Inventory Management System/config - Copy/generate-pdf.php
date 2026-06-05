<?php

// Initialize mPDF
$mpdf = new \Mpdf\Mpdf();

// Write HTML content to the PDF
$html = '<h1>Hello, this is a PDF!</h1>';
$mpdf->WriteHTML($html);

// Output the PDF as a string (binary data) instead of saving it to a file
$pdfData = $mpdf->Output('', 'S'); // 'S' for output as a string




// -------------------------------------
// require_once '../../vendor/autoload.php';

// $mpdf = new \Mpdf\Mpdf();
// $mpdf->WriteHTML($data);

// // Save a copy to the specified folder
// $pdfPath = '../../PDFs/' . $pdfname;
// $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE); // Saves to ../../PDFs/

// Prompt download for the user
// $mpdf->Output($pdfname, \Mpdf\Output\Destination::DOWNLOAD); // Initiates download
