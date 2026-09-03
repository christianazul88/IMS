<?php
require "../config/database.php";
require "../config/on_session.php";

/*
|--------------------------------------------------------------------------
| PRINT / SAVE-AS-PDF LIMIT
|--------------------------------------------------------------------------
| Set to false after the feature becomes paid/unlimited.
|
| TRUE  = maximum print/save actions, tracked in print_limit.json
| FALSE = unlimited printing; tracker is not created/updated.
|--------------------------------------------------------------------------
*/
$PRINT_LIMIT_ENABLED = true;
$PRINT_LIMIT = 8;
$PRINT_TRACKER = __DIR__ . '/print_limit.json';

// Opening the report in the browser is tracked separately from printing.
$VIEW_LIMIT_ENABLED = true;
$VIEW_LIMIT = 10;
$VIEW_TRACKER = __DIR__ . '/view_limit.json';

if (!isset($_GET['transfer_id']) || empty($_GET['transfer_id'])) {
    die("Transfer ID is required.");
}

$transfer_id = $_GET['transfer_id'];
$filename_id = $transfer_id + 10000; // Add 10000 to the transfer ID for the filename

/*
|--------------------------------------------------------------------------
| Main Query (unchanged from the CSV export)
|--------------------------------------------------------------------------
*/
$main_query = "SELECT 
    (st.id + 10000) AS transfer_id,
    st.status,
    st.date_out,
    st.date_received,
    st.remarks_sender,
    st.remarks_receiver,
    st.received_userid,

    stc.unique_barcode,

    s.capital,

    p.description AS product_description,
    b.brand_name,
    c.category_name,

    w_sender.warehouse_name AS sender_warehouse,
    w_receiver.warehouse_name AS receiver_warehouse,
    w_on.warehouse_name AS current_location,

    CONCAT(
        COALESCE(u_sender.user_fname, ''),
        ' ',
        COALESCE(u_sender.user_lname, '')
    ) AS dispatched_by,

    CONCAT(
        COALESCE(u_receiver.user_fname, ''),
        ' ',
        COALESCE(u_receiver.user_lname, '')
    ) AS received_by,

    COALESCE(latest_audit.action, 'Not Audited') AS audit_status,
    latest_audit.date AS audit_date

FROM stock_transfer st

INNER JOIN stock_transfer_content stc 
    ON st.id = stc.st_id

LEFT JOIN warehouse w_sender 
    ON st.from_warehouse = w_sender.hashed_id

LEFT JOIN warehouse w_receiver 
    ON st.to_warehouse = w_receiver.hashed_id

LEFT JOIN stocks s 
    ON s.unique_barcode = stc.unique_barcode

LEFT JOIN warehouse w_on 
    ON s.warehouse = w_on.hashed_id

LEFT JOIN users u_sender 
    ON st.from_userid = u_sender.hashed_id

LEFT JOIN users u_receiver 
    ON st.received_userid = u_receiver.hashed_id

LEFT JOIN product p 
    ON p.hashed_id = s.product_id

LEFT JOIN brand b 
    ON b.hashed_id = p.brand

LEFT JOIN category c 
    ON c.hashed_id = p.category

/*
|--------------------------------------------------------------------------
| Get latest Audited timeline entry for each barcode
|--------------------------------------------------------------------------
*/
LEFT JOIN (
    SELECT st1.*
    FROM stock_timeline st1

    INNER JOIN (
        SELECT 
            unique_barcode,
            MAX(id) AS max_id

        FROM stock_timeline

        WHERE title = 'Audited'

        GROUP BY unique_barcode
    ) st2
        ON st1.id = st2.max_id

) latest_audit
    ON stc.unique_barcode = latest_audit.unique_barcode

WHERE st.id = ?";

$stmt = $conn->prepare($main_query);
$stmt->bind_param("i", $transfer_id);
$stmt->execute();

$result = $stmt->get_result();

/*
|--------------------------------------------------------------------------
| Prepare Data
|--------------------------------------------------------------------------
*/

$rows = [];

$total_qty = 0;
$total_capital = 0;

