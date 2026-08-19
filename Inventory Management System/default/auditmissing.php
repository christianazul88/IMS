<?php
/**
 * Barcode Inventory Movement Tracker
 *
 * CSV:
 *   - Barcode is expected in column[2]
 *
 * Output adds:
 *   - Current Warehouse
 *   - Transfer Count
 *   - Last Transfer From
 *   - Last Transfer To
 *   - Last Transfer Date
 *   - Transfer History
 *
 * Database relationships:
 *   stocks.unique_barcode
 *   stock_transfer_content.unique_barcode
 *   stock_transfer_content.st_id -> stock_transfer.id
 *   stock_transfer.from_warehouse -> warehouse.hashed_id
 *   stock_transfer.to_warehouse   -> warehouse.hashed_id
 */

ini_set('memory_limit', '1024M');
set_time_limit(0);

/* =========================================================
   DATABASE CONFIGURATION
   ========================================================= */

$dbHost = "localhost";
$dbUser = "root";
$dbPassword = "";
$dbName = "lpo_db";

$conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/* =========================================================
   SETTINGS
   ========================================================= */

/*
 * Number of unique barcodes processed in one database batch.
 * 500 is a safe starting point for MySQL/MariaDB.
 */
$batchSize = 500;

/*
 * Change these labels if your preferred output wording differs.
 */
$NOT_FOUND = "NOT FOUND";
$NO_HISTORY = "NO TRANSFER HISTORY";


/* =========================================================
   HELPERS
   ========================================================= */

function makePlaceholders(int $count): string
{
    return implode(',', array_fill(0, $count, '?'));
}

/**
 * Bind an arbitrary number of string parameters.
 */
function bindStringParams(mysqli_stmt $stmt, array $values): void
{
    if (!$values) {
        return;
    }

    $types = str_repeat('s', count($values));
    $params = [$types];

    foreach ($values as $key => $value) {
        $params[] = $value;
    }

    $refs = [];

    foreach ($params as $key => &$value) {
        $refs[$key] = &$value;
    }

    call_user_func_array([$stmt, 'bind_param'], $refs);
}

/**
 * Safely format a date for CSV output.
 */
