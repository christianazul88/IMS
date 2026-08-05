<?php
/**
 * inbound-void/functions.php
 *
 * Reusable core for the Inbound Void Request & Approval module.
 *
 * This file assumes $conn (mysqli) already exists in scope — it is
 * included by index.php AFTER ../config/database.php has run, so no
 * database include happens here.
 *
 * Does NOT touch the existing barcode-void workflow (void_logs rows
 * with request_type = 'barcode', or void_items). Only adds behavior
 * for request_type = 'inbound'.
 */

/**
 * Build the full merged product+barcode tree for a given inbound
 * transaction (po_id + unique_key).
 *
 * Single source of truth used by: the create-request preview, the
 * approval detail view, and the approval action itself.
 *
 * Returns:
 * [
 *   'product_id' => [
 *       'product_id'    => ...,
 *       'description'   => ...,
 *       'brand_name'    => ...,
 *       'category_name' => ...,
 *       'ordered_qty'   => int|null,  // null if extra-only
 *       'extra_qty'     => int|null,  // null if PO-only
 *       'is_extra'      => bool,      // true if not on the PO at all
 *       'barcodes'      => [ ['unique_barcode'=>, 'capital'=>, 'item_status'=>], ... ],
 *   ],
 *   ...
 * ]
 */
function getInboundTransaction($conn, $po_id, $unique_key)
{
    $po_id = (int) $po_id;
    $unique_key = mysqli_real_escape_string($conn, $unique_key);

    $products = [];

    // --- 1. Base products from the purchase order (existing query, untouched) ---
    $poContentQuery = "
        SELECT
            poc.product_id,
            poc.qty,
            p.description,
            b.brand_name,
            c.category_name
        FROM purchased_order_content poc
        LEFT JOIN product p ON poc.product_id = p.hashed_id
        LEFT JOIN brand b ON p.brand = b.hashed_id
        LEFT JOIN category c ON p.category = c.hashed_id
        WHERE poc.po_id = $po_id
    ";
    $poResult = mysqli_query($conn, $poContentQuery);
    while ($row = mysqli_fetch_assoc($poResult)) {
        $pid = $row['product_id'];
        $products[$pid] = [
            'product_id'    => $pid,
            'description'   => $row['description'],
            'brand_name'    => $row['brand_name'],
            'category_name' => $row['category_name'],
            'ordered_qty'   => (int) $row['qty'],
            'extra_qty'     => null,
            'is_extra'      => false,
            'barcodes'      => [],
        ];
    }

    // --- 2. Extra products (received but not on the original PO) ---
    $extraQuery = "
        SELECT
            ie.product_id,
            ie.qty,
            p.description,
            b.brand_name,
            c.category_name
        FROM inbound_extra_items ie
        LEFT JOIN product p ON ie.product_id = p.hashed_id
        LEFT JOIN brand b ON p.brand = b.hashed_id
        LEFT JOIN category c ON p.category = c.hashed_id
        WHERE ie.unique_key = '$unique_key'
    ";
    $extraResult = mysqli_query($conn, $extraQuery);
    while ($row = mysqli_fetch_assoc($extraResult)) {
        $pid = $row['product_id'];
        if (isset($products[$pid])) {
            $products[$pid]['extra_qty'] = (int) $row['qty'];
        } else {
            $products[$pid] = [
                'product_id'    => $pid,
                'description'   => $row['description'],
                'brand_name'    => $row['brand_name'],
                'category_name' => $row['category_name'],
                'ordered_qty'   => null,
                'extra_qty'     => (int) $row['qty'],
                'is_extra'      => true,
                'barcodes'      => [],
            ];
        }
    }

    // --- 3. For every merged product, pull its barcodes (existing critical query, untouched) ---
    foreach ($products as $pid => &$product) {
        $safe_pid = mysqli_real_escape_string($conn, $pid);
        $stocks_display_Query = "
            SELECT
                s.unique_barcode,
                s.capital,
                s.item_status
            FROM stocks s
            WHERE
                s.unique_key = '$unique_key'
                AND s.product_id = '$safe_pid'
        ";
        $stocksResult = mysqli_query($conn, $stocks_display_Query);
        while ($stockRow = mysqli_fetch_assoc($stocksResult)) {
            $product['barcodes'][] = $stockRow;
        }
    }
    unset($product);

    return $products;
}

/**
 * Flatten the tree into a plain list of unique_barcode values.
 * Used at approval time to know exactly what to reverse.
 */
function getAllBarcodesForInbound($conn, $po_id, $unique_key)
{
    $tree = getInboundTransaction($conn, $po_id, $unique_key);
    $barcodes = [];
    foreach ($tree as $product) {
        foreach ($product['barcodes'] as $b) {
            $barcodes[] = $b['unique_barcode'];
        }
    }
    return $barcodes;
}

/**
 * Create a new Inbound Void Request.
 * Inserts exactly ONE row into void_logs. Never touches void_items.
 * Returns the new void_logs id on success, or false on failure.
 */
