<?php
require_once 'db.php';
require_once 'Classes/Trades.php';

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
if (empty($body['batch_id']) || empty($body['trades']) || !is_array($body['trades'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    $saved = (new Trades($pdo))->saveBatch($body['batch_id'], $body['trades']);
    echo json_encode(['saved' => $saved]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
