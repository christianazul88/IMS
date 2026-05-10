<?php
header('Content-Type: application/json');

include "../config/database.php";
include "../config/on_session.php";

$notifications = [];
$notification1 = "SELECT
                    p.product_img,
                    p.description,
                    p.safety,
                    p.hashed_id,
                    b.brand_name,
                    c.category_name
                  FROM product p
                  LEFT JOIN brand b ON b.hashed_id = p.brand
                  LEFT JOIN category c ON c.hashed_id = p.category";
$result1 = $conn->query($notification1);

if ($result1->num_rows > 0) {
    while ($row1 = $result1->fetch_assoc()) {
        $not_product_description = $row1['description'];
        $not_product_img = $row1['product_img'] ?? '../../assets/img/def_img.png';
        $not_product_id = $row1['hashed_id'];
        $not_brand_name = $row1['brand_name'];
        $not_category_name = $row1['category_name'];

        $notification2 = "SELECT
                            s.item_status,
                            s.date,
                            s.unique_barcode,
                            w.warehouse_name
                          FROM stocks s
                          LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
                          WHERE s.warehouse IN ($user_warehouse_id)
                          AND s.product_id = '$not_product_id'";
        $result2 = $conn->query($notification2);

        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $not_date_added = $row2['date'];
                $not_item_status = $row2['item_status'];
                $not_barcode = $row2['unique_barcode'];
                $not_dateTime = new DateTime($not_date_added);
                $not_now = new DateTime();
                $formatted_date_added = $not_dateTime->format('F j, Y');
                $not_interval = $not_dateTime->diff($not_now);
                $not_totalMonths = ($not_interval->y * 12) + $not_interval->m;
                $link = "../Product-info/?prod=" . $not_barcode;
                if ($not_totalMonths >= 1) {
                    $monthsString = ($not_totalMonths === 1) ? '1 month' : "{$not_totalMonths} months";
                    if ($not_item_status == 0 || $not_item_status == 2 || $not_item_status == 3) {
                        $notifications[] = [
                            'title' => "System",
                            'message' => "The product '{$not_product_description}' (Brand: {$not_brand_name}, Category: {$not_category_name}) has been in our store for over {$monthsString} since {$formatted_date_added}.",
                            'imageUrl' => $not_product_img,
                            'linkUrl' => $link
                        ];
                    }
                }
            }
        }
    }
}

echo json_encode($notifications);
?>
