<?php
header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(['available' => is_file(__DIR__ . '/assets/music.mp3')]);
