<?php
session_start();
if (!isset($_SESSION['logado'])) {
    header('Location: login.php');
    exit;
}
?>
<?php
require_once __DIR__ . '/../api/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$action = $_GET['action'] ?? '';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $collection = getMongoCollection();

    switch ($action) {

        case 'listar':
            $hoje = new DateTime("now", new DateTimeZone('America/Sao_Paulo'));
            $diaDaSemana = (int)$hoje->format('w');
            $diasAteSexta = 5 - $diaDaSemana;
            if ($diasAteSexta <= 0) $diasAteSexta += 7;
            $proximaSexta = clone $hoje;
            $proximaSexta->modify("+$diasAteSexta days");
            $dataJogoFormatada = $proximaSexta->format('d/m');

            $pipeline = [
                ['$match' => ['dataJogo' => $dataJogoFormatada]],
                ['$sort' => ['timestamp' => -1]],
                ['$group' => ['_id' => '$nomeJogador', 'ultimoRegistro' => ['$first' => '$$ROOT']]]
            ];

            $result = $collection->aggregate($pipeline);
            $dados = [];

            foreach ($result as $entry) {
                $registro = $entry['ultimoRegistro'];
                $dados[] = [
                    'nome' => $registro['nomeJogador'],
                    'presente' => $registro['presente']
                ];
            }

            echo json_encode($dados);
            break;

        case 'limpar':
            $collection->deleteMany([]);
            echo json_encode(['sucesso' => true]);
            break;

        case 'listarJogadores':
            $jogadores = $client->pelada->jogadores->find([], ['sort' => ['nome' => 1]]);
            $resultado = [];
            foreach ($jogadores as $jogador) {
                $resultado[] = ['id' => (string)$jogador->_id, 'nome' => $jogador->nome];
            }
            echo json_encode($resultado);
            break;

        case 'adicionarJogador':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['nome']) || empty(trim($data['nome']))) {
                http_response_code(400);
                echo json_encode(['erro' => 'Nome inválido']);
                break;
            }
            $client->pelada->jogadores->insertOne(['nome' => trim($data['nome'])]);
            echo json_encode(['sucesso' => true]);
            break;

        case 'removerJogador':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'ID não informado']);
                break;
            }
            $client->pelada->jogadores->deleteOne(['_id' => new MongoDB\BSON\ObjectId($data['id'])]);
            echo json_encode(['sucesso' => true]);
            break;

        case 'editarJogador':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['id'], $data['novoNome'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados incompletos']);
                break;
            }
            $client->pelada->jogadores->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($data['id'])],
                ['$set' => ['nome' => trim($data['novoNome'])]]
            );
            echo json_encode(['sucesso' => true]);
            break;

        default:
            echo json_encode(['erro' => 'Ação inválida']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
