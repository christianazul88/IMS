<?php
session_start();

if (isset($_SESSION['po_list']) && !empty($_SESSION['po_list'])) {
    foreach ($_SESSION['po_list'] as $item) {
        ?>
        <tr class="sortable-item">
            <td>
                <button class="btn btn-transparent fs-11 py-0 px-2 delete-btn" target-id="<?php echo $item['id'];?>" type="button"><span class="fas fa-window-close"></span></button>
            </td>
            <th class="align-middle fs-11 desc"><?php echo htmlspecialchars($item['description']); ?></th>
            <th class="align-middle fs-11 barcode"><?php echo htmlspecialchars($item['barcode']); ?></th>
            <td class="align-middle fs-11 brand"><?php echo htmlspecialchars($item['brand']); ?></td>
            <td class="align-middle fs-11 cat"><?php echo htmlspecialchars($item['category']); ?></td>
            <td class="align-middle fs-11 cat" hidden>
                <input type="text" name="product_id[]" value="<?php echo htmlspecialchars($item['id']);?>" hidden>
                <input type="text" name="parent_barcode[]" value="<?php echo htmlspecialchars($item['barcode']);?>" hidden>
                <input type="text" name="product_desc[]" value="<?php echo htmlspecialchars($item['description']) ;?>" hidden>
                <input type="text" name="brand[]" value="<?php echo htmlspecialchars($item['brand']);?>" hidden>
                <input type="text" name="category[]" value="<?php echo htmlspecialchars($item['category']);?>" hidden>
            </td>
            <td class="align-middle fs-11 cat table-primary">
                <input type="number" name="order_qty[]" class="form-control bg-danger fs-11 text-white" min="0" placeholder="Order Qty">
            </td>
            <td class="align-middle fs-11 white-space-nowrap text-end pe-3 qty">N/A</td>
        </tr>
        <?php
    }
} else {
    echo "<tr><td colspan='5' class='text-center'>No products found.</td></tr>";
}
?>