while ($row = $result->fetch_assoc()) {

    /*
    |--------------------------------------------------------------------------
    | Received By
    |--------------------------------------------------------------------------
    */

    if (
        $row['received_userid'] ===
        "6b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b"
    ) {
        $received_by = "SYSTEM";
    } else {
        $received_by = trim($row['received_by']);
    }

    /*
    |--------------------------------------------------------------------------
    | Barcode Information
    |--------------------------------------------------------------------------
    */

    $barcode_parts = explode('-', $row['unique_barcode'], 2);

    $parent_barcode = $barcode_parts[0] ?? '';
    $sequence_number = $barcode_parts[1] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Quantity
    |--------------------------------------------------------------------------
    */

    $qty = 1;

    /*
    |--------------------------------------------------------------------------
    | Capital
    |--------------------------------------------------------------------------
    */

    $capital = (float) ($row['capital'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | Totals
    |--------------------------------------------------------------------------
    */

    $total_qty += $qty;
    $total_capital += $capital;

    /*
    |--------------------------------------------------------------------------
    | Store Row
    |--------------------------------------------------------------------------
    */

    $rows[] = [
        'transfer_id'         => $row['transfer_id'],
        'status'               => $row['status'],

        'date_out'             => $row['date_out'],
        'date_received'        => $row['date_received'],

        'remarks_sender'       => $row['remarks_sender'],
        'remarks_receiver'     => $row['remarks_receiver'],

        'unique_barcode'       => $row['unique_barcode'],
        'parent_barcode'       => $parent_barcode,
        'sequence_number'      => (int) $sequence_number,

        'product_description'  => $row['product_description'],
        'brand_name'           => $row['brand_name'],
        'category_name'        => $row['category_name'],

        'sender_warehouse'     => $row['sender_warehouse'],
        'receiver_warehouse'   => $row['receiver_warehouse'],
        'current_location'     => $row['current_location'],

        'dispatched_by'        => trim($row['dispatched_by']),
        'received_by'          => $received_by,

        'audit_status'         => $row['audit_status'],
        'audit_date'           => $row['audit_date'] ?? null,

        'capital'              => $capital,
        'qty'                  => $qty,
    ];
}

/*
|--------------------------------------------------------------------------
| No Records
|--------------------------------------------------------------------------
*/

if (count($rows) === 0) {
    die("No records found for transfer ID: " . $transfer_id);
}

/*
|--------------------------------------------------------------------------
| Legacy PDF export code (retained temporarily but disabled)
|--------------------------------------------------------------------------
| This is intentionally placed AFTER the no-records check so a failed
| /empty report does not consume one of the available downloads.
|--------------------------------------------------------------------------
*/
$downloads_remaining = null;

if (false) {
    function registerPdfDownload($tracker_file, $limit)
    {
        /*
        * Open/create the JSON tracker.
        * __DIR__ ensures this is the folder containing this PHP file.
        */
        $fp = fopen($tracker_file, 'c+');

        if ($fp === false) {
            http_response_code(500);
            die(
                "Unable to create the PDF download tracker. " .
                "Please make sure this folder is writable by PHP."
            );
        }

        // Prevent simultaneous exports from using the same count.
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            http_response_code(500);
            die("Unable to access the PDF download tracker. Please try again.");
        }

        rewind($fp);
        $contents = stream_get_contents($fp);
        $tracker = json_decode($contents, true);

        if (!is_array($tracker)) {
            $tracker = [
                'download_count' => 0,
                'download_limit' => $limit,
                'created_at' => date('c'),
            ];
        }

        $download_count = (int) ($tracker['download_count'] ?? 0);

        if ($download_count >= $limit) {
            flock($fp, LOCK_UN);
            fclose($fp);

            http_response_code(429);

            /*
            * Professional download-limit page.
            *
            * This page intentionally does not use Content-Disposition because
            * the user needs to see the message in the browser instead of
            * receiving another file download.
            *
            * Bootstrap 5.3 is loaded from jsDelivr.
            */
            $safe_limit = (int) $limit;

            echo '<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Download Limit Reached</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <style>
        :root {
            --page-bg: #f5f7fb;
            --card-border: #e9edf3;
            --text-main: #1f2937;
            --text-muted: #6b7280;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top, rgba(13, 110, 253, 0.08), transparent 35%),
                var(--page-bg);
            color: var(--text-main);
        }

        .limit-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .limit-card {
            width: 100%;
            max-width: 520px;
            border: 1px solid var(--card-border);
            border-radius: 1.25rem;
            background: #fff;
            box-shadow: 0 1rem 3rem rgba(31, 41, 55, 0.10);
            overflow: hidden;
        }

        .limit-card-body {
            padding: 2.5rem;
        }

        .limit-icon {
            width: 76px;
            height: 76px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #fff3cd;
            color: #997404;
            font-size: 2rem;
        }

        .limit-title {
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .limit-message {
            color: var(--text-muted);
            line-height: 1.7;
        }

        .limit-info {
            border: 1px solid #e9ecef;
            border-radius: .875rem;
            background: #f8f9fa;
            padding: 1rem 1.125rem;
        }

        .btn {
            min-height: 46px;
            border-radius: .75rem;
            font-weight: 600;
        }

        @media (max-width: 575.98px) {
            .limit-card-body {
                padding: 2rem 1.25rem;
            }
        }
    </style>
</head>

<body>
    <main class="limit-wrapper">
        <section class="limit-card" aria-labelledby="downloadLimitTitle">
            <div class="limit-card-body text-center">

                <div class="limit-icon" aria-hidden="true">
                    <svg
                        width="36"
                        height="36"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M12 3v12"></path>
                        <path d="m7 10 5 5 5-5"></path>
                        <path d="M5 21h14"></path>
                    </svg>
                </div>

                <div class="text-uppercase small fw-semibold text-secondary mb-2">
                    Export unavailable
                </div>

                <h1 id="downloadLimitTitle" class="limit-title h3 mb-3">
                    Download Limit Reached
                </h1>

                <p class="limit-message mb-4">
                    You have reached the maximum number of PDF downloads currently
                    available for this feature.
                </p>

                <div class="limit-info text-start mb-4">
                    <div class="d-flex justify-content-between align-items-center gap-3">
                        <span class="text-secondary">Current download limit</span>
                        <span class="fw-bold">' . $safe_limit . ' downloads</span>
                    </div>
                </div>

                <p class="small text-secondary mb-4">
                    Thank you for your understanding. Please contact the developer
                    if you need additional export access.
                </p>

                <div class="d-grid gap-2">
                    <button
                        type="button"
                        class="btn btn-primary"
                        onclick="goBack()"
                    >
                        <svg
                            class="me-1"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="m15 18-6-6 6-6"></path>
                        </svg>
                        Back to Previous Page
                    </button>

                    <a
                        href="javascript:void(0)"
                        class="btn btn-outline-secondary"
                        onclick="window.location.reload()"
                    >
                        Try Again
                    </a>
                </div>

            </div>
        </section>
    </main>

    <script>
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = document.referrer || "/";
            }
        }
    </script>
</body>
</html>';

            exit;
        }

        $download_count++;

        $tracker['download_count'] = $download_count;
        $tracker['download_limit'] = $limit;
        $tracker['updated_at'] = date('c');

        rewind($fp);
        ftruncate($fp, 0);

        $json = json_encode(
            $tracker,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        if ($json === false || fwrite($fp, $json) === false) {
            flock($fp, LOCK_UN);
            fclose($fp);

            http_response_code(500);
            die("Unable to update the PDF download tracker.");
        }

        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $download_count;
    }

    $download_count = registerPdfDownload(
        $PDF_EXPORT_TRACKER,
        $PDF_EXPORT_LIMIT
    );

}

/** Register an intentional Print / Save as PDF action, atomically. */
function registerPrintAction($tracker_file, $limit)
{
    $fp = fopen($tracker_file, 'c+');
    if ($fp === false || !flock($fp, LOCK_EX)) {
        if ($fp !== false) fclose($fp);
        throw new RuntimeException('Unable to access the print counter. Please try again.');
    }

    rewind($fp);
    $tracker = json_decode(stream_get_contents($fp), true);
    if (!is_array($tracker)) {
        $legacy_file = dirname($tracker_file) . '/pdf_download_limit.json';
        $legacy = is_file($legacy_file) ? json_decode((string) file_get_contents($legacy_file), true) : [];
        $tracker = [
            'print_count' => is_array($legacy) ? (int) ($legacy['download_count'] ?? 0) : 0,
            'print_limit' => $limit,
            'created_at' => date('c'),
        ];
    }

    $count = (int) ($tracker['print_count'] ?? 0);
    if ($count >= $limit) {
        flock($fp, LOCK_UN);
        fclose($fp);
        throw new RuntimeException('The limit of ' . $limit . ' print or Save as PDF actions has been reached.');
    }

    $count++;
    $tracker['print_count'] = $count;
    $tracker['print_limit'] = $limit;
    $tracker['updated_at'] = date('c');
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($tracker, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $count;
}

$print_count = 0;
if ($PRINT_LIMIT_ENABLED && is_file($PRINT_TRACKER)) {
    $tracker = json_decode((string) file_get_contents($PRINT_TRACKER), true);
    $print_count = is_array($tracker) ? (int) ($tracker['print_count'] ?? 0) : 0;
} elseif ($PRINT_LIMIT_ENABLED && is_file(__DIR__ . '/pdf_download_limit.json')) {
    // Preserve any usage already recorded by the previous PDF-only version.
    $legacy_tracker = json_decode((string) file_get_contents(__DIR__ . '/pdf_download_limit.json'), true);
    $print_count = is_array($legacy_tracker) ? (int) ($legacy_tracker['download_count'] ?? 0) : 0;
}
$prints_remaining = max(0, $PRINT_LIMIT - $print_count);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register_print') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $count = $PRINT_LIMIT_ENABLED ? registerPrintAction($PRINT_TRACKER, $PRINT_LIMIT) : 0;
        echo json_encode(['ok' => true, 'remaining' => $PRINT_LIMIT_ENABLED ? $PRINT_LIMIT - $count : null]);
    } catch (RuntimeException $e) {
        http_response_code(429);
        echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/** Register an intentional browser view, atomically. */
function registerReportView($tracker_file, $limit)
{
    $fp = fopen($tracker_file, 'c+');
    if ($fp === false || !flock($fp, LOCK_EX)) {
        if ($fp !== false) fclose($fp);
        throw new RuntimeException('Unable to access the view counter. Please try again.');
    }
    rewind($fp);
    $tracker = json_decode(stream_get_contents($fp), true);
    if (!is_array($tracker)) $tracker = ['view_count' => 0, 'view_limit' => $limit, 'created_at' => date('c')];
    $count = (int) ($tracker['view_count'] ?? 0);
    if ($count >= $limit) {
        flock($fp, LOCK_UN);
        fclose($fp);
        throw new RuntimeException('The limit of ' . $limit . ' report views has been reached.');
    }
    $count++;
    $tracker['view_count'] = $count;
    $tracker['view_limit'] = $limit;
    $tracker['updated_at'] = date('c');
    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($tracker, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $count;
}

$views_remaining = null;
if ($VIEW_LIMIT_ENABLED && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $view_count = registerReportView($VIEW_TRACKER, $VIEW_LIMIT);
        $views_remaining = $VIEW_LIMIT - $view_count;
    } catch (RuntimeException $e) {
        http_response_code(429);
        die('<!doctype html><html><head><meta charset="utf-8"><title>View Limit Reached</title></head><body style="font-family:Arial,sans-serif;max-width:560px;margin:12vh auto;padding:32px;color:#1f2937"><h1>View Limit Reached</h1><p>' . htmlspecialchars($e->getMessage()) . '</p><p>Please contact the system developer if you need additional access.</p></body></html>');
    }
}

/*
|--------------------------------------------------------------------------
| First Row = Transfer Information
|--------------------------------------------------------------------------
*/

$first = $rows[0];

/*
|--------------------------------------------------------------------------
| Filename
|--------------------------------------------------------------------------
*/

$filename =
    "Transfer ID - " .
    $filename_id .
    " " .
    date('Y-m-d') .
    ".pdf";

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Format a raw datetime string the same way across the report.
 * Returns 'N/A' for empty/unparseable values.
 */
function formatReportDate($value)
{
    if (empty($value)) {
        return 'N/A';
    }
    $ts = strtotime($value);
    return $ts !== false ? date('n/j/Y g:i A', $ts) : 'N/A';
}

/**
 * Format sequence numbers into consecutive ranges.
 * Examples:
 * 1,2,3,4,5       => 1-5
 * 1,3,4,5         => 1 and 3-5
 * 1,3,5,7,8,9     => 1, 3, 5 and 7-9
 * 1,2,4,6,8,9     => 1-2, 4, 6 and 8-9
 */
function formatSequences($sequences)
{
    if (empty($sequences)) {
        return '';
    }

    $sequences = array_unique(array_map('intval', $sequences));
    sort($sequences, SORT_NUMERIC);

    $ranges = [];
    $start = $sequences[0];
    $previous = $sequences[0];

    for ($i = 1; $i < count($sequences); $i++) {
        $current = $sequences[$i];

        if ($current === $previous + 1) {
            $previous = $current;
            continue;
        }

        $ranges[] = ($start === $previous)
            ? (string) $start
            : $start . '-' . $previous;

        $start = $current;
        $previous = $current;
    }

    $ranges[] = ($start === $previous)
        ? (string) $start
        : $start . '-' . $previous;

    if (count($ranges) === 1) {
        return $ranges[0];
    }

    if (count($ranges) === 2) {
        return $ranges[0] . ' and ' . $ranges[1];
    }

    $last = array_pop($ranges);
    return implode(', ', $ranges) . ' and ' . $last;
}

/**
 * Map a transfer/audit status string to a badge style + display label,
 * so the PDF reads at a glance instead of as plain uppercase text.
 * Falls back to a neutral navy badge for anything unrecognized, so a
 * new status value added later never breaks rendering.
 */
function statusBadge($status)
{
    $label = trim((string) $status) !== '' ? strtoupper($status) : 'N/A';
    $normalized = strtolower(trim((string) $status));

    $success = ['completed', 'received', 'approved'];
    $warning = ['pending', 'in transit', 'in_transit', 'dispatched'];
    $danger  = ['cancelled', 'canceled', 'rejected', 'void', 'voided'];

    if (in_array($normalized, $success, true)) {
        $class = 'badge-success';
    } elseif (in_array($normalized, $warning, true)) {
        $class = 'badge-warning';
    } elseif (in_array($normalized, $danger, true)) {
        $class = 'badge-danger';
    } else {
        $class = 'badge-neutral';
    }

    return ['label' => $label, 'class' => $class];
}

/**
 * Build the full report HTML. Kept as a pure function of its inputs
 * (no DB/session access) so the markup can be reviewed or tested on
 * its own, independent of the query above.
 */
function renderTransferReportHtml(array $ctx)
{
    extract($ctx);
    /**
     * @var array  $rows
     * @var array  $first
     * @var float  $total_qty
     * @var float  $total_capital
     * @var array  $product_summary
     * @var string $date_dispatched
     * @var string $date_received
     * @var string $generated_at
     * @var string $generated_by
     * @var string $generated_position
     * @var bool   $print_limit_enabled
     * @var int    $prints_remaining
     * @var int    $print_limit
     * @var bool   $view_limit_enabled
     * @var int|null $views_remaining
     * @var int    $view_limit
     */

    $status_badge = statusBadge($first['status']);
    $audit_capable_rows = $rows;

    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            @page {
                margin: 14mm 12mm;
            }

            :root {
                --navy: #1f3a5f;
                --navy-soft: #eef2f8;
                --border: #e4e8ee;
                --text: #1f2937;
                --text-muted: #6b7280;
                --danger: #b3261e;
                --danger-soft: #fbebea;
                --success-bg: #e6f4ea;
                --success-text: #1e7a3a;
                --warning-bg: #fdf3e0;
                --warning-text: #8a6110;
            }

            * {
                box-sizing: border-box;
            }

            body {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 12px;
                color: var(--text);
                margin: 0;
                background: #f3f6fa;
            }

            .screen-toolbar { max-width: 210mm; margin: 18px auto 12px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
            .screen-toolbar p { margin: 0; color: var(--text-muted); font-size: 13px; }
            .print-button { appearance: none; border: 0; border-radius: 7px; background: var(--navy); color: #fff; padding: 11px 16px; font-weight: 700; cursor: pointer; font-size: 13px; box-shadow: 0 4px 12px rgba(31,58,95,.2); }
            .print-button:disabled { opacity: .6; cursor: wait; }
            .print-status { color: var(--text-muted); font-size: 12px; text-align: right; }
            .document { max-width: 210mm; min-height: 297mm; margin: 0 auto 24px; padding: 14mm 12mm; background: #fff; box-shadow: 0 6px 30px rgba(31,41,55,.12); }

            h1, h2 {
                margin: 0;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            /* Report header */
            .report-header {
                display: table;
                width: 100%;
                padding-bottom: 12px;
                margin-bottom: 14px;
                border-bottom: 2px solid var(--navy);
            }
            .brand-mark { width: 54px; height: 54px; object-fit: contain; vertical-align: middle; margin-right: 12px; }
            .report-heading { display: table-cell; vertical-align: middle; }
            .report-title {
                font-size: 18px;
                font-weight: 700;
                color: var(--navy);
                letter-spacing: 0.01em;
            }
            .report-sub {
                font-size: 10.5px;
                color: var(--text-muted);
                margin-top: 2px;
            }

            /* Meta grid (two columns) */
            .meta-grid {
                width: 100%;
                border-collapse: separate;
                background: var(--navy-soft);
                border-radius: 8px;
                padding: 10px 14px;
                margin-bottom: 14px;
            }
            .meta-grid td {
                padding: 3px 8px;
                font-size: 10.5px;
                vertical-align: top;
            }
            .meta-label {
                color: var(--text-muted);
                font-weight: 600;
                white-space: nowrap;
            }
            .meta-value {
                color: var(--text);
                font-weight: 600;
                text-align: right;
            }

            .remarks-box {
                border: 1px solid var(--border);
                border-radius: 8px;
                padding: 8px 12px;
                margin-bottom: 14px;
                font-size: 10.5px;
            }
            .remarks-box .meta-label {
                display: block;
                margin-bottom: 4px;
            }

            /* Badges */
            .badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 999px;
                font-size: 9.5px;
                font-weight: 700;
                letter-spacing: 0.02em;
            }
            .badge-success { background: var(--success-bg); color: var(--success-text); }
            .badge-warning { background: var(--warning-bg); color: var(--warning-text); }
            .badge-danger  { background: var(--danger-soft); color: var(--danger); }
            .badge-neutral { background: var(--navy-soft);   color: var(--navy); }

            /* Section headings */
            .section-title {
                font-size: 12.5px;
                font-weight: 700;
                color: var(--navy);
                text-transform: uppercase;
                letter-spacing: 0.03em;
                margin: 4px 0 8px 0;
                padding-bottom: 4px;
                border-bottom: 1px solid var(--border);
            }

            /* Data tables */
            .data-table {
                border: 1px solid var(--border);
                margin-bottom: 4px;
            }
            .data-table thead {
                display: table-header-group;
            }
            .data-table tfoot {
                display: table-footer-group;
            }
            .data-table tr {
                page-break-inside: avoid;
            }
            .data-table th {
                font-size: 9px;
                font-weight: 700;
                letter-spacing: 0.03em;
                text-transform: uppercase;
                color: var(--navy);
                background: var(--navy-soft);
                padding: 6px 7px;
                text-align: left;
                border-bottom: 1px solid var(--border);
            }
            .data-table td {
                padding: 6px 7px;
                border-top: 1px solid var(--border);
                font-size: 10.5px;
            }
            .data-table .text-end {
                text-align: right;
            }
            .data-table .num {
                width: 22px;
                color: var(--text-muted);
            }
            .data-table .code {
                font-family: "DejaVu Sans Mono", monospace;
                font-size: 9.5px;
                background: var(--navy-soft);
                padding: 1px 5px;
                border-radius: 4px;
                white-space: nowrap;
            }
            .totals-row td {
                font-weight: 700;
                border-top: 2px solid var(--navy);
                background: var(--navy-soft);
            }

            .page-break {
                break-before: page;
                page-break-before: always;
                height: 0;
                clear: both;
            }

            /* Signature / confirmation block */
            .signature-grid {
                width: 100%;
                margin-top: 20px;
            }
            .signature-grid td {
                width: 50%;
                padding: 0 10px;
                vertical-align: top;
            }
            .signature-line {
                margin-top: 34px;
                border-top: 1px solid var(--text);
                padding-top: 4px;
                font-size: 10px;
            }
            .signature-name {
                font-size: 11.5px;
                font-weight: 700;
            }
            .signature-role {
                font-size: 9.5px;
                color: var(--text-muted);
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }

            .report-footer {
                margin-top: 16px;
                padding-top: 8px;
                border-top: 1px solid var(--border);
                font-size: 9px;
                color: var(--text-muted);
                text-align: center;
            }

            .export-notice {
                border: 1px solid var(--warning-text);
                background: var(--warning-bg);
                color: var(--warning-text);
                border-radius: 8px;
                padding: 8px 12px;
                margin-bottom: 14px;
                font-size: 9.5px;
                line-height: 1.5;
            }
            .receiver-signature {
                width: 46%;
                margin: 26px 0 8px auto;
                break-inside: avoid;
                page-break-inside: avoid;
                text-align: center;
            }
            .receiver-signature .signature-line {
                margin-top: 0;
                min-height: 30px;
            }
            @media print {
                @page { size: A4 portrait; margin: 14mm 12mm; }
                body { background: #fff; }
                .screen-toolbar { display: none !important; }
                .document { max-width: none; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
                .export-notice { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="screen-toolbar">
            <p>Use the print dialog to print this report or choose <strong>Save as PDF</strong>.</p>
            <div class="print-status">
                <?php if ($print_limit_enabled): ?>
                    <span id="printRemaining"><?= (int) $prints_remaining ?> of <?= (int) $print_limit ?> actions remaining</span><br>
                <?php endif; ?>
                <?php if ($view_limit_enabled): ?>
                    <span><?= (int) $views_remaining ?> of <?= (int) $view_limit ?> views remaining</span><br>
                <?php endif; ?>
                <button id="printButton" class="print-button" type="button">Print / Save as PDF</button>
            </div>
        </div>
        <main class="document">

        <!-- ==================== HEADER ==================== -->
        <div class="report-header">
            <img class="brand-mark" src="/IMS/assets/img/logo/LPO%20Emblem.png" alt="LPO emblem">
            <div class="report-heading">
                <div class="report-title">STOCK TRANSFER REPORT</div>
                <div class="report-sub">
                Transfer #<?= htmlspecialchars($first['transfer_id']) ?>
                &middot; Generated <?= htmlspecialchars($generated_at) ?>
                </div>
            </div>
        </div>

        <?php if (!empty($print_limit_enabled)): ?>
            <div class="export-notice">
                <strong><?= (int) $prints_remaining ?> print/save action<?= $prints_remaining === 1 ? '' : 's' ?> remaining.</strong>
                Printing and choosing “Save as PDF” both use the same temporary limit of <?= (int) $print_limit ?> actions.
            </div>
        <?php endif; ?>

        <!-- ==================== TRANSFER SUMMARY ==================== -->
        <table class="meta-grid">
            <tr>
                <td class="meta-label" style="width:22%;">Transfer ID</td>
                <td class="meta-value" style="width:28%;"><?= htmlspecialchars($first['transfer_id']) ?></td>
                <td class="meta-label" style="width:22%;">Transfer Status</td>
                <td class="meta-value" style="width:28%;">
                    <span class="badge <?= $status_badge['class'] ?>"><?= htmlspecialchars($status_badge['label']) ?></span>
                </td>
            </tr>
            <tr>
                <td class="meta-label">Date Dispatched</td>
                <td class="meta-value"><?= htmlspecialchars($date_dispatched) ?></td>
                <td class="meta-label">Date Received</td>
                <td class="meta-value"><?= htmlspecialchars($date_received) ?></td>
            </tr>
            <tr>
                <td class="meta-label">Origin Warehouse</td>
                <td class="meta-value"><?= htmlspecialchars($first['sender_warehouse'] ?? 'N/A') ?></td>
                <td class="meta-label">Destination Warehouse</td>
                <td class="meta-value"><?= htmlspecialchars($first['receiver_warehouse'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td class="meta-label">Dispatched By</td>
                <td class="meta-value"><?= htmlspecialchars($first['dispatched_by'] ?: 'N/A') ?></td>
                <td class="meta-label">Received By</td>
                <td class="meta-value"><?= htmlspecialchars($first['received_by'] ?: 'N/A') ?></td>
            </tr>
        </table>

        <?php if (!empty($first['remarks_sender']) || !empty($first['remarks_receiver'])): ?>
            <div class="remarks-box">
                <table style="width:100%;">
                    <tr>
                        <td style="width:50%; vertical-align:top; padding-right:10px;">
                            <span class="meta-label">Sender Remarks</span>
                            <?= nl2br(htmlspecialchars($first['remarks_sender'] ?: '—')) ?>
                        </td>
                        <td style="width:50%; vertical-align:top;">
                            <span class="meta-label">Receiver Remarks</span>
                            <?= nl2br(htmlspecialchars($first['remarks_receiver'] ?: '—')) ?>
                        </td>
                    </tr>
                </table>
            </div>
        <?php endif; ?>

        <!-- ==================== ITEM DETAILS ==================== -->
        <div class="section-title">Item Details</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="num">#</th>
                    <th>Status</th>
                    <th>Item Barcode</th>
                    <th>Product Description</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Current Location</th>
                    <th>Latest Audit</th>
                    <th>Audit Date</th>
                    <th class="text-end">Capital</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audit_capable_rows as $i => $row): ?>
                    <?php $row_status = statusBadge($row['status']); ?>
                    <tr>
                        <td class="num"><?= $i + 1 ?></td>
                        <td><span class="badge <?= $row_status['class'] ?>"><?= htmlspecialchars($row_status['label']) ?></span></td>
                        <td><span class="code"><?= htmlspecialchars($row['unique_barcode']) ?></span></td>
                        <td><?= htmlspecialchars($row['product_description'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['brand_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['category_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['current_location'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['audit_status'] ?? 'Not Audited') ?></td>
                        <td><?= htmlspecialchars(formatReportDate($row['audit_date'])) ?></td>
                        <td class="text-end">&#8369;<?= number_format($row['capital'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="9">TOTAL</td>
                    <td class="text-end">&#8369;<?= number_format($total_capital, 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="receiver-signature" aria-label="Receiver signature">
            <div class="signature-line"></div>
            <div class="signature-role">Received By</div>
        </div>

        <!-- ==================== PRODUCT SUMMARY (own page) ==================== -->
        <div class="page-break"></div>

        <div class="report-header">
            <img class="brand-mark" src="/IMS/assets/img/logo/LPO%20Emblem.png" alt="LPO emblem">
            <div class="report-heading">
                <div class="report-title">PRODUCT SUMMARY</div>
                <div class="report-sub">
                Transfer #<?= htmlspecialchars($first['transfer_id']) ?>
                &middot; Items grouped by product
                </div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Product Description</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Parent Barcode</th>
                    <th>Sequences</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Avg. Unit Cost</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($product_summary as $summary): ?>
                    <?php
                        $sequence_text = '(' . formatSequences($summary['sequences']) . ')';
                        $unit_cost = $summary['qty'] > 0 ? $summary['capital'] / $summary['qty'] : 0;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($summary['product_description'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($summary['brand_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($summary['category_name'] ?? 'N/A') ?></td>
                        <td><span class="code"><?= htmlspecialchars($summary['parent_barcode']) ?></span></td>
                        <td><?= htmlspecialchars($sequence_text) ?></td>
                        <td class="text-end"><?= number_format($summary['qty']) ?></td>
                        <td class="text-end">&#8369;<?= number_format($unit_cost, 2) ?></td>
                        <td class="text-end">&#8369;<?= number_format($summary['capital'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="5">GRAND TOTAL</td>
                    <td class="text-end"><?= number_format($total_qty) ?></td>
                    <td></td>
                    <td class="text-end">&#8369;<?= number_format($total_capital, 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="receiver-signature" aria-label="Receiver signature">
            <div class="signature-line"></div>
            <div class="signature-role">Received By</div>
        </div>

        <!-- ==================== SIGNATURES ==================== -->
        <!-- <table class="signature-grid">
            <tr>
                <td>
                    <div class="signature-line">
                        <div class="signature-name"><?= htmlspecialchars($first['dispatched_by'] ?: 'N/A') ?></div>
                        <div class="signature-role">Dispatched By</div>
                    </div>
                </td>
                <td>
                    <div class="signature-line">
                        <div class="signature-name"><?= htmlspecialchars($first['received_by'] ?: 'N/A') ?></div>
                        <div class="signature-role">Received By</div>
                    </div>
                </td>
            </tr>
        </table> -->

        <div class="report-footer">
            Stock Transfer Report &middot; Ref # <?= htmlspecialchars($first['transfer_id']) ?>
            &middot; Generated <?= htmlspecialchars($generated_at) ?>
            by <?= htmlspecialchars($generated_by) ?><?= $generated_position ? ' (' . htmlspecialchars($generated_position) . ')' : '' ?>
        </div>

        </main>
        <script>
            let registeredForCurrentPrint = false;

            function registerUnmanagedPrint() {
                if (registeredForCurrentPrint) {
                    registeredForCurrentPrint = false;
                    return;
                }
                const data = new FormData();
                data.append('action', 'register_print');
                navigator.sendBeacon(window.location.href, data);
            }

            window.addEventListener('beforeprint', registerUnmanagedPrint);

            document.getElementById('printButton').addEventListener('click', async function () {
                const button = this;
                button.disabled = true;
                button.textContent = 'Preparing print…';
                try {
                    const formData = new FormData();
                    formData.append('action', 'register_print');
                    const response = await fetch(window.location.href, { method: 'POST', body: formData, credentials: 'same-origin' });
                    const result = await response.json();
                    if (!response.ok || !result.ok) throw new Error(result.message || 'Unable to register this print action.');
                    const remaining = document.getElementById('printRemaining');
                    if (remaining && result.remaining !== null) remaining.textContent = result.remaining + ' of <?= (int) $print_limit ?> actions remaining';
                    registeredForCurrentPrint = true;
                    window.print();
                } catch (error) {
                    window.alert(error.message);
                } finally {
                    button.disabled = false;
                    button.textContent = 'Print / Save as PDF';
                }
            });
        </script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/*
|--------------------------------------------------------------------------
| Date Formatting
|--------------------------------------------------------------------------
*/

$date_dispatched = formatReportDate($first['date_out']);
$date_received   = formatReportDate($first['date_received']);

/*
|--------------------------------------------------------------------------
| Group Products (fixes the original CSV's "Unit Cost" bug, which
| duplicated the Subtotal value instead of an actual per-unit cost --
| avg. unit cost is now computed as the group's capital / qty)
|--------------------------------------------------------------------------
*/

$product_summary = [];

foreach ($rows as $row) {

    $group_key =
        $row['product_description'] . '|' .
        $row['brand_name'] . '|' .
        $row['category_name'] . '|' .
        $row['parent_barcode'];

    if (!isset($product_summary[$group_key])) {
        $product_summary[$group_key] = [
            'product_description' => $row['product_description'],
            'brand_name'          => $row['brand_name'],
            'category_name'       => $row['category_name'],
            'parent_barcode'      => $row['parent_barcode'],
            'sequences'           => [],
            'qty'                 => 0,
            'capital'             => 0,
        ];
    }

    $product_summary[$group_key]['sequences'][] = $row['sequence_number'];
    $product_summary[$group_key]['qty'] += $row['qty'];
    $product_summary[$group_key]['capital'] += $row['capital'];
}

/* Render the browser-based, print-ready report. */

$html = renderTransferReportHtml([
    'rows'                     => $rows,
    'first'                    => $first,
    'total_qty'                => $total_qty,
    'total_capital'            => $total_capital,
    'product_summary'          => $product_summary,
    'date_dispatched'          => $date_dispatched,
    'date_received'            => $date_received,
    'generated_at'             => date('F j, Y g:i A'),
    'generated_by'             => $user_fullname ?? 'N/A',
    'generated_position'       => $user_position_name ?? '',
    'print_limit_enabled'      => $PRINT_LIMIT_ENABLED,
    'prints_remaining'         => $prints_remaining,
    'print_limit'              => $PRINT_LIMIT,
    'view_limit_enabled'       => $VIEW_LIMIT_ENABLED,
    'views_remaining'          => $views_remaining,
    'view_limit'               => $VIEW_LIMIT,
]);
header('Content-Type: text/html; charset=utf-8');
echo $html;
exit();
