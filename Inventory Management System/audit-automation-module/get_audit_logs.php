<?php
include "../config/database.php";
include "../config/on_session.php";

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$offset = ($page - 1) * $limit;

$isAdmin = (
    $user_email === "vp_ronadanesito@laptoppcoutlet.com" ||
    $user_email === "administrator@admin.admin"
);

// Escape search
$search = $conn->real_escape_string($search);

// Build WHERE clause
$conditions = [];

if (!$isAdmin) {
    $conditions[] = "al.warehouse IN ($user_warehouse_id)";
}

if (!empty($search)) {
    $conditions[] = "(
        al.audit_num LIKE '%$search%' OR
        w.warehouse_name LIKE '%$search%' OR
        CONCAT(u.user_fname, ' ', u.user_lname) LIKE '%$search%' OR
        CONCAT(u2.user_fname, ' ', u2.user_lname) LIKE '%$search%' OR
        al.audit_status LIKE '%$search%'
    )";
}

$where = "";
if (!empty($conditions)) {
    $where = "WHERE " . implode(" AND ", $conditions);
}

// ==============================
// GET TOTAL COUNT
// ==============================
$count_query = "
SELECT COUNT(*) AS total
FROM audit_logs al
LEFT JOIN users u
    ON al.created_by = u.hashed_id COLLATE utf8mb4_unicode_ci
LEFT JOIN users u2
    ON al.updated_by = u2.hashed_id COLLATE utf8mb4_unicode_ci
LEFT JOIN warehouse w
    ON al.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci
$where
";

$count_result = $conn->query($count_query);
$total = $count_result->fetch_assoc()['total'];

// ==============================
// GET DATA
// ==============================
$data_query = "
SELECT
    al.*,
    u.user_fname,
    u.user_lname,
    u2.user_fname AS updater_fname,
    u2.user_lname AS updater_lname,
    w.warehouse_name
FROM audit_logs al
LEFT JOIN users u
    ON al.created_by = u.hashed_id COLLATE utf8mb4_unicode_ci
LEFT JOIN users u2
    ON al.updated_by = u2.hashed_id COLLATE utf8mb4_unicode_ci
LEFT JOIN warehouse w
    ON al.warehouse = w.hashed_id COLLATE utf8mb4_unicode_ci
$where
ORDER BY al.id DESC
LIMIT $limit OFFSET $offset
";

$data_result = $conn->query($data_query);

$rows = [];

while ($row = $data_result->fetch_assoc()) {
    $rows[] = $row;
}

header('Content-Type: application/json');
echo json_encode([
    'data'  => $rows,
    'total' => $total
]);

$conn->close();
?>