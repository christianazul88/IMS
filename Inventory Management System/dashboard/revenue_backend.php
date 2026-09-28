<?php
include "../config/database.php";
include "../config/on_session.php";

function json_response($data = null, $httpStatus = 200) {
    header_remove();
    http_response_code($httpStatus);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}


$revenue_data = [];

if (isset($_POST['rev_dateGross']) && strpos((string) $_POST['rev_dateGross'], ' to ') !== false) {
    $date_between = $_POST['rev_dateGross']; // example: 01/04/25 to 30/04/25
    list($start_date, $end_date) = explode(' to ', $date_between);

    // Convert to MySQL format Y-m-d
    $start_date_mysql = DateTime::createFromFormat('d/m/y', $start_date)->format('Y-m-d');
    $end_date_mysql = DateTime::createFromFormat('d/m/y', $end_date)->format('Y-m-d');

    // Format for display (e.g., Jan 1, 2025)
    $start_date_display = DateTime::createFromFormat('d/m/y', $start_date)->format('M j, Y');
    $end_date_display = DateTime::createFromFormat('d/m/y', $end_date)->format('M j, Y');
    $date_label = "$start_date_display to $end_date_display";
    $trend_anchor_mysql = $end_date_mysql;

    if (!empty($_GET['wh'])) {
        $warehouse_dashboard_id = $_GET['wh']; // sample: warehouse1
        $warehouse_dashboard_id = mysqli_real_escape_string($conn, $warehouse_dashboard_id); // Sanitize the input
        
        // Outbound query
        $outbound_query = "
        SELECT 
            SUM(oc.sold_price) AS total_outbound_sales,
            SUM(s.capital) AS total_good_sold
        FROM outbound_content oc
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id   
        LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
        WHERE ol.warehouse = '$warehouse_dashboard_id' AND oc.status IN (0, 6)
          AND ol.date_sent >= '$start_date_mysql 00:00:00'
          AND ol.date_sent < DATE_ADD('$end_date_mysql', INTERVAL 1 DAY)
        ";
    } else {
        // Convert into quoted format
        $warehouse_list = explode(',', $_SESSION['warehouse_ids']);
        // Sanitize each warehouse ID string to avoid SQL injection
        $warehouse_list = array_map(function($warehouse) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $warehouse) . "'";
        }, $warehouse_list);
        
        $warehouse_dashboard_id = implode(",", $warehouse_list); // sample: 'warehouse1','warehouse2','warehouse3'
    
        // Outbound query
        $outbound_query = "
        SELECT 
            SUM(oc.sold_price) AS total_outbound_sales,
            SUM(s.capital) AS total_good_sold
        FROM outbound_content oc
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id   
        LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
        WHERE ol.warehouse IN ($warehouse_dashboard_id) AND oc.status IN (0, 6)
          AND ol.date_sent >= '$start_date_mysql 00:00:00'
          AND ol.date_sent < DATE_ADD('$end_date_mysql', INTERVAL 1 DAY)
        ";
    }
    

    $outbound_result = mysqli_query($conn, $outbound_query);

    if ($outbound_result && mysqli_num_rows($outbound_result) > 0) {
        $outbound_data = mysqli_fetch_assoc($outbound_result);
        $total_outbound_sales = $outbound_data['total_outbound_sales'] ?? 0;
        $total_good_sold = $outbound_data['total_good_sold'] ?? 0;
        $total_net_income = $total_outbound_sales - $total_good_sold;
    } else {
        $total_outbound_sales = 0;
        $total_good_sold = 0;
        $total_net_income = 0;
    }




} else {
    //first day of the month
    $start_date = date("Y-m-01");
    $first_day_ofmonth = date("M j, Y", strtotime($start_date));
    // Today's date
    $today_mysql = date('Y-m-d');
    $start_date_mysql = $start_date;
    $end_date_mysql = $today_mysql;
    $today_display = date('M j, Y');
    // $date_label = $first_day_ofmonth . " to " .$today_display;

    $today_formatted = date('M j, Y'); // example: "Apr 27, 2025"
    $date_label = $first_day_ofmonth . " to " .$today_display;
    $trend_anchor_mysql = $today_mysql;


    if (!empty($_GET['wh'])) {
        $warehouse_dashboard_id = $_GET['wh']; // sample: warehouse1
        $warehouse_dashboard_id = mysqli_real_escape_string($conn, $warehouse_dashboard_id); // Sanitize the input
        
        // Outbound query
        $outbound_query = "
        SELECT 
            SUM(oc.sold_price) AS total_outbound_sales,
            SUM(s.capital) AS total_good_sold
        FROM outbound_content oc
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id   
        LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
        WHERE ol.warehouse = '$warehouse_dashboard_id' AND oc.status IN (0, 6)
          AND ol.date_sent >= '$start_date 00:00:00'
          AND ol.date_sent < DATE_ADD('$today_mysql', INTERVAL 1 DAY)
        ";
    } else {
        // Convert into quoted format
        $warehouse_list = explode(',', $_SESSION['warehouse_ids']);
        // Sanitize each warehouse ID string to avoid SQL injection
        $warehouse_list = array_map(function($warehouse) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $warehouse) . "'";
        }, $warehouse_list);
        
        $warehouse_dashboard_id = implode(",", $warehouse_list); // sample: 'warehouse1','warehouse2','warehouse3'
    
        // Outbound query
        $outbound_query = "
        SELECT 
            SUM(oc.sold_price) AS total_outbound_sales,
            SUM(s.capital) AS total_good_sold
        FROM outbound_content oc
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id   
        LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
        WHERE ol.warehouse IN ($warehouse_dashboard_id) AND oc.status IN (0, 6)
          AND ol.date_sent >= '$start_date 00:00:00'
          AND ol.date_sent < DATE_ADD('$today_mysql', INTERVAL 1 DAY)
        ";
    }


    $outbound_result = mysqli_query($conn, $outbound_query);

    if ($outbound_result && mysqli_num_rows($outbound_result) > 0) {
        $outbound_data = mysqli_fetch_assoc($outbound_result);
        $total_outbound_sales = $outbound_data['total_outbound_sales'] ?? 0;
        $total_good_sold = $outbound_data['total_good_sold'] ?? 0;
        $total_net_income = $total_outbound_sales - $total_good_sold;
    } else {
        $total_outbound_sales = 0;
        $total_good_sold = 0;
        $total_net_income = 0;
    }
}

