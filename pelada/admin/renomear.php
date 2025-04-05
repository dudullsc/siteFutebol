<?php
require_once __DIR__ . '/../api/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$body = json_decode(file_get_contents('php://input'), true);
$antigo = $body['antigo'] ?? null;
$novo = $body['novo'] ?? null;

if (!$antigo || !$novo) {
    echo json_encode(['status' => 'error', 'message' => 'Nomes antigo e novo são obrigatórios.']);
    exit;
}

try {
    $collection = getMongoCollection();
    $res = $collection->updateMany(
        ['nomeJogador' => $antigo],
        ['$set' => ['nomeJogador' => $novo]]
    );
    echo json_encode(['status' => 'success', 'message' => "Nome alterado de '$antigo' para '$novo'.", 'modified' => $res->getModifiedCount()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao renomear: ' . $e->getMessage()]);
}
