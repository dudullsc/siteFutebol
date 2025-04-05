<?php
session_start();
require_once 'conexao.php';

$usuario = $_POST['usuario'] ?? '';
$senha = $_POST['senha'] ?? '';

$usuarioDB = $usuariosCollection->findOne(['usuario' => $usuario]);

if ($usuarioDB && password_verify($senha, $usuarioDB['senha'])) {
    $_SESSION['usuario'] = $usuario;
    header('Location: index.php');
    exit();
} else {
    echo "Usuário ou senha inválidos!";
}

