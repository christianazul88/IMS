<?php
include "database.php";
require_once '../../vendor/autoload.php';

// Query to get all purchase orders
$sql = "SELECT * FROM purchased_order ORDER BY id DESC";
$res = $conn->query($sql);

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $po_id = $row["id"];
        $po_supplier = $row["supplier_name"] ?? 'Unknown Supplier'; // Adjust according to your table columns
        $pdfname = "PO-" . $po_id . " - " . $po_supplier . ".pdf";

        // Generate HTML content for each PDF
        $data = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Purchase Order ' . $po_id . '</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                /* Styles go here */
                body { font-family: Arial, sans-serif; }
                .purchase-order { width: 80%; max-width: 800px; margin: 20px auto; padding: 20px; }
                .company-info, .supplier-info { margin: 20px 0; }
                table { width: 100%; }
            </style>
        </head>
        <body>
            <div class="purchase-order">
                <h2>Purchase Order - ' . $po_id . '</h2>
                <p>Supplier: ' . $po_supplier . '</p>
                <p>Date: ' . date('F j, Y') . '</p>
                <!-- Add more content as needed -->
            </div>
        </body>
        </html>
        ';

        // Create mPDF instance and save PDF without downloading
        // $mpdf = new \Mpdf\Mpdf();
        // $mpdf->WriteHTML($data);

        // // Save the PDF to the specified folder
        // $pdfPath = '../../PDFs/' . $pdfname;
        // $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE); // Only saves to folder without triggering download

        // require_once '../../vendor/autoload.php';

        $mpdf = new \Mpdf\Mpdf();
        $mpdf->WriteHTML($data);

        // Save a copy to the specified folder
        $pdfPath = '../../PDFs/' . $pdfname;
        $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE); // Saves to ../../PDFs/
    }
}

$conn->close();
