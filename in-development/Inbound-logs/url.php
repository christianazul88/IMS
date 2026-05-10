<?php 
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '4G');
ini_set('display_errors', 1); 

include "../config/database.php";
include "../config/on_session.php";
require_once '../../vendor/autoload.php'; // mPDF

use Picqer\Barcode\BarcodeGeneratorPNG;

if (isset($_GET['id'])) {
    $unique_key = htmlspecialchars($_GET['id']);
    $total = 0;
    $SQL = "SELECT il.*, w.warehouse_name, u.user_fname, u.user_lname, s.supplier_name, up.position_name, s.local_international, il.staff_reason
            FROM inbound_logs il
            LEFT JOIN supplier s ON s.hashed_id = il.supplier
            LEFT JOIN users u ON u.hashed_id = il.user_id
            LEFT JOIN warehouse w ON w.hashed_id = il.warehouse
            LEFT JOIN user_position up ON up.hashed_id = u.user_position
            WHERE il.unique_key = '$unique_key'
            LIMIT 1";
    $res = $conn->query($SQL);
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $inbound_warehouse_name = $row['warehouse_name'];
        $inbound_supplier_name = $row['supplier_name'];
        $inbound_receiver = $row['user_fname'] . " " . $row['user_lname'];
        $inbound_receiver_pos = $row['position_name'];
        $supplier_info = $row['local_international'];
        $date_received = (new DateTime($row['date_received']))->format('F j, Y');
        $staff_reason = !empty($row['staff_reason']) ? $row['staff_reason'] : null;
        $authorized_reason = !empty($row['authorize_reason']) ? $row['authorize_reason'] : null;
        $void_request_date = !empty($row['date_request_void']) ? $row['date_request_void'] : null;
        $approved_void_date = !empty($row['date_approved']) ? $row['date_approved'] : null;

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Inbound Document</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    background-color: #f8f9fa;
                }
                .header, .footer {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .container {
                    max-width: 900px;
                    margin: auto;
                    background-color: #fff;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                h2, h4, h5 {
                    color: #0d6efd;
                }
                .table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                .table th, .table td {
                    border: 1px solid #dee2e6;
                    padding: 8px;
                }
                .table th {
                    background-color: #e9ecef;
                    text-align: left;
                }
                .table tfoot {
                    font-weight: bold;
                    background-color: #d1ecf1;
                }
                .text-end {
                    text-align: right;
                }
                .btn {
                    padding: 8px 12px;
                    border: none;
                    border-radius: 5px;
                    background-color: #ffc107;
                    color: black;
                    text-decoration: none;
                    font-weight: bold;
                }
                .btn:hover {
                    background-color: #e0a800;
                }
                .container-reason {
                    margin-top: 20px;
                    padding: 15px;
                    background-color: #e9f7fe;
                    border-radius: 8px;
                }
                .container-reason table {
                    width: 100%;
                }
                .container-reason th {
                    text-align: left;
                    vertical-align: top;
                    padding: 10px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Inbound Document</h2>
                    <p><strong>Reference No:</strong> <?php echo $unique_key; ?></p>
                </div>
                <h5>Sender Details</h5>
                <p><strong>Name:</strong> <?php echo $inbound_supplier_name; ?></p>
                <p><strong>Address:</strong> <?php echo $supplier_info; ?></p>
                <p><strong>Date Received:</strong> <?php echo $date_received; ?></p>

                <h5>Item Details</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item Name</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Parent Barcode</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $query = "
                        SELECT 
                            COUNT(s.product_id) AS quantity, 
                            s.capital, 
                            p.description, 
                            b.brand_name, 
                            c.category_name, 
                            p.parent_barcode
                        FROM stocks s
                        LEFT JOIN product p ON s.product_id = p.hashed_id
                        LEFT JOIN brand b ON p.brand = b.hashed_id
                        LEFT JOIN category c ON p.category = c.hashed_id
                        WHERE s.unique_key = '$unique_key'
                        GROUP BY s.product_id
                    ";
                    $result = $conn->query($query);
                    if ($result->num_rows > 0) {
                        $number = 0;
                        while ($row = $result->fetch_assoc()) {
                            $number++;
                            $description = $row['description'];
                            $brand_name = $row['brand_name'];
                            $category_name = $row['category_name'];
                            $product_quantity = $row['quantity'];
                            $parent_barcode = $row['parent_barcode'];
                            $unit_price = $row['capital'];
                            $subtotal = $unit_price * $product_quantity;
                            $total += $subtotal;
                            ?>
                            <tr>
                                <td><?php echo $number; ?></td>
                                <td><?php echo $description; ?></td>
                                <td><?php echo $brand_name; ?></td>
                                <td><?php echo $category_name; ?></td>
                                <td><?php echo $parent_barcode; ?></td>
                                <td class="text-end"><?php echo $product_quantity; ?></td>
                                <td class="text-end">₱<?php echo number_format($unit_price, 2); ?></td>
                                <td class="text-end">₱<?php echo number_format($subtotal, 2); ?></td>
                            </tr>
                        <?php }
                    } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6">Total</td>
                            <td></td>
                            <td class="text-end">₱<?php echo number_format($total, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>

                <h5 class="mt-4">Received By</h5>
                <p><u><?php echo $inbound_receiver; ?></u></p>
                <p><?php echo $inbound_receiver_pos; ?></p>
                <p><strong><?php echo $inbound_warehouse_name; ?></strong></p>

                <div class="container-reason">
                    <table>
                        <tr>
                            <th>
                                <strong>Staff Reason:</strong><br>
                                <small><?php echo $void_request_date; ?></small><br>
                                <?php echo $staff_reason; ?>
                            </th>
                            <th>
                                <strong>Reason (if declined):</strong><br>
                                <small><?php echo $approved_void_date; ?></small><br>
                                <?php echo $authorized_reason; ?>
                            </th>
                        </tr>
                    </table>
                </div>

            </div>
        </body>
        </html>
        <?php
        $html = ob_get_clean();
        
    }
}

$mpdf = new \Mpdf\Mpdf([
    'format' => [297, 210], // A4 Landscape
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 0,
    'margin_bottom' => 0,
]);

$mpdf->WriteHTML($html);
$fileName = 'Inbound Document.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
$mpdf->Output($fileName, 'D');
exit;
$conn->close();
?>
