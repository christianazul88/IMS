<?php
include "../config/database.php";

// Get the start and end dates of the current week (Sunday to Saturday)
$startOfWeek = date('Y-m-d 00:00:00', strtotime('last sunday'));
$endOfWeek = date('Y-m-d 23:59:59', strtotime('next saturday'));

// Query to get daily sales totals
$sql = "
    SELECT 
        DAYNAME(o.date_sent) AS day,
        SUM(oc.sold_price) AS total_sales
    FROM outbound_logs o
    JOIN outbound_content oc ON o.id = oc.outbound_log_id
    WHERE o.date_sent BETWEEN '$startOfWeek' AND '$endOfWeek'
    GROUP BY DAYOFWEEK(o.date_sent)
    ORDER BY DAYOFWEEK(o.date_sent);
";

$result = $conn->query($sql);

$dailySales = [
    'Sunday' => 0,
    'Monday' => 0,
    'Tuesday' => 0,
    'Wednesday' => 0,
    'Thursday' => 0,
    'Friday' => 0,
    'Saturday' => 0
];

// Populate sales data
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dailySales[$row['day']] = (float)$row['total_sales'];
    }
}

// Return data as JSON
echo json_encode(['daily_sales' => $dailySales]);

// Close connection
$conn->close();
?>