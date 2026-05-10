<?php
session_start();

if (isset($_SESSION['po_list']) && !empty($_SESSION['po_list'])) {
    foreach ($_SESSION['po_list'] as $item) {
        echo "<tr>";
        echo "<td class=' fs-11 name'>" . htmlspecialchars($item['description']) . "</td>";
        echo "<td class=' fs-11 email'>" . htmlspecialchars($item['brand']) . "</td>";
        echo "<td class=' fs-11 age'>" . htmlspecialchars($item['category']) . "</td>";
        echo "<td class=' fs-11 age'><input type='text' name='barcode[]' value='" . htmlspecialchars($item['barcode']) . "' hidden> " . htmlspecialchars($item['barcode']) . "</td>";
        echo "<td class=' fs-11 age text-end'>" . htmlspecialchars($item['qty']) . "</td>";

        echo "<td class=' fs-11 age'><input type='number' name='qty[]' id='qty_received' class='form-control' min='0'></td>";
        echo "<td class=' fs-11 age'><input type='number' name='unit_amount[]' id='unit_amount' step='0.01' class='form-control''></td>";
        echo "<td class=' fs-11 age' id='subtotal_td'></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='5' class='text-center'>No products found.</td></tr>";
}
?>
