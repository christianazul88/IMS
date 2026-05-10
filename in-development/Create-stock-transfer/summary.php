<?php
session_start(); // Ensure session is started

// Retrieve and decode the scanned items from the session
$existingData = isset($_SESSION['scanned_item']) ? json_decode($_SESSION['scanned_item'], true) : [];

// Check if data exists and is an array
if (!is_array($existingData)) {
    $existingData = []; // Default to an empty array if decoding failed
}

// Group items by description, brand_name, and category_name
$groupedData = [];

foreach ($existingData as $item) {
    if (
        is_array($item) &&
        isset($item['description'], $item['brand_name'], $item['category_name'])
    ) {
        $groupKey = $item['description'] . '|' . $item['brand_name'] . '|' . $item['category_name'];

        if (!isset($groupedData[$groupKey])) {
            $groupedData[$groupKey] = [
                'description' => $item['description'],
                'brand_name' => $item['brand_name'],
                'category_name' => $item['category_name'],
                'quantity' => 0
            ];
        }

        $groupedData[$groupKey]['quantity']++;
    }
}

// If no items were found or grouped, show a message and exit
if (empty($groupedData)) {
?>
<div class="table-responsive scrollbar">
    <table class="table table-bordered bordered-table table-sm table-stripe">
        <thead class="table-info">
            <tr>
                <th>Description</th>
                <th>Brand</th>
                <th>Category</th>
                <th class="text-end">Quantity</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center p-6 fs-3" colspan="4"><b>NO BARCODE WERE SCANNED YET!</b></td>
            </tr>
        </tbody>
    </table>
</div>
<?php
    exit;
}
?>

<!-- Display grouped data -->
<div class="table-responsive scrollbar">
    <table class="table table-bordered table-sm table-striped">
        <thead class="table-info">
            <tr>
                <th class="fs-11">Description</th>
                <th class="fs-11">Brand</th>
                <th class="fs-11">Category</th>
                <th class="text-end fs-11 ">Quantity</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groupedData as $group): ?>
            <tr>
                <td class="fs-11"><?php echo htmlspecialchars($group['description']); ?></td>
                <td class="fs-11"><?php echo htmlspecialchars($group['brand_name']); ?></td>
                <td class="fs-11"><?php echo htmlspecialchars($group['category_name']); ?></td>
                <td class="text-end fs-11 "><?php echo $group['quantity']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
