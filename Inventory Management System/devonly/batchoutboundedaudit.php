<?php
include "../config/database.php";
include "../config/on_session.php";

$file = "outbounded as of july242026.csv";

if (!file_exists($file)) {
    die("File not found.");
}

$stmt = $conn->prepare("
    UPDATE items_to_audit
    SET audit_status = 'outbounded',
        order_num = ?
    WHERE unique_barcode = ?
    AND audit_status = 'pending'
");

$updated = 0;
$not_updated = [];

if (($handle = fopen($file, "r")) !== false) {

    // Skip header
    fgetcsv($handle);

    while (($row = fgetcsv($handle)) !== false) {

        $barcode = trim($row[0]);
        $order_number = trim($row[1]);

        $stmt->bind_param("ss", $order_number, $barcode);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $updated++;
        } else {
            $not_updated[] = [
                'barcode' => $barcode,
                'order_number' => $order_number
            ];
        }
    }

    fclose($handle);
}

$stmt->close();
$conn->close();

echo "<h3>Summary</h3>";
echo "Updated: <b>{$updated}</b><br>";
echo "Not Updated: <b>" . count($not_updated) . "</b><br><br>";

if (!empty($not_updated)) {

    echo "<h3>Barcodes Not Found / Not Updated</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>
            <th>#</th>
            <th>Barcode</th>
            <th>Order Number</th>
          </tr>";

    foreach ($not_updated as $index => $item) {
        echo "<tr>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td>{$item['barcode']}</td>";
        echo "<td>{$item['order_number']}</td>";
        echo "</tr>";
    }

    echo "</table>";
}
?>