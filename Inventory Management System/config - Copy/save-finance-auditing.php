<?php
include "../config/database.php";
include "../config/on_session.php";
header('Content-Type: application/json');

$currentDateTime = date('Y-m-d H:i:s');

// Create finance_audit table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS finance_audit (
        id INT AUTO_INCREMENT PRIMARY KEY,
        status VARCHAR(15),
        order_number VARCHAR(50),
        order_line_id VARCHAR(50),
        warehouse VARCHAR(50),
        client VARCHAR(50),
        paid_amount DECIMAL(10,2),
        expected_amount VARCHAR(100),
        user_id VARCHAR(100),
        date DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

function log_stock_timeline($conn, $barcode, $action, $user_id, $date) {
    $stmt = $conn->prepare("
        INSERT INTO stock_timeline (unique_barcode, title, action, user_id, date) 
        VALUES (?, 'Outbound', ?, ?, ?)
    ");
    $stmt->bind_param("ssss", $barcode, $action, $user_id, $date);
    $stmt->execute();
}

function log_outbound_paid($conn, $outbound_id, $user_id, $date) {
    $log_action = "Outbound #$outbound_id has been successfully paid via finance auditing.";
    $stmt = $conn->prepare("
        INSERT INTO logs (title, action, user_id, date) 
        VALUES ('OUTBOUND', ?, ?, ?)
    ");
    $stmt->bind_param("sss", $log_action, $user_id, $date);
    $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['order_num'] as $i => $order_num) {
        $order_line    = $_POST['order_line'][$i] ?? null;
        $warehouse     = $_POST['warehouse'][$i] ?? null;
        $client        = $_POST['client'][$i] ?? null;
        $status        = $_POST['status'][$i] ?? null;
        $paid_amount   = $_POST['paid_amount'][$i] ?? 0;
        $expect_amount = $_POST['expect_amount'][$i] ?? 0;
        $csv_id        = $_POST['csv_id'][$i] ?? null;

        // Check previous record
        $stmt = $conn->prepare("
            SELECT * FROM finance_audit 
            WHERE order_number = ? AND order_line_id = ? AND warehouse = ? AND client = ?
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->bind_param("ssss", $order_num, $order_line, $warehouse, $client);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $audit_status = $row['status'];

            // Update audit record
            $update = $conn->prepare("
                UPDATE finance_audit 
                SET status = ?, paid_amount = ?, expected_amount = ?, user_id = ?, date = ? 
                WHERE order_number = ? AND order_line_id = ? AND warehouse = ? AND client = ?
            ");
            $update->bind_param("sdsssssss", $status, $paid_amount, $expect_amount, $user_id, $currentDateTime, $order_num, $order_line, $warehouse, $client);
            $update->execute();

        } else {
            // Insert new audit record
            $insert = $conn->prepare("
                INSERT INTO finance_audit 
                (status, order_number, order_line_id, warehouse, client, paid_amount, expected_amount, user_id, date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insert->bind_param("sssssssss", $status, $order_num, $order_line, $warehouse, $client, $paid_amount, $expect_amount, $user_id, $currentDateTime);
            $insert->execute();
        }

        // Fetch outbound hashed_id
        $stmt = $conn->prepare("
            SELECT hashed_id, UPPER(customer_fullname) AS customer_fullname FROM outbound_logs 
            WHERE order_num = ? AND order_line_id = ? LIMIT 1
        ");
        $stmt->bind_param("ss", $order_num, $order_line);
        $stmt->execute();
        $res_outbound = $stmt->get_result();

        if ($res_outbound->num_rows > 0) {
            $row_outbound = $res_outbound->fetch_assoc();
            $outbound_id = $row_outbound['hashed_id'];
            $customer_fullname = trim(preg_replace('/\s+/', ' ', $row_outbound['customer_fullname']));

            if($client === $customer_fullname){
                if ($status === "PAID") {
                    // Update outbound statuses
                    $stmt1 = $conn->prepare("UPDATE outbound_logs SET status = 0 WHERE hashed_id = ?");
                    $stmt2 = $conn->prepare("UPDATE outbound_content SET status = 0 WHERE hashed_id = ?");
                    $stmt1->bind_param("s", $outbound_id);
                    $stmt2->bind_param("s", $outbound_id);
                    $stmt1->execute();
                    $stmt2->execute();

                    // Log each barcode
                    $result = $conn->query("SELECT unique_barcode FROM outbound_content WHERE hashed_id = '$outbound_id'");
                    while ($r = $result->fetch_assoc()) {
                        $action = "Paid via finance auditing. The staff who processed it is $user_fullname";
                        log_stock_timeline($conn, $r['unique_barcode'], $action, $user_id, $currentDateTime);
                    }
                    log_outbound_paid($conn, $outbound_id, $user_id, $currentDateTime);

                } elseif (in_array($status, ['UNPAID', 'PENDING'])) {
                    // Get a barcode for logging
                    $result = $conn->query("
                        SELECT oc.unique_barcode 
                        FROM outbound_content oc 
                        JOIN outbound_logs ol ON oc.hashed_id = ol.hashed_id 
                        WHERE ol.order_num = '$order_num' AND ol.order_line_id = '$order_line' AND ol.status IN (0, 6) LIMIT 1
                    ");
                    if ($row = $result->fetch_assoc()) {
                        $action_text = $status === 'UNPAID' 
                            ? "Updated the status to unpaid via finance auditing"
                            : "Checked the status to see if it remained the same via finance auditing";
                        $action = "$action_text. The staff who processed it is $user_fullname";
                        log_stock_timeline($conn, $row['unique_barcode'], $action, $user_id, $currentDateTime);
                    }
                }
             
            }
        }

        // Update CSV auditing status
        $stmt = $conn->prepare("UPDATE csv_auditing SET status = 2 WHERE id = ?");
        $stmt->bind_param("i", $csv_id);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Records processed successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
