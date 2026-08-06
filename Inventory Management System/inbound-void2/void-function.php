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
$void_log_id    = (int) ($_GET['void_log_id'] ?? 0);
$void_log_ids   = array_values(array_unique(array_filter(
    array_map('intval', explode(',', $_GET['void_log_ids'] ?? (string) $void_log_id)),
    fn($id) => $id > 0
)));
$approval_scope = $_GET['approval_scope'] ?? '';
$approval_barcode = $_GET['approval_barcode'] ?? '';
$approval_parent = $_GET['approval_parent'] ?? '';

// ---- validation -----------------------------------------------------

if ($po_id === '' || $unique_key === '') {
    respond(false, 'Missing required parameters.');
}

$valid_types = ['inbound', 'parent', 'sequence', 'done', 'approve', 'reject'];
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

if (in_array($void_type, ['approve', 'reject'], true) && empty($void_log_ids)) {
    respond(false, 'Missing void request ID.');
}

if ($void_type === 'approve' && !in_array($approval_scope, ['barcode', 'parent', 'inbound'], true)) {
    respond(false, 'Invalid approval scope.');
}

if ($void_type === 'approve' && $approval_scope === 'barcode' && $approval_barcode === '') {
    respond(false, 'Missing barcode for approval.');
}

if ($void_type === 'approve' && $approval_scope === 'parent' && $approval_parent === '') {
    respond(false, 'Missing parent barcode for approval.');
}

if (!isset($conn)) {
    error_log('[void-function] $conn is not set after the database.php include.');
    respond(false, 'Database connection is not available.');
}

