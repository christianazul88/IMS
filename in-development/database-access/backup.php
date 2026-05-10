<?php
include '../config/database.php'; // Relative path, no __DIR__

$mysqldump = 'C:/xampp/mysql/bin/mysqldump.exe'; // Adjust if needed
$timestamp = date("Y-m-d_H-i-s");
$dbName = DB_NAME;

// Temp .sql file
$tmpSql = tempnam(sys_get_temp_dir(), 'db_') . ".sql";

// Dump database to file
$cmd = "\"$mysqldump\" -h " . DB_SERVER . " -u " . DB_USERNAME . " -p\"" . DB_PASSWORD . "\" $dbName > \"$tmpSql\"";
exec($cmd, $output, $status);

// Handle failure
if ($status !== 0) {
    http_response_code(500);
    echo "❌ Failed to dump database.";
    exit;
}

// Download SQL
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="backup_' . $dbName . '_' . $timestamp . '.sql"');
header('Content-Length: ' . filesize($tmpSql));
readfile($tmpSql);
unlink($tmpSql); // Cleanup
exit;
