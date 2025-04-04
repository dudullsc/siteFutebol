<?php
// db.php - Conexão com MongoDB usando variáveis configuráveis

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Garante que a classe MongoDB esteja carregada

use MongoDB\Client; // <-- IMPORTANTE!

static $mongoClient = null;
static $mongoDb = null;
static $mongoCollection = null;

function getMongoCollection() {
    global $mongoClient, $mongoDb, $mongoCollection;
    global $mongoConnectionString, $mongoDbName, $mongoCollectionName;

    if ($mongoCollection !== null) {
        return $mongoCollection;
    }

    if ($mongoClient === null) {
        try {
            $mongoClient = new Client($mongoConnectionString);
            $mongoDb = $mongoClient->selectDatabase($mongoDbName);
            $mongoDb->listCollectionNames(); // Apenas para testar conexão
            error_log("✅ MongoDB conectado e banco selecionado com sucesso.");
        } catch (Throwable $e) {
            error_log("❌ ERRO DB: Falha ao conectar com o MongoDB: " . $e->getMessage());
            throw new Exception("Falha crítica na conexão com o banco de dados.");
        }
    }

    try {
        $mongoCollection = $mongoDb->selectCollection($mongoCollectionName);
        return $mongoCollection;
    } catch (Exception $e) {
        error_log("❌ Erro ao selecionar coleção '{$mongoCollectionName}': " . $e->getMessage());
        throw new Exception("Falha ao selecionar a coleção no banco de dados.");
    }
}
