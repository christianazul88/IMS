<?php

require_once "../config/database.php";

$cutoff = '2026-01-31 23:59:59';

$sql = "
WITH

/* =========================================================
   1. STOCKS / INBOUND
   ========================================================= */
inbound AS (
    SELECT
        s.unique_barcode,
        MIN(s.date) AS inbound_date
    FROM stocks s
    WHERE s.date <= ?
    GROUP BY s.unique_barcode
),

/* =========================================================
   2. LATEST OUTBOUND
   ========================================================= */
latest_outbound AS (
    SELECT
        x.unique_barcode,
        x.date_sent AS outbound_date
    FROM (
        SELECT
            oc.unique_barcode,
            ol.date_sent,
            ROW_NUMBER() OVER (
                PARTITION BY oc.unique_barcode
                ORDER BY ol.date_sent DESC
            ) AS rn
        FROM outbound_content oc
        INNER JOIN outbound_logs ol
            ON ol.hashed_id = oc.hashed_id
        WHERE ol.date_sent <= ?
    ) x
    WHERE x.rn = 1
),

/* =========================================================
   3. LATEST RETURN
   ========================================================= */
latest_return AS (
    SELECT
        x.unique_barcode,
        x.return_date,
        x.return_warehouse
    FROM (
        SELECT
            r.unique_barcode,
            r.date AS return_date,
            r.warehouse AS return_warehouse,
            ROW_NUMBER() OVER (
                PARTITION BY r.unique_barcode
                ORDER BY r.date DESC
            ) AS rn
        FROM returns r
        WHERE r.date <= ?
    ) x
    WHERE x.rn = 1
),

/* =========================================================
   4. LATEST TRANSFER
   ========================================================= */
latest_transfer AS (
    SELECT
        x.unique_barcode,
        x.date_out,
        x.date_received,
        x.transfer_status,
        x.from_warehouse,
        x.to_warehouse
    FROM (
        SELECT
            stc.unique_barcode,
            st.date_out,
            st.date_received,
            st.status AS transfer_status,
            st.from_warehouse,
            st.to_warehouse,

            ROW_NUMBER() OVER (
                PARTITION BY stc.unique_barcode
                ORDER BY
                    COALESCE(st.date_received, st.date_out) DESC,
                    st.id DESC
            ) AS rn

        FROM stock_transfer_content stc

        INNER JOIN stock_transfer st
            ON st.id = stc.st_id

        WHERE st.date_out <= ?
    ) x
    WHERE x.rn = 1
)

/* =========================================================
   5. FINAL RESULT
   ========================================================= */
SELECT

    i.unique_barcode,

    i.inbound_date,

    lo.outbound_date,

    lr.return_date,

    lr.return_warehouse,

    lt.date_out AS transfer_date_out,

    lt.date_received AS transfer_date_received,

    lt.transfer_status,

    lt.from_warehouse,

    lt.to_warehouse,

    CASE
        WHEN lo.outbound_date IS NULL
            THEN 'AVAILABLE'

        WHEN lr.return_date > lo.outbound_date
            THEN 'AVAILABLE'

        ELSE 'OUTBOUNDED'
    END AS stock_status,

    CASE

        /* Transfer has not been received */
        WHEN lt.date_out IS NOT NULL
             AND (
                 lt.date_received IS NULL
                 OR lt.transfer_status IS NULL
             )
             AND lt.date_out >= COALESCE(
                    lr.return_date,
                    i.inbound_date
                 )
        THEN lt.from_warehouse

        /* Transfer has been received */
        WHEN lt.date_received IS NOT NULL
             AND lt.transfer_status = 'received'
             AND lt.date_received >= COALESCE(
                    lr.return_date,
                    i.inbound_date
                 )
        THEN lt.to_warehouse

        /* Returned item */
        WHEN lr.return_date IS NOT NULL
             AND lr.return_date >= i.inbound_date
        THEN lr.return_warehouse

        ELSE NULL

    END AS warehouse

FROM inbound i

LEFT JOIN latest_outbound lo
    ON lo.unique_barcode = i.unique_barcode

LEFT JOIN latest_return lr
    ON lr.unique_barcode = i.unique_barcode

LEFT JOIN latest_transfer lt
    ON lt.unique_barcode = i.unique_barcode

WHERE
    lo.outbound_date IS NULL
    OR lr.return_date > lo.outbound_date

ORDER BY i.unique_barcode
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

/*
 * Five cutoff parameters:
 *
 * 1. stocks.date
 * 2. outbound_logs.date_sent
 * 3. returns.date
 * 4. stock_transfer.date_out
 *
 * There are actually 4 placeholders.
 */
