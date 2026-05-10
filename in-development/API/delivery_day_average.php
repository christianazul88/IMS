<?php
header("Content-Type: application/json");
include "../config/database.php";
include "../config/on_session.php";

// Get the date 3 months ago from today
$three_months_ago = date("Y-m-d", strtotime("-3 months"));

$sql = "SELECT
            s.local_international AS supplier_type,
            po.date_order,
            po.date_received
        FROM purchased_order po
        LEFT JOIN supplier s ON s.hashed_id = po.supplier
        WHERE po.date_order >= '$three_months_ago'"; // Only get data from the past 3 months

$res = $conn->query($sql);

if ($res->num_rows > 0) {
    $shipment_data = [
        'local' => ['total_days' => 0, 'count' => 0],
        'international' => ['total_days' => 0, 'count' => 0]
    ];

    while ($row = $res->fetch_assoc()) {
        $supplier_type = strtolower($row['supplier_type']); // Ensure consistent lowercase keys
        $date_order = strtotime($row['date_order']);
        $date_received = strtotime($row['date_received']);

        // Calculate shipment days
        if ($date_order && $date_received && $date_received >= $date_order) {
            $shipment_days = ($date_received - $date_order) / (60 * 60 * 24); // Convert seconds to days

            if (isset($shipment_data[$supplier_type])) {
                $shipment_data[$supplier_type]['total_days'] += $shipment_days;
                $shipment_data[$supplier_type]['count']++;
            }
        }
    }

    // Prepare final JSON response
    $data = [];
    foreach ($shipment_data as $type => $values) {
        if ($values['count'] > 0) {
            $average_days = $values['total_days'] / $values['count'];
            $data[] = [
                "supplier_type" => $type,
                "average_shipment_days" => round($average_days, 1) // Round to 1 decimal place
            ];
        }
    }

    echo json_encode($data);
} else {
    echo json_encode([]);
}
