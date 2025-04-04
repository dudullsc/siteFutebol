<?php
require_once __DIR__ . '/api/db.php';

header('Content-Type: application/json');

try {
    $collection = getMongoCollection();
    $deleteResult = $collection->deleteMany([]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Coleção limpa com sucesso!',
        'deletedCount' => $deleteResult->getDeletedCount()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao limpar dados: ' . $e->getMessage()
    ]);
}
?>
