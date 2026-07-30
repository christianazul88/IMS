<?php
include "../config/database.php";
include "../config/on_session.php";

header('Content-Type: application/json');

// These were referenced but never set in the original script.
// $user_id should already exist from on_session.php if this always runs
// in a logged-in context; otherwise use a fixed "system" user id/constant.
$currentDateTime = date('Y-m-d H:i:s');
$user_id = $user_id ?? 'SYSTEM';

$receiverUserId = '6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b';
$remarks = "Received automatically by the system for being pending for 3 days.";

$stock_sql = "
    SELECT id, to_warehouse, to_rack
    FROM stock_transfer
    WHERE date_out <= DATE_SUB(NOW(), INTERVAL 3 DAY)
        AND status = 'pending'
    GROUP BY id
    ORDER BY id DESC
";
$result = $conn->query($stock_sql);

$results = [];   // per-transfer outcome, for the frontend to render/count
$total = 0;
$successCount = 0;

if ($result && $result->num_rows > 0) {

    // Prepared statements, reused inside the loop
    $wh_stmt = $conn->prepare("SELECT warehouse_name FROM warehouse WHERE hashed_id = ? LIMIT 1");
    $update_stmt = $conn->prepare("
        UPDATE stock_transfer
        SET received_userid = ?, status = 'received', date_received = ?, remarks_receiver = ?
        WHERE id = ?
    ");
    $log_stmt = $conn->prepare("
        INSERT INTO logs SET title = 'STOCK TRANSFER', action = ?, date = ?, user_id = ?
    ");
    $items_stmt = $conn->prepare("SELECT unique_barcode FROM stock_transfer_content WHERE st_id = ?");
    $stock_update_stmt = $conn->prepare("
        UPDATE stocks SET item_status = 0, warehouse = ?, item_location = ? WHERE unique_barcode = ?
    ");
    $timeline_stmt = $conn->prepare("
        INSERT INTO stock_timeline SET unique_barcode = ?, title = 'STOCK TRANSFER', action = ?, date = ?, user_id = ?
    ");

    while ($row = $result->fetch_assoc()) {
        $total++;
        $stock_transfer_id = (int)$row['id'];
        $to_warehouse = $row['to_warehouse'];
        $to_rack = $row['to_rack'] ?? 0;
        $receiver_warehouse = $to_warehouse;

        $transferOk = true;
        $errorMsg = null;

        // Look up warehouse name
        $wh_stmt->bind_param('s', $receiver_warehouse);
        $wh_stmt->execute();
        $wh_res = $wh_stmt->get_result();
        $wh_row = $wh_res->fetch_assoc();
        $toWarehouse_name = $wh_row['warehouse_name'] ?? 'Unknown Warehouse';

        // Update stock_transfer
        $update_stmt->bind_param('sssi', $receiverUserId, $currentDateTime, $remarks, $stock_transfer_id);
        if ($update_stmt->execute()) {
            $action = "#" . $stock_transfer_id . " has been received by system for staying enroute for more than 3 days.";
            $log_stmt->bind_param('sss', $action, $currentDateTime, $user_id);

            if ($log_stmt->execute()) {
                $items_stmt->bind_param('i', $stock_transfer_id);
                $items_stmt->execute();
                $items_res = $items_stmt->get_result();

                while ($item_row = $items_res->fetch_assoc()) {
                    $unique_barcode = $item_row['unique_barcode'];

                    $stock_update_stmt->bind_param('sss', $receiver_warehouse, $to_rack, $unique_barcode);
                    if ($stock_update_stmt->execute()) {
                        $stock_action = "has been received by " . $toWarehouse_name . " (System Received). transfer #" . $stock_transfer_id;
                        $timeline_stmt->bind_param('ssss', $unique_barcode, $stock_action, $currentDateTime, $user_id);
                        if (!$timeline_stmt->execute()) {
                            $transferOk = false;
                            $errorMsg = "Timeline insert failed for barcode $unique_barcode";
                        }
                    } else {
                        $transferOk = false;
                        $errorMsg = "Stock update failed for barcode $unique_barcode";
                    }
                }
            } else {
                $transferOk = false;
                $errorMsg = "Log insert failed";
            }
        } else {
            $transferOk = false;
            $errorMsg = "Transfer update failed";
        }

        if ($transferOk) {
            $successCount++;
        }

        $results[] = [
            'id'      => $stock_transfer_id,
            'success' => $transferOk,
            'error'   => $errorMsg,
        ];
    }
}

echo json_encode([
    'total'   => $total,
    'success' => $successCount,
    'match'   => ($total === $successCount),
    'results' => $results,
]);
