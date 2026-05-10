<?php
include "../config/database.php";

// Set header to return JSON
header('Content-Type: application/json');

$sql = "SELECT ol.hashed_id, ol.date_sent, w.warehouse_name 
        FROM outbound_logs ol 
        LEFT JOIN warehouse w ON w.hashed_id = ol.warehouse 
        ORDER BY ol.date_sent ASC";

$res = $conn->query($sql);

if (!$res) {
    die(json_encode(["error" => "SQL Error (outbound_logs): " . $conn->error]));
}

$outbound_data = [];

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $outbound_id = $row['hashed_id'];
        $outbound_warehouse = $row['warehouse_name'];
        $outbound_out = $row['date_sent'];

        $query = "SELECT 
                    s.unique_barcode,
                    p.description,
                    b.brand_name,
                    c.category_name,
                    oc.sold_price,
                    p.id AS product_id,
                    s.capital
                  FROM outbound_content oc
                  LEFT JOIN stocks s ON s.unique_barcode = oc.unique_barcode
                  LEFT JOIN product p ON p.hashed_id = s.product_id
                  LEFT JOIN brand b ON b.hashed_id = p.brand
                  LEFT JOIN category c ON c.hashed_id = p.category
                  WHERE oc.hashed_id = '$outbound_id'";

        $result = $conn->query($query);

        if (!$result) {
            die(json_encode(["error" => "SQL Error (outbound_content): " . $conn->error]));
        }

        $products = [];
        while ($prod = $result->fetch_assoc()) {
            $products[] = [
                "barcode" => $prod['unique_barcode'] ?? "N/A",
                "description" => $prod['description'] ?? "N/A",
                "brand_name" => $prod['brand_name'] ?? "N/A",
                "category_name" => $prod['category_name'] ?? "N/A",
                "sold_price" => $prod['sold_price'] ?? "0.00",
                "product_id" => $prod['product_id'] ?? "0",
                "capital" => $prod['capital'] ?? "0.00"
            ];
        }

        $outbound_data[] = [
            "outbound_id" => $outbound_id,
            "warehouse" => $outbound_warehouse ?? "Unknown",
            "outbound_date" => $outbound_out ?? "0000-00-00 00:00:00",
            "products" => $products
        ];
    }
}

// Encode as JSON safely
echo json_encode($outbound_data, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE);
?>
