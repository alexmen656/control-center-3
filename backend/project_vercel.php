<?php
require_once 'config.php';
require_once 'head.php';
header('Content-Type: application/json');

echo json_encode(['success' => true, 'message' => 'Vercel integration replaced by built-in deploy', 'projects' => []]);
