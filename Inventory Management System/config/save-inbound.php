<?php
require '../config/database.php';
require '../config/on_session.php';
header('Content-Type: application/json');

$response = ["status" => "error", "message" => "Something went wrong!"];

function generateUniqueKey($conn) {
    do {
        $uniqueKey = random_int(100000000000, 999999999999);
        $query = "SELECT COUNT(*) as count FROM inbound_logs WHERE unique_key = '$uniqueKey'";
        $result = $conn->query($query);
        $row = $result->fetch_assoc();
    } while ($row['count'] > 0);

    return $uniqueKey;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// Check this BEFORE generating a new inbound reference.
// After a successful inbound, the session is cleared, so a duplicate
// request cannot create another inbound transaction.
if (!isset($_SESSION['inbound_po_id']) || empty($_SESSION['inbound_po_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "You just inbounded from a different tab with a different Purchase Order. This creates conflicts in the system. Inbound only one tab at a time to avoid errors."
    ]);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "User not authenticated."]);
    exit;
}

$allQtys = $_POST['qty'] ?? [];

// Check if all quantities are zero.
$hasNonZero = false;
foreach ($allQtys as $qty) {
    if ((int)$qty > 0) {
        $hasNonZero = true;
        break;
    }
}

if (!$hasNonZero) {
    echo json_encode([
        "status" => "error",
        "message" => "All product quantities are zero. Nothing was received."
    ]);
    exit;
}

$po_id = $_SESSION['inbound_po_id'];
$currentDateTime = $_SESSION['inbound_received_date'] ?? date('Y-m-d H:i:s');
$batch_code = "PObatch-" . $po_id;

// Generate ONE reference for ONE inbound transaction.
$unique_key = generateUniqueKey($conn);
$_SESSION['unique_key'] = $unique_key;

$po_query = "SELECT po.warehouse, po.supplier, w.warehouse_name
             FROM purchased_order po
             LEFT JOIN warehouse w ON w.hashed_id = po.warehouse
             WHERE po.id='$po_id' LIMIT 1";

$po_result = $conn->query($po_query);

if (!$po_result || !($row = $po_result->fetch_assoc())) {
    echo json_encode(["status" => "error", "message" => "Purchase Order not found."]);
    exit;
}

$inbound_warehouse = $row['warehouse'];
$inbound_warehouse_name = $row['warehouse_name'];
$supplier = $row['supplier'];

$_SESSION['inbound_warehouse'] = $inbound_warehouse;

