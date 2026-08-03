<?php
session_start(); // Ensure session is started

// Retrieve and decode the scanned items from the session
$existingData = isset($_SESSION['scanned_void']) ? json_decode($_SESSION['scanned_void'], true) : [];

// Check if data exists and is an array
if (!is_array($existingData)) {
    $existingData = []; // Default to an empty array if decoding failed
}

// If no items were found, show a message and exit
if (empty($existingData)) {
?>
<table class="table table-bordered bordered-table table-sm table-stripe">
    <thead class="table-info">
        <tr>
            <th></th>
            <th>Description</th>
            <th>Barcode</th>
            <th>Brand</th>
            <th>Category</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="text-center p-6 fs-3" colspan="5"><b>NO BARCODE WERE SCANNED YET!</b></td>
        </tr>
    </tbody>
</table>
<?php
    exit;
}
?>

<table class="table table-sm table-striped fs-10 mb-0 overflow-auto">
    <thead class="table-info">
        <tr>
            <th></th>
            <th>Description</th>
            <th>Barcode</th>
            <th>Brand</th>
            <th>Category</th>
            <th>Currently on Warehouse</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($existingData as $item): ?>
            <tr>
                <td>
                    <button class="btn text-danger btn-sm delete-session-item" type="button" data-barcode="<?= htmlspecialchars($item['unique_barcode']) ?>"><span class="far fa-window-close"></span></button>
                </td>
                <td><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($item['unique_barcode'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($item['brand_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($item['warehouse_name'] ?? 'N/A'); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
