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

// Read CSV file first
if (file_exists($csvFile)) {
    if (($handle = fopen($csvFile, 'r')) !== false) {
        // Read headers
        if (($headers = fgetcsv($handle, 1000, ',')) !== false) {
            // Read all rows
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                // Skip rows where first three columns are blank
                if (!empty($row[0]) || !empty($row[1]) || !empty($row[2])) {
                    $data[] = $row;
                }
            }
            // Sort by column[4] (order number duplicate) ascending
            usort($data, function($a, $b) {
                return strcmp($a[4], $b[4]);
            });
        }
        fclose($handle);
    } else {
        $error = "Unable to open the CSV file.";
    }
} else {
    $error = "CSV file not found: " . $csvFile;
}

if (isset($_POST['insert'])) {
    // Enable output buffering to show progress
    ob_start();
    
    // Initialize counters and error arrays
    $successful_inserts = 0;
    $failed_inserts = [];
    $successful_records = [];
    $skipped_duplicates = 0;
    $saved_rows = 0;
    $total_rows = count($data);
    $processed_rows = 0;
    
    echo "<div style='background:#f0f0f0; padding:10px; margin:10px 0; border-radius:4px;'>";
    echo "<h3>Processing 7000+ Records...</h3>";
    echo "<div id='progress' style='width:100%; height:30px; background:#ddd; border-radius:4px;'>";
    echo "<div id='bar' style='width:0%; height:100%; background:#4CAF50; border-radius:4px; text-align:center; color:white; line-height:30px;'>0%</div>";
    echo "</div>";
    echo "<p id='status'>Starting...</p>";
    echo "</div>";
    ob_flush();
    flush();
    
    // Process each row for database insertion
    foreach ($data as $rowIndex => $row) {
        // Extract column values as variables
        $customer = isset($row[0]) ? trim($row[0]) : '';
        $barcode = isset($row[1]) ? trim($row[1]) : '';
        $sold_for = isset($row[2]) ? trim($row[2]) : '';
        $order_number = isset($row[3]) ? trim($row[3]) : '';
        $order_number_duplicate = isset($row[4]) ? trim($row[4]) : '';
        $warehouse = null; // Initialize warehouse_id variable
        $platform = "";
        $orderLineID = "";
        $processedBy = "6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b";
        $payment_method = "Not Available";
        $outbound_id = random_int(1000000, 9999999);
        // Apply display logic
        if (empty($customer)) {
            $customer = 'System Administrator';
        }
        if (empty($sold_for)) {
            $sold_for = '0.00';
        }
        
        // TODO: Add your database insertion code here
        // Example: $pdo->prepare("INSERT INTO your_table (customer, barcode, sold_for, order_number, order_number_duplicate) VALUES (?, ?, ?, ?, ?)")->execute([$customer, $barcode, $sold_for, $order_number, $order_number_duplicate]);
        $get_warehouse_id = "SELECT warehouse FROM stocks WHERE unique_barcode = '$barcode' LIMIT 1";
        $get_warehouse_id_result = $conn->query($get_warehouse_id);
        if ($get_warehouse_id_result->num_rows > 0) {
            $warehouse = $get_warehouse_id_result->fetch_assoc()['warehouse'];
        }

        // Check if order_number_duplicate already exists in outbound_logs
        $row_saved = false;
        $check_existing = "SELECT hashed_id FROM outbound_logs WHERE order_num = '$order_number_duplicate' LIMIT 1";
        $check_result = $conn->query($check_existing);
        if ($check_result->num_rows == 0) {
            // Only insert if it doesn't exist
            $insert_outbound = "INSERT INTO outbound_logs SET date_sent = '$currentDateTime', warehouse = '$warehouse', user_id = '$processedBy', customer_fullname = '$customer', courier = '', platform = '$platform', order_num = '$order_number_duplicate', order_line_id = '$orderLineID', hashed_id = '$outbound_id', status = '6', payment_method = '$payment_method'";
            
            // Execute the insert
            if ($conn->query($insert_outbound)) {
                $successful_inserts++;
                $successful_records[] = [
                    'barcode' => $barcode,
                    'order_number' => $order_number_duplicate
                ];
                $row_saved = true;
            } else {
                // Record failed insertion
                $failed_inserts[] = [
                    'barcode' => $barcode,
                    'order_number' => $order_number_duplicate,
                    'error' => $conn->error
                ];
            }
        } else {
            $skipped_duplicates++;
            $row_saved = true;
        }

        // Fetch product_id safely
            $product_infoQuery = "SELECT product_id FROM stocks WHERE unique_barcode = ? GROUP BY product_id";
            $stmt = $conn->prepare($product_infoQuery);
            $stmt->bind_param("s", $barcode);
            $stmt->execute();
            $product_infoRes = $stmt->get_result();
            $product_id = $product_infoRes->fetch_assoc()['product_id'] ?? null;

            // Fetch product quantity before update
            if ($product_id) {
                $product_quantity_before_query = "SELECT COUNT(unique_barcode) AS quantity FROM stocks WHERE product_id = ? AND item_status = 0 AND warehouse = ?";
                $stmt = $conn->prepare($product_quantity_before_query);
                $stmt->bind_param("ss", $product_id, $warehouse);
                $stmt->execute();
                $result = $stmt->get_result();
                $product_quantity_before = $result->fetch_assoc()['quantity'] ?? 0;
            }

            // Insert into outbound_content
            $itemSql = "INSERT INTO outbound_content (unique_barcode, sold_price, hashed_id, quantity_before, status) VALUES (?, ?, ?, ?, 6)";
            $stmt = $conn->prepare($itemSql);
            $stmt->bind_param("sdsi", $barcode, $sold_for, $outbound_id, $product_quantity_before);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert order item: " . $stmt->error);
            }
            $outbound_content_id = $conn->insert_id;

            // Update stock status
            $update_stock = "UPDATE stocks SET item_status = 1, outbound_id = ?, outbounded_by = ? WHERE unique_barcode = ?";
            $stmt = $conn->prepare($update_stock);
            $stmt->bind_param("sss", $outbound_id, $processedBy, $barcode);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update stock: " . $stmt->error);
            }

            // Insert into stock timeline
            $action = "Outbounded to Customer: " . $customer;
            $insert_to_item_history = "INSERT INTO stock_timeline (unique_barcode, title, action, user_id, date) VALUES (?, 'Outbound', ?, ?, ?)";
            $stmt = $conn->prepare($insert_to_item_history);
            $stmt->bind_param("ssss", $barcode, $action, $processedBy, $currentDateTime);
            $stmt->execute();

            // Fetch product quantity after update
            $product_quantity_after_query = "SELECT COUNT(unique_barcode) AS quantity FROM stocks WHERE product_id = ? AND item_status = 0 AND warehouse = ?";
            $stmt = $conn->prepare($product_quantity_after_query);
            $stmt->bind_param("ss", $product_id, $warehouse);
            $stmt->execute();
            $result = $stmt->get_result();
            $product_quantity_after = $result->fetch_assoc()['quantity'] ?? 0;

            // Update outbound_content
            $update = "UPDATE outbound_content SET quantity_after = ? WHERE id = ?";
            $stmt = $conn->prepare($update);
            $stmt->bind_param("si", $product_quantity_after, $outbound_content_id);
            $stmt->execute();
        

        
        // Update progress every 100 rows
        $processed_rows++;
        if ($processed_rows % 100 == 0 || $processed_rows == $total_rows) {
            $percentage = round(($processed_rows / $total_rows) * 100);
            echo "<script>
                document.getElementById('bar').style.width = '$percentage%';
                document.getElementById('bar').textContent = '$percentage%';
                document.getElementById('status').textContent = 'Processed: $processed_rows / $total_rows rows';
            </script>";
            ob_flush();
            flush();
        }

        if ($row_saved) {
            $saved_rows++;
        }

        // For now, just collect processed data
        $processedData[] = [$customer, $barcode, $sold_for, $order_number, $order_number_duplicate];
    }
    
    // Build the message
    $message = "Processing complete! ";
    $message .= "Saved to database: $saved_rows/$total_rows, ";
    $message .= "Successful inserts: $successful_inserts, ";
    $message .= "Skipped duplicates: $skipped_duplicates, ";
    $message .= "Failed inserts: " . count($failed_inserts);
    
    ob_end_clean(); // Clear the progress bar output
    
    if (!empty($successful_records)) {
        $message .= "<br><br><strong>Successful Insertions:</strong><br>";
        $message .= "<table style='width:100%; border-collapse:collapse; margin-top:10px;'>";
        $message .= "<thead><tr style='background-color:#d4edda;'>";
        $message .= "<th style='border:1px solid #ddd; padding:8px;'>Barcode</th>";
        $message .= "<th style='border:1px solid #ddd; padding:8px;'>Order Number</th>";
        $message .= "</tr></thead><tbody>";
        
        foreach ($successful_records as $success) {
            $message .= "<tr>";
            $message .= "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($success['barcode']) . "</td>";
            $message .= "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($success['order_number']) . "</td>";
            $message .= "</tr>";
        }
        
        $message .= "</tbody></table>";
    }
    
    if (!empty($failed_inserts)) {
        $message .= "<br><br><strong>Failed Insertions:</strong><br>";
        $message .= "<table style='width:100%; border-collapse:collapse; margin-top:10px;'>";
        $message .= "<thead><tr style='background-color:#f8d7da;'>";
        $message .= "<th style='border:1px solid #ddd; padding:8px;'>Barcode</th>";
        $message .= "<th style='border:1px solid #ddd; padding:8px;'>Order Number</th>";
        $message .= "<th style='border:1px solid #ddd; padding:8px;'>Error</th>";
        $message .= "</tr></thead><tbody>";
        
        foreach ($failed_inserts as $failed) {
            $message .= "<tr>";
            $message .= "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($failed['barcode']) . "</td>";
            $message .= "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($failed['order_number']) . "</td>";
            $message .= "<td style='border:1px solid #ddd; padding:8px;'>" . htmlspecialchars($failed['error']) . "</td>";
            $message .= "</tr>";
        }
        
        $message .= "</tbody></table>";
    }
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
            
            <form method="post" style="margin-top: 20px;">
                <button type="submit" name="insert" value="1" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Insert to Database</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
