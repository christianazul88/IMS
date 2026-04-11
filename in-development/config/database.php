<?php
require_once __DIR__ . '../../../vendor/autoload.php'; // Adjust path based on your file structure

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '../../../'); // Adjust path if .env is in a different directory
$dotenv->load();

date_default_timezone_set($_ENV['TIMEZONE']);

define('DB_SERVER', $_ENV['DB_SERVER']);
define('DB_USERNAME', $_ENV['DB_USERNAME']);
define('DB_PASSWORD', $_ENV['DB_PASSWORD']);
define('DB_NAME', $_ENV['DB_NAME']);

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$timezone = new DateTimeZone('Asia/Manila');
$currentDateTime = (new DateTime('now', $timezone))->format('Y-m-d H:i:s');