$stmt->bind_param(
    "ssss",
    $cutoff,
    $cutoff,
    $cutoff,
    $cutoff
);

if (!$stmt->execute()) {
    die("SQL Execute Error: " . $stmt->error);
}

$result = $stmt->get_result();

$totalAvailable = $result->num_rows;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Inventory Snapshot - January 31, 2026</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f6f8;
        }

        .container {
            max-width: 1600px;
        }

        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        .table-responsive {
            max-height: 70vh;
            overflow-y: auto;
        }

        thead th {
            position: sticky;
            top: 0;
            background: #212529 !important;
            color: white;
            z-index: 2;
        }

        .badge-available {
            background: #198754;
        }

        .badge-outbound {
            background: #dc3545;
        }

        .barcode {
            font-family: monospace;
            font-weight: 600;
        }

        .warehouse {
            font-family: monospace;
            font-size: 12px;
        }

    </style>

</head>

<body>

<div class="container-fluid py-4">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Inventory Snapshot
                </h2>

                <div class="text-muted">
                    Historical inventory as of
                    <strong>January 31, 2026 11:59:59 PM</strong>
                </div>

            </div>

            <div class="text-end">

                <div class="text-muted">
                    Available Stocks
                </div>

                <div class="display-6 fw-bold">
                    <?= number_format($totalAvailable) ?>
                </div>

            </div>

        </div>


        <div class="card">

            <div class="card-body">

                <?php if ($totalAvailable === 0): ?>

                    <div class="alert alert-warning mb-0">
                        No available stocks were found as of
                        January 31, 2026.
                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover table-sm align-middle mb-0">

                            <thead>

                                <tr>

                                    <th>#</th>

                                    <th>Unique Barcode</th>

                                    <th>Inbound Date</th>

                                    <th>Last Outbound</th>

                                    <th>Last Return</th>

                                    <th>Return Warehouse</th>

                                    <th>Transfer Out</th>

                                    <th>Transfer Received</th>

                                    <th>Transfer Status</th>

                                    <th>From Warehouse</th>

                                    <th>To Warehouse</th>

                                    <th>Current Warehouse</th>

                                    <th>Status</th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php

                            $counter = 1;

                            while ($row = $result->fetch_assoc()):

                            ?>

                                <tr>

                                    <td>
                                        <?= $counter++ ?>
                                    </td>

                                    <td class="barcode">
                                        <?= htmlspecialchars(
                                            $row['unique_barcode'] ?? ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $row['inbound_date'] ?? '—'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $row['outbound_date'] ?? '—'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $row['return_date'] ?? '—'
                                        ) ?>
                                    </td>

                                    <td class="warehouse">
                                        <?= htmlspecialchars(
                                            $row['return_warehouse'] ?? '—'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $row['transfer_date_out'] ?? '—'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $row['transfer_date_received'] ?? '—'
                                        ) ?>
                                    </td>

                                    <td>

                                        <?php if (
                                            $row['transfer_status'] === 'received'
                                        ): ?>

                                            <span class="badge bg-success">
                                                received
                                            </span>

                                        <?php elseif (
                                            $row['transfer_status'] === null
                                        ): ?>

                                            <span class="badge bg-warning text-dark">
                                                pending
                                            </span>

                                        <?php else: ?>

                                            <?= htmlspecialchars(
                                                $row['transfer_status']
                                            ) ?>

                                        <?php endif; ?>

                                    </td>

                                    <td class="warehouse">
                                        <?= htmlspecialchars(
                                            $row['from_warehouse'] ?? '—'
                                        ) ?>
                                    </td>

                                    <td class="warehouse">
                                        <?= htmlspecialchars(
                                            $row['to_warehouse'] ?? '—'
                                        ) ?>
                                    </td>

                                    <td class="warehouse fw-bold">

                                        <?php if (
                                            !empty($row['warehouse'])
                                        ): ?>

                                            <?= htmlspecialchars(
                                                $row['warehouse']
                                            ) ?>

                                        <?php else: ?>

                                            <span class="text-muted">
                                                Unknown
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <span class="badge badge-available">
                                            AVAILABLE
                                        </span>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>


        <div class="mt-3">

            <div class="alert alert-info">

                <strong>Snapshot logic:</strong>

                A barcode is considered available if it was
                inbounded on or before the cutoff and either
                has no outbound transaction before the cutoff
                or has a return transaction occurring after
                its latest outbound transaction.

            </div>

        </div>

    </div>

</div>

</body>

</html>

<?php

$stmt->close();
$conn->close();

?>