// Additional inventory context for the selected period. Inbound cost is the
// capital value of every item received during the date range; it is kept
// separate from COGS, which only includes items that were outbounded/sold.
$warehouse_request = trim((string) ($_GET['wh'] ?? ''));
if ($warehouse_request !== '') {
    $warehouse_request_escaped = mysqli_real_escape_string($conn, $warehouse_request);
    $inbound_warehouse_clause = "il.warehouse = '$warehouse_request_escaped'";
    $stock_warehouse_clause = "s.warehouse = '$warehouse_request_escaped'";
} else {
    $inbound_warehouse_clause = "il.warehouse IN ($warehouse_dashboard_id)";
    $stock_warehouse_clause = "s.warehouse IN ($warehouse_dashboard_id)";
}

// Stock rows created by the receiving workflow normally point to an
// inbound_logs row. Some legitimate extra/manual receipts are written
// directly to stocks, however, so use the stock warehouse/date as a safe
// fallback whenever the inbound log is absent.
if ($warehouse_request !== '') {
    $inbound_warehouse_clause = "COALESCE(il.warehouse, s.warehouse) = '$warehouse_request_escaped'";
} else {
    $inbound_warehouse_clause = "COALESCE(il.warehouse, s.warehouse) IN ($warehouse_dashboard_id)";
}

$inbound_cost_query = "
    SELECT COALESCE(SUM(s.capital), 0) AS total_inbound_cost
    FROM stocks s
    LEFT JOIN inbound_logs il ON il.id = s.inbound_id
    WHERE (il.id IS NULL OR il.status = 0)
      AND $inbound_warehouse_clause
      AND COALESCE(il.date_received, s.date) >= '$start_date_mysql 00:00:00'
      AND COALESCE(il.date_received, s.date) < DATE_ADD('$end_date_mysql', INTERVAL 1 DAY)
