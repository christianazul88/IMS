<?php

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Historical Inventory Snapshot
|--------------------------------------------------------------------------
|
| Snapshot date:
| January 31, 2026 11:59:59 PM
|
| Availability logic:
|
| 1. Stock must have been inbounded by the cutoff.
| 2. If there is no outbound -> AVAILABLE.
| 3. If there is an outbound:
|       - return after outbound -> AVAILABLE
|       - no return after outbound -> OUTBOUNDED
|
| Warehouse logic:
|
| - Start with stocks.warehouse
| - A later return changes the warehouse to returns.warehouse
| - A later transfer changes the warehouse based on:
|       received -> to_warehouse
|       pending  -> from_warehouse
|
|--------------------------------------------------------------------------
*/

$cutoff = '2026-01-31 23:59:59';

/*
|--------------------------------------------------------------------------
| HISTORICAL BARCODE SNAPSHOT
|--------------------------------------------------------------------------
*/

$sql = "

WITH

/* ============================================================
   1. FIRST INBOUND RECORD
   ============================================================ */

inbound AS (

    SELECT
        s.unique_barcode,
        MIN(s.date) AS inbound_date

    FROM stocks s

    WHERE s.date <= ?

    GROUP BY s.unique_barcode
),


/* ============================================================
   2. BASE STOCK INFORMATION
   ============================================================ */

stock_base AS (

    SELECT
        s.unique_barcode,
        s.product_id,
        s.capital,
        s.warehouse AS original_warehouse

    FROM stocks s

    INNER JOIN inbound i
        ON i.unique_barcode = s.unique_barcode

       AND s.date = i.inbound_date

),


/* ============================================================
   3. LATEST OUTBOUND
   ============================================================ */

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


/* ============================================================
   4. LATEST RETURN
   ============================================================ */

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


/* ============================================================
   5. LATEST TRANSFER
   ============================================================ */

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

                    CASE
                        WHEN st.date_received IS NOT NULL
                        THEN st.date_received

                        ELSE st.date_out
                    END DESC,

                    st.id DESC

            ) AS rn

        FROM stock_transfer_content stc

        INNER JOIN stock_transfer st
            ON st.id = stc.st_id

        WHERE st.date_out <= ?

    ) x

    WHERE x.rn = 1
),


/* ============================================================
   6. DETERMINE HISTORICAL STATUS
   ============================================================ */

transaction_status AS (

    SELECT

        sb.*,

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

        END AS stock_status

    FROM stock_base sb

    INNER JOIN inbound i
        ON i.unique_barcode = sb.unique_barcode

    LEFT JOIN latest_outbound lo
        ON lo.unique_barcode = sb.unique_barcode

    LEFT JOIN latest_return lr
        ON lr.unique_barcode = sb.unique_barcode

    LEFT JOIN latest_transfer lt
        ON lt.unique_barcode = sb.unique_barcode
),


/* ============================================================
   7. DETERMINE HISTORICAL WAREHOUSE
   ============================================================ */

historical_stock AS (

    SELECT

        ts.*,

        CASE

            /*
             * -------------------------------------------------
             * RECEIVED TRANSFER AFTER THE LAST RETURN/INBOUND
             * -------------------------------------------------
             */

            WHEN
                ts.transfer_date_received IS NOT NULL

                AND ts.transfer_status = 'received'

                AND ts.transfer_date_received >=
                    COALESCE(
                        ts.return_date,
                        ts.inbound_date
                    )

            THEN ts.to_warehouse


            /*
             * -------------------------------------------------
             * PENDING TRANSFER AFTER LAST RETURN/INBOUND
             * -------------------------------------------------
             */

            WHEN
                ts.transfer_date_out IS NOT NULL

                AND ts.transfer_date_received IS NULL

                AND ts.transfer_date_out >=
                    COALESCE(
                        ts.return_date,
                        ts.inbound_date
                    )

            THEN ts.from_warehouse


            /*
             * -------------------------------------------------
             * RETURN WAREHOUSE
             * -------------------------------------------------
             */

            WHEN
                ts.return_date IS NOT NULL

                AND ts.return_date >= ts.inbound_date

            THEN ts.return_warehouse


            /*
             * -------------------------------------------------
             * ORIGINAL STOCK WAREHOUSE
             * -------------------------------------------------
             */

            ELSE ts.original_warehouse

        END AS historical_warehouse

    FROM transaction_status ts
)


