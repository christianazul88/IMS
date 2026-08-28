<?php

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| HISTORICAL INVENTORY SNAPSHOT
|--------------------------------------------------------------------------
*/

$cutoff = '2026-01-31 23:59:59';


/*
|--------------------------------------------------------------------------
| EXPORT DETAILED CSV
|--------------------------------------------------------------------------
|
| Open this page with:
|
| historical_inventory.php?export=csv
|
|--------------------------------------------------------------------------
*/

if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    $filename = 'inventory_snapshot_2026-01-31.csv';

    header('Content-Type: text/csv; charset=utf-8');

    header(
        'Content-Disposition: attachment; filename="' .
        $filename .
        '"'
    );

    header('Pragma: no-cache');
    header('Expires: 0');


    $output = fopen('php://output', 'w');


    /*
    |--------------------------------------------------------------------------
    | CSV HEADER
    |--------------------------------------------------------------------------
    */

    fputcsv($output, [
        'category',
        'supplier',
        'supplier location',
        'brand',
        'description',
        'unique barcode',
        'capital',
        'warehouse'
    ]);


    /*
    |--------------------------------------------------------------------------
    | HISTORICAL INVENTORY QUERY
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

            s.supplier,

            s.capital,

            s.warehouse AS original_warehouse

        FROM stocks s

        INNER JOIN inbound i

            ON i.unique_barcode = s.unique_barcode

            AND s.date = i.inbound_date

    ),


    /* ============================================================
       3. LATEST OUTBOUND BEFORE CUTOFF
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
       4. LATEST RETURN BEFORE CUTOFF
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
       5. LATEST TRANSFER BEFORE CUTOFF
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
       6. DETERMINE STOCK STATUS
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
                 * Latest received transfer
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
                 * Pending transfer
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
                 * Returned item
                 */

                WHEN

                    ts.return_date IS NOT NULL

                    AND ts.return_date >= ts.inbound_date

                THEN ts.return_warehouse


                /*
                 * Original stock warehouse
                 */

                ELSE ts.original_warehouse

            END AS historical_warehouse

        FROM transaction_status ts
    )


    /* ============================================================
       8. FINAL DETAILED REPORT
       ============================================================ */

    SELECT

        /*
         * CATEGORY
         *
         * Assumes product.category contains the
         * category hashed ID.
         */

        c.category_name AS category,


        /*
         * SUPPLIER
         */

        sup.supplier_name AS supplier,


        /*
         * SUPPLIER LOCATION
         */

        sup.local_international AS supplier_location,


        /*
         * BRAND
         */

        b.brand_name AS brand,


        /*
         * DESCRIPTION
         */

        p.description AS description,


        /*
         * BARCODE
         */

        hs.unique_barcode AS unique_barcode,


        /*
         * CAPITAL
         */

        hs.capital AS capital,


        /*
         * HISTORICAL WAREHOUSE
         */

        w.warehouse_name AS warehouse


    FROM historical_stock hs


    /*
    |--------------------------------------------------------------------------
    | PRODUCT
    |--------------------------------------------------------------------------
    */

    LEFT JOIN product p

        ON p.hashed_id = hs.product_id


    /*
    |--------------------------------------------------------------------------
    | CATEGORY
    |--------------------------------------------------------------------------
    */

    LEFT JOIN category c

        ON c.hashed_id = p.category


    /*
    |--------------------------------------------------------------------------
    | BRAND
    |--------------------------------------------------------------------------
    */

    LEFT JOIN brand b

        ON b.hashed_id = p.brand


    /*
    |--------------------------------------------------------------------------
    | SUPPLIER
    |--------------------------------------------------------------------------
    */

    LEFT JOIN supplier sup

        ON sup.hashed_id = hs.supplier


    /*
    |--------------------------------------------------------------------------
    | WAREHOUSE
    |--------------------------------------------------------------------------
    */

    LEFT JOIN warehouse w

        ON w.hashed_id = hs.historical_warehouse


    /*
    |--------------------------------------------------------------------------
    | ONLY AVAILABLE STOCKS
    |--------------------------------------------------------------------------
    */

    WHERE hs.stock_status = 'AVAILABLE'


    ORDER BY

        w.warehouse_name,

        c.category_name,

        sup.supplier_name,

        b.brand_name,

        p.description,

        hs.unique_barcode

    ";


    $stmt = $conn->prepare($sql);


    if (!$stmt) {

        die(
            'SQL Prepare Error: ' .
            $conn->error
        );

    }


    /*
    |--------------------------------------------------------------------------
    | BIND CUTOFF DATES
    |--------------------------------------------------------------------------
    */

    $stmt->bind_param(
        'ssss',
        $cutoff,
        $cutoff,
        $cutoff,
        $cutoff
    );


    if (!$stmt->execute()) {

        die(
            'SQL Execute Error: ' .
            $stmt->error
        );

    }


    $result = $stmt->get_result();


    /*
    |--------------------------------------------------------------------------
    | STREAM CSV
    |--------------------------------------------------------------------------
    */

    while ($row = $result->fetch_assoc()) {

        fputcsv($output, [

            $row['category'] ?? '',

            $row['supplier'] ?? '',

            $row['supplier_location'] ?? '',

            $row['brand'] ?? '',

            $row['description'] ?? '',

            $row['unique_barcode'] ?? '',

            $row['capital'] ?? '',

            $row['warehouse'] ?? ''

        ]);

    }


    fclose($output);

    $stmt->close();

    $conn->close();

    exit;
}


/*
|--------------------------------------------------------------------------
| NORMAL PAGE
|--------------------------------------------------------------------------
|
| We intentionally don't query all barcode records here.
| This keeps the page fast.
|
|--------------------------------------------------------------------------
*/

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


        .snapshot-card {

            border: none;

            border-radius: 12px;

            box-shadow:
                0 2px 10px rgba(0,0,0,.06);

        }

    </style>

</head>


<body>


<div class="container py-5">


    <div class="card snapshot-card">

        <div class="card-body p-5">


            <div class="text-center">


                <h2 class="mb-2">

                    Historical Inventory Snapshot

                </h2>


                <p class="text-muted mb-4">

                    Available inventory as of

                    <strong>
                        January 31, 2026 11:59:59 PM
                    </strong>

                </p>


                <a
                    href="?export=csv"
                    class="btn btn-primary btn-lg"
                >

                    Download Detailed CSV Report

                </a>


                <div class="mt-3 text-muted small">

                    The detailed report is generated
                    directly from the database and
                    streamed as a CSV file.

                </div>


            </div>


        </div>

    </div>


    <div class="alert alert-info mt-4">

        <strong>CSV Columns:</strong>

        category,
        supplier,
        supplier location,
        brand,
        description,
        unique barcode,
        capital,
        warehouse

    </div>


</div>


</body>

</html>