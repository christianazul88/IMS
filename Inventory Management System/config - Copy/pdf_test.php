<?php
error_reporting(E_ALL);
ini_set('display_errors', 1); 
require_once '../../vendor/autoload.php'; // Load mPDF

// Generate random content
function getRandomString($length = 100) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Create mPDF instance
$mpdf = new \Mpdf\Mpdf();

// Define HTML content
$html = "<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Random Content</h1>
    <p>" . getRandomString(500) . "</p>
</body>
</html>";

// Write content to PDF
$mpdf->WriteHTML($html);

// Output PDF for download
$fileName = "random_content.pdf";
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
$mpdf->Output($fileName, 'D');
exit;
?>