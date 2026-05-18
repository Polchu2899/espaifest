<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$file = DATA_DIR . 'cache_bust.json';

// ── GET: devolver timestamp actual ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Cache-Control: no-cache');
    if (!file_exists($file)) {
        echo json_encode(['ts' => 0]);
    } else {
        echo file_get_contents($file);
    }
    exit;
}

// ── POST: actualizar timestamp ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SERVER['HTTP_X_TOKEN'] ?? '') !== API_TOKEN) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        exit;
    }
    $ts = (int)(microtime(true) * 1000);
    file_put_contents($file, json_encode(['ts' => $ts]));
    echo json_encode(['ok' => true, 'ts' => $ts]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
