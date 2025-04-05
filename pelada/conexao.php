<?php
require 'vendor/autoload.php'; // garante que o MongoDB esteja carregado

try {
    $client = new MongoDB\Client("mongodb://localhost:27017"); // ajuste se usar outra porta ou host
    $db = $client->pelada; // nome do banco de dados
} catch (Exception $e) {
    die("Erro ao conectar com MongoDB: " . $e->getMessage());
}
?>
