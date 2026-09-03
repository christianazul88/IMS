<?php
include "../config/database.php";
include "../config/on_session.php";

/*
|--------------------------------------------------------------------------
| TEMPORARY CSV EXPORT LIMIT
|--------------------------------------------------------------------------
| Set to false after the feature becomes paid/unlimited.
|
| TRUE  = maximum of CSV exports defined by $CSV_EXPORT_LIMIT, tracked in csv_download_limit.json
| FALSE = unlimited exports; tracker is not created/updated.
|--------------------------------------------------------------------------
*/
$CSV_EXPORT_LIMIT_ENABLED = true;
$CSV_EXPORT_LIMIT = 8;
$CSV_EXPORT_TRACKER = __DIR__ . '/csv_download_limit.json';

if(isset($_GET['transfer_id']) && !empty($_GET['transfer_id'])) {
    $transfer_id = $_GET['transfer_id'];
    $filename_id = $transfer_id + 10000; // Add 10000 to the transfer ID for the filename

    

    /*
    |--------------------------------------------------------------------------
    | Main Query
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

        $capital = (float)($row['capital'] ?? 0);

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
            'status'              => $row['status'],

            'date_out'            => $row['date_out'],
            'date_received'       => $row['date_received'],

            'remarks_sender'      => $row['remarks_sender'],
            'remarks_receiver'    => $row['remarks_receiver'],

            'unique_barcode'      => $row['unique_barcode'],
            'parent_barcode'      => $parent_barcode,
            'sequence_number'     => (int)$sequence_number,

            'product_description' => $row['product_description'],
            'brand_name'          => $row['brand_name'],
            'category_name'       => $row['category_name'],

            'sender_warehouse'    => $row['sender_warehouse'],
            'receiver_warehouse'  => $row['receiver_warehouse'],
            'current_location'    => $row['current_location'],

            'dispatched_by'       => trim($row['dispatched_by']),
            'received_by'         => $received_by,

            'audit_status'        => $row['audit_status'],
            'audit_date'          => $row['audit_date'] ?? 'N/A',

            'capital'             => $capital,
            'qty'                 => $qty
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
    | Register CSV Export
    |--------------------------------------------------------------------------
    | This is intentionally placed AFTER the no-records check so a failed
    |/empty report does not consume one of the 10 available downloads.
    |--------------------------------------------------------------------------
    */
    $downloads_remaining = null;

    if ($CSV_EXPORT_LIMIT_ENABLED) {
    function registerCsvDownload($tracker_file, $limit)
    {
        /*
        * Open/create the JSON tracker.
        * __DIR__ ensures this is the folder containing this PHP file.
        */
        $fp = fopen($tracker_file, 'c+');

        if ($fp === false) {
            http_response_code(500);
            die(
                "Unable to create the CSV download tracker. " .
                "Please make sure this folder is writable by PHP."
            );
        }

        // Prevent simultaneous exports from using the same count.
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            http_response_code(500);
            die("Unable to access the CSV download tracker. Please try again.");
        }

        rewind($fp);
        $contents = stream_get_contents($fp);
        $tracker = json_decode($contents, true);

        if (!is_array($tracker)) {
            $tracker = [
                'download_count' => 0,
                'download_limit' => $limit,
                'created_at' => date('c')
            ];
        }

        $download_count = (int)($tracker['download_count'] ?? 0);

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
            $safe_limit = (int)$limit;

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
                        You have reached the maximum number of CSV downloads currently
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
            die("Unable to update the CSV download tracker.");
        }

        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $download_count;
    }


        $download_count = registerCsvDownload(
            $CSV_EXPORT_TRACKER,
            $CSV_EXPORT_LIMIT
        );

        $downloads_remaining = $CSV_EXPORT_LIMIT - $download_count;
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
        ".csv";


    /*
    |--------------------------------------------------------------------------
    | CSV Headers
    |--------------------------------------------------------------------------
    */

    header('Content-Type: text/csv; charset=utf-8');

    header(
        'Content-Disposition: attachment; filename="' .
        $filename .
        '"'
    );

    $output = fopen('php://output', 'w');


    /*
    |--------------------------------------------------------------------------
    | UTF-8 BOM
    |--------------------------------------------------------------------------
    */

    fputs(
        $output,
        chr(0xEF) . chr(0xBB) . chr(0xBF)
    );


    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    function csvRow($output, $data)
    {
        fputcsv($output, $data);
    }

    /*
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
                ? (string)$start
                : $start . '-' . $previous;

            $start = $current;
            $previous = $current;
        }

        $ranges[] = ($start === $previous)
            ? (string)$start
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


    /*
    |--------------------------------------------------------------------------
    | Date Formatting
    |--------------------------------------------------------------------------
    */

    $date_dispatched = !empty($first['date_out'])
        ? date('n/j/Y H:i', strtotime($first['date_out']))
        : '';

    $date_received = !empty($first['date_received'])
        ? date('n/j/Y H:i', strtotime($first['date_received']))
        : '';


    /*
    |--------------------------------------------------------------------------
    | =========================================================================
    | TRANSFER SUMMARY
    | =========================================================================
    |--------------------------------------------------------------------------
    |
    | Column layout:
    |
    | A = Label
    | B = Value
    | C = blank
    | D = Label
    | E = Value
    |
    |--------------------------------------------------------------------------
    */


    csvRow($output, [
        'STOCK TRANSFER REPORT',
        '',
        '',
        '',
        ''
    ]);

    if ($CSV_EXPORT_LIMIT_ENABLED) {
        csvRow($output, [
            'CSV EXPORT USAGE',
            $downloads_remaining . ' download' .
            ($downloads_remaining === 1 ? '' : 's') . ' remaining',
            '',
            '',
            'Notice: This export feature includes a temporary limit of ' .
            $CSV_EXPORT_LIMIT .
            ' downloads. To permanently unlock unlimited access, a one-time fee of ₱1,800 to ₱2,500 (depending on requested custom fields) is required. Once payment is settled, full access will be activated immediately. Thank you for your understanding.'
        ]);
    }


    csvRow($output, [
        ''
    ]);

    csvRow($output, [
        'Transfer ID',
        $first['transfer_id'],
        '',
        'Dispatched By',
        $first['dispatched_by']
    ]);

    csvRow($output, [
        'Date Dispatched',
        $date_dispatched,
        '',
        'Received By',
        $first['received_by']
    ]);

    csvRow($output, [
        'Date Received',
        $date_received,
        '',
        'Transfer Status',
        strtoupper($first['status'])
    ]);

    csvRow($output, [
        'Origin Warehouse (From)',
        $first['sender_warehouse'],
        '',
        'Destination Warehouse (To)',
        $first['receiver_warehouse']
    ]);

    csvRow($output, [
        'Sender Remarks',
        $first['remarks_sender'],
        '',
        'Receiver Remarks',
        $first['remarks_receiver']
    ]);


    /*
    |--------------------------------------------------------------------------
    | Spacer
    |--------------------------------------------------------------------------
    */

    csvRow($output, []);
    csvRow($output, []);


    /*
    |--------------------------------------------------------------------------
    | =========================================================================
    | ITEM DETAILS
    | =========================================================================
    |--------------------------------------------------------------------------
    */

    csvRow($output, [
        'ITEM DETAILS'
    ]);

    csvRow($output, [
        'Transfer Status',
        'Item Barcode',
        'Product Description',
        'Brand',
        'Category',
        'Current Location',
        'Latest Audit Action',
        'Audit Date',
        'Capital'
    ]);


    /*
    |--------------------------------------------------------------------------
    | Item Rows
    |--------------------------------------------------------------------------
    */

    foreach ($rows as $row) {

        csvRow($output, [
            $row['status'],
            $row['unique_barcode'],
            $row['product_description'],
            $row['brand_name'],
            $row['category_name'],
            $row['current_location'],
            $row['audit_status'],
            $row['audit_date'],
            number_format($row['capital'], 2, '.', '')
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ITEM TOTALS
    |--------------------------------------------------------------------------
    |
    | Put totals at the RIGHT SIDE of the item table.
    |
    |--------------------------------------------------------------------------
    */

    csvRow($output, []);

    csvRow($output, [
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        'TOTAL ITEMS',
        $total_qty
    ]);

    csvRow($output, [
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        'TOTAL CAPITAL',
        number_format($total_capital, 2, '.', '')
    ]);


    /*
    |--------------------------------------------------------------------------
    | Spacer
    |--------------------------------------------------------------------------
    */

    csvRow($output, []);
    csvRow($output, []);
    csvRow($output, []);

    csvRow($output, [
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        'Received By',
        $first['received_by']
    ]);

    csvRow($output, [
        '',
        '',
        '',
        '',
        '',
        '',
        '',
        'Transfer ID',
        $first['transfer_id']
    ]);


    /*
    |--------------------------------------------------------------------------
    | =========================================================================
    | PRODUCT SUMMARY
    | =========================================================================
    |--------------------------------------------------------------------------
    */

    csvRow($output, [
        'PRODUCT SUMMARY'
    ]);

    csvRow($output, [
        'Product Description',
        'Brand',
        'Category',
        'Parent Barcode',
        'Sequence',
        'Qty',
        'Capital',
        'Subtotal'
    ]);


    /*
    |--------------------------------------------------------------------------
    | Group Products
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
                'capital'             => 0
            ];
        }

        $product_summary[$group_key]['sequences'][] =
            $row['sequence_number'];

        $product_summary[$group_key]['qty'] +=
            $row['qty'];

        $product_summary[$group_key]['capital'] +=
            $row['capital'];
    }


    /*
    |--------------------------------------------------------------------------
    | Product Summary Rows
    |--------------------------------------------------------------------------
    */

    foreach ($product_summary as $summary) {

        $sequence_text =
            '(' . formatSequences($summary['sequences']) . ')';

        csvRow($output, [
            $summary['product_description'],
            $summary['brand_name'],
            $summary['category_name'],
            $summary['parent_barcode'],
            $sequence_text,
            $summary['qty'],
            number_format(
                $summary['capital'],
                2,
                '.',
                ''
            ),
            number_format(
                $summary['capital'],
                2,
                '.',
                ''
            )
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | =========================================================================
    | GRAND TOTAL
    | =========================================================================
    |--------------------------------------------------------------------------
    |
    | Grand total is placed on the RIGHT side of the Product Summary.
    |
    |--------------------------------------------------------------------------
    */

    csvRow($output, []);

    csvRow($output, [
        '',
        '',
        '',
        '',
        'GRAND TOTAL',
        $total_qty,
        '',
        number_format(
            $total_capital,
            2,
            '.',
            ''
        )
    ]);


    /*
    |--------------------------------------------------------------------------
    | Spacer
    |--------------------------------------------------------------------------
    */

    csvRow($output, []);
    csvRow($output, []);
    csvRow($output, []);


    /*
    |--------------------------------------------------------------------------
    | =========================================================================
    | FINAL RECEIVED CONFIRMATION
    | =========================================================================
    |--------------------------------------------------------------------------
    */

    csvRow($output, [
        '',
        '',
        '',
        '',
        '',
        '',
        'Received By',
        $first['received_by']
    ]);

    csvRow($output, [
        '',
        '',
        '',
        '',
        '',
        '',
        'Transfer ID',
        $first['transfer_id']
    ]);


    /*
    |--------------------------------------------------------------------------
    | Close
    |--------------------------------------------------------------------------
    */

    fclose($output);

    exit();

} else {
    die("Transfer ID is required.");
}

