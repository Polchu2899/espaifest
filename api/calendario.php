<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$file = DATA_DIR . 'calendario.json';

// ── GET: devolver calendario completo ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists($file)) {
        echo '{}';
    } else {
        header('Cache-Control: no-cache');
        echo file_get_contents($file);
    }
    exit;
}

// Escrituras — requieren token
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

$cal = file_exists($file) ? (json_decode(file_get_contents($file), true) ?? []) : [];

// ── POST bulk: sincronización masiva { bulk:true, data:{fecha:obj,...} } ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($body['bulk']) && isset($body['data'])) {
    foreach ($body['data'] as $fecha => $data) {
        unset($data['updatedAt']);
        $cal[$fecha] = $data;
    }
    file_put_contents($file, json_encode($cal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['ok' => true, 'count' => count($body['data']), 'ts' => time() * 1000]);
    exit;
}

// Operaciones por fecha individual — requieren 'fecha'
if (!isset($body['fecha'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Falta el campo fecha']);
    exit;
}

// ── DELETE: eliminar una fecha ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    unset($cal[$body['fecha']]);
    file_put_contents($file, json_encode($cal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['ok' => true]);
    exit;
}

// ── POST: upsert de una fecha ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $body['fecha'];
    $data  = $body['data'] ?? $body;
    unset($data['fecha']);      // no duplicar dentro del objeto
    unset($data['updatedAt']);  // Timestamp de Firebase no es serializable

    // Merge: conservar campos no enviados (p. ej. franjasCustom si solo se actualiza ocupación)
    $existing    = $cal[$fecha] ?? [];
    $cal[$fecha] = array_merge($existing, $data);

    if (file_put_contents($file, json_encode($cal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error de escritura']);
        exit;
    }
    echo json_encode(['ok' => true, 'ts' => time() * 1000]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
