<?php
// api/config.php
// Conexão para MongoDB rodando localmente SEM autenticação

// String de conexão padrão para MongoDB local sem auth
$mongoConnectionString = "mongodb://127.0.0.1:27017"; 

// Nome do banco de dados que usaremos
$mongoDbName = "pelada_db"; 

// Nome da coleção (como uma tabela) onde salvaremos as confirmações
$mongoCollectionName = "confirmations"; 

// Define o fuso horário para datas (IMPORTANTE para consistência)
date_default_timezone_set('America/Sao_Paulo'); // Ajuste para seu fuso horário! Ex: 'America/Sao_Paulo'

// Habilita exibição de erros PHP (APENAS PARA DESENVOLVIMENTO - Desabilitar em produção!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