function createInboundVoidRequest($conn, $po_id, $unique_key, $remarks, $requested_by = null)
{
    $po_id = (int) $po_id;
    $unique_key_esc = mysqli_real_escape_string($conn, $unique_key);
    $remarks_esc = mysqli_real_escape_string($conn, $remarks);

    // Guard: refuse to create a request for a transaction with no stock rows.
    $barcodes = getAllBarcodesForInbound($conn, $po_id, $unique_key);
    if (empty($barcodes)) {
        return false;
    }

    $requested_by_val = $requested_by !== null ? "'" . mysqli_real_escape_string($conn, $requested_by) . "'" : 'NULL';

    $query = "
        INSERT INTO void_logs (request_type, po_id, unique_key, remarks, status, requested_by, created_at)
        VALUES ('inbound', $po_id, '$unique_key_esc', '$remarks_esc', 'pending', $requested_by_val, NOW())
    ";

    if (mysqli_query($conn, $query)) {
        return mysqli_insert_id($conn);
    }
    return false;
}

/**
 * Fetch a single inbound void_logs request by id.
 * Returns null if not found or not an inbound request.
 */
function getInboundVoidRequest($conn, $void_log_id)
{
    $void_log_id = (int) $void_log_id;
    $query = "SELECT * FROM void_logs WHERE id = $void_log_id AND request_type = 'inbound'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

/**
 * List all pending inbound void requests, for the administrator queue.
 */
function listPendingInboundVoidRequests($conn)
{
    $query = "
        SELECT *
        FROM void_logs
        WHERE request_type = 'inbound' AND status = 'pending'
        ORDER BY created_at ASC
    ";
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Reverse a single barcode's inventory impact.
 *
 * WIRE THIS UP: replace the body with a call to whatever function your
 * existing barcode-void approval already uses per-barcode, e.g.:
 *
 *   return reverseInventoryForBarcode($conn, $unique_barcode);
 *
 * Left throwing on purpose so this is never silently a no-op.
 */
function reverseInboundBarcode($conn, $unique_barcode)
{
    throw new Exception('reverseInboundBarcode() must be wired to the existing inventory reversal logic.');
}

/**
 * Approve an inbound void request inside a single transaction.
 * Locks the row, re-derives the authoritative barcode list, reverses
 * every barcode via the existing logic, then marks approved.
 * Throws on failure; caller should catch and display the message.
 */
function approveInboundVoidRequest($conn, $void_log_id, $approved_by = null)
{
    $void_log_id = (int) $void_log_id;

    mysqli_begin_transaction($conn);

    try {
        $lockQuery = "SELECT * FROM void_logs WHERE id = $void_log_id AND request_type = 'inbound' FOR UPDATE";
        $lockResult = mysqli_query($conn, $lockQuery);
        if (!$lockResult || mysqli_num_rows($lockResult) === 0) {
            throw new Exception("Inbound void request #$void_log_id not found.");
        }
        $request = mysqli_fetch_assoc($lockResult);

        if ($request['status'] !== 'pending') {
            throw new Exception("Inbound void request #$void_log_id is not pending (status: {$request['status']}).");
        }

        $po_id = $request['po_id'];
        $unique_key = $request['unique_key'];

        $barcodes = getAllBarcodesForInbound($conn, $po_id, $unique_key);
        if (empty($barcodes)) {
            throw new Exception("No stock records found for unique_key '$unique_key' — nothing to reverse.");
        }

        foreach ($barcodes as $unique_barcode) {
            reverseInboundBarcode($conn, $unique_barcode);
        }

        $approved_by_val = $approved_by !== null ? "'" . mysqli_real_escape_string($conn, $approved_by) . "'" : 'NULL';

        $updateQuery = "
            UPDATE void_logs
            SET status = 'approved', approved_by = $approved_by_val, approved_at = NOW()
            WHERE id = $void_log_id
        ";
        if (!mysqli_query($conn, $updateQuery)) {
            throw new Exception('Failed to update void_logs status: ' . mysqli_error($conn));
        }

        mysqli_commit($conn);
        return true;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}

/**
 * Reject an inbound void request. No inventory changes.
 */
function rejectInboundVoidRequest($conn, $void_log_id, $rejected_by = null)
{
    $void_log_id = (int) $void_log_id;
    $rejected_by_val = $rejected_by !== null ? "'" . mysqli_real_escape_string($conn, $rejected_by) . "'" : 'NULL';

    $query = "
        UPDATE void_logs
        SET status = 'rejected', rejected_by = $rejected_by_val, rejected_at = NOW()
        WHERE id = $void_log_id AND request_type = 'inbound' AND status = 'pending'
    ";
    if (!mysqli_query($conn, $query)) {
        throw new Exception('Failed to reject request: ' . mysqli_error($conn));
    }
    return mysqli_affected_rows($conn) > 0;
}

/**
 * Add an "extra" product to an inbound transaction (received but not on
 * the original PO). Does not touch purchased_order_content. Barcodes for
 * this product should still land in `stocks` under the same unique_key
 * via your normal receiving flow — this only records the extra line item.
 */
function addInboundExtraItem($conn, $po_id, $unique_key, $product_id, $qty, $capital, $remarks = null)
{
    $po_id = (int) $po_id;
    $unique_key_esc = mysqli_real_escape_string($conn, $unique_key);
    $product_id_esc = mysqli_real_escape_string($conn, $product_id);
    $qty = (int) $qty;
    $capital = (float) $capital;
    $remarks_val = $remarks !== null ? "'" . mysqli_real_escape_string($conn, $remarks) . "'" : 'NULL';

    $query = "
        INSERT INTO inbound_extra_items (unique_key, po_id, product_id, qty, capital, remarks, created_at)
        VALUES ('$unique_key_esc', $po_id, '$product_id_esc', $qty, $capital, $remarks_val, NOW())
    ";
    if (mysqli_query($conn, $query)) {
        return mysqli_insert_id($conn);
    }
    return false;
}
