<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$response = ['status' => 'error', 'message' => 'Requisição inválida.'];
$statusCode = 400;

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['acao'])) {
        throw new Exception("Ação não especificada.");
    }

    $collection = getMongoCollection(); // Confirmações
    $jogadoresCollection = getMongoCollection('jogadores'); // Outra coleção para jogadores

    switch ($input['acao']) {
        case 'adicionar':
            if (!isset($input['nome'], $input['tipo'])) throw new Exception("Dados incompletos.");
            $doc = [
                'nome' => $input['nome'],
                'tipo' => ucfirst($input['tipo']), // Mensalista / Avulso / Goleiro
                'criadoEm' => new MongoDB\BSON\UTCDateTime()
            ];
            $jogadoresCollection->insertOne($doc);
            $response = ['status' => 'success', 'message' => "✅ Jogador '{$input['nome']}' adicionado como {$input['tipo']}."];
            $statusCode = 200;
            break;

        case 'remover':
            if (!isset($input['nome'])) throw new Exception("Nome do jogador ausente.");
            $result = $jogadoresCollection->deleteOne(['nome' => $input['nome']]);
            if ($result->getDeletedCount() > 0) {
                $response = ['status' => 'success', 'message' => "🗑️ Jogador '{$input['nome']}' removido."];
            } else {
                $response = ['status' => 'warning', 'message' => "Nenhum jogador encontrado com o nome '{$input['nome']}'."];
            }
            $statusCode = 200;
            break;

        case 'renomear':
            if (!isset($input['antigo'], $input['novo'])) throw new Exception("Dados incompletos para renomear.");
            $result = $jogadoresCollection->updateOne(
                ['nome' => $input['antigo']],
                ['$set' => ['nome' => $input['novo']]]
            );
            if ($result->getModifiedCount() > 0) {
                $response = ['status' => 'success', 'message' => "✏️ Nome alterado de '{$input['antigo']}' para '{$input['novo']}'."];
            } else {
                $response = ['status' => 'warning', 'message' => "Nenhuma modificação feita. Verifique o nome antigo."];
            }
            $statusCode = 200;
            break;

        default:
            throw new Exception("Ação desconhecida.");
    }

} catch (Exception $e) {
    $response['message'] = "Erro: " . $e->getMessage();
    $statusCode = 400;
}

http_response_code($statusCode);
echo json_encode($response);
exit;
