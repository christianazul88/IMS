<?php
try {
    do {
        // Generate a 12-digit random number
        $unique_key = str_pad(mt_rand(0, 999999999999), 12, '0', STR_PAD_LEFT);

        // Query to check if the key already exists in the database
        $checking_only = "SELECT id FROM inbound_logs WHERE unique_key = '$unique_key' LIMIT 1";
        $result = $conn->query($checking_only);

        // Check if the key exists; if so, regenerate
    } while ($result->num_rows > 0);

    // Store the unique key in the session
    $_SESSION['unique_key'] = $unique_key;
} catch (Exception $e) {
    // Handle any potential errors
    echo "Error generating secure key: " . $e->getMessage();
}
?>
