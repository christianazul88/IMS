<?php
$num =1;
if (isset($_SESSION['unique_key'])) {
    $unique_key = $_SESSION['unique_key'];

    $query = "
        SELECT 
            s.capital, 
            p.safety, 
            s.batch_code, 
            s.parent_barcode, 
            s.unique_barcode,
            sup.supplier_name,
            p.keyword, 
            p.description, 
            b.brand_name, 
            c.category_name
        FROM stocks s
        LEFT JOIN product p ON s.product_id = p.hashed_id
        LEFT JOIN brand b ON p.brand = b.hashed_id
        LEFT JOIN category c ON p.category = c.hashed_id
        LEFT JOIN supplier sup ON s.supplier = sup.hashed_id
        WHERE s.unique_key = '$unique_key'
        ORDER BY s.id ASC
    ";

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
?>
<tr>
    <td>
        <?php echo "#" . $num; $num++;?>
    </td>
    <td>
        <?php echo $row['description']; ?>
    </td>
    <td>
        <?php echo $row['keyword']; ?>
    </td>
    <td>
        <?php echo $row['capital']; ?>
    </td>
    <td>
        <?php echo $row['supplier_name']; ?>
    </td>
    <td>
        <?php echo $row['unique_barcode']; ?>
    </td>
    <td>
        <?php echo $row['batch_code']; ?>
    </td>
    <td>
        <?php echo $row['brand_name']; ?>
    </td>
    <td>
        <?php echo $row['category_name']; ?>
    </td>
    
    <td>
        <?php echo $row['safety']; ?>
    </td>
    <td>
        <input type="text" name="unique_barcode[]" value="<?php echo $row['unique_barcode']?>" hidden>
        <select name="item_location[]" class="form-select js-choice" id="" size="1" name="organizerSingle" data-options='{"removeItemButton":true,"placeholder":true}'>
            <option value="">Select Item Location</option>
            <option value="na">None</option>
            <?php 
            $item_loc_query = "SELECT * FROM item_location WHERE warehouse = '$selected_warehouse_SIL' ORDER BY location_name";
            $loc_result = $conn->query($item_loc_query);
            if ($loc_result->num_rows > 0) {
                while ($loc_row = $loc_result->fetch_assoc()) {
                    echo '<option value="' . $loc_row['id'] . '">' . $loc_row['location_name'] . '</option>';
                }
            } else {
                echo '<option value="">No data</option>';
            }
            ?>
        </select>
    </td>
</tr>
<?php
        }
    } else {
        echo "<tr><td colspan='12'>No records found</td></tr>";
    }
}
?>
