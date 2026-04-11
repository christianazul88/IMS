<?php
include "../config/database.php";
include "../config/on_session.php";

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
    while ($row1 = $result1->fetch_assoc()) { // Corrected from $result to $result1
        $not_product_description = $row1['description'];
        $not_product_img = $row['product_img'] ?? '../../assets/img/def_img.png';
        $not_safety = $row1['safety'];
        $not_product_id = $row1['hashed_id'];
        $not_brand_name = $row1['brand_name'];
        $not_category_name = $row1['category_name'];


        // Query to fetch stock information
        $notification2 = "SELECT
                            s.item_status,
                            s.date,
                            w.warehouse_name
                          FROM stocks s
                          LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
                          WHERE s.warehouse IN ($user_warehouse_id)
                          AND s.product_id = '$not_product_id'";
        $result2 = $conn->query($notification2);

        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) { // Corrected from $result to $result2
                $not_item_status = $row2['item_status'];
                $not_date_added = $row2['date'];
                $not_warehouse = $row2['warehouse_name'];

                // Convert the input date into a DateTime object
                $not_dateTime = new DateTime($not_date_added);
                $not_now = new DateTime(); // Get the current date and time

                // Format the date to "January 1, 2020"
                $formatted_date_added = $not_dateTime->format('F j, Y');

                // Calculate the difference between the dates
                $not_interval = $not_dateTime->diff($not_now);

                // Get the total months difference
                $not_totalMonths = ($not_interval->y * 12) + $not_interval->m;

                // Generate notifications based on total months
                if ($not_totalMonths >= 1 && $not_totalMonths < 2) {
                    if ($not_item_status == 0 || $not_item_status == 2 || $not_item_status == 3) {
                        echo "<br>Product_image: " . $not_product_img .  "<br>Description: $not_product_description<br>Brand: $not_brand_name<br>Category: $not_category_name<br>is now more than 1 month on our store since $formatted_date_added";
                    }
                } elseif ($not_totalMonths >= 2 && $not_totalMonths < 3) {
                    if ($not_item_status == 0 || $not_item_status == 2 || $not_item_status == 3) {
                        echo "<br>Product_image: " . $not_product_img .  "<br>Description: $not_product_description<br>Brand: $not_brand_name<br>Category: $not_category_name<br>is now more than 2 months on our store since $formatted_date_added";
                    }
                } elseif ($not_totalMonths >= 3) {
                    if ($not_item_status == 0 || $not_item_status == 2 || $not_item_status == 3) {
                        echo "<br>Product_image: " . $not_product_img .  "<br>Description: $not_product_description<br>Brand: $not_brand_name<br>Category: $not_category_name<br>Warehouse: $not_warehouse<br>is now more than 3 months on our store since $formatted_date_added";
                    }
                }

            }
        }
    }
}
?>