/* ============================================================
   8. FINAL RESULT
   ============================================================ */

SELECT

    hs.unique_barcode,

    hs.product_id,

    p.description AS product_description,

    b.brand_name,

    hs.capital,

    hs.inbound_date,

    hs.outbound_date,

    hs.return_date,

    hs.transfer_date_out,

    hs.transfer_date_received,

    hs.transfer_status,

    hs.stock_status,

    hs.historical_warehouse AS warehouse_hash,

    w.warehouse_name

FROM historical_stock hs

LEFT JOIN product p
    ON p.hashed_id = hs.product_id

LEFT JOIN brand b
    ON b.hashed_id = p.brand

LEFT JOIN warehouse w
    ON w.hashed_id = hs.historical_warehouse

WHERE hs.stock_status = 'AVAILABLE'

ORDER BY

    w.warehouse_name,
    p.description,
    hs.unique_barcode

";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "SQL Prepare Error: " .
        htmlspecialchars($conn->error)
    );

}


/*
|--------------------------------------------------------------------------
| Four cutoff parameters
|--------------------------------------------------------------------------
*/

$stmt->bind_param(
    "ssss",
    $cutoff,
    $cutoff,
    $cutoff,
    $cutoff
);


if (!$stmt->execute()) {

    die(
        "SQL Execute Error: " .
        htmlspecialchars($stmt->error)
    );

}


$result = $stmt->get_result();


/*
|--------------------------------------------------------------------------
| Build summary while reading results
|--------------------------------------------------------------------------
*/

$total_quantity = 0;
$total_capital = 0;

$warehouse_summary = [];
$product_summary = [];

$rows = [];


