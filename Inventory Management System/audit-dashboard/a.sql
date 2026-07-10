SELECT
    -- Positive Variance
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND audit_status IN ('scanned', 'approved')
        AND (
            outbounded = 'yes'
            OR warehouse_origin <> warehouse_onscanned
        )
    ) AS total_positive_variance,

    -- Outbounded
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND audit_status IN ('scanned', 'approved')
        AND outbounded = 'yes'
    ) AS total_outbounded,

    -- Wrong Warehouse
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND audit_status IN ('scanned', 'approved')
        AND warehouse_origin <> warehouse_onscanned
    ) AS total_wrong_warehouse,

    -- Expected Qty
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND warehouse_origin = '670671cd97404156226e507973f2ab8330d3022ca96e0c93bdbdb320c41adcaf'
    ) AS total_expected_qty,

    -- Missing Qty
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND audit_status = 'pending'
    ) AS total_missing_qty,

    -- Total Scanned
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND audit_status IN ('scanned','approved')
    ) AS total_scanned,

    -- Total scanned expected
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND audit_status IN ('scanned','approved')
        AND warehouse_origin = warehouse_onscanned
    ) AS total_expected_scanned,

    -- Total unexpected scanned
    (
        SELECT COUNT(*)
        FROM items_to_audit
        WHERE audit_id = 27
        AND audit_status IN ('scanned', 'approved')
        AND (warehouse_origin != warehouse_onscanned OR outbounded != 'no')
    ) AS total_unexpected_scanned;