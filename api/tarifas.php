<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$file = DATA_DIR . 'tarifas.json';

// ── GET: devolver tarifas ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists($file)) {
        echo '{}';
    } else {
        header('Cache-Control: no-cache');
        echo file_get_contents($file);
    }
    exit;
}

// ── POST: guardar tarifas ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SERVER['HTTP_X_TOKEN'] ?? '') !== API_TOKEN) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        exit;
    }
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
        exit;
    }

    if (isset($body['tipo']) && isset($body['filas'])) {
        // Actualización parcial: { tipo: 'estandar', filas: [...] }
        $current = file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];
        $current[$body['tipo']] = $body['filas'];
        $data = $current;
    } else {
        // Guardar objeto completo: { estandar:[...], ampliados:[...], ... }
        $data = $body;
    }

    if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error de escritura']);
        exit;
    }
    echo json_encode(['ok' => true, 'ts' => time() * 1000]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