";
$inbound_cost_result = mysqli_query($conn, $inbound_cost_query);
$total_inbound_cost = 0;
if ($inbound_cost_result) {
    $inbound_cost_data = mysqli_fetch_assoc($inbound_cost_result);
    $total_inbound_cost = (float) ($inbound_cost_data['total_inbound_cost'] ?? 0);
}

$remaining_inventory_query = "
    SELECT COALESCE(SUM(s.capital), 0) AS remaining_inventory_value
    FROM stocks s
    WHERE $stock_warehouse_clause
      AND s.item_status IN (0, 3)
";
$remaining_inventory_result = mysqli_query($conn, $remaining_inventory_query);
$remaining_inventory_value = 0;
if ($remaining_inventory_result) {
    $remaining_inventory_data = mysqli_fetch_assoc($remaining_inventory_result);
    $remaining_inventory_value = (float) ($remaining_inventory_data['remaining_inventory_value'] ?? 0);
}

// Build a compact monthly trend for the revenue chart. It follows the same
// warehouse scope and status rules as the KPI query above, then fills months
// with zeroes so the chart always has a stable six- or twelve-point timeline.
$trend_months = (int) ($_POST['trend_months'] ?? 6);
if (!in_array($trend_months, [3, 6, 12], true)) {
    $trend_months = 6;
}

$trend_anchor = DateTime::createFromFormat('!Y-m-d', $trend_anchor_mysql);
if (!$trend_anchor) {
    $trend_anchor = new DateTime('today');
}

$trend_start = (clone $trend_anchor)->modify('first day of this month')->modify('-' . ($trend_months - 1) . ' months');
$trend_start_mysql = $trend_start->format('Y-m-d');
$trend_end_exclusive = (clone $trend_anchor)->modify('+1 day')->format('Y-m-d');
$today_exclusive = (new DateTime('today'))->modify('+1 day')->format('Y-m-d');
$movement_end_exclusive = max($trend_end_exclusive, $today_exclusive);

if (!empty($_GET['wh'])) {
    $trend_warehouse = mysqli_real_escape_string($conn, (string) $_GET['wh']);
    $trend_warehouse_clause = "ol.warehouse = '$trend_warehouse'";
    $trend_inbound_warehouse_clause = "il.warehouse = '$trend_warehouse'";
    $trend_stock_warehouse_clause = "s.warehouse = '$trend_warehouse'";
    $trend_inbound_source_warehouse_clause = "COALESCE(il.warehouse, s.warehouse) = '$trend_warehouse'";
    $trend_return_warehouse_clause = "r.warehouse = '$trend_warehouse'";
    $trend_rts_warehouse_clause = "rl.warehouse = '$trend_warehouse'";
    $trend_transfer_from_warehouse_clause = "st.from_warehouse = '$trend_warehouse'";
    $trend_transfer_to_warehouse_clause = "st.to_warehouse = '$trend_warehouse'";
} else {
    $trend_warehouse_list = explode(',', (string) ($_SESSION['warehouse_ids'] ?? ''));
    $trend_warehouse_list = array_map(function ($warehouse) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, trim($warehouse)) . "'";
    }, array_filter($trend_warehouse_list, static fn($warehouse) => trim($warehouse) !== ''));
    $trend_warehouse_clause = $trend_warehouse_list
        ? 'ol.warehouse IN (' . implode(',', $trend_warehouse_list) . ')'
        : '1 = 0';
    $trend_inbound_warehouse_clause = $trend_warehouse_list
        ? 'il.warehouse IN (' . implode(',', $trend_warehouse_list) . ')'
        : '1 = 0';
    $trend_stock_warehouse_clause = $trend_warehouse_list
        ? 's.warehouse IN (' . implode(',', $trend_warehouse_list) . ')'
        : '1 = 0';
    $trend_inbound_source_warehouse_clause = $trend_warehouse_list
        ? 'COALESCE(il.warehouse, s.warehouse) IN (' . implode(',', $trend_warehouse_list) . ')'
        : '1 = 0';
    $trend_return_warehouse_clause = $trend_warehouse_list
        ? 'r.warehouse IN (' . implode(',', $trend_warehouse_list) . ')'
        : '1 = 0';
    $trend_rts_warehouse_clause = $trend_warehouse_list
        ? 'rl.warehouse IN (' . implode(',', $trend_warehouse_list) . ')'
        : '1 = 0';
    // For an all-accessible/partial scope, internal transfers between two
    // included warehouses do not change total inventory. Only transfer
    // boundaries crossing out of or into the scope need to be charted.
    $trend_transfer_from_warehouse_clause = $trend_warehouse_list
        ? 'st.from_warehouse IN (' . implode(',', $trend_warehouse_list) . ') AND (st.to_warehouse IS NULL OR st.to_warehouse NOT IN (' . implode(',', $trend_warehouse_list) . '))'
        : '1 = 0';
    $trend_transfer_to_warehouse_clause = $trend_warehouse_list
        ? 'st.to_warehouse IN (' . implode(',', $trend_warehouse_list) . ') AND (st.from_warehouse IS NULL OR st.from_warehouse NOT IN (' . implode(',', $trend_warehouse_list) . '))'
        : '1 = 0';
}

