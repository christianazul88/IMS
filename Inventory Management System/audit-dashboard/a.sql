SELECT

    -- Expected Qty
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND warehouse_origin = '670671cd97404156226e507973f2ab8330d3022ca96e0c93bdbdb320c41adcaf'
        AND outbounded = 'no'
    ) AS total_expected_qty,

    -- Expected Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = 27
        AND ita.warehouse_origin = '670671cd97404156226e507973f2ab8330d3022ca96e0c93bdbdb320c41adcaf'
        AND ita.outbounded = 'no'
    ) AS total_expected_amount,

    -- Total Scanned
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND audit_status IN ('scanned','approved')
    ) AS total_scanned,

    -- Total Scanned Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = 27
        AND ita.audit_status IN ('scanned','approved')
    ) AS total_scanned_amount,

    -- Total Expected Scanned QTY
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND audit_status IN ('scanned','approved')
        AND warehouse_origin = '670671cd97404156226e507973f2ab8330d3022ca96e0c93bdbdb320c41adcaf'
        AND outbounded = 'no'
    ) AS total_expected_scanned_qty,

    -- Total Expected Scanned Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = 27
        AND ita.warehouse_origin = '670671cd97404156226e507973f2ab8330d3022ca96e0c93bdbdb320c41adcaf'
        AND ita.audit_status IN ('scanned','approved')
        AND ita.outbounded = 'no'
    ) AS total_expected_scanned_amount,


    -- Missing Qty
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND audit_status = 'pending'
        AND warehouse_origin = '670671cd97404156226e507973f2ab8330d3022ca96e0c93bdbdb320c41adcaf'
        AND outbounded = 'no'
    ) AS total_missing_qty,

    -- Missing Expected amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = 27
        AND ita.audit_status = 'pending'
        AND ita.warehouse_origin = '670671cd97404156226e507973f2ab8330d3022ca96e0c93bdbdb320c41adcaf'
        AND ita.outbounded = 'no'
    ) AS total_missing_amount,


    -- Positive Variance Outbounded
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND audit_status IN ('scanned','approved')
        AND warehouse_origin = '670671cd97404156226e507973f2ab8330d3022ca96e0c93bdbdb320c41adcaf'
        AND outbounded != 'no'
    ) AS total_scanned_outbounded_as_positive_variance_qty,

    -- Positive Variance Outbounded Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = 27
        AND ita.audit_status IN ('scanned','approved')
        AND ita.warehouse_origin = '670671cd97404156226e507973f2ab8330d3022ca96e0c93bdbdb320c41adcaf'
        AND ita.outbounded != 'no'
    ) AS total_scanned_outbounded_as_positive_variance_amount,

    -- Positive Variance Wrong Warehouse
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND audit_status IN ('scanned','approved')
        AND warehouse_origin != '670671cd97404156226e507973f2ab8330d3022ca96e0c93bdbdb320c41adcaf'
    ) AS total_scanned_wrong_warehouse_as_positive_variance_qty,

    -- Positive Variace Wrong Warehouse Amount
    (
        SELECT SUM(s.capital)
        FROM items_to_audit ita
        LEFT JOIN stocks s
            ON s.unique_barcode = ita.unique_barcode
        WHERE ita.audit_id = 27
        AND ita.audit_status IN ('scanned','approved')
        AND ita.warehouse_origin != '670671cd97404156226e507973f2ab8330d3022ca96e0c93bdbdb320c41adcaf'
    ) AS total_scanned_wrong_warehouse_as_positive_variance_amount;