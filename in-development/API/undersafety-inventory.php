<?php
include "../config/database.php";
include "../config/on_session.php";

$api = [];

$query = "SELECT hashed_id, warehouse_name FROM warehouse WHERE hashed_id IN ($user_warehouse_id)";
$result = $conn->query($query);
if($result->num_rows>0){
    while($row = $result->fetch_assoc()){
        $notification_Warehouse_id = $row['hashed_id'];
        $notification_warehouse_name = $row['warehouse_name'];
        $BELOW_SAFETY = 0;
        // -------------------------------------------------------
        $sql = "SELECT 
                SUM(CASE WHEN s.item_status IN (0, 2, 3) THEN 1 ELSE 0 END) AS quantity,
                p.safety AS safety
            FROM stocks s
            LEFT JOIN product p ON p.hashed_id = s.product_id
            WHERE s.warehouse  = '$notification_Warehouse_id'
            GROUP BY s.product_id, s.warehouse";
        $res = $conn->query($sql);

        if($res->num_rows>0){
            while($row=$res->fetch_assoc()){
                $quantity = $row['quantity'];
                $safety = $row['safety'];
      
                if($quantity<=$safety){
                    $BELOW_SAFETY ++;
                }
            }
        }
        // -------------------------------------------------------
        
        $api[] = [
            'warehouse' => $notification_warehouse_name,
            'quantity' => $BELOW_SAFETY
        ];
    }
}

echo json_encode($notifications);