$trend_values = [];
$outbound_movement_values = [];
$outbound_events_query = "
    SELECT ol.date_sent, ol.status AS outbound_log_status,
           oc.status AS outbound_content_status, oc.sold_price,
           oc.unique_barcode
    FROM outbound_logs ol
    INNER JOIN outbound_content oc ON oc.hashed_id = ol.hashed_id
    WHERE $trend_warehouse_clause
      AND ol.date_sent >= '$trend_start_mysql 00:00:00'
      AND ol.date_sent < '$movement_end_exclusive 00:00:00'
";
$outbound_events_result = mysqli_query($conn, $outbound_events_query);
$outbound_capital_by_barcode = [];
if ($outbound_events_result && mysqli_num_rows($outbound_events_result) > 0) {
    $outbound_capital_result = mysqli_query($conn, "SELECT unique_barcode, capital FROM stocks WHERE unique_barcode IS NOT NULL");
    if ($outbound_capital_result) {
        while ($outbound_capital_row = mysqli_fetch_assoc($outbound_capital_result)) {
            $outbound_capital_key = strtolower(trim((string) $outbound_capital_row['unique_barcode']));
            $outbound_capital_by_barcode[$outbound_capital_key] = (float) ($outbound_capital_row['capital'] ?? 0);
        }
    }
}

$outbound_movement_defaults = ['outbound_cost' => 0];
while ($outbound_events_result && ($outbound_row = mysqli_fetch_assoc($outbound_events_result))) {
    $outbound_date = (string) ($outbound_row['date_sent'] ?? '');
    if ($outbound_date === '' || $outbound_date === '0000-00-00 00:00:00') {
        continue;
    }
    $outbound_period = $outbound_date >= $trend_end_exclusive . ' 00:00:00'
        ? 'after_anchor'
        : substr($outbound_date, 0, 7);
    $outbound_capital_key = strtolower(trim((string) $outbound_row['unique_barcode']));
    $outbound_capital = (float) ($outbound_capital_by_barcode[$outbound_capital_key] ?? 0);

    if ($outbound_row['outbound_log_status'] != 4 && $outbound_row['outbound_content_status'] != 4) {
        if ($outbound_period === 'after_anchor') {
            $outbound_movement_values['after_anchor'] = ($outbound_movement_values['after_anchor'] ?? $outbound_movement_defaults);
            $outbound_movement_values['after_anchor']['outbound_cost'] += $outbound_capital;
        } else {
            $outbound_movement_values[$outbound_period] = ($outbound_movement_values[$outbound_period] ?? $outbound_movement_defaults);
            $outbound_movement_values[$outbound_period]['outbound_cost'] += $outbound_capital;
        }
    }

    if ($outbound_period !== 'after_anchor' && in_array((int) $outbound_row['outbound_content_status'], [0, 6], true)) {
        if (!isset($trend_values[$outbound_period])) {
            $trend_values[$outbound_period] = ['sales' => 0, 'cogs' => 0, 'profit' => 0];
        }
        $trend_values[$outbound_period]['sales'] += (float) ($outbound_row['sold_price'] ?? 0);
        $trend_values[$outbound_period]['cogs'] += $outbound_capital;
    }
}
foreach ($trend_values as $trend_key => &$trend_value) {
    $trend_value['profit'] = $trend_value['sales'] - $trend_value['cogs'];
}
unset($trend_value);

