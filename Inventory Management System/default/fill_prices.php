<?php
/*
 * fill_prices.php
 *
 * Upload a CSV containing:
 *   column 0 = description
 *   column 1 = barcode
 *   column 2 = price
 *
 * The script looks up:
 *   SELECT capital FROM stocks WHERE unique_barcode = ? LIMIT 1
 *
 * If the barcode is not found:
 *   price = "not found on database"
 *
 * The completed CSV is downloaded automatically.
 */

// =========================
// DATABASE CONFIGURATION
// =========================
$host = 'localhost';
$db   = 'lpo_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// =========================
// CSV CONFIGURATION
// =========================
$barcodeColumnIndex = 1; // column[1] = barcode
$priceColumnIndex   = 2; // column[2] = price

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Fill CSV Prices</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f5f5f5;
                padding: 40px 20px;
            }
            .container {
                max-width: 650px;
                margin: auto;
                background: #fff;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,.08);
            }
            h2 { margin-top: 0; }
            .info {
                background: #f0f7ff;
                border-left: 4px solid #0d6efd;
                padding: 12px 15px;
                margin: 20px 0;
                line-height: 1.5;
            }
            input[type=file] {
                width: 100%;
                padding: 10px 0;
                margin-bottom: 15px;
            }
            button {
                background: #0d6efd;
                color: white;
                border: 0;
                padding: 12px 20px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 15px;
            }
            button:hover { background: #0b5ed7; }
            code {
                background: #eee;
                padding: 2px 5px;
                border-radius: 3px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Fill CSV Prices</h2>

            <div class="info">
                <strong>CSV format expected:</strong><br>
                Column 0 = Description<br>
                Column 1 = Barcode<br>
                Column 2 = Price
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input
                    type="file"
                    name="csv_file"
                    accept=".csv,text/csv"
                    required
                >

                <button type="submit">Upload &amp; Fill Prices</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// =========================
// VALIDATE UPLOAD
// =========================
if (!isset($_FILES['csv_file'])) {
    die('No CSV file was uploaded.');
}

if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    die('Upload failed. Error code: ' . $_FILES['csv_file']['error']);
}

$file = $_FILES['csv_file']['tmp_name'];
$originalName = $_FILES['csv_file']['name'];

$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if ($extension !== 'csv') {
    die('Please upload a CSV file.');
}

// =========================
// CONNECT TO DATABASE
// =========================
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

// One prepared query is reused for every barcode.
$stmt = $pdo->prepare(
    "SELECT capital
     FROM stocks
     WHERE unique_barcode = ?
     LIMIT 1"
);

// =========================
// OPEN CSV
// =========================
$handle = fopen($file, 'r');

if ($handle === false) {
    die('Unable to open uploaded CSV.');
}

// Detect BOM if present and remove it from first field.
$firstRow = fgetcsv($handle);

if ($firstRow === false) {
    fclose($handle);
    die('The CSV file is empty.');
}

if (isset($firstRow[0])) {
    $firstRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', $firstRow[0]);
}

// =========================
// PREPARE OUTPUT FILE
// =========================
$outputDir = __DIR__ . DIRECTORY_SEPARATOR . 'output';

if (!is_dir($outputDir)) {
    if (!mkdir($outputDir, 0777, true)) {
        fclose($handle);
        die('Unable to create output directory.');
    }
}

$outputFilename =
    'variance_filled_' . date('Y-m-d_H-i-s') . '.csv';

$outputPath = $outputDir . DIRECTORY_SEPARATOR . $outputFilename;

$out = fopen($outputPath, 'w');

if ($out === false) {
    fclose($handle);
    die('Unable to create output CSV.');
}

// Excel-friendly UTF-8 BOM.
fwrite($out, "\xEF\xBB\xBF");

// =========================
// PROCESS CSV
// =========================
$totalQty = 0;
$totalPrice = 0.0;
$foundCount = 0;
$notFoundCount = 0;
$blankBarcodeCount = 0;

// Cache prevents duplicate database queries for repeated barcodes.
$priceCache = [];

// Breakdown:
// price => quantity
$breakdown = [];

// Process first row + remaining rows.
// If your CSV has a header, it is preserved unchanged.
$rows = [$firstRow];

while (($row = fgetcsv($handle)) !== false) {
    $rows[] = $row;
}

fclose($handle);

foreach ($rows as $rowNumber => $row) {

    // Make sure the row has enough columns.
    while (count($row) <= $priceColumnIndex) {
        $row[] = '';
    }

    // Preserve the first row as a header if it looks like one.
    if ($rowNumber === 0) {
        fputcsv($out, $row);
        continue;
    }

    $barcode = trim((string)$row[$barcodeColumnIndex]);

    if ($barcode === '') {
        $row[$priceColumnIndex] = 'not found on database';
        $blankBarcodeCount++;
        fputcsv($out, $row);
        continue;
    }

    // Check cache first.
    if (array_key_exists($barcode, $priceCache)) {
        $capital = $priceCache[$barcode];
    } else {
        $stmt->execute([$barcode]);
        $result = $stmt->fetch();

        if ($result === false) {
            $capital = null;
        } else {
            $capital = $result['capital'];
        }

        $priceCache[$barcode] = $capital;
    }

    if ($capital === null) {
        $row[$priceColumnIndex] = 'not found on database';
        $notFoundCount++;
    } else {
        $row[$priceColumnIndex] = $capital;

        $numericCapital = is_numeric($capital)
            ? (float)$capital
            : 0.0;

        $totalQty++;
        $totalPrice += $numericCapital;
        $foundCount++;

        $breakdownKey = (string)$capital;

        if (!isset($breakdown[$breakdownKey])) {
            $breakdown[$breakdownKey] = 0;
        }

        $breakdown[$breakdownKey]++;
    }

    fputcsv($out, $row);
}

// =========================
// ADD BREAKDOWN AT BOTTOM
// =========================
fputcsv($out, []);
fputcsv($out, ['BREAKDOWN']);
fputcsv($out, ['Price / Capital', 'Qty', 'Total Price']);

ksort($breakdown, SORT_NATURAL);

foreach ($breakdown as $price => $qty) {
    $numericPrice = is_numeric($price) ? (float)$price : 0.0;
    $lineTotal = $numericPrice * $qty;

    fputcsv($out, [
        $price,
        $qty,
        number_format($lineTotal, 2, '.', '')
    ]);
}

fputcsv($out, []);
fputcsv($out, ['SUMMARY']);
fputcsv($out, ['Found', $foundCount]);
fputcsv($out, ['Not Found', $notFoundCount]);
fputcsv($out, ['Blank Barcode', $blankBarcodeCount]);
fputcsv($out, ['Total Quantity', $totalQty]);
fputcsv($out, ['Grand Total Price', number_format($totalPrice, 2, '.', '')]);

fclose($out);

// =========================
// DOWNLOAD OUTPUT
// =========================
if (!file_exists($outputPath)) {
    die('Output file was not created.');
}

header('Content-Type: text/csv; charset=UTF-8');
header(
    'Content-Disposition: attachment; filename="' .
    basename($outputFilename) .
    '"'
);
header('Content-Length: ' . filesize($outputPath));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($outputPath);

// Delete generated file after download.
@unlink($outputPath);

exit;
?>
