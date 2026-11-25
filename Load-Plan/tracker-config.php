<?php
// Set these environment variables on the VPS; defaults support local XAMPP.
function tracking_db() {
 static $db = null;
 if ($db instanceof PDO) return $db;
 $dsn = 'mysql:host='.(getenv('TRACK_DB_HOST') ?: 'localhost');
 $user = getenv('TRACK_DB_USER') ?: 'root';
 $password = getenv('TRACK_DB_PASSWORD') ?: '';
 $options = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC];
 try {
  $connection = new PDO($dsn.';dbname=sampletest;charset=utf8mb4', $user, $password, $options);
 } catch (PDOException $e) {
  // Only a missing database triggers installation, never authentication errors.
  if ((int)($e->errorInfo[1] ?? 0) !== 1049) throw $e;
  $connection = new PDO($dsn.';charset=utf8mb4', $user, $password, $options);
  $connection->exec('CREATE DATABASE IF NOT EXISTS sampletest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
  $connection->exec('USE sampletest');
 }
 $connection->exec("SET time_zone = '+00:00'");
 try {
  $connection->query('SELECT visit_id FROM scene_visits LIMIT 0');
 } catch (PDOException $e) {
  if ((int)($e->errorInfo[1] ?? 0) !== 1146) throw $e;
  // IF NOT EXISTS handles simultaneous first visitors safely.
  $connection->exec('CREATE TABLE IF NOT EXISTS scene_visits (
   visit_id CHAR(36) PRIMARY KEY,
   device_id CHAR(36) NOT NULL,
   started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   last_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   active_seconds INT UNSIGNED NOT NULL DEFAULT 0,
   INDEX device_time(device_id, started_at)
  ) ENGINE=InnoDB');
 }
 $db = $connection;
 return $db;
}
