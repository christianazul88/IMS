<?php
session_start();

// Check if session variable exists
if (!isset($_SESSION['stored_data']) || empty($_SESSION['stored_data'])) {
    echo "<tr><td class='text-center' colspan='5'>No Data Yet!</td></tr>";
    exit;
}
?>

    
            <?php foreach ($_SESSION['stored_data'] as $item): ?>
                <tr>
                    <td>
                        <button class="btn text-danger btn-sm delete-session-item" type="button" data-barcode="<?= htmlspecialchars($item['unique_barcode']) ?>"><span class="far fa-window-close"></span></button>
                        <input type='checkbox' name='product_id[]' value='<?php echo $item['product_id']; ?>' checked='' hidden>
                        <input type='checkbox' name='product_image[]' value='<?php echo basename($item['image']);?>' checked='' hidden>
                        <input type='checkbox' name='product_desc[]' value='<?php echo $item['description'];?>' checked='' hidden>
                        <input type='checkbox' name='parent_barcode[]' value='<?php echo $item['unique_barcode']; ?>' checked='' hidden>
                        <input type='checkbox' name='brand[]' value='<?php echo $item['brand_name']; ?>' checked='' hidden>
                        <input type='checkbox' name='category[]' value='<?php echo $item['category_name']; ?>' checked='' hidden>
                    </td>
                    <td><?= htmlspecialchars($item['unique_barcode']) ?></td>
                    <td><?= htmlspecialchars($item['description']) ?></td>
                    <td><?= htmlspecialchars($item['brand_name']) ?></td>
                    <td><?= htmlspecialchars($item['category_name']) ?></td>
                </tr>
            <?php endforeach; ?>
        

