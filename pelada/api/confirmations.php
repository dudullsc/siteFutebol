<?php
// FORÇAR EXIBIÇÃO DE ERROS PHP (APENAS PARA DEBUG - REMOVER DEPOIS!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclui helpers e configurações
require_once __DIR__ . '/db.php';

// Cabeçalhos essenciais
header('Content-Type: application/json');
// Permitir qualquer origem para teste (Ajuste em produção!)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Lida com requisição OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}

// Resposta padrão
$response = ['status' => 'error', 'message' => 'Requisição inválida.', 'states' => new stdClass()];
$statusCode = 400; // Bad Request por padrão

// --- Lógica para Requisição GET (Carregar Estado) ---
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        // Calcula Data do Próximo Jogo
        $hoje = new DateTime("now", new DateTimeZone(date_default_timezone_get() ?: 'America/Sao_Paulo'));
        $diaDaSemana = (int)$hoje->format('w');
        $diasAteSexta = 5 - $diaDaSemana;
        if ($diasAteSexta <= 0) $diasAteSexta += 7;
        $proximaSexta = clone $hoje;
        $proximaSexta->modify("+$diasAteSexta days");
        $dataJogoFormatada = $proximaSexta->format('d/m');

        $collection = getMongoCollection(); // Tenta obter coleção

        // Busca último status por jogador para a data atual
        $pipeline = [
            ['$match' => ['dataJogo' => $dataJogoFormatada]],
            ['$sort' => ['timestamp' => -1]],
            ['$group' => ['_id' => '$nomeJogador', 'latestStatus' => ['$first' => '$status']]],
            ['$project' => ['_id' => 0, 'nomeJogador' => '$_id', 'status' => '$latestStatus']]
        ];
        $cursor = $collection->aggregate($pipeline);

        $playerStates = new stdClass();
        foreach ($cursor as $doc) {
            if (isset($doc['nomeJogador']) && isset($doc['status']) && ($doc['status'] === 'Vou' || $doc['status'] === 'Não Vou')) {
                $playerStates->{$doc['nomeJogador']} = $doc['status'];
            }
        }

        $response = ['status' => 'success', 'message' => 'Estados carregados', 'gameDate' => $dataJogoFormatada, 'states' => $playerStates];
        $statusCode = 200;

    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => "Erro GET: " . $e->getMessage(), 'states' => new stdClass()];
        error_log("API GET Error: " . $e->getMessage() . " | Stack: " . $e->getTraceAsString());
        $statusCode = 500;
    }
}

// --- Lógica para Requisição POST (Salvar/Atualizar Confirmação) ---
elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jsonPayload = file_get_contents('php://input');
    $requestData = json_decode($jsonPayload, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($requestData['nome'], $requestData['status'], $requestData['categoria'], $requestData['dataJogo'])) {
        $response['message'] = 'Dados POST inválidos ou incompletos.';
        error_log("API POST Error: Dados inválidos - " . $jsonPayload);
        $statusCode = 400;
    } else {
        try {
            $collection = getMongoCollection();
            $timestamp = new MongoDB\BSON\UTCDateTime();

            $nomeJogador = $requestData['nome'];
            $status = $requestData['status'];
            $categoria = $requestData['categoria'];
            $dataJogo = $requestData['dataJogo'];

            $filter = ['nomeJogador' => $nomeJogador, 'dataJogo' => $dataJogo];
            $update = [
                '$set' => ['status' => $status, 'timestamp' => $timestamp],
                '$setOnInsert' => [
                    'nomeJogador' => $nomeJogador,
                    'categoria' => $categoria,
                    'dataJogo' => $dataJogo,
                    'firstTimestamp' => $timestamp
                ]
            ];
            $options = ['upsert' => true];

            $updateResult = $collection->updateOne($filter, $update, $options);

            $acaoRealizada = ($updateResult->getMatchedCount() > 0 || $updateResult->getModifiedCount() > 0)
                ? "updated"
                : (($updateResult->getUpsertedCount() > 0) ? "inserted" : "unchanged");

            $response = ['status' => 'success', 'message' => 'Confirmação salva!', 'action' => $acaoRealizada];
            $statusCode = 200;

            error_log("Confirmação salva: $nomeJogador -> $status p/ $dataJogo ($acaoRealizada)");

        } catch (Exception $e) {
            $response['message'] = "Erro POST: " . $e->getMessage();
            error_log("API POST Error: " . $e->getMessage() . " | Stack: " . $e->getTraceAsString());
            $statusCode = 500;
        }
    }
}

// Envia a resposta final como JSON
http_response_code($statusCode);
echo json_encode($response);
exit;
?>
