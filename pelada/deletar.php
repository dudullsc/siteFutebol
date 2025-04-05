<?php
require_once __DIR__ . '/vendor/autoload.php';

$client = new MongoDB\Client("mongodb://localhost:27017");
$collection = $client->pelada->jogadores;

$result = $collection->deleteMany([]);
echo "Todos os jogadores foram removidos do banco. Total apagado: " . $result->getDeletedCount();

