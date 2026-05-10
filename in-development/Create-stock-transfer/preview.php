<?php
session_start(); // Ensure session is started

// Retrieve and decode the scanned items from the session
$existingData = isset($_SESSION['scanned_item']) ? json_decode($_SESSION['scanned_item'], true) : [];

// Check if data exists and is an array
if (!is_array($existingData)) {
    $existingData = []; // Default to an empty array if decoding failed
}

// Group items by parent_barcode and count the quantity for each group
$groupedData = [];

foreach ($existingData as $item) {
    if (is_array($item) && isset($item['parent_barcode'])) {
        $parentBarcode = $item['parent_barcode'];

        // If this parent_barcode has not been grouped yet, initialize it
        if (!isset($groupedData[$parentBarcode])) {
            $groupedData[$parentBarcode] = [
                'item' => $item,  // Store the first item in this group
                'quantity' => 0    // Initialize quantity count
            ];
        }

        // Increment quantity for the current parent_barcode group
        $groupedData[$parentBarcode]['quantity']++;
    }
}

// If no items were found or grouped, show a message and exit
if (empty($groupedData)) {
?>
<div class="table-responsive scrollbar">
<table class="table table-bordered bordered-table table-sm table-stripe">
        <thead class="table-info">
            <tr>
                <th>Parent Barcode</th>
                <th>Description</th>
                <th>Brand</th>
                <th>Category</th>
                <th class="text-end">Quantity</th>
            </tr>
        </thead>
        <tbody>
                <tr>
                    <td class="text-center p-6 fs-3" colspan="5">
                        <b>NO BARCODE WERE SCANNED YET!</b>
                        <input type="text" name="panggulo" required hidden>
                    </td>
                </tr>
        </tbody>
    </table>
</div>

<?php
    exit;
}
?>


<?php if (!empty($groupedData)): ?>
    
            <?php foreach ($existingData as $item): ?>
                <tr>
                    <td>
                        <button class="btn text-danger btn-sm delete-session-item" type="button" data-barcode="<?= htmlspecialchars($item['unique_barcode']) ?>"><span class="far fa-window-close"></span></button>
                    </td>
                    <td><?php echo htmlspecialchars($item['unique_barcode']); ?></td>
                    <td><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($item['brand_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                </tr>
            <?php endforeach; ?>
<?php else: ?>
<?php endif; ?>