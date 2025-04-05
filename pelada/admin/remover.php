<?php
require_once __DIR__ . '/../api/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$body = json_decode(file_get_contents('php://input'), true);
$nome = $body['nome'] ?? null;

if (!$nome) {
    echo json_encode(['status' => 'error', 'message' => 'Nome do jogador não informado.']);
    exit;
}

try {
    $collection = getMongoCollection();
    $res = $collection->deleteMany(['nomeJogador' => $nome]);
    echo json_encode(['status' => 'success', 'message' => "Jogador '$nome' removido com sucesso.", 'deleted' => $res->getDeletedCount()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao remover: ' . $e->getMessage()]);
}