// Inventory flow is a movement ledger, not simply inbound minus outbound.
// Each source mirrors an actual IMS process:
//   inbound          + stock received (including direct stock-only receipts)
//   outbound         - stock sent to a customer (voided outbounds excluded)
//   customer_return  + products returned from a customer
//   supplier_return  - products reserved/returned to a supplier
//   transfer_out     - stock leaving the selected warehouse
//   transfer_in      + stock received by the selected warehouse
// Event warehouse/date fields are used so a later transfer does not rewrite
// the original inbound or outbound movement. Each source is aggregated on
// its own because the transfer history is large and should not be scanned
// repeatedly through one materialized UNION query.
$movement_sources = [
    'inbound_cost' => [
        'date' => 'COALESCE(il.date_received, s.date)',
        'value' => 'COALESCE(s.capital, 0)',
        'from' => 'FROM stocks s LEFT JOIN inbound_logs il ON il.id = s.inbound_id',
        'where' => "(il.id IS NULL OR il.status = 0) AND $trend_inbound_source_warehouse_clause",
    ],
    'customer_return_cost' => [
        'date' => 'r.date',
        'value' => 'COALESCE(s.capital, 0)',
        'from' => 'FROM returns r LEFT JOIN stocks s ON s.unique_barcode = r.unique_barcode',
        'where' => $trend_return_warehouse_clause,
    ],
    'supplier_return_cost' => [
        'date' => 'rl.date',
        'value' => 'COALESCE(s.capital, 0)',
        'from' => 'FROM rts_logs rl INNER JOIN rts_content rc ON rc.rts_id = rl.id LEFT JOIN stocks s ON s.unique_barcode = rc.unique_barcode',
        'where' => "$trend_rts_warehouse_clause AND rc.status IN (0, 1, 2)",
    ],
];

$movement_defaults = [
    'inbound_cost' => 0,
    'outbound_cost' => 0,
    'customer_return_cost' => 0,
    'supplier_return_cost' => 0,
    'transfer_in_cost' => 0,
    'transfer_out_cost' => 0,
];
$movement_values = [];
$movement_after_anchor = $movement_defaults;

foreach ($outbound_movement_values as $outbound_period => $outbound_point) {
    if ($outbound_period === 'after_anchor') {
        $movement_after_anchor['outbound_cost'] += (float) ($outbound_point['outbound_cost'] ?? 0);
        continue;
    }
    if (!isset($movement_values[$outbound_period])) {
        $movement_values[$outbound_period] = $movement_defaults;
    }
    $movement_values[$outbound_period]['outbound_cost'] += (float) ($outbound_point['outbound_cost'] ?? 0);
}