function formatTransferDate(?string $date): string
{
    if (!$date) {
        return "";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date("Y-m-d H:i:s", $timestamp);
}


/* =========================================================
   PROCESS CSV
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['csv_file'])) {
        die("No CSV file uploaded.");
    }

    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        die("Upload failed. Error code: " . $_FILES['csv_file']['error']);
    }

    $uploadedFile = $_FILES['csv_file']['tmp_name'];

    $handle = fopen($uploadedFile, "r");

    if (!$handle) {
        die("Unable to open uploaded CSV.");
    }

    /*
     * Detect UTF-8 BOM if present.
     */
    $firstBytes = fread($handle, 3);

    if ($firstBytes !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    /*
     * Read header.
     */
    $header = fgetcsv($handle);

    if ($header === false) {
        fclose($handle);
        die("The CSV file is empty.");
    }

    /*
     * Make sure column[2] exists.
     */
    if (!array_key_exists(2, $header)) {
        fclose($handle);
        die("The uploaded CSV does not contain column[2].");
    }


    /* =====================================================
       CREATE OUTPUT FILE
       ===================================================== */

    $outputFile = tempnam(sys_get_temp_dir(), "barcode_movement_");

    $output = fopen($outputFile, "w");

    if (!$output) {
        fclose($handle);
        die("Unable to create output CSV.");
    }

    /*
     * Preserve the original columns and append our columns.
     */
    $header[] = "Current Warehouse";
    $header[] = "Transfer Count";
    $header[] = "Last Transfer From";
    $header[] = "Last Transfer To";
    $header[] = "Last Transfer Date";
    $header[] = "Transfer History";

    fputcsv($output, $header);


    /* =====================================================
       PREPARE ROW STORAGE
       ===================================================== */

    /*
     * We process CSV rows in batches.
     *
     * Each row keeps:
     *   original row
     *   normalized barcode
     */
    $rows = [];

    /*
     * Keeps unique barcodes in current batch.
     */
    $batchBarcodes = [];

    /*
     * Statistics.
     */
    $totalRows = 0;
    $foundRows = 0;
    $notFoundRows = 0;
    $historyRows = 0;


    /* =====================================================
       PROCESS ONE BATCH
       ===================================================== */

    $processBatch = function (
        array $rows,
        array $batchBarcodes
    ) use (
        $conn,
        $output,
        &$foundRows,
        &$notFoundRows,
        &$historyRows,
        $NOT_FOUND,
        $NO_HISTORY
    ) {

        if (empty($rows)) {
            return;
        }

        /*
         * -------------------------------------------------
         * 1. CURRENT WAREHOUSE LOOKUP
         * -------------------------------------------------
         *
         * One query for the entire batch.
         */
        $currentWarehouses = [];

        if (!empty($batchBarcodes)) {

            $placeholders = makePlaceholders(count($batchBarcodes));

            $sql = "
                SELECT
                    s.unique_barcode,
                    w.warehouse_name
                FROM stocks s
                LEFT JOIN warehouse w
                    ON s.warehouse = w.hashed_id
                WHERE s.unique_barcode IN ($placeholders)
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception(
                    "Current warehouse query failed: " . $conn->error
                );
            }

            bindStringParams($stmt, $batchBarcodes);

            if (!$stmt->execute()) {
                throw new Exception(
                    "Current warehouse query execution failed: " .
                    $stmt->error
                );
            }

            $result = $stmt->get_result();

            while ($data = $result->fetch_assoc()) {

                $barcode = trim((string)$data['unique_barcode']);

                /*
                 * If duplicate stock rows exist, keep the first
                 * non-empty warehouse name.
                 */
                if (
                    !isset($currentWarehouses[$barcode]) ||
                    empty($currentWarehouses[$barcode])
                ) {
                    $currentWarehouses[$barcode] =
                        !empty($data['warehouse_name'])
                            ? $data['warehouse_name']
                            : $NOT_FOUND;
                }
            }

            $stmt->close();
        }


        /*
         * -------------------------------------------------
         * 2. TRANSFER HISTORY LOOKUP
         * -------------------------------------------------
         *
         * One query for the entire batch.
         *
         * We use DISTINCT because a barcode may potentially
         * occur more than once inside the same transfer.
         */
        $histories = [];

        if (!empty($batchBarcodes)) {

            $placeholders = makePlaceholders(count($batchBarcodes));

            $sql = "
                SELECT DISTINCT
                    stc.unique_barcode,
                    st.id AS transfer_id,
                    st.from_warehouse,
                    st.to_warehouse,
                    wf.warehouse_name AS from_warehouse_name,
                    wt.warehouse_name AS to_warehouse_name,
                    st.status,
                    st.date_out,
                    st.date_received
                FROM stock_transfer_content stc
                INNER JOIN stock_transfer st
                    ON stc.st_id = st.id
                LEFT JOIN warehouse wf
                    ON st.from_warehouse = wf.hashed_id
                LEFT JOIN warehouse wt
                    ON st.to_warehouse = wt.hashed_id
                WHERE stc.unique_barcode IN ($placeholders)
                ORDER BY
                    stc.unique_barcode ASC,
                    COALESCE(st.date_out, st.date_received) ASC,
                    st.id ASC
            ";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                throw new Exception(
                    "Transfer history query failed: " . $conn->error
                );
            }

            bindStringParams($stmt, $batchBarcodes);

            if (!$stmt->execute()) {
                throw new Exception(
                    "Transfer history query execution failed: " .
                    $stmt->error
                );
            }

            $result = $stmt->get_result();

            while ($data = $result->fetch_assoc()) {

                $barcode = trim((string)$data['unique_barcode']);

                if (!isset($histories[$barcode])) {
                    $histories[$barcode] = [];
                }

                $histories[$barcode][] = $data;
            }

            $stmt->close();
        }


        /*
         * -------------------------------------------------
         * 3. BUILD OUTPUT ROWS
         * -------------------------------------------------
         */

        foreach ($rows as $item) {

            $row = $item['row'];
            $barcode = $item['barcode'];

            /*
             * Current warehouse.
             */
            if (
                isset($currentWarehouses[$barcode]) &&
                $currentWarehouses[$barcode] !== ''
            ) {
                $currentWarehouse = $currentWarehouses[$barcode];
                $foundRows++;
            } else {
                $currentWarehouse = $NOT_FOUND;
                $notFoundRows++;
            }


            /*
             * Transfer history.
             */
            $history = $histories[$barcode] ?? [];

            $transferCount = count($history);

            if ($transferCount > 0) {
                $historyRows++;
            }


            $lastTransferFrom = "";
            $lastTransferTo = "";
            $lastTransferDate = "";
            $historyText = "";


            if ($transferCount > 0) {

                /*
                 * -------------------------------------------------
                 * Build movement history.
                 * -------------------------------------------------
                 *
                 * Example:
                 *
                 * Warehouse A -> Warehouse B
                 * Warehouse B -> Warehouse C
                 * Warehouse C -> Warehouse A
                 */
                $historyParts = [];

                foreach ($history as $transfer) {

                    $from = trim(
                        (string)($transfer['from_warehouse_name'] ?? '')
                    );

                    $to = trim(
                        (string)($transfer['to_warehouse_name'] ?? '')
                    );

                    /*
                     * Fallback when warehouse names cannot be resolved.
                     */
                    if ($from === '') {
                        $from = !empty($transfer['from_warehouse'])
                            ? $transfer['from_warehouse']
                            : "UNKNOWN";
                    }

                    if ($to === '') {
                        $to = !empty($transfer['to_warehouse'])
                            ? $transfer['to_warehouse']
                            : "UNKNOWN";
                    }

                    $date =
                        $transfer['date_out']
                        ?: $transfer['date_received']
                        ?: "";

                    $dateText = formatTransferDate($date);

                    if ($dateText !== '') {
                        $historyParts[] =
                            $from . " -> " . $to . " (" . $dateText . ")";
                    } else {
                        $historyParts[] =
                            $from . " -> " . $to;
                    }

                    /*
                     * The query is ordered chronologically,
                     * so each later transfer replaces the previous
                     * last-transfer information.
                     */
                    $lastTransferFrom = $from;
                    $lastTransferTo = $to;
                    $lastTransferDate = $dateText;
                }

                $historyText = implode(" | ", $historyParts);

            } else {

                $historyText = $NO_HISTORY;
            }


            /*
             * Append our new columns.
             */
            $row[] = $currentWarehouse;
            $row[] = $transferCount;
            $row[] = $lastTransferFrom;
            $row[] = $lastTransferTo;
            $row[] = $lastTransferDate;
            $row[] = $historyText;

            fputcsv($output, $row);
        }
    };


    /* =====================================================
       READ CSV ROWS
       ===================================================== */

    try {

        while (($row = fgetcsv($handle)) !== false) {

            $totalRows++;

            /*
             * Skip completely empty rows.
             */
            $isEmpty = true;

            foreach ($row as $value) {
                if (trim((string)$value) !== '') {
                    $isEmpty = false;
                    break;
                }
            }

            if ($isEmpty) {
                continue;
            }


            /*
             * Barcode is column[2].
             */
            $barcode = isset($row[2])
                ? trim((string)$row[2])
                : "";


            /*
             * Keep the row even if barcode is missing.
             */
            if ($barcode === '') {

                $row[] = "NO BARCODE";
                $row[] = 0;
                $row[] = "";
                $row[] = "";
                $row[] = "";
                $row[] = "NO BARCODE";

                fputcsv($output, $row);

                $notFoundRows++;

                continue;
            }


            /*
             * Store row.
             */
            $rows[] = [
                'row' => $row,
                'barcode' => $barcode
            ];


            /*
             * Only store each barcode once for database lookup.
             */
            $batchBarcodes[$barcode] = $barcode;


            /*
             * Process batch when it reaches the limit.
             */
            if (count($batchBarcodes) >= $batchSize) {

                $processBatch(
                    $rows,
                    array_values($batchBarcodes)
                );

                $rows = [];
                $batchBarcodes = [];
            }
        }


        /*
         * Process remaining rows.
         */
        if (!empty($rows)) {

            $processBatch(
                $rows,
                array_values($batchBarcodes)
            );
        }


    } catch (Throwable $e) {

        fclose($handle);
        fclose($output);

        if (file_exists($outputFile)) {
            unlink($outputFile);
        }

        die(
            "Processing failed: " .
            htmlspecialchars($e->getMessage())
        );
    }


    fclose($handle);
    fclose($output);


    /* =====================================================
       DOWNLOAD
       ===================================================== */

    $downloadName =
        "barcode-movement-" .
        date("Y-m-d-H-i-s") .
        ".csv";


    header("Content-Type: text/csv; charset=utf-8");

    header(
        'Content-Disposition: attachment; filename="' .
        $downloadName .
        '"'
    );

    header("Content-Length: " . filesize($outputFile));

    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    readfile($outputFile);

    unlink($outputFile);

    $conn->close();

    exit;
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Barcode Movement Tracker</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fa;
        }

        .upload-card {
            max-width: 760px;
            margin: 70px auto;
        }

        .card {
            border: 0;
            border-radius: 16px;
        }

        .icon {
            font-size: 42px;
        }

        .feature {
            border-radius: 10px;
            background: #f8f9fa;
            padding: 12px 15px;
            margin-bottom: 8px;
        }

        #processing {
            display: none;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="upload-card">

        <div class="card shadow-sm">

            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">

                    <div class="icon mb-2">
                        📦
                    </div>

                    <h3 class="fw-bold">
                        Barcode Movement Tracker
                    </h3>

                    <p class="text-muted mb-0">
                        Upload your CSV and trace the warehouse
                        movement of every barcode.
                    </p>

                </div>


                <div class="mb-4">

                    <div class="feature">
                        <strong>Column[2]</strong>
                        <br>
                        Barcode used for all lookups
                    </div>

                    <div class="feature">
                        <strong>Current Warehouse</strong>
                        <br>
                        Retrieved from the current stock record
                    </div>

                    <div class="feature">
                        <strong>Transfer History</strong>
                        <br>
                        Shows every recorded warehouse transfer
                    </div>

                    <div class="feature">
                        <strong>Missing Barcodes</strong>
                        <br>
                        Displays <code>NOT FOUND</code> when no stock
                        record exists
                    </div>

                </div>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                    id="uploadForm"
                >

                    <div class="mb-3">

                        <label
                            for="csv_file"
                            class="form-label fw-semibold"
                        >
                            Select CSV File
                        </label>

                        <input
                            type="file"
                            name="csv_file"
                            id="csv_file"
                            class="form-control form-control-lg"
                            accept=".csv,text/csv"
                            required
                        >

                        <div class="form-text">
                            The barcode must be located in column[2].
                        </div>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary btn-lg w-100"
                        id="processButton"
                    >
                        Process CSV
                    </button>

                </form>


                <div
                    id="processing"
                    class="alert alert-info mt-4 mb-0"
                >
                    <div class="fw-semibold mb-1">
                        Processing CSV...
                    </div>

                    <div class="small">
                        Please wait. Large files may take some time
                        to process.
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>


<script>

document.getElementById("uploadForm").addEventListener(
    "submit",
    function () {

        document.getElementById("processButton").disabled = true;

        document.getElementById("processButton").innerHTML =
            "Processing... Please wait";

        document.getElementById("processing").style.display = "block";
    }
);

</script>

</body>

</html>