try {
    // Everything is committed together. If anything fails, nothing is saved.
    $conn->begin_transaction();

    // Lock the PO so two simultaneous requests cannot process it at the same time.
    $po_lock = "SELECT id, date_received
                FROM purchased_order
                WHERE id='$po_id'
                FOR UPDATE";

    $po_lock_result = $conn->query($po_lock);

    if (!$po_lock_result || !$po_lock_result->num_rows) {
        throw new Exception("Purchase Order not found.");
    }

    $po_row = $po_lock_result->fetch_assoc();

    // Prevent the same PO from being inbounded twice.
    if (!empty($po_row['date_received'])) {
        $conn->rollback();
        echo json_encode([
            "status" => "error",
            "message" => "This Purchase Order has already been inbounded. Duplicate submission was prevented."
        ]);
        exit;
    }

    $update_po = "UPDATE purchased_order
                  SET date_received = '$currentDateTime'
                  WHERE id = '$po_id'";

    if (!$conn->query($update_po)) {
        throw new Exception("Failed to update Purchase Order.");
    }

    // Create exactly ONE inbound_logs record.
    $insert_inbound = "INSERT INTO inbound_logs
                       (po_id, supplier, date_received, user_id, warehouse, unique_key)
                       VALUES
                       ('$po_id', '$supplier', '$currentDateTime', '$user_id', '$inbound_warehouse', '$unique_key')";

    if (!$conn->query($insert_inbound)) {
        throw new Exception("Failed to create inbound log.");
    }

    $inbound_id = $conn->insert_id;
    $total_items = 0;

    if (!isset($_POST['barcode']) || !is_array($_POST['barcode'])) {
        throw new Exception("No barcode data was submitted.");
    }

    foreach ($_POST['barcode'] as $index => $barcode) {
        $qty_received = (int)($_POST['qty'][$index] ?? 0);

        if ($qty_received <= 0) {
            continue;
        }

        $unit_price = (float)($_POST['unit_amount'][$index] ?? 0);

        $product_query = "SELECT hashed_id
                          FROM product
                          WHERE parent_barcode = '$barcode'
                          LIMIT 1";

        $product_res = $conn->query($product_query);

        if (!$product_res || !($row = $product_res->fetch_assoc())) {
            throw new Exception("Product not found for barcode: $barcode");
        }

        $product_id = $row['hashed_id'];

        // Get the latest extension while the transaction is active.
        $stock_query = "SELECT barcode_extension
                        FROM stocks
                        WHERE parent_barcode='$barcode'
                          AND product_id='$product_id'
                        ORDER BY barcode_extension DESC
                        LIMIT 1
                        FOR UPDATE";

        $stock_res = $conn->query($stock_query);

        if (!$stock_res) {
            throw new Exception("Failed to check existing stock.");
        }

        if ($stock_res->num_rows > 0) {
            $row = $stock_res->fetch_assoc();
            $chan = (int)$row['barcode_extension'];
        } else {
            $chan = 0;
        }

        for ($i = 1; $i <= $qty_received; $i++) {
            $extension = $chan + $i;
            $unique_barcode = $barcode . "-" . $extension;

            // Prevent duplicate stock records.
            $duplicate_check = "SELECT id
                                 FROM stocks
                                 WHERE unique_barcode='$unique_barcode'
                                 LIMIT 1";

            $duplicate_res = $conn->query($duplicate_check);

            if (!$duplicate_res) {
                throw new Exception("Failed to check duplicate stock.");
            }

            if ($duplicate_res->num_rows > 0) {
                throw new Exception("Stock barcode $unique_barcode already exists. Inbound was cancelled to prevent duplicate records.");
            }

            $insert_stock = "INSERT INTO stocks
                            (item_status, inbound_id, unique_barcode, barcode_extension, product_id, parent_barcode,
                             batch_code, capital, warehouse, supplier, `date`, user_id, unique_key)
                            VALUES
                            (0, '$inbound_id', '$unique_barcode', '$extension', '$product_id', '$barcode',
                             '$batch_code', '$unit_price', '$inbound_warehouse', '$supplier', '$currentDateTime', '$user_id', '$unique_key')";

            if (!$conn->query($insert_stock)) {
                throw new Exception("Failed to insert stock for $unique_barcode.");
            }

            // Exactly ONE INBOUND timeline record for each stock item.
            $stock_timeline = "INSERT INTO stock_timeline
                              (unique_barcode, title, `action`, `date`, user_id)
                              VALUES
                              ('$unique_barcode',
                               'INBOUND',
                               'Inbound #: $unique_key, Product was inbounded to $inbound_warehouse_name',
                               '$currentDateTime',
                               '$user_id')";

            if (!$conn->query($stock_timeline)) {
                throw new Exception("Failed to create stock timeline for $unique_barcode.");
            }

            $total_items++;
        }
    }

    if ($total_items <= 0) {
        throw new Exception("No stock items were received.");
    }

    // ONE general log per inbound transaction, not one log per item.
    $logs = "INSERT INTO logs
             (title, action, date, user_id)
             VALUES
             ('INBOUND',
              'PO-$po_id has been successfully inbounded to $inbound_warehouse_name. Created inbound reference no.: $inbound_id',
              '$currentDateTime',
              '$user_id')";

    if (!$conn->query($logs)) {
        throw new Exception("Failed to create system log.");
    }

    // Only now make all changes permanent.
    $conn->commit();

    // Clear the inbound session only after a successful commit.
    unset(
        $_SESSION['inbound_po_id'],
        $_SESSION['inbound_received_date'],
        $_SESSION['po_list'],
        $_SESSION['success']
    );

    $_SESSION['success'] = 'Inbound items saved successfully!';

    $response = [
        "status" => "success",
        "message" => "Inbound items saved successfully!",
        "inbound_id" => $inbound_id,
        "unique_key" => $unique_key,
        "total_items" => $total_items
    ];

} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable $rollbackError) {
        // Ignore rollback errors.
    }

    $response = [
        "status" => "error",
        "message" => $e->getMessage()
    ];
}

$conn->close();

echo json_encode($response);
exit;
