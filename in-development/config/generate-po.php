<?php
include "database.php";
include "on_session.php";

$po_id = $_POST['poid'];
$selected_warehouse = $_SESSION['selected_warehouse_id'];
$user_id = $_SESSION['user_id'];  // Ensure this is defined

$sql = "SELECT warehouse_name FROM warehouse WHERE hashed_id = '$selected_warehouse' LIMIT 1";
$result = $conn->query($sql);
if($result->num_rows>0){
    $row=$result->fetch_assoc();
    $powarehouse_name = $row['warehouse_name'];
}
$po_logs = "SELECT po.*, u.user_fname, u.user_lname, s.supplier_name
            FROM purchased_order po
            LEFT JOIN users u ON u.hashed_id = po.user_id
            LEFT JOIN supplier s ON s.hashed_id = po.supplier
            WHERE po.id = '$po_id' AND po.warehouse = '$selected_warehouse' AND po.user_id = '$user_id'
            ORDER BY po.id DESC LIMIT 1";

$result = mysqli_query($conn, $po_logs);
$row = $result->fetch_assoc();

if ($row) {  // Check if the query returned any rows
    $po_id = $row['id'];
    $po_supplier = $row['supplier_name'];
    $pdf = $row['pdf'];
    $pdf_creator = $row['user_fname'] . " " . $row['user_lname'];

    $po_content_query = "SELECT po.*, p.description, p.parent_barcode, c.category_name, b.brand_name FROM purchased_order_content po
                            LEFT JOIN product p ON p.hashed_id = po.product_id
                            LEFT JOIN category c ON c.hashed_id = p.category
                            LEFT JOIN brand b ON b.hashed_id = p.brand 
                            WHERE po_id = '$po_id'";
    $po_content_result = mysqli_query($conn, $po_content_query);
    $orders = [];  // Initialize the array for rows

    if ($po_content_result->num_rows > 0) {
        while ($row = $po_content_result->fetch_assoc()) {
            $orders[] = '<tr>
                            <td><small>' . $row['description'] . '</small></td>
                            <td><small>' . $row['brand_name'] . '</small></td>
                            <td><small>' . $row['category_name'] . '</small></td>
                            <td><small>' . $row['qty'] . '</small></td>
                        </tr>';
        }
    }

    // Join rows into HTML
    $orderRows = implode("\n", $orders);

    $data = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order</title>
    <style>
        body {
        font-family: \'Calibri\', sans-serif; /* Calibri font applied */
        margin: 0;
        padding: 0;
        }
        .container {
        margin: 20px auto;
        padding: 20px;
        }
        .logo-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        }
        
        .company-name {
        font-size: 24px;
        font-weight: bold;
        text-align: center;
        flex: 1;
        }
        
        .header {
        text-align: center;
        margin-bottom: 20px;
        }
        .header h1 {
        font-size: 26px;
        margin: 0;
        }
        .details-table {
        width: 100%;
        margin-bottom: 30px;
        }
        .details-table td {
        padding: 10px;
        text-align: left;
        vertical-align: top;
        }
        .details-table th {
        width: 30%;
        font-weight: bold;
        }
        .product-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        }
        .product-table th, .product-table td {
        border: 1px solid #ccc;
        padding: 10px;
        text-align: left;
        }
        .product-table th {
        background-color: #f0f0f0;
        }
        .footer {
        margin-top: 20px;
        text-align: right;
        font-size: 14px;
        }
    </style>
    </head>
    <body>
    <div class="container">
        <!-- Logo Section -->
        <div class="logo-section">
        <img src="../../assets/img/logo/LPO Logo.png" style="width: 80px;" alt="Company Logo">
        <div class="company-name">
            <!-- Company name or other text could be here if needed -->
        </div>
        </div>
        
        <!-- Header -->
        <div class="header">
        <h1>Purchase Order</h1>
        </div>

        <!-- Details Table -->
        <table class="details-table">
        <tr>
            <th style="text-align: left;">P.O #:</th>
            <td>' . $po_id . '</td>
            <th style="text-align: left;">Supplier:</th>
            <td>' . htmlspecialchars($po_supplier) . '</td>
        </tr>
        <tr>
            <th style="text-align: left;">Order Date:</th>
            <td>2025-01-07</td>
            <th style="text-align: left;">Ship To:</th>
            <td>' . htmlspecialchars($powarehouse_name) . '</td>
        </tr>
        </table>

        <!-- Product Table with Description, Brand, Category, and Quantity -->
        <table class="product-table">
        <thead>
            <tr>
            <th>Description</th>
            <th>Brand</th>
            <th>Category</th>
            <th>Quantity</th>
            </tr>
        </thead>
        <tbody>
            ' . $orderRows . '
        </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
        <p>Prepared by: <span>' . htmlspecialchars($pdf_creator) . '</span></p>
        </div>
        <div class="barcode-container" style="align-items: center; text-align: center;">
            <!-- Barcode Image on Top Right Corner -->
            <img src="../../assets/barcode/barcode.php?codetype=Code128&size=50&text=LPO 4-8888901000-' . $po_id . '&print=true" alt="Company Barcode" class="barcode-img" style="width: 120px;">
        </div>
    </div>
    </body>
    </html>
    ';

    $pdfname = "PO-" . $po_id . " - " . $po_supplier . ".pdf";
    // Initialize mPDF and generate the PDF
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($data);
    $pdfData = $mpdf->Output('', 'S'); // Get PDF as a string

    // Escape the binary PDF data for insertion into the database
    $pdfData = $conn->real_escape_string($pdfData);
    if (empty($pdf)) {
        $update_po = "UPDATE purchased_order SET pdf = '$pdfData' WHERE id = '$po_id'";
        if ($conn->query($update_po) === TRUE) {
            
            header("location: ../PO-logs/?generate=true");
        } else {
            echo "Error updating record: " . $conn->error;
        }
    }
} else {
    echo "No purchase order found.";
}

$conn->close();
