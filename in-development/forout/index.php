<?php
// Increase execution time for large batch processing
set_time_limit(0); // No time limit
ini_set('memory_limit', '512M'); // Increase memory

include "../config/database.php";
include "../config/on_session.php";

$csvFile = __DIR__ . '/to-out.csv';

$data = [];
$headers = [];
$message = '';

function readCsvRows($csvFile)
{
    $headers = [];
    $rows = [];

    if (!file_exists($csvFile)) {
        return [$headers, $rows];
    }

    if (($handle = fopen($csvFile, 'r')) !== false) {
        if (($headers = fgetcsv($handle, 1000, ',')) !== false) {
            while (count($headers) < 6) {
                $headers[] = '';
            }
            if (count($headers) === 6 && trim($headers[5]) === '') {
                $headers[5] = 'status';
            }

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rows[] = array_pad($row, 6, '');
            }
        }
        fclose($handle);
    }

    return [$headers, $rows];
}

function writeCsvRows($csvFile, array $headers, array $rows)
{
    if (($handle = fopen($csvFile, 'w')) === false) {
        return false;
    }

    fputcsv($handle, $headers);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);
    return true;
}

list($headers, $csvRows) = readCsvRows($csvFile);

if (empty($headers) && !file_exists($csvFile)) {
    $error = "CSV file not found: " . $csvFile;
} else {
    $data = [];
    foreach ($csvRows as $row) {
        if (!empty($row[0]) || !empty($row[1]) || !empty($row[2])) {
            $data[] = $row;
        }
    }
    usort($data, function ($a, $b) {
        return strcmp($a[4], $b[4]);
    });
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch'])) {
    header('Content-Type: application/json');
    $offset = max(0, intval($_POST['offset'] ?? 0));
    $validRowIndices = [];
    foreach ($csvRows as $rowIndex => $row) {
        if (!empty($row[0]) || !empty($row[1]) || !empty($row[2])) {
            $validRowIndices[] = $rowIndex;
        }
    }
    $total_rows = count($validRowIndices);
    $chunkSize = max(1, intval($_POST['chunkSize'] ?? ceil($total_rows * 0.1)));

    $current_chunk = 0;
    $success_count = 0;
    $failed_count = 0;
    $failed_rows = [];
    $updated = false;

    $chunkIndices = array_slice($validRowIndices, $offset, $chunkSize);
    foreach ($chunkIndices as $rowIndex) {
        $row = &$csvRows[$rowIndex];

        if (strtolower(trim($row[5])) === 'completed') {
            continue;
        }

        if (strtolower(trim($row[5])) === 'completed') {
            continue;
        }

        $processed++;
        $customer = trim($row[0]);
        $barcode = trim($row[1]);
        $sold_for = trim($row[2]);
        $order_number = trim($row[3]);
        $order_number_duplicate = trim($row[4]);

        if ($customer === '') {
            $customer = 'System Administrator';
        }
        if ($sold_for === '') {
            $sold_for = '0.00';
        }

        $warehouse = null;
        $platform = "";
        $orderLineID = "";
        $processedBy = "6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b";
        $payment_method = "Not Available";
        $outbound_id = random_int(1000000, 9999999);

        $get_warehouse_id = "SELECT warehouse FROM stocks WHERE unique_barcode = '$barcode' LIMIT 1";
        $get_warehouse_id_result = $conn->query($get_warehouse_id);
        if ($get_warehouse_id_result && $get_warehouse_id_result->num_rows > 0) {
            $warehouse = $get_warehouse_id_result->fetch_assoc()['warehouse'];
        }

        $dbError = '';
        $check_existing = "SELECT hashed_id FROM outbound_logs WHERE order_num = '$order_number_duplicate' LIMIT 1";
        $check_result = $conn->query($check_existing);
        if ($check_result && $check_result->num_rows == 0) {
            $insert_outbound = "INSERT INTO outbound_logs SET date_sent = '$currentDateTime', warehouse = '$warehouse', user_id = '$processedBy', customer_fullname = '$customer', courier = '', platform = '$platform', order_num = '$order_number_duplicate', order_line_id = '$orderLineID', hashed_id = '$outbound_id', status = '6', payment_method = '$payment_method'";

            if (!$conn->query($insert_outbound)) {
                $dbError = $conn->error;
            } else {
                $product_infoQuery = "SELECT product_id FROM stocks WHERE unique_barcode = ? GROUP BY product_id";
                $stmt = $conn->prepare($product_infoQuery);
                $stmt->bind_param("s", $barcode);
                $stmt->execute();
                $product_infoRes = $stmt->get_result();
                $product_id = $product_infoRes->fetch_assoc()['product_id'] ?? null;

                $product_quantity_before = 0;
                if ($product_id) {
                    $product_quantity_before_query = "SELECT COUNT(unique_barcode) AS quantity FROM stocks WHERE product_id = ? AND item_status = 0 AND warehouse = ?";
                    $stmt = $conn->prepare($product_quantity_before_query);
                    $stmt->bind_param("ss", $product_id, $warehouse);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $product_quantity_before = $result->fetch_assoc()['quantity'] ?? 0;
                }

                $itemSql = "INSERT INTO outbound_content (unique_barcode, sold_price, hashed_id, quantity_before, status) VALUES (?, ?, ?, ?, 6)";
                $stmt = $conn->prepare($itemSql);
                $stmt->bind_param("sdsi", $barcode, $sold_for, $outbound_id, $product_quantity_before);
                if (!$stmt->execute()) {
                    $dbError = $stmt->error;
                } else {
                    $outbound_content_id = $conn->insert_id;

                    $update_stock = "UPDATE stocks SET item_status = 1, outbound_id = ?, outbounded_by = ? WHERE unique_barcode = ?";
                    $stmt = $conn->prepare($update_stock);
                    $stmt->bind_param("sss", $outbound_id, $processedBy, $barcode);
                    if (!$stmt->execute()) {
                        $dbError = $stmt->error;
                    }

                    if (!$dbError) {
                        $action = "Outbounded to Customer: " . $customer;
                        $insert_to_item_history = "INSERT INTO stock_timeline (unique_barcode, title, action, user_id, date) VALUES (?, 'Outbound', ?, ?, ?)";
                        $stmt = $conn->prepare($insert_to_item_history);
                        $stmt->bind_param("ssss", $barcode, $action, $processedBy, $currentDateTime);
                        if (!$stmt->execute()) {
                            $dbError = $stmt->error;
                        }
                    }

                    if (!$dbError && $product_id) {
                        $product_quantity_after_query = "SELECT COUNT(unique_barcode) AS quantity FROM stocks WHERE product_id = ? AND item_status = 0 AND warehouse = ?";
                        $stmt = $conn->prepare($product_quantity_after_query);
                        $stmt->bind_param("ss", $product_id, $warehouse);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $product_quantity_after = $result->fetch_assoc()['quantity'] ?? 0;

                        $update = "UPDATE outbound_content SET quantity_after = ? WHERE id = ?";
                        $stmt = $conn->prepare($update);
                        $stmt->bind_param("si", $product_quantity_after, $outbound_content_id);
                        if (!$stmt->execute()) {
                            $dbError = $stmt->error;
                        }
                    }
                }
            }
        } else {
            $row[5] = 'completed';
            $success_count++;
            $row_saved = true;
        }

        if ($dbError === '') {
            if (!$row_saved) {
                $row[5] = 'completed';
                $success_count++;
                $row_saved = true;
            }
        } else {
            $row[5] = 'failed';
            $failed_count++;
            $failed_rows[] = [
                'barcode' => $barcode,
                'order_number' => $order_number_duplicate,
                'error' => $dbError,
            ];
        }

        $updated = true;
        $current_chunk++;
    }

    if ($updated) {
        writeCsvRows($csvFile, $headers, $csvRows);
    }

    $completed_rows = 0;
    foreach ($csvRows as $row) {
        if (!empty($row[0]) || !empty($row[1]) || !empty($row[2])) {
            if (strtolower(trim($row[5])) === 'completed') {
                $completed_rows++;
            }
        }
    }

    $remaining_rows = 0;
    foreach ($csvRows as $row) {
        if (!empty($row[0]) || !empty($row[1]) || !empty($row[2])) {
            $status = strtolower(trim($row[5]));
            if ($status !== 'completed') {
                $remaining_rows++;
            }
        }
    }

    $done = $offset + $chunkSize >= $total_rows;
    echo json_encode([
        'totalRows' => $total_rows,
        'completedRows' => $completed_rows,
        'remainingRows' => $remaining_rows,
        'chunkProcessed' => $current_chunk,
        'successCount' => $success_count,
        'failedCount' => $failed_count,
        'failedRows' => $failed_rows,
        'nextOffset' => min($offset + $chunkSize, $total_rows),
        'done' => $done,
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Data Viewer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .info {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        thead {
            background-color: #4CAF50;
            color: white;
        }
        
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #ddd;
        }
        
        td {
            padding: 10px 12px;
            border: 1px solid #ddd;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tbody tr:hover {
            background-color: #f0f0f0;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
            margin-bottom: 20px;
        }
        
        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            border: 1px solid #c3e6cb;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>CSV Data Viewer</h1>
        <div class="info">File: <?php echo basename($csvFile); ?></div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (empty($data)): ?>
            <div class="error">No data found in the CSV file.</div>
        <?php else: ?>
            <?php if (!empty($message)): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <div class="info">Total Records: <?php echo count($data); ?></div>
            
            <table>
                <thead>
                    <tr>
                        <?php foreach ($headers as $header): ?>
                            <th><?php echo htmlspecialchars($header); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ($row as $index => $cell): ?>
                                <td><?php 
                                    if ($index === 0 && empty($cell)) {
                                        echo htmlspecialchars("System Administrator");
                                    } elseif ($index === 2 && empty($cell)) {
                                        echo htmlspecialchars("0.00");
                                    } else {
                                        echo htmlspecialchars($cell);
                                    }
                                ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div id="batch-controls" style="margin-top: 20px;">
                <button id="startBatch" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Start Batch Insert</button>
            </div>
            <div id="batch-status" style="display:none; margin-top: 16px;">
                <div style="width:100%; height:28px; background:#ddd; border-radius:4px; overflow:hidden;">
                    <div id="batchBar" style="width:0%; height:100%; background:#4CAF50; color:white; text-align:center; line-height:28px;">0%</div>
                </div>
                <p id="batchMessage" style="margin-top:10px; color:#333;">Ready to start batch insert.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