while ($row = $result->fetch_assoc()) {

    $rows[] = $row;

    $total_quantity++;

    $capital = (float) $row['capital'];

    $total_capital += $capital;


    /*
    |--------------------------------------------------------------------------
    | Warehouse Summary
    |--------------------------------------------------------------------------
    */

    $warehouseKey =
        $row['warehouse_hash'] ?? 'UNKNOWN';

    $warehouseName =
        $row['warehouse_name'] ?? 'Unknown Warehouse';


    if (!isset($warehouse_summary[$warehouseKey])) {

        $warehouse_summary[$warehouseKey] = [

            'warehouse_name' => $warehouseName,
            'quantity' => 0,
            'capital' => 0

        ];

    }


    $warehouse_summary[$warehouseKey]['quantity']++;

    $warehouse_summary[$warehouseKey]['capital']
        += $capital;


    /*
    |--------------------------------------------------------------------------
    | Product Summary
    |--------------------------------------------------------------------------
    */

    $productKey =
        ($row['product_id'] ?? '') . '|' .
        ($warehouseKey ?? '');


    if (!isset($product_summary[$productKey])) {

        $product_summary[$productKey] = [

            'product_description' =>
                $row['product_description'] ??
                'Unknown Product',

            'brand_name' =>
                $row['brand_name'] ??
                'Unknown Brand',

            'warehouse_name' =>
                $warehouseName,

            'quantity' => 0,

            'capital' => 0

        ];

    }


    $product_summary[$productKey]['quantity']++;

    $product_summary[$productKey]['capital']
        += $capital;

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Inventory Snapshot - January 31, 2026
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <style>

        body {

            background: #f5f6f8;

        }


        .container-main {

            max-width: 1800px;

        }


        .stat-card {

            border: 0;

            border-radius: 12px;

            box-shadow:
                0 2px 10px rgba(0,0,0,.06);

        }


        .table-responsive {

            max-height: 70vh;

            overflow: auto;

        }


        thead th {

            position: sticky;

            top: 0;

            background: #212529 !important;

            color: white;

            z-index: 2;

            white-space: nowrap;

        }


        .barcode {

            font-family: monospace;

            font-weight: 600;

        }


        .warehouse-hash {

            font-family: monospace;

            font-size: 11px;

            color: #6c757d;

        }


        .money {

            text-align: right;

            white-space: nowrap;

        }


        .summary-table td,
        .summary-table th {

            vertical-align: middle;

        }

    </style>

</head>


<body>


<div class="container-fluid py-4">


<div class="container-main mx-auto">


    <!-- =====================================================
         HEADER
         ===================================================== -->

    <div
        class="d-flex
               flex-wrap
               justify-content-between
               align-items-center
               mb-4"
    >

        <div>

            <h2 class="mb-1">

                Historical Inventory Snapshot

            </h2>


            <div class="text-muted">

                As of

                <strong>
                    January 31, 2026 11:59:59 PM
                </strong>

            </div>

        </div>


        <div class="text-end mt-3 mt-md-0">

            <div class="text-muted">

                Available Stocks

            </div>


            <div class="display-6 fw-bold">

                <?= number_format($total_quantity) ?>

            </div>

        </div>

    </div>



    <!-- =====================================================
         STAT CARDS
         ===================================================== -->

    <div class="row g-3 mb-4">


        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="text-muted">

                        Available Quantity

                    </div>

                    <div class="fs-2 fw-bold">

                        <?= number_format(
                            $total_quantity
                        ) ?>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="text-muted">

                        Total Capital

                    </div>

                    <div class="fs-2 fw-bold">

                        ₱<?= number_format(
                            $total_capital,
                            2
                        ) ?>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="text-muted">

                        Warehouses

                    </div>

                    <div class="fs-2 fw-bold">

                        <?= number_format(
                            count($warehouse_summary)
                        ) ?>

                    </div>

                </div>

            </div>

        </div>


    </div>



    <!-- =====================================================
         WAREHOUSE SUMMARY
         ===================================================== -->

    <div class="card shadow-sm border-0 mb-4">


        <div class="card-header bg-white">

            <h5 class="mb-0">

                Warehouse Summary

            </h5>

        </div>


        <div class="card-body p-0">


            <div class="table-responsive">


                <table
                    class="table
                           table-bordered
                           table-hover
                           summary-table
                           mb-0"
                >

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Warehouse</th>

                            <th class="text-end">
                                Available Qty
                            </th>

                            <th class="text-end">
                                Total Capital
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    $warehouseCounter = 1;

                    foreach (
                        $warehouse_summary
                        as $warehouse
                    ):

                    ?>


                        <tr>

                            <td>
                                <?= $warehouseCounter++ ?>
                            </td>


                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $warehouse[
                                            'warehouse_name'
                                        ]
                                    ) ?>

                                </strong>

                            </td>


                            <td class="text-end">

                                <?= number_format(
                                    $warehouse['quantity']
                                ) ?>

                            </td>


                            <td class="text-end">

                                ₱<?= number_format(
                                    $warehouse['capital'],
                                    2
                                ) ?>

                            </td>

                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                    <tfoot>

                        <tr class="fw-bold">

                            <td colspan="2">
                                TOTAL
                            </td>


                            <td class="text-end">

                                <?= number_format(
                                    $total_quantity
                                ) ?>

                            </td>


                            <td class="text-end">

                                ₱<?= number_format(
                                    $total_capital,
                                    2
                                ) ?>

                            </td>

                        </tr>

                    </tfoot>


                </table>

            </div>

        </div>

    </div>



    <!-- =====================================================
         PRODUCT SUMMARY
         ===================================================== -->

    <div class="card shadow-sm border-0 mb-4">


        <div class="card-header bg-white">

            <h5 class="mb-0">

                Product Summary by Warehouse

            </h5>

        </div>


        <div class="card-body p-0">


            <div class="table-responsive">


                <table
                    class="table
                           table-bordered
                           table-hover
                           summary-table
                           mb-0"
                >

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Product</th>

                            <th>Brand</th>

                            <th>Warehouse</th>

                            <th class="text-end">
                                Quantity
                            </th>

                            <th class="text-end">
                                Total Capital
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    $productCounter = 1;

                    foreach (
                        $product_summary
                        as $product
                    ):

                    ?>


                        <tr>

                            <td>

                                <?= $productCounter++ ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $product[
                                        'product_description'
                                    ]
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $product[
                                        'brand_name'
                                    ]
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $product[
                                        'warehouse_name'
                                    ]
                                ) ?>

                            </td>


                            <td class="text-end">

                                <?= number_format(
                                    $product['quantity']
                                ) ?>

                            </td>


                            <td class="text-end">

                                ₱<?= number_format(
                                    $product['capital'],
                                    2
                                ) ?>

                            </td>

                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>

            </div>

        </div>

    </div>



    <!-- =====================================================
         BARCODE DETAIL
         ===================================================== -->

    <div class="card shadow-sm border-0">


        <div class="card-header bg-white">


            <div
                class="d-flex
                       flex-wrap
                       justify-content-between
                       align-items-center"
            >

                <h5 class="mb-0">

                    Available Barcode Details

                </h5>


                <span class="badge bg-success">

                    <?= number_format(
                        $total_quantity
                    ) ?>

                    Available

                </span>

            </div>

        </div>


        <div class="card-body p-0">


            <div class="table-responsive">


                <table
                    class="table
                           table-bordered
                           table-hover
                           table-sm
                           align-middle
                           mb-0"
                >


                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Unique Barcode</th>

                            <th>Product</th>

                            <th>Brand</th>

                            <th>Capital</th>

                            <th>Inbound Date</th>

                            <th>Last Outbound</th>

                            <th>Last Return</th>

                            <th>Transfer Out</th>

                            <th>Transfer Received</th>

                            <th>Warehouse</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    $counter = 1;

                    foreach ($rows as $row):

                    ?>


                        <tr>


                            <td>

                                <?= $counter++ ?>

                            </td>


                            <td class="barcode">

                                <?= htmlspecialchars(
                                    $row[
                                        'unique_barcode'
                                    ] ?? ''
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $row[
                                        'product_description'
                                    ] ??
                                    'Unknown Product'
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $row[
                                        'brand_name'
                                    ] ??
                                    'Unknown Brand'
                                ) ?>

                            </td>


                            <td class="money">

                                ₱<?= number_format(
                                    (float) (
                                        $row['capital']
                                        ?? 0
                                    ),
                                    2
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $row[
                                        'inbound_date'
                                    ] ?? '—'
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $row[
                                        'outbound_date'
                                    ] ?? '—'
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $row[
                                        'return_date'
                                    ] ?? '—'
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $row[
                                        'transfer_date_out'
                                    ] ?? '—'
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $row[
                                        'transfer_date_received'
                                    ] ?? '—'
                                ) ?>

                            </td>


                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        $row[
                                            'warehouse_name'
                                        ] ??
                                        'Unknown Warehouse'
                                    ) ?>

                                </strong>


                                <?php if (
                                    !empty(
                                        $row[
                                            'warehouse_hash'
                                        ]
                                    )
                                ): ?>

                                    <div
                                        class="warehouse-hash"
                                    >

                                        <?= htmlspecialchars(
                                            $row[
                                                'warehouse_hash'
                                            ]
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                            </td>


                            <td>

                                <span
                                    class="badge bg-success"
                                >

                                    AVAILABLE

                                </span>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>

            </div>

        </div>

    </div>



    <!-- =====================================================
         LOGIC INFORMATION
         ===================================================== -->

    <div class="alert alert-info mt-4">


        <strong>Snapshot Logic:</strong>

        A barcode is included when it was already
        inbounded by January 31, 2026 and either had
        no outbound transaction before the cutoff or
        was returned after its latest outbound.


        <br><br>


        <strong>Warehouse Logic:</strong>

        The warehouse is reconstructed from the
        historical transaction records available
        before the cutoff. A received transfer uses
        <code>to_warehouse</code>, a pending transfer
        uses <code>from_warehouse</code>, and a return
        uses the warehouse recorded in the
        <code>returns</code> table.

    </div>


</div>

</div>


</body>

</html>


<?php

$stmt->close();

$conn->close();

?>