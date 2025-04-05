<?php
require_once 'conexao.php';

$collection = (new MongoDB\Client)->pelada->usuarios;

$senhaHash = password_hash("senha123", PASSWORD_DEFAULT);

$collection->insertOne([
    "usuario" => "admin",
    "senha" => $senhaHash
]);

echo "Usuário criado com sucesso!";
