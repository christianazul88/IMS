<?php
header("Content-Type: application/json");
include "../config/database.php";
include "../config/on_session.php";

$response = [];

// First API: Fetch product details
$sql = "SELECT p.hashed_id AS product_id, 
               p.description, 
               b.brand_name, 
               c.category_name, 
               ol.date_sent, 
               ol.order_num, 
               ol.order_line_id, 
               p.parent_barcode, 
               w.warehouse_name, 
               w.hashed_id AS warehouse_id, 
               oc.unique_barcode 
        FROM outbound_content oc
        LEFT JOIN outbound_logs ol ON ol.hashed_id = oc.hashed_id
        LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
        LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
        LEFT JOIN product p ON p.hashed_id = s.product_id
        LEFT JOIN brand b ON b.hashed_id = p.brand
        LEFT JOIN category c ON c.hashed_id = p.category";

$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        foreach ($result as $row) {
            $product_id = $row['product_id'];
            $date_sent = $row['date_sent'] ? date("Y-m-d", strtotime($row['date_sent'])) : null;
            $warehouse_id = $row['warehouse_id'];

            // Check if product_id, date_sent, and warehouse_id already exist in the array
            $exists = false;
            foreach ($response as &$item) {
                if ($item['product_id'] === $product_id && 
                    $item['date_sent'] === $date_sent && 
                    $item['warehouse_id'] === $warehouse_id) {
                    $item['daily_usage'] += 1; // Increment count
                    $exists = true;
                    break;
                }
            }

            // If not exists, add a new entry
            if (!$exists) {
                $response[] = [
                    "product_id" => $product_id,
                    "product_description" => $row['description'],
                    "brand_name" => $row['brand_name'],
                    "category_name" => $row['category_name'],
                    "date_sent" => $date_sent,
                    "order_num" => $row['order_num'],
                    "order_line_id" => $row['order_line_id'],
                    "parent_barcode" => $row['parent_barcode'],
                    "warehouse_name" => $row['warehouse_name'],
                    "warehouse_id" => $warehouse_id,
                    "unique_barcode" => $row['unique_barcode'],
                    "daily_usage" => 1, // Initial count
                    "shipment_data" => [] // Placeholder for shipment data
                ];
            }
        }
    }
}

// Second API: Fetch shipment data
$three_months_ago = date("Y-m-d", strtotime("-3 months"));
$sql_shipment = "SELECT
                    s.local_international AS supplier_type,
                    po.date_order,
                    po.date_received
                FROM purchased_order po
                LEFT JOIN supplier s ON s.hashed_id = po.supplier
                WHERE po.date_order >= '$three_months_ago'";

$res_shipment = $conn->query($sql_shipment);
$shipment_data = ['local' => ['total_days' => 0, 'count' => 0], 'international' => ['total_days' => 0, 'count' => 0]];

if ($res_shipment->num_rows > 0) {
    while ($row = $res_shipment->fetch_assoc()) {
        $supplier_type = strtolower($row['supplier_type']); // Normalize keys
        $date_order = strtotime($row['date_order']);
        $date_received = strtotime($row['date_received']);

        if ($date_order && $date_received && $date_received >= $date_order) {
            $shipment_days = ($date_received - $date_order) / (60 * 60 * 24); // Convert seconds to days
            if (isset($shipment_data[$supplier_type])) {
                $shipment_data[$supplier_type]['total_days'] += $shipment_days;
                $shipment_data[$supplier_type]['count']++;
            }
        }
    }
}

// Calculate average shipment days
$shipment_averages = [];
foreach ($shipment_data as $type => $values) {
    if ($values['count'] > 0) {
        $shipment_averages[$type] = round($values['total_days'] / $values['count'], 1);
    }
}

// Merge shipment data into the response
foreach ($response as &$item) {
    foreach ($shipment_averages as $supplier_type => $average_days) {
        $item['shipment_data'][] = [
            "supplier_type" => $supplier_type,
            "lead_time" => $average_days
        ];
    }
}

// Output final JSON response
if (!empty($response)) {
    echo json_encode(["status" => "success", "data" => $response], JSON_PRETTY_PRINT);
} else {
    echo json_encode(["status" => "error", "message" => "No records found"], JSON_PRETTY_PRINT);
}

$conn->close();
?>
