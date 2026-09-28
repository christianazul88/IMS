<?php
/*
 * Server-rendered dashboard widget endpoint.
 *
 * Only the SQL-heavy cards use this endpoint. The inventory section remains a
 * direct include in content.php, and the existing widget files stay unchanged.
 */
require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Authentication required.');
}

$widgetFiles = [
    'inventory_health' => [
        'file' => 'inventory_health.php',
        'allowed' => static function (string $access, string $position): bool {
            return $position === 'Superadmin'
                || strpos($access, 'outbound_safety_available') !== false
                || strpos($access, 'under_safety') !== false;
        },
    ],
    'promotion' => [
        'file' => 'promotion.php',
        'allowed' => static function (string $access, string $position): bool {
            return $position === 'Superadmin' || strpos($access, 'promotion') !== false;
        },
    ],
    'under_safety' => [
        'file' => 'under_safety.php',
        'allowed' => static function (string $access, string $position): bool {
            return $position === 'Superadmin' || strpos($access, 'under_safety') !== false;
        },
    ],
    'revenue_dropping' => [
        'file' => 'revenue_dropping.php',
        'allowed' => static function (string $access, string $position): bool {
            return $position === 'Superadmin' || strpos($access, 'revenue_drop') !== false;
        },
    ],
];

$widget = (string) ($_GET['widget'] ?? '');
if (!isset($widgetFiles[$widget])) {
    http_response_code(404);
    exit('Unknown dashboard widget.');
}

$access = (string) ($_SESSION['access'] ?? '');
$user_position_name = (string) ($_SESSION['position_name'] ?? '');
if (!$widgetFiles[$widget]['allowed']($access, $user_position_name)) {
    http_response_code(403);
    exit('Dashboard widget not available.');
}

$user_id = (string) $_SESSION['user_id'];
$user_fullname = (string) ($_SESSION['full_name'] ?? '');
$user_fname = (string) ($_SESSION['first_name'] ?? '');
$user_lname = (string) ($_SESSION['last_name'] ?? '');
$user_email = (string) ($_SESSION['email'] ?? '');

$user_warehouse_ids = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) ($_SESSION['warehouse_ids'] ?? ''))
), static fn($id) => $id !== ''));

$quoted_warehouse_ids = array_map(
    static fn($id) => "'" . mysqli_real_escape_string($conn, $id) . "'",
    $user_warehouse_ids
);
$imploded_warehouse_ids = $quoted_warehouse_ids
    ? implode(',', $quoted_warehouse_ids)
    : "''";
$user_warehouse_id = $imploded_warehouse_ids;

$dashboard_wh = trim((string) ($_GET['wh'] ?? ''));
if ($dashboard_wh !== '' && !in_array($dashboard_wh, $user_warehouse_ids, true)) {
    http_response_code(403);
    exit('Warehouse is not available.');
}

$warehouse_options2 = [];
$warehouse_dropdow_dashboard = [];
$warehouse_stmt = $conn->prepare(
    'SELECT hashed_id, warehouse_name FROM warehouse WHERE hashed_id = ? LIMIT 1'
);
foreach ($user_warehouse_ids as $warehouse_id) {
    $warehouse_stmt->bind_param('s', $warehouse_id);
    $warehouse_stmt->execute();
    $warehouse_row = $warehouse_stmt->get_result()->fetch_assoc();
    if (!$warehouse_row) {
        continue;
    }

    $safe_id = htmlspecialchars($warehouse_row['hashed_id'], ENT_QUOTES, 'UTF-8');
    $safe_name = htmlspecialchars($warehouse_row['warehouse_name'], ENT_QUOTES, 'UTF-8');
    $selected = $dashboard_wh === $warehouse_row['hashed_id'] ? ' selected' : '';
    $warehouse_options2[] = '<option value="' . $safe_id . '"' . $selected . '>' . $safe_name . '</option>';
    $warehouse_dropdow_dashboard[] = '<a class="dropdown-item" href="../dashboard/?wh='
        . rawurlencode($warehouse_row['hashed_id'])
        . '&wha=' . rawurlencode($warehouse_row['warehouse_name'])
        . '">' . $safe_name . '</a>';
}
$warehouse_stmt->close();

$date_today = date('M j, Y');
$date_for_file = date('M j Y');
$hour = (int) date('G');
$time_name = $hour >= 5 && $hour < 12
    ? 'Morning'
    : ($hour >= 12 && $hour < 17 ? 'Afternoon' : ($hour >= 17 && $hour < 21 ? 'Evening' : 'Midnight'));

require __DIR__ . '/' . $widgetFiles[$widget]['file'];
