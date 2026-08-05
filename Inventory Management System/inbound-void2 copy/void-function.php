<?php
include "../config/database.php";
include "../config/on_session.php";

header('Content-Type: application/json');

/**
 * Send a JSON response in the shape content.php's JS expects
 * ({ success, message, ... }) and stop execution.
 */
function respond(bool $success, string $message, array $extra = []): void
{
    http_response_code($success ? 200 : 400);
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $extra
    ));
    exit;
}

/**
 * Whether a barcode has already been recorded as voided under this
 * specific void_log_id (prevents double-clicks / repeat requests from
 * creating duplicate void_items rows for the same barcode).
 */
function barcodeAlreadyVoided(mysqli $conn, int $void_log_id, string $barcode): bool
{
    $stmt = $conn->prepare("SELECT id FROM void_items WHERE void_log_id = ? AND unique_barcode = ? LIMIT 1");
    $stmt->bind_param('is', $void_log_id, $barcode);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

$po_id      = $_GET['po_id'] ?? '';
$unique_key = $_GET['unique_key'] ?? '';
$void_type  = $_GET['void-inbound'] ?? '';
$void_parent    = $_GET['parent'] ?? '';
$void_sequence  = $_GET['sequence'] ?? '';
$void_remarks   = trim($_GET['remarks'] ?? '');

// ---- validation -----------------------------------------------------

if ($po_id === '' || $unique_key === '') {
    respond(false, 'Missing required parameters.');
}

$valid_types = ['inbound', 'parent', 'sequence', 'done'];
if (!in_array($void_type, $valid_types, true)) {
    respond(false, 'Invalid void type.');
}

if ($void_type === 'parent' && $void_parent === '') {
    respond(false, 'Missing parent barcode.');
}

if ($void_type === 'sequence' && $void_sequence === '') {
    respond(false, 'Missing barcode sequence.');
}

if ($void_type === 'done' && $void_remarks === '') {
    respond(false, 'Remarks are required to complete this void request.');
}

if (!isset($conn)) {
    error_log('[void-function] $conn is not set after the database.php include.');
    respond(false, 'Database connection is not available.');
}

try {
    // ---- reuse a pending void log for this PO/unique key, or create one --
    // (content.php can fire several void actions for the same po_id +
    // unique_key before "Done" is clicked; those should all land under one
    // void_logs row rather than a new one per click.)
    $check_stmt = $conn->prepare(
        "SELECT id FROM void_logs WHERE request_type = 'void' AND po_id = ? AND unique_key = ? AND `status` = 'pending'"
    );
    $check_stmt->bind_param('ss', $po_id, $unique_key);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $void_log_id = $check_result->fetch_assoc()['id'];
    } else {
        $insert_stmt = $conn->prepare(
            "INSERT INTO void_logs (request_type, po_id, unique_key, requested_by, `status`, created_at)
             VALUES ('void', ?, ?, ?, 'pending', NOW())"
        );
        $insert_stmt->bind_param('sss', $po_id, $unique_key, $user_id);
        $insert_stmt->execute();
        $void_log_id = $conn->insert_id;
        $insert_stmt->close();
    }
    $check_stmt->close();

    $insert_void_item = $conn->prepare(
        "INSERT INTO void_items (void_log_id, unique_barcode, capital) VALUES (?, ?, ?)"
    );

    // ---- handle the action --------------------------------------------

    if ($void_type === 'inbound') {
        $stocks_stmt = $conn->prepare("SELECT unique_barcode, capital FROM stocks WHERE unique_key = ?");
        $stocks_stmt->bind_param('s', $unique_key);
        $stocks_stmt->execute();
        $stocks_result = $stocks_stmt->get_result();

        $count = 0;
        $skipped = 0;
        while ($stock = $stocks_result->fetch_assoc()) {
            if (barcodeAlreadyVoided($conn, $void_log_id, $stock['unique_barcode'])) {
                $skipped++;
                continue;
            }
            $insert_void_item->bind_param('isd', $void_log_id, $stock['unique_barcode'], $stock['capital']);
            $insert_void_item->execute();
            $count++;
        }
        $stocks_stmt->close();

        $message = "Voided the entire inbound ({$count} item" . ($count === 1 ? '' : 's') . ").";
        if ($skipped > 0) {
            $message .= " {$skipped} item" . ($skipped === 1 ? ' was' : 's were') . " already voided and skipped.";
        }

        respond(true, $message, [
            'void_log_id'   => $void_log_id,
            'voided_count'  => $count,
            'skipped_count' => $skipped,
        ]);
    }

    if ($void_type === 'parent') {
        $stocks_stmt = $conn->prepare(
            "SELECT unique_barcode, capital FROM stocks WHERE unique_key = ? AND parent_barcode = ?"
        );
        $stocks_stmt->bind_param('ss', $unique_key, $void_parent);
        $stocks_stmt->execute();
        $stocks_result = $stocks_stmt->get_result();

        $count = 0;
        $skipped = 0;
        while ($stock = $stocks_result->fetch_assoc()) {
            if (barcodeAlreadyVoided($conn, $void_log_id, $stock['unique_barcode'])) {
                $skipped++;
                continue;
            }
            $insert_void_item->bind_param('isd', $void_log_id, $stock['unique_barcode'], $stock['capital']);
            $insert_void_item->execute();
            $count++;
        }
        $stocks_stmt->close();

        if ($count === 0 && $skipped === 0) {
            respond(false, 'No stock sequences were found for this product.');
        }

        if ($count === 0 && $skipped > 0) {
            respond(false, 'All sequences for this product have already been voided.');
        }

        $message = "Voided {$count} sequence" . ($count === 1 ? '' : 's') . " for this product.";
        if ($skipped > 0) {
            $message .= " {$skipped} sequence" . ($skipped === 1 ? ' was' : 's were') . " already voided and skipped.";
        }

        respond(true, $message, [
            'void_log_id'   => $void_log_id,
            'voided_count'  => $count,
            'skipped_count' => $skipped,
        ]);
    }

    if ($void_type === 'sequence') {
        if (barcodeAlreadyVoided($conn, $void_log_id, $void_sequence)) {
            respond(false, "Barcode {$void_sequence} has already been voided for this request.");
        }

        $stock_stmt = $conn->prepare(
            "SELECT capital FROM stocks WHERE unique_key = ? AND unique_barcode = ? LIMIT 1"
        );
        $stock_stmt->bind_param('ss', $unique_key, $void_sequence);
        $stock_stmt->execute();
        $stock_result = $stock_stmt->get_result();
        $stock = $stock_result->fetch_assoc();
        $stock_stmt->close();

        if (!$stock) {
            respond(false, "Barcode {$void_sequence} was not found under this inbound.");
        }

        $insert_void_item->bind_param('isd', $void_log_id, $void_sequence, $stock['capital']);
        $insert_void_item->execute();

        respond(true, "Barcode {$void_sequence} has been voided.", ['void_log_id' => $void_log_id]);
    }

    if ($void_type === 'done') {
        $update_stmt = $conn->prepare(
            "UPDATE void_logs SET remarks = ?, `status` = 'completed' WHERE id = ?"
        );
        $update_stmt->bind_param('si', $void_remarks, $void_log_id);
        $update_stmt->execute();
        $update_stmt->close();

        respond(true, 'This void request has been marked as done.', ['void_log_id' => $void_log_id]);
    }
} catch (Throwable $e) {
    // Don't leak internal error details (queries, DB structure) to the client,
    // but do log the real cause so it's actually debuggable server-side.
    error_log('[void-function] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    respond(false, 'A server error occurred while processing this void request.');
}