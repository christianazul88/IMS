<?php
require "../config/database.php";
require "../config/on_session.php";
<?php
require 'db_connection.php';

$request = $_POST;

$columns = [
    0 => 'st.id',
    1 => 'st.id',
    2 => 'batch_codes',
    3 => 'st.status',
    4 => 'fw.warehouse_name',
    5 => 'from_fullname',
    6 => 'st.date_out',
    7 => 'st.remarks_sender',
    8 => 'tw.warehouse_name',
    9 => 'st.date_received',
    10 => 'receiver_fullname',
    11 => 'st.remarks_receiver'
];

$limit  = intval($request['length']);
$offset = intval($request['start']);

$orderColumnIndex = $request['order'][0]['column'];
$orderColumn = $columns[$orderColumnIndex];
$orderDir = $request['order'][0]['dir'];

$searchValue = $request['search']['value'];

/* Warehouse filtering */
$quoted_warehouse_ids = array_map(fn($id) => "'" . trim($id) . "'", $user_warehouse_ids);
$warehouseFilter = implode(",", $quoted_warehouse_ids);

/* Base Query */
$baseQuery = "
FROM stock_transfer st
LEFT JOIN stock_transfer_content stc ON stc.st_id = st.id
LEFT JOIN stocks s ON stc.unique_barcode = s.unique_barcode
LEFT JOIN warehouse fw ON st.from_warehouse = fw.hashed_id
LEFT JOIN warehouse tw ON st.to_warehouse = tw.hashed_id
LEFT JOIN users fu ON st.from_userid = fu.hashed_id
LEFT JOIN users ru ON st.received_userid = ru.hashed_id
WHERE (st.from_warehouse IN ($warehouseFilter)
   OR st.to_warehouse IN ($warehouseFilter))
";

/* Search */
if (!empty($searchValue)) {
    $searchValue = $conn->real_escape_string($searchValue);
    $baseQuery .= " AND (
        fw.warehouse_name LIKE '%$searchValue%' OR
        tw.warehouse_name LIKE '%$searchValue%' OR
        fu.user_fname LIKE '%$searchValue%' OR
        ru.user_fname LIKE '%$searchValue%' OR
        s.batch_code LIKE '%$searchValue%'
    )";
}

/* Total Records */
$totalQuery = "SELECT COUNT(DISTINCT st.id) as total $baseQuery";
$totalResult = $conn->query($totalQuery);
$totalData = $totalResult->fetch_assoc()['total'];

/* Main Data Query */
$dataQuery = "
SELECT 
    st.id,
    st.status,
    st.date_out,
    st.date_received,
    st.remarks_sender,
    st.remarks_receiver,
    fw.warehouse_name AS from_warehouse_name,
    tw.warehouse_name AS to_warehouse_name,
    CONCAT(fu.user_fname,' ',fu.user_lname) AS from_fullname,
    CONCAT(ru.user_fname,' ',ru.user_lname) AS receiver_fullname,
    GROUP_CONCAT(DISTINCT s.batch_code ORDER BY s.batch_code DESC SEPARATOR ', ') AS batch_codes
$baseQuery
GROUP BY st.id
ORDER BY $orderColumn $orderDir
LIMIT $limit OFFSET $offset
";

$result = $conn->query($dataQuery);

$data = [];

while ($row = $result->fetch_assoc()) {

    $status_badge = match ($row['status']) {
        "pending" => '<span class="badge bg-primary">Pending</span>',
        "enroute" => '<span class="badge bg-warning">Enroute</span>',
        "received" => '<span class="badge bg-success">Received</span>',
        default => '<span class="badge bg-danger">Failed</span>',
    };

    $data[] = [
        '',
        10000 + $row['id'],
        htmlspecialchars(substr($row['batch_codes'],0,50)),
        $status_badge,
        htmlspecialchars($row['from_warehouse_name']),
        htmlspecialchars($row['from_fullname']),
        htmlspecialchars($row['date_out']),
        htmlspecialchars($row['remarks_sender']),
        htmlspecialchars($row['to_warehouse_name']),
        htmlspecialchars($row['date_received']),
        htmlspecialchars($row['receiver_fullname']),
        htmlspecialchars($row['remarks_receiver'])
    ];
}

/* Response */
echo json_encode([
    "draw" => intval($request['draw']),
    "recordsTotal" => intval($totalData),
    "recordsFiltered" => intval($totalData),
    "data" => $data
]);