foreach ($movement_sources as $movement_key => $movement_source) {
    $movement_date_sql = $movement_source['date'];
    $movement_query = "
        SELECT
            CASE
                WHEN $movement_date_sql < '$trend_end_exclusive 00:00:00' THEN DATE_FORMAT($movement_date_sql, '%Y-%m')
                ELSE 'after_anchor'
            END AS movement_period,
            COALESCE(SUM({$movement_source['value']}), 0) AS movement_value
        {$movement_source['from']}
        WHERE {$movement_source['where']}
          AND $movement_date_sql >= '$trend_start_mysql 00:00:00'
          AND $movement_date_sql < '$movement_end_exclusive 00:00:00'
        GROUP BY movement_period
    ";
    $movement_result = mysqli_query($conn, $movement_query);
    if (!$movement_result) {
        continue;
    }
    while ($movement_row = mysqli_fetch_assoc($movement_result)) {
        $movement_period = (string) ($movement_row['movement_period'] ?? '');
        $movement_value = (float) ($movement_row['movement_value'] ?? 0);
        if ($movement_period === 'after_anchor') {
            $movement_after_anchor[$movement_key] = $movement_value;
        } elseif ($movement_period !== '') {
            if (!isset($movement_values[$movement_period])) {
                $movement_values[$movement_period] = $movement_defaults;
            }
            $movement_values[$movement_period][$movement_key] = $movement_value;
        }
    }
}

// Transfer history is the largest movement source. Fetch its event rows
// without the expensive per-row stock join, then resolve capital values once
// in PHP. This keeps transfer-in/out mapping accurate while avoiding a slow
// grouped join on large transfer histories.
$transfer_out_query = "
    SELECT st.date_out, stc.unique_barcode
    FROM stock_transfer st
    INNER JOIN stock_transfer_content stc ON stc.st_id = st.id
    WHERE $trend_transfer_from_warehouse_clause
      AND st.status IN ('enroute', 'received')
      AND st.date_out IS NOT NULL
      AND st.date_out < '$movement_end_exclusive 00:00:00'
";
$transfer_out_result = mysqli_query($conn, $transfer_out_query);

$transfer_in_query = "
    SELECT st.date_received, stc.unique_barcode
    FROM stock_transfer st
    INNER JOIN stock_transfer_content stc ON stc.st_id = st.id
    WHERE $trend_transfer_to_warehouse_clause
      AND st.status = 'received'
      AND st.date_received IS NOT NULL
      AND st.date_received < '$movement_end_exclusive 00:00:00'
";
$transfer_in_result = mysqli_query($conn, $transfer_in_query);

$transfer_capital_by_barcode =& $outbound_capital_by_barcode;
if (($transfer_out_result && mysqli_num_rows($transfer_out_result) > 0) || ($transfer_in_result && mysqli_num_rows($transfer_in_result) > 0)) {
    if (empty($transfer_capital_by_barcode)) {
        $transfer_capital_result = mysqli_query($conn, "SELECT unique_barcode, capital FROM stocks WHERE unique_barcode IS NOT NULL");
        if ($transfer_capital_result) {
            while ($transfer_capital_row = mysqli_fetch_assoc($transfer_capital_result)) {
                $transfer_capital_key = strtolower(trim((string) $transfer_capital_row['unique_barcode']));
                $transfer_capital_by_barcode[$transfer_capital_key] = (float) ($transfer_capital_row['capital'] ?? 0);
            }
        }
    }
}

$add_transfer_movement = static function ($movement_key, $movement_date, $unique_barcode) use (&$movement_values, &$movement_after_anchor, $movement_defaults, $trend_start_mysql, $trend_end_exclusive, $movement_end_exclusive, &$transfer_capital_by_barcode) {
    $movement_date = (string) $movement_date;
    if ($movement_date === '' || $movement_date === '0000-00-00 00:00:00') {
        return;
    }
    $movement_capital_key = strtolower(trim((string) $unique_barcode));
    $movement_value = (float) ($transfer_capital_by_barcode[$movement_capital_key] ?? 0);
    if ($movement_date >= $trend_end_exclusive . ' 00:00:00') {
        $movement_after_anchor[$movement_key] += $movement_value;
        return;
    }
    if ($movement_date < $trend_start_mysql . ' 00:00:00') {
        return;
    }
    $movement_period = substr($movement_date, 0, 7);
    if (!isset($movement_values[$movement_period])) {
        $movement_values[$movement_period] = $movement_defaults;
    }
    $movement_values[$movement_period][$movement_key] += $movement_value;
};

