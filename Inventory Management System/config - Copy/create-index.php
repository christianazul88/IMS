<?php
include "database.php";

$db = DB_NAME; // use constant from your .env setup

// Define the indexes to check and create
$indexes = [
    'category' => ['idx_category_hashed_id' => 'hashed_id'],
    'product' => [
        'idx_product_category' => 'category',
        'idx_product_hashed_id' => 'hashed_id',
        'idx_product_brand' => 'brand',
    ],
    'stocks' => [
        'idx_stocks_product_id' => 'product_id',
        'idx_stocks_unique_barcode' => 'unique_barcode',
        'idx_stocks_supplier' => 'supplier',
        'idx_stocks_item_status' => 'item_status',
    ],
    'outbound_content' => [
        'idx_oc_unique_barcode' => 'unique_barcode',
        'idx_oc_hashed_id' => 'hashed_id',
        'idx_oc_status' => 'status',
    ],
    'outbound_logs' => [
        'idx_ol_hashed_id' => 'hashed_id',
        'idx_ol_user_id' => 'user_id',
        'idx_ol_warehouse' => 'warehouse',
        'idx_ol_date_sent' => 'date_sent',
    ],
    'supplier' => ['idx_supplier_hashed_id' => 'hashed_id'],
    'brand' => ['idx_brand_hashed_id' => 'hashed_id'],
    'users' => ['idx_users_hashed_id' => 'hashed_id'],
];

foreach ($indexes as $table => $indexList) {
    foreach ($indexList as $indexName => $column) {
        $checkQuery = "
            SELECT COUNT(1) AS count
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE table_schema = '$db' 
              AND table_name = '$table' 
              AND index_name = '$indexName'
        ";
        $result = $conn->query($checkQuery);
        if (!$result) {
            echo "❌ Error checking index $indexName on $table: " . $conn->error . "\n";
            continue;
        }
        $row = $result->fetch_assoc();

        if ($row['count'] == 0) {
            $createQuery = "CREATE INDEX $indexName ON $table($column)";
            if ($conn->query($createQuery) === TRUE) {
                echo "✅ Created index $indexName on $table($column)\n";
            } else {
                echo "❌ Failed to create index $indexName on $table($column): " . $conn->error . "\n";
            }
        } else {
            echo "ℹ️ Index $indexName already exists on $table\n";
        }
    }
}
