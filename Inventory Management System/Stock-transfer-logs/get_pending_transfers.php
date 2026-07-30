<?php
include "../config/database.php";
include "../config/on_session.php";

header('Content-Type: application/json');

$sql = "
    SELECT id
    FROM stock_transfer
    WHERE date_out <= DATE_SUB(NOW(), INTERVAL 3 DAY)
        AND status = 'pending'
    GROUP BY id
    ORDER BY id DESC
";

$result = $conn->query($sql);

$ids = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int)$row['id'];
    }
}

echo json_encode([
    'count' => count($ids),
    'ids'   => $ids,
]);
