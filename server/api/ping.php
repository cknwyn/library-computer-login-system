<?php
// Simple lightweight endpoint to check API availability
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode(['success' => true, 'timestamp' => time()]);
