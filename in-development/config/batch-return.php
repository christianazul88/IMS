<?php
include "database.php";
include "on_session.php";

$selected_wh = $_SESSION['hashed_warehouse'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if(!isset($_SESSION['return_supplier'])) {
        $parent_barcode = $_POST["parent-barcode"];
        $start_seq = intval($_POST["start-sequence"]);
        $last_seq = intval($_POST["last-sequence"]);

        // Ensure existing data is properly decoded as an array
        if (isset($_SESSION['scanned_return'])) {
            $existingData = json_decode($_SESSION['scanned_return'], true);

            // Check if the decoding resulted in a valid array
            if (!is_array($existingData)) {
                $existingData = []; // Default to an empty array if decoding failed
            }
        } else {
            $existingData = []; // If no session data, default to empty array
        }

        $skippedBarcodes = [];
        $invalidBarcodes = 0;
        $anyAdded = false;
        $allValid = true;

        // Loop through the sequence of barcodes
        for($i = $start_seq; $i <= $last_seq; $i++) {
            $barcode = $parent_barcode . "-" . $i;

            // Check if the barcode already exists in the session data
            $barcodeExists = false;
            foreach ($existingData as $item) {
                if (isset($item['unique_barcode']) && $item['unique_barcode'] === $barcode) {
                    $barcodeExists = true;
                    break;  // Stop checking once we find the barcode
                }
            }

            // Skip the current barcode if it already exists
            if ($barcodeExists) {
                $skippedBarcodes[] = $barcode; // Store the skipped barcode
                continue; // Proceed to the next iteration
            }

            // Perform database query to get the barcode details
            $query = "SELECT s.unique_barcode, p.description, b.brand_name, s.parent_barcode, c.category_name, s.supplier
                    FROM stocks s
                    LEFT JOIN product p ON p.hashed_id = s.product_id
                    LEFT JOIN brand b ON b.hashed_id = p.brand
                    LEFT JOIN category c ON c.hashed_id = p.category
                    WHERE s.unique_barcode = '$barcode' AND s.warehouse = '$selected_wh' AND s.item_status = 0
                    LIMIT 1";

            $result = $conn->query($query);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $description = $row['description'];
                $brand_name = $row['brand_name'];
                $category_name = $row['category_name'];
                $parent_barcode = $row['parent_barcode'];
                $supplier = $row['supplier'];
                $_SESSION['return_supplier'] = $supplier;
                // Create new item data
                $newItem = [
                    "unique_barcode" => $barcode,
                    "description" => $description,
                    "brand_name" => $brand_name,
                    "category_name" => $category_name,
                    "parent_barcode" => $parent_barcode
                ];

                // Append new item to existing data
                $existingData[] = $newItem;
                $anyAdded = true; // Mark that at least one barcode was added successfully
            } else {
                $allValid = false; // At least one barcode was invalid
                $invalidBarcodes++; // Increment the count of invalid barcodes
            }
        }

        // After the loop, determine the message to echo
        if ($anyAdded) {
            if (count($skippedBarcodes) > 0) {
                $skippedBarcodesList = implode(', ', $skippedBarcodes);
                if ($invalidBarcodes > 0) {
                    echo "All data were saved successfully, except $invalidBarcodes invalid data. Skipped barcodes: $skippedBarcodesList.";
                } else {
                    echo "All data saved successfully! Skipped barcodes: $skippedBarcodesList.";
                }
            } else {
                if ($invalidBarcodes > 0) {
                    echo "All data were saved successfully, except $invalidBarcodes invalid data.";
                } else {
                    echo "All data saved successfully!";
                }
            }
        } else {
            if (count($skippedBarcodes) === ($last_seq - $start_seq + 1)) {
                echo "All data already exists, no new items saved!";
            } elseif (!$allValid) {
                echo "None of the barcodes were valid, no items saved!";
            }
        }

        // Store the updated session data
        if ($anyAdded) {
            $jsonData = json_encode($existingData);
            $_SESSION['scanned_return'] = $jsonData;

            // Store in Global Variable
            $GLOBALS['scanned_return'] = $jsonData;

            // Store in APCu (if available)
            if (function_exists('apcu_store')) {
                apcu_store('scanned_return', $jsonData);
            }
        }
    } else {
        $supplier = $_SESSION['return_supplier'];
        $parent_barcode = $_POST["parent-barcode"];
        $start_seq = intval($_POST["start-sequence"]);
        $last_seq = intval($_POST["last-sequence"]);

        // Ensure existing data is properly decoded as an array
        if (isset($_SESSION['scanned_return'])) {
            $existingData = json_decode($_SESSION['scanned_return'], true);

            // Check if the decoding resulted in a valid array
            if (!is_array($existingData)) {
                $existingData = []; // Default to an empty array if decoding failed
            }
        } else {
            $existingData = []; // If no session data, default to empty array
        }

        $skippedBarcodes = [];
        $invalidBarcodes = 0;
        $anyAdded = false;
        $allValid = true;

        // Loop through the sequence of barcodes
        for($i = $start_seq; $i <= $last_seq; $i++) {
            $barcode = $parent_barcode . "-" . $i;

            // Check if the barcode already exists in the session data
            $barcodeExists = false;
            foreach ($existingData as $item) {
                if (isset($item['unique_barcode']) && $item['unique_barcode'] === $barcode) {
                    $barcodeExists = true;
                    break;  // Stop checking once we find the barcode
                }
            }

            // Skip the current barcode if it already exists
            if ($barcodeExists) {
                $skippedBarcodes[] = $barcode; // Store the skipped barcode
                continue; // Proceed to the next iteration
            }

            // Perform database query to get the barcode details
            $query = "SELECT s.unique_barcode, p.description, b.brand_name, s.parent_barcode, c.category_name
                    FROM stocks s
                    LEFT JOIN product p ON p.hashed_id = s.product_id
                    LEFT JOIN brand b ON b.hashed_id = p.brand
                    LEFT JOIN category c ON c.hashed_id = p.category
                    WHERE s.unique_barcode = '$barcode' AND s.warehouse = '$selected_wh' AND s.item_status = 0 AND s.supplier = '$supplier'
                    LIMIT 1";

            $result = $conn->query($query);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $description = $row['description'];
                $brand_name = $row['brand_name'];
                $category_name = $row['category_name'];
                $parent_barcode = $row['parent_barcode'];

                // Create new item data
                $newItem = [
                    "unique_barcode" => $barcode,
                    "description" => $description,
                    "brand_name" => $brand_name,
                    "category_name" => $category_name,
                    "parent_barcode" => $parent_barcode
                ];

                // Append new item to existing data
                $existingData[] = $newItem;
                $anyAdded = true; // Mark that at least one barcode was added successfully
            } else {
                $allValid = false; // At least one barcode was invalid
                $invalidBarcodes++; // Increment the count of invalid barcodes
            }
        }

        // After the loop, determine the message to echo
        if ($anyAdded) {
            if (count($skippedBarcodes) > 0) {
                $skippedBarcodesList = implode(', ', $skippedBarcodes);
                if ($invalidBarcodes > 0) {
                    echo "All data were saved successfully, except $invalidBarcodes invalid data. Skipped barcodes: $skippedBarcodesList.";
                } else {
                    echo "All data saved successfully! Skipped barcodes: $skippedBarcodesList.";
                }
            } else {
                if ($invalidBarcodes > 0) {
                    echo "All data were saved successfully, except $invalidBarcodes invalid data.";
                } else {
                    echo "All data saved successfully!";
                }
            }
        } else {
            if (count($skippedBarcodes) === ($last_seq - $start_seq + 1)) {
                echo "All data already exists, no new items saved!";
            } elseif (!$allValid) {
                echo "None of the barcodes were valid or not the same supplier as the first entry, no items saved!";
            }
        }

        // Store the updated session data
        if ($anyAdded) {
            $jsonData = json_encode($existingData);
            $_SESSION['scanned_return'] = $jsonData;

            // Store in Global Variable
            $GLOBALS['scanned_return'] = $jsonData;

            // Store in APCu (if available)
            if (function_exists('apcu_store')) {
                apcu_store('scanned_return', $jsonData);
            }
        }
    }
}
?>
