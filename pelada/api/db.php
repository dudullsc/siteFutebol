<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Garante que a classe MongoDB esteja carregada

static $mongoClient = null;
static $mongoDb = null;

function getMongoCollection($collectionName = null) {
    global $mongoClient, $mongoDb;
    global $mongoConnectionString, $mongoDbName, $mongoCollectionName;

    if ($mongoClient === null) {
        try {
            $mongoClient = new MongoDB\Client($mongoConnectionString);
            $mongoDb = $mongoClient->selectDatabase($mongoDbName);
            $mongoDb->listCollectionNames(); // Testa conexão
            error_log("✅ MongoDB conectado com sucesso.");
        } catch (Throwable $e) {
            error_log("❌ ERRO DB: " . $e->getMessage());
            throw new Exception("Falha crítica na conexão com o banco de dados.");
        }
    }

    $collectionToUse = $collectionName ?? $mongoCollectionName;

    try {
        return $mongoDb->selectCollection($collectionToUse);
    } catch (Exception $e) {
        error_log("❌ Erro ao acessar coleção '{$collectionToUse}': " . $e->getMessage());
        throw new Exception("Erro ao acessar a coleção no banco de dados.");
    }
}