try {
    // Administrators approve/reject a completed request.  This happens before
    // the request-creation logic below so an approval click cannot create a
    // new void_logs row.
    if (in_array($void_type, ['approve', 'reject'], true)) {
        if (($user_position_name ?? '') !== 'Administrator') {
            respond(false, 'Only administrators can approve or reject void requests.');
        }

        mysqli_begin_transaction($conn);
        try {
            $updated_count = 0;
            foreach ($void_log_ids as $review_log_id) {
                if ($void_type === 'approve') {
                    // Delete only the scope the administrator approved. Every
                    // delete is additionally constrained to void_items for the
                    // submitted void log and the current inbound.
                    if ($approval_scope === 'barcode') {
                        $delete_stmt = $conn->prepare(
                            "DELETE s
                             FROM stocks s
                             INNER JOIN void_items vi ON vi.unique_barcode = s.unique_barcode
                             INNER JOIN void_logs vl ON vl.id = vi.void_log_id
                             WHERE vi.void_log_id = ?
                               AND vi.unique_barcode = ?
                               AND s.unique_barcode = ? AND s.unique_key = ?
                               AND vl.po_id = ? AND vl.unique_key = ?
                               AND vl.request_type = 'void' AND vl.remarks IS NOT NULL
                               AND vl.status IN ('pending', 'completed')"
                        );
                        $delete_stmt->bind_param('isssis', $review_log_id, $approval_barcode, $approval_barcode, $unique_key, $po_id, $unique_key);
                    } elseif ($approval_scope === 'parent') {
                        $delete_stmt = $conn->prepare(
                            "DELETE s
                             FROM stocks s
                             INNER JOIN void_items vi ON vi.unique_barcode = s.unique_barcode
                             INNER JOIN void_logs vl ON vl.id = vi.void_log_id
                             WHERE vi.void_log_id = ?
                               AND s.parent_barcode = ? AND s.unique_key = ?
                               AND vl.po_id = ? AND vl.unique_key = ?
                               AND vl.request_type = 'void' AND vl.remarks IS NOT NULL
                               AND vl.status IN ('pending', 'completed')"
                        );
                        $delete_stmt->bind_param('issis', $review_log_id, $approval_parent, $unique_key, $po_id, $unique_key);
                    } else {
                        $delete_stmt = $conn->prepare(
                            "DELETE s
                             FROM stocks s
                             INNER JOIN void_items vi ON vi.unique_barcode = s.unique_barcode
                             INNER JOIN void_logs vl ON vl.id = vi.void_log_id
                             WHERE vi.void_log_id = ?
                               AND s.unique_key = ?
                               AND vl.po_id = ? AND vl.unique_key = ?
                               AND vl.request_type = 'void' AND vl.remarks IS NOT NULL
                               AND vl.status IN ('pending', 'completed')"
                        );
                        $delete_stmt->bind_param('isis', $review_log_id, $unique_key, $po_id, $unique_key);
                    }
                    $delete_stmt->execute();
                    $delete_stmt->close();

                    $approval_stmt = $conn->prepare(
                        "UPDATE void_logs
                         SET `status` = 'approved', approved_by = ?, approved_at = NOW()
                         WHERE id = ? AND po_id = ? AND unique_key = ?
                           AND request_type = 'void' AND remarks IS NOT NULL
                           AND `status` IN ('pending', 'completed')"
                    );
                    $approval_stmt->bind_param('siss', $user_id, $review_log_id, $po_id, $unique_key);

                    // If a supplier change was requested alongside this void
                    // (prev_supplier != new_supplier), apply it to stocks now
                    // that the request is being approved.
                    $supplier_stmt = $conn->prepare(
                        "SELECT prev_supplier, new_supplier FROM void_logs WHERE id = ?"
                    );
                    $supplier_stmt->bind_param('i', $review_log_id);
                    $supplier_stmt->execute();
                    $supplier_row = $supplier_stmt->get_result()->fetch_assoc();
                    $supplier_stmt->close();

                    if ($supplier_row
                        && $supplier_row['new_supplier'] !== null
                        && $supplier_row['new_supplier'] !== ''
                        && $supplier_row['prev_supplier'] !== $supplier_row['new_supplier']
                    ) {
                        $update_supplier_stmt = $conn->prepare(
                            "UPDATE stocks SET supplier = ? WHERE unique_key = ?"
                        );
                        $update_supplier_stmt->bind_param(
                            'ss',
                            $supplier_row['new_supplier'],
                            $unique_key
                        );
                        $update_supplier_stmt->execute();
                        $update_supplier_stmt->close();
                    }
                } else {
                    $approval_stmt = $conn->prepare(
                        "UPDATE void_logs
                         SET `status` = 'rejected', rejected_by = ?, rejected_at = NOW()
                         WHERE id = ? AND po_id = ? AND unique_key = ?
                           AND request_type = 'void' AND remarks IS NOT NULL
                           AND `status` IN ('pending', 'completed')"
                    );
                    $approval_stmt->bind_param('siss', $user_id, $review_log_id, $po_id, $unique_key);
                }

                $approval_stmt->execute();
                $row_updated = $approval_stmt->affected_rows > 0;
                $updated_count += $approval_stmt->affected_rows;
                $approval_stmt->close();

                // Notify the person who originally requested this void, and
                // log the decision, once the status change actually applied.
                if ($row_updated) {
                    $requested_by_stmt = $conn->prepare(
                        "SELECT requested_by FROM void_logs WHERE id = ?"
                    );
                    $requested_by_stmt->bind_param('i', $review_log_id);
                    $requested_by_stmt->execute();
                    $to_userid = $requested_by_stmt->get_result()->fetch_assoc()['requested_by'] ?? null;
                    $requested_by_stmt->close();

                    if ($to_userid !== null) {
                        $currentDateTime = date('Y-m-d H:i:s');

                        if ($void_type === 'approve') {
                            $notification_message = $user_fullname . ' approved your request to void inbound and stocks with ref #: ' . $unique_key;
                            $response_value = 'approved';
                        } else {
                            $notification_message = $user_fullname . ' declined your request to void inbound and stocks with ref #: ' . $unique_key;
                            $response_value = 'declined';
                        }

                        // Insert notification
                        $stmt3 = $conn->prepare(
                            "INSERT INTO notification (title, message, date, to_userid, status) VALUES (?, ?, ?, ?, 0)"
                        );
                        $title = 'Inbound and Stocks void request.';
                        $stmt3->bind_param("ssss", $title, $notification_message, $currentDateTime, $to_userid);
                        $stmt3->execute();
                        $stmt3->close();

                        // Insert log
                        $log_action = 'Inbound and Stocks Ref #: ' . $unique_key . ' have been ' . $response_value . ' to be voided by ' . $user_fullname . '.';
                        $stmt4 = $conn->prepare(
                            "INSERT INTO logs (title, action, user_id, date) VALUES ('INBOUND & STOCKS APPROVAL', ?, ?, ?)"
                        );
                        $stmt4->bind_param("sss", $log_action, $user_id, $currentDateTime);
                        $stmt4->execute();
                        $stmt4->close();
                    }
                }
            }

            if ($updated_count !== count($void_log_ids)) {
                throw new RuntimeException('One or more void requests are no longer available for approval.');
            }

            mysqli_commit($conn);
            respond(true, $void_type === 'approve'
                ? 'The void request has been approved and the requested stocks were removed.'
                : 'The void request has been rejected.');
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            throw $e;
        }
    }

    // ---- reuse a pending void log for this PO/unique key, or create one --
    // (content.php can fire several void actions for the same po_id +
    // unique_key before "Done" is clicked; those should all land under one
    // void_logs row rather than a new one per click.)
    $check_stmt = $conn->prepare(
        "SELECT id, new_supplier FROM void_logs WHERE request_type = 'void' AND po_id = ? AND unique_key = ? AND `status` = 'pending'"
    );
    $check_stmt->bind_param('ss', $po_id, $unique_key);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $existing_void_log = $check_result->fetch_assoc();
        $void_log_id = $existing_void_log['id'];
        $void_new_supplier = $existing_void_log['new_supplier'];
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
            "UPDATE void_logs SET remarks = ? WHERE id = ?"
        );
        $update_stmt->bind_param('si', $void_remarks, $void_log_id);

        if ($update_stmt->execute()) {
            $update_stmt->close();

            $currentDateTime = date('Y-m-d H:i:s');

            // Insert notification
            $notification_message = $user_fullname . ' is requesting to void inbound with ref #: ' . $unique_key;
            $stmt3 = $conn->prepare("INSERT INTO `notification` (title, `message`, `date`, `status`) VALUES (?, ?, ?, 0)");
            $title = 'Inbound void request.';
            $stmt3->bind_param("sss", $title, $notification_message, $currentDateTime);

            if ($stmt3->execute()) {
                $stmt3->close();

                // Insert log entry
                $log_action = 'Inbound Ref #: ' . $unique_key . ' has been successfully requested to be voided by ' . $user_fullname . '.';
                $stmt4 = $conn->prepare("INSERT INTO logs (title, action, user_id, date) VALUES ('INBOUND VOID', ?, ?, ?)");
                $stmt4->bind_param("sss", $log_action, $user_id, $currentDateTime);

                if ($stmt4->execute()) {
                    $stmt4->close();
                    respond(true, 'This void request has been marked as done.', ['void_log_id' => $void_log_id]);
                } else {
                    $stmt4->close();
                    respond(false, 'Void request saved, but failed to log the request.', ['void_log_id' => $void_log_id]);
                }
            } else {
                $stmt3->close();
                respond(false, 'Void request saved, but failed to create notification.', ['void_log_id' => $void_log_id]);
            }
        } else {
            $update_stmt->close();
            respond(false, 'Failed to save remarks for this void request.');
        }
    }
} catch (Throwable $e) {
    // Don't leak internal error details (queries, DB structure) to the client,
    // but do log the real cause so it's actually debuggable server-side.
    error_log('[void-function] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    respond(false, 'A server error occurred while processing this void request.');
}
