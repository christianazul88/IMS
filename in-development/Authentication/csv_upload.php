<?php
function normalize($str) {
    return strtoupper(trim($str));
}

$rows = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['file']['tmp_name'];
        if (($handle = fopen($fileTmpPath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            if ($headers) {
                while (($row = fgetcsv($handle)) !== false) {
                    $rows[] = array_map('normalize', $row);
                }
            } else {
                $error = 'CSV file is empty or unreadable.';
            }
            fclose($handle);
        } else {
            $error = 'Could not open uploaded file.';
        }
    } else {
        $error = 'File upload error.';
    }
} else {
    $error = 'No file uploaded.';
}
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php elseif (!empty($rows)): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ORDER NUMBER</th>
                    <th>ORDER LINE ID</th>
                    <th>WAREHOUSE</th>
                    <th>CLIENT</th>
                    <th>FULFILLMENT STATUS</th>
                    <th class="text-end">AMOUNT PAID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                        $status = $row[4]; // Fulfillment Status
                        $class = '';
                        if ($status === 'PAID') $class = 'table-success';
                    ?>
                    <tr class="<?= $class ?>">
                        <td><?= $row[0] ?></td>
                        <td><?= $row[1] ?></td>
                        <td><?= $row[2] ?></td>
                        <td><?= $row[3] ?></td>
                        <td><?= $row[4] ?></td>
                        <td class="text-end"><?= $row[5] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
