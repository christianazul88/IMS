<?php
require_once '../../vendor/autoload.php'; // mPDF
include "../config/database.php";
include "../config/on_session.php";

use Picqer\Barcode\BarcodeGeneratorPNG;

$target_id = $_GET['target-id'] ?? "";

if (isset($target_id) && !empty($target_id)) {
    $po_query = "SELECT po.*, w.warehouse_name, sup.supplier_name, sup.local_international, u.user_fname, u.user_lname 
                 FROM purchased_order po 
                 LEFT JOIN warehouse w ON w.hashed_id = po.warehouse 
                 LEFT JOIN supplier sup ON sup.hashed_id = po.supplier 
                 LEFT JOIN users u ON u.hashed_id = po.user_id 
                 WHERE po.id = '$target_id' LIMIT 1";
    $po_res = $conn->query($po_query);

    if ($po_res->num_rows > 0) {
        $row = $po_res->fetch_assoc();
        $warehouseName = $row['warehouse_name'];
        $supplierName = $row['supplier_name'];
        $receivedBy = $row['user_fname'] . " " . $row['user_lname'];
        $orderDate = $row['date_order'];
        $receivedDate = $row['date_received'];
        $supplier_type = $row['local_international'] === "International" ? "Import" : $row['local_international'];

        switch ($row['status']) {
            case 1:
                $status = '<span class="badge">Sent to Supplier</span>';
                break;
            case 2:
                $status = '<span class="badge">Confirmed by Supplier</span>';
                break;
            case 3:
                $status = '<span class="badge">In Transit/ Shipped</span>';
                break;
            case 4:
                $status = '<span class="badge">Received</span>';
                break;
            default:
                $status = '<span class="badge">Drafted</span>';
        }

        switch ($row['status']) {
            case 0:
                $button_anchor = '<a href="../config/receive-po.php?status=1&&po=' . $target_id . '" class="btn btn-info">Sent to supplier</a>';
                break;
            case 1:
                $button_anchor = '<a href="../config/receive-po.php?status=2&&po=' . $target_id . '" class="btn btn-secondary">Confirmed by supplier</a>';
                break;
            case 2:
                $button_anchor = '<a href="../config/receive-po.php?status=3&&po=' . $target_id . '" class="btn btn-primary">In Transit/ Shipped</a>';
                break;
            case 3:
                $button_anchor = '<a href="../config/receive-po.php?status=4&&po=' . $target_id . '" class="btn btn-success">Received</a>';
                break;
            default:
                $button_anchor = '';
        }

        $purchased_order_contents = "SELECT p.description, b.brand_name, c.category_name, poc.* 
                                     FROM purchased_order_content poc 
                                     LEFT JOIN product p ON p.hashed_id = poc.product_id 
                                     LEFT JOIN brand b ON b.hashed_id = p.brand 
                                     LEFT JOIN category c ON c.hashed_id = p.category 
                                     WHERE po_id = '$target_id'";

        // Start output buffering
        ob_start();
        ?>

        <div style="font-family: sans-serif; font-size: 12px;">
            <div style="text-align:center;">
                <h2>PURCHASED ORDER</h2>
                <p>#<?php echo htmlspecialchars($target_id); ?></p>
            </div>
            <hr>
            <table width="100%" border="0" cellpadding="4">
                <tr>
                    <th>To:</th><td><?php echo htmlspecialchars($supplierName); ?></td>
                    <th>From:</th><td><?php echo htmlspecialchars($warehouseName); ?></td>
                </tr>
                <tr>
                    <th>Address:</th><td><?php echo htmlspecialchars($supplier_type); ?></td>
                    <th>Order Date:</th><td><?php echo htmlspecialchars($orderDate); ?></td>
                </tr>
                <tr>
                    <th>Status:</th><td><?php echo $status; ?></td>
                    <th>Prepared by:</th><td><?php echo htmlspecialchars($receivedBy); ?></td>
                </tr>
            </table>

            <hr>
            <table border="1" width="100%" cellpadding="4" cellspacing="0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Description</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Order QTY</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $number = 0;
                    $purchased_order_results = $conn->query($purchased_order_contents);
                    if ($purchased_order_results->num_rows > 0) {
                        while ($row = $purchased_order_results->fetch_assoc()) {
                            $number++;
                            ?>
                            <tr>
                                <td><?php echo $number; ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo htmlspecialchars($row['brand_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td align="right"><?php echo htmlspecialchars($row['qty']); ?></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>

            <div style="margin-top: 20px; text-align: right;">
                <?php if ($supplier_type !== "Local") echo $button_anchor; ?>
            </div>
        </div>

        <?php
        // End of buffer
        $html = ob_get_clean();

        $mpdf = new \Mpdf\Mpdf([
            'format' => [210, 297], // A4 Portrait
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->WriteHTML($html);
        $fileName = 'PO-' . $target_id . '.pdf';

        // Output to browser
        $mpdf->Output($fileName, 'D');
        exit;
    }
}
?>
