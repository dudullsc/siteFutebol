<?php
require_once __DIR__ . '/vendor/autoload.php';

$client = new MongoDB\Client("mongodb://localhost:27017");
$collection = $client->pelada->jogadores;

// Exemplo de jogadores
$jogadores = [
    ['nome' => 'Tiago', 'categoria' => 'Mensalista'],
    ['nome' => 'Jo', 'categoria' => 'Mensalista'],
    ['nome' => 'Eduardo', 'categoria' => 'Mensalista'],
    ['nome' => 'Saulo', 'categoria' => 'Avulso'],
    ['nome' => 'Pedro', 'categoria' => 'Avulso'],
    ['nome' => 'Leu', 'categoria' => 'Goleiro'],
];

$result = $collection->insertMany($jogadores);
echo "Jogadores adicionados com sucesso. Total: " . count($result->getInsertedIds());

