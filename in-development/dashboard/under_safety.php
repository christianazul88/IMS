<?php 
if(empty($dashboard_wh)){
    $under_safety_query = "
    SELECT 
        COUNT(s.unique_barcode) AS available_qty,
        s.safety,
        p.description,
        b.brand_name,
        c.category_name,
        w.warehouse_name
    FROM stocks s
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
    WHERE s.item_status IN (0, 3)
    AND s.warehouse IN ($imploded_warehouse_ids)
    GROUP BY 
        s.product_id, 
        s.parent_barcode, 
        p.description, 
        b.brand_name, 
        c.category_name,
        s.safety,
        w.warehouse_name
    HAVING COUNT(s.unique_barcode) < s.safety
    LIMIT 10
    ";
} else {
    $under_safety_query = "
    SELECT 
        COUNT(s.unique_barcode) AS available_qty,
        s.safety,
        p.description,
        b.brand_name,
        c.category_name,
        w.warehouse_name
    FROM stocks s
    LEFT JOIN product p ON p.hashed_id = s.product_id
    LEFT JOIN brand b ON b.hashed_id = p.brand
    LEFT JOIN category c ON c.hashed_id = p.category
    LEFT JOIN warehouse w ON w.hashed_id = s.warehouse
    WHERE s.item_status IN (0, 3)
    AND s.warehouse = '$dashboard_wh'
    GROUP BY 
        s.product_id, 
        s.parent_barcode, 
        p.description, 
        b.brand_name, 
        c.category_name,
        s.safety,
        s.warehouse
    HAVING COUNT(s.unique_barcode) < s.safety
    LIMIT 10
    ";
}


$under_safety_res = $conn->query($under_safety_query);

if ($under_safety_res->num_rows > 0) {
?>


<div class="col-lg-12 mb-3">
    <div class="accordion" id="undersafetyitems">
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading4"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#undersafety" aria-expanded="true" aria-controls="collapse4">Under Safety Items</button></h2>
            <div class="accordion-collapse collapse" id="undersafety" aria-labelledby="heading4" data-bs-parent="#accordionExample">
                <div class="accordion-body">
                    <table class="table mb-0 data-table fs-10" data-datatables="data-datatables">
                        <thead>
                            <tr>
                                <th>DESCRIPTION</th>
                                <th>BRAND</th>
                                <th>CATEGORY</th>
                                <th>WAREHOUSE</th>
                                <th>QUANTITY</th>
                                <th>SAFETY</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            while ($row = $under_safety_res->fetch_assoc()) {
                                // Assign values to variables for easier readability and reuse
                                $description     = $row['description'];
                                $brand_name      = $row['brand_name'];
                                $category_name   = $row['category_name'];
                                $warehouse_name  = $row['warehouse_name'];
                                $available_qty   = $row['available_qty'];
                                $safety          = $row['safety'];
                            ?>
                            <tr>
                                <td><?php echo $description; ?></td>
                                <td><?php echo $brand_name; ?></td>
                                <td><?php echo $category_name; ?></td>
                                <td><?php echo $warehouse_name; ?></td>
                                <td><?php echo $available_qty; ?></td>
                                <td><?php echo $safety; ?></td>
                            </tr>
                            <?php 
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php 
}
?>
