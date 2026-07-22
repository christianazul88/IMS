<?php

$onlineFile = "online buffer.csv"; // CSV 1
$localFile  = "local buffer.csv";  // CSV 2

function getBarcodes($filename)
{
    $barcodes = [];

    if (($handle = fopen($filename, "r")) !== false) {

        // Read header
        $header = fgetcsv($handle);

        // Find the Unique Barcode column
        $barcodeIndex = array_search("Unique Barcode", $header);

        if ($barcodeIndex === false) {
            die("Column 'Unique Barcode' not found in {$filename}");
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (isset($row[$barcodeIndex])) {
                $barcode = trim($row[$barcodeIndex]);

                if ($barcode !== "") {
                    $barcodes[$barcode] = true;
                }
            }
        }

        fclose($handle);
    }

    return $barcodes;
}

$onlineBarcodes = getBarcodes($onlineFile);
$localBarcodes  = getBarcodes($localFile);

// Present in local but missing in online
$missing = array_diff_key($localBarcodes, $onlineBarcodes);

echo "<h2>Missing Barcodes (Local → Online)</h2>";

if (empty($missing)) {
    echo "No missing barcodes found.";
} else {

    echo "Total Missing: <b>" . count($missing) . "</b><br><br>";

    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>#</th><th>Barcode</th></tr>";

    $i = 1;
    foreach ($missing as $barcode => $value) {
        echo "<tr>";
        echo "<td>{$i}</td>";
        echo "<td>{$barcode}</td>";
        echo "</tr>";
        $i++;
    }

    echo "</table>";
}
?>