if ($transfer_out_result) {
    while ($transfer_out_row = mysqli_fetch_assoc($transfer_out_result)) {
        $add_transfer_movement('transfer_out_cost', $transfer_out_row['date_out'], $transfer_out_row['unique_barcode']);
    }
}
if ($transfer_in_result) {
    while ($transfer_in_row = mysqli_fetch_assoc($transfer_in_result)) {
        $add_transfer_movement('transfer_in_cost', $transfer_in_row['date_received'], $transfer_in_row['unique_barcode']);
    }
}

unset($add_transfer_movement);
$transfer_capital_by_barcode = [];
unset($outbound_capital_by_barcode);

// The current available-inventory KPI is the authoritative anchor. Reverse
// any movements after the selected trend end so the chart remains correct for
// a historical date range without scanning every outbound record ever saved.
$anchor_inventory_value = $remaining_inventory_value
    - $movement_after_anchor['inbound_cost']
    - $movement_after_anchor['customer_return_cost']
    - $movement_after_anchor['transfer_in_cost']
    + $movement_after_anchor['outbound_cost']
    + $movement_after_anchor['supplier_return_cost']
    + $movement_after_anchor['transfer_out_cost'];

$remaining_inventory_by_period = [];
$reverse_inventory_value = $anchor_inventory_value;
$trend_last_month = (clone $trend_anchor)->modify('first day of this month');
$reverse_cursor = clone $trend_last_month;
while ($reverse_cursor >= $trend_start) {
    $reverse_key = $reverse_cursor->format('Y-m');
    $remaining_inventory_by_period[$reverse_key] = max(0, $reverse_inventory_value);
    $reverse_point = $movement_values[$reverse_key] ?? $movement_defaults;
    $reverse_inventory_value -= $reverse_point['inbound_cost']
        + $reverse_point['customer_return_cost']
        + $reverse_point['transfer_in_cost']
        - $reverse_point['outbound_cost']
        - $reverse_point['supplier_return_cost']
        - $reverse_point['transfer_out_cost'];
    $reverse_cursor->modify('-1 month');
}

$trend = [];
$trend_cursor = clone $trend_start;
while ($trend_cursor <= $trend_last_month) {
    $trend_key = $trend_cursor->format('Y-m');
    $trend_point = $trend_values[$trend_key] ?? ['sales' => 0, 'cogs' => 0, 'profit' => 0];
    $movement_point = $movement_values[$trend_key] ?? [
        'inbound_cost' => 0,
        'outbound_cost' => 0,
        'customer_return_cost' => 0,
        'supplier_return_cost' => 0,
        'transfer_in_cost' => 0,
        'transfer_out_cost' => 0,
    ];
    $trend[] = [
        'label' => $trend_cursor->format('M'),
        'period' => $trend_cursor->format('M Y'),
        'sales' => $trend_point['sales'],
        'profit' => $trend_point['profit'],
        'inbound_cost' => $movement_point['inbound_cost'],
        'outbound_cost' => $movement_point['outbound_cost'],
        'customer_return_cost' => $movement_point['customer_return_cost'],
        'supplier_return_cost' => $movement_point['supplier_return_cost'],
        'transfer_in_cost' => $movement_point['transfer_in_cost'],
        'transfer_out_cost' => $movement_point['transfer_out_cost'],
        'net_movement' => $movement_point['inbound_cost']
            + $movement_point['customer_return_cost']
            + $movement_point['transfer_in_cost']
            - $movement_point['outbound_cost']
            - $movement_point['supplier_return_cost']
            - $movement_point['transfer_out_cost'],
        'remaining_inventory' => $remaining_inventory_by_period[$trend_key] ?? max(0, $anchor_inventory_value),
    ];
    $trend_cursor->modify('+1 month');
}

// Final response array
$revenue_data = [
    'date_selected' => $date_label,
    'total_sales' => (float)$total_outbound_sales,
    'good_sold' => (float)$total_good_sold,
    'net_income' => (float)$total_net_income,
    'inbound_cost' => (float)$total_inbound_cost,
    'remaining_inventory' => (float)$remaining_inventory_value,
    'trend_months' => $trend_months,
    'trend' => $trend
];

// Output JSON
json_response($revenue_data);
?>
