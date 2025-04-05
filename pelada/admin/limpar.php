<?php
require_once __DIR__ . '/../api/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $collection = getMongoCollection();
    $result = $collection->deleteMany([]);
    echo json_encode(['status' => 'success', 'message' => 'Todos os dados foram removidos.', 'deleted' => $result->getDeletedCount()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao limpar: ' . $e->getMessage()]);
}
