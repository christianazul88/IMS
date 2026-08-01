<?php
// Include the database connection file
include('database.php');
include('on_session.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $productDescription = $_POST['product_description'] ?? '';
    $category = $_POST['category'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $parentBarcode = $_POST['parent_barcode'] ?? '';
    $safety = $_POST['safety'];

    $voltage  = $_POST['volt'] ?? '';
    $amperage = $_POST['amp'] ?? '';
    $pin_size = $_POST['pin'] ?? '';
 
    /**
     * Makes sure a lookup table exists, then inserts the given value
     * into it only if that value isn't already present.
     *
     * @param mysqli $conn
     * @param string $tableName   e.g. 'voltage'
     * @param string $columnName  e.g. 'volt_value'
     * @param string $value       the value coming from the form
     */
    function ensureLookupValue($conn, $tableName, $columnName, $value) {
        // Nothing to store if the field was left empty
        if ($value === null || $value === '') {
            return;
        }
 
        // Create the table if it doesn't exist yet
        // (VARCHAR(100) to match the table-seeding helper on the form page,
        // so values created there and here never collide on column width)
        $createSql = "CREATE TABLE IF NOT EXISTS `$tableName` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `$columnName` VARCHAR(100) NULL
        )";
        $conn->query($createSql);
 
        // Check whether the value already exists
        $checkSql = "SELECT `id` FROM `$tableName` WHERE `$columnName` = ? LIMIT 1";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("s", $value);
        $stmt->execute();
        $stmt->store_result();
        $alreadyExists = $stmt->num_rows > 0;
        $stmt->close();
 
        // Only insert if it doesn't exist yet
        if (!$alreadyExists) {
            $insertSql = "INSERT INTO `$tableName` (`$columnName`) VALUES (?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("s", $value);
            $insertStmt->execute();
            $insertStmt->close();
        }
    }

    /**
     * For "pick existing or type your own" fields, the select carries either
     * an existing lookup value, "not_available"/"" (nothing chosen), or "new"
     * (meaning: use the paired *_custom text field instead). Only the "new"
     * case represents a value that isn't already in the lookup table.
     *
     * @param mysqli $conn
     * @param string $tableName   e.g. 'laptop_cpu'
     * @param string $columnName  e.g. 'cpu_value'
     * @param string $selectValue the submitted <field>_select/<field> value
     * @param string $customValue the submitted <field>_custom value
     */
    function ensureLookupValueForField($conn, $tableName, $columnName, $selectValue, $customValue) {
        if ($selectValue !== 'new') {
            return;
        }
        ensureLookupValue($conn, $tableName, $columnName, trim($customValue));
    }
 
    // Apply the same "create table if needed, insert if new" logic
    // for each of the three lookup fields.
    ensureLookupValue($conn, 'voltage', 'volt_value', $voltage);
    ensureLookupValue($conn, 'amperage', 'amp_value', $amperage);
    ensureLookupValue($conn, 'pin', 'pin_size_value', $pin_size);

    // Processor generation (Desktop + Laptop)
    ensureLookupValueForField($conn, 'processor_generation', 'generation_value', $_POST['processor_generation'] ?? '', $_POST['processor_generation_custom'] ?? '');

    // Laptop specs that are saved for reuse (per the form's own hint text)
    ensureLookupValueForField($conn, 'laptop_cpu', 'cpu_value', $_POST['laptop_cpu'] ?? '', $_POST['laptop_cpu_custom'] ?? '');
    ensureLookupValueForField($conn, 'laptop_ram', 'ram_value', $_POST['laptop_ram'] ?? '', $_POST['laptop_ram_custom'] ?? '');
    ensureLookupValueForField($conn, 'laptop_gpu', 'gpu_value', $_POST['laptop_gpu'] ?? '', $_POST['laptop_gpu_custom'] ?? '');
    ensureLookupValueForField($conn, 'laptop_screen_size', 'screen_size_value', $_POST['laptop_screen_size'] ?? '', $_POST['laptop_screen_size_custom'] ?? '');
    ensureLookupValueForField($conn, 'laptop_color', 'color_value', $_POST['laptop_color'] ?? '', $_POST['laptop_color_custom'] ?? '');

    // Laptop Battery / CP Battery specs (voltage + mAh tables are shared between the two categories)
    ensureLookupValueForField($conn, 'battery_voltage', 'voltage_value', $_POST['battery_voltage'] ?? '', $_POST['battery_voltage_custom'] ?? '');
    ensureLookupValueForField($conn, 'battery_mah', 'mah_value', $_POST['battery_mah'] ?? '', $_POST['battery_mah_custom'] ?? '');
    ensureLookupValueForField($conn, 'battery_voltage', 'voltage_value', $_POST['cp_battery_voltage'] ?? '', $_POST['cp_battery_voltage_custom'] ?? '');
    ensureLookupValueForField($conn, 'battery_mah', 'mah_value', $_POST['cp_battery_mah'] ?? '', $_POST['cp_battery_mah_custom'] ?? '');

    // Laptop Keyboard specs (color shares the laptop_color table above)
    ensureLookupValueForField($conn, 'keyboard_panel_type', 'panel_type_value', $_POST['keyboard_panel_type'] ?? '', $_POST['keyboard_panel_type_custom'] ?? '');
    ensureLookupValueForField($conn, 'laptop_color', 'color_value', $_POST['keyboard_color'] ?? '', $_POST['keyboard_color_custom'] ?? '');
    ensureLookupValueForField($conn, 'keyboard_layout', 'layout_value', $_POST['keyboard_layout'] ?? '', $_POST['keyboard_layout_custom'] ?? '');

    // LCD screen size (shares the laptop_screen_size table above)
    ensureLookupValueForField($conn, 'laptop_screen_size', 'screen_size_value', $_POST['lcd_screen_size'] ?? '', $_POST['lcd_screen_size_custom'] ?? '');

    // Power Cord specs (voltage/amperage share the same tables as Electrical specifications;
    // "Type" is a fixed 2/3-prong/Monitor Cord choice and "Compatibility" is free text, so neither is persisted here)
    ensureLookupValueForField($conn, 'cable_length', 'length_value', $_POST['powercord_length'] ?? '', $_POST['powercord_length_custom'] ?? '');
    ensureLookupValueForField($conn, 'voltage', 'volt_value', $_POST['powercord_voltage'] ?? '', $_POST['powercord_voltage_custom'] ?? '');
    ensureLookupValueForField($conn, 'amperage', 'amp_value', $_POST['powercord_amperage'] ?? '', $_POST['powercord_amperage_custom'] ?? '');

    // Power Socket specs (amperage/voltage share the same tables as Electrical specifications)
    ensureLookupValueForField($conn, 'watts', 'watts_value', $_POST['socket_watts'] ?? '', $_POST['socket_watts_custom'] ?? '');
    ensureLookupValueForField($conn, 'amperage', 'amp_value', $_POST['socket_amperage'] ?? '', $_POST['socket_amperage_custom'] ?? '');
    ensureLookupValueForField($conn, 'voltage', 'volt_value', $_POST['socket_voltage'] ?? '', $_POST['socket_voltage_custom'] ?? '');

    // Wall Mount specs
    ensureLookupValueForField($conn, 'wallmount_item_type', 'item_type_value', $_POST['wallmount_item_type'] ?? '', $_POST['wallmount_item_type_custom'] ?? '');
    ensureLookupValueForField($conn, 'vesa', 'vesa_value', $_POST['wallmount_vesa'] ?? '', $_POST['wallmount_vesa_custom'] ?? '');
    ensureLookupValueForField($conn, 'wallmount_suitable_size', 'size_range_value', $_POST['wallmount_suitable_size'] ?? '', $_POST['wallmount_suitable_size_custom'] ?? '');
    ensureLookupValueForField($conn, 'load_capacity', 'capacity_value', $_POST['wallmount_load_capacity'] ?? '', $_POST['wallmount_load_capacity_custom'] ?? '');

    function generateUniqueBarcode($conn) {
        do {
            $barcode = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            $query = "SELECT COUNT(*) FROM product WHERE parent_barcode = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $barcode);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
        } while ($count > 0);
        return $barcode;
    }

    if (empty($parentBarcode)) {
        $parentBarcode = generateUniqueBarcode($conn);
    }

    // Process multiple image uploads
    $productImages = $_FILES['product_image'] ?? null;
    $imageBlobs = [];

    if ($productImages && is_array($productImages['tmp_name'])) {
        $totalImages = count($productImages['tmp_name']);

        if ($totalImages > 10) {
            header("Location: ../Product-list/?success=false&err=max_img");
            exit;
        }

        for ($i = 0; $i < $totalImages; $i++) {
            if ($productImages['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $productImages['tmp_name'][$i];
                $imageData = file_get_contents($tmpName);
                $imageBlobs[] = base64_encode($imageData); // Store as base64 for safe serialization
            }
        }
    }

    // Convert array of base64-encoded images to a single string for BLOB storage
    $finalBlobData = serialize($imageBlobs); // Or json_encode($imageBlobs)

    $currentDateTime = date('Y-m-d H:i:s'); // Add this if not already defined

    // Prepare the SQL statement to insert product data
    $sql = "INSERT INTO product (`description`, category, brand, parent_barcode, product_img, `date`, `user_id`, `safety`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $productDescription, $category, $brand, $parentBarcode, $finalBlobData, $currentDateTime, $user_id, $safety);

    if ($stmt->execute()) {
        $product_id = $stmt->insert_id;
        $hashed_product_id = hash('sha256', $product_id);
        $update = "UPDATE product SET hashed_id = '$hashed_product_id' WHERE id = '$product_id'";
        if ($conn->query($update) === TRUE) {
            header("Location: ../create-product/?success=true");
        }
    } else {
        $error_message = "Error: " . $stmt->error;
        header("Location: ../create-product/?success=false&err=$error_message");
    }

    $stmt->close();
    $conn->close();
}
?>
