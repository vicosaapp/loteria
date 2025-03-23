<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido',
        'data' => null
    ]);
    exit;
}

// Verificar autenticação
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

if (empty($token)) {
    echo json_encode([
        'success' => false,
        'message' => 'Token não fornecido',
        'data' => null
    ]);
    exit;
}

// Verificar token no banco de dados
$stmt = $conn->prepare("SELECT id, nome FROM revendedores WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Token inválido',
        'data' => null
    ]);
    exit;
}

$revendedor = $result->fetch_assoc();
$revendedor_id = $revendedor['id'];

// Obter dados do POST
$data = json_decode(file_get_contents('php://input'), true);
$aposta_id = isset($data['aposta_id']) ? (int)$data['aposta_id'] : 0;

// Validar dados
if ($aposta_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Aposta inválida',
        'data' => null
    ]);
    exit;
}

try {
    // Verificar se a aposta existe e pertence ao revendedor
    $stmt = $conn->prepare("
        SELECT 
            id,
            status
        FROM apostas 
        WHERE id = ? AND revendedor_id = ?
    ");
    $stmt->bind_param("ii", $aposta_id, $revendedor_id);
    $stmt->execute();
    $aposta = $stmt->get_result()->fetch_assoc();
    
    if (!$aposta) {
        echo json_encode([
            'success' => false,
            'message' => 'Aposta não encontrada',
            'data' => null
        ]);
        exit;
    }

    // Verificar se a aposta já está cancelada
    if ($aposta['status'] === 'cancelada') {
        echo json_encode([
            'success' => false,
            'message' => 'Esta aposta já está cancelada',
            'data' => null
        ]);
        exit;
    }

    // Verificar se a aposta já foi paga
    if ($aposta['status'] === 'paga') {
        echo json_encode([
            'success' => false,
            'message' => 'Não é possível cancelar uma aposta já paga',
            'data' => null
        ]);
        exit;
    }

    // Cancelar aposta
    $stmt = $conn->prepare("UPDATE apostas SET status = 'cancelada' WHERE id = ?");
    $stmt->bind_param("i", $aposta_id);
    $stmt->execute();
    
    // Buscar dados atualizados da aposta
    $stmt = $conn->prepare("
        SELECT 
            a.id,
            a.data_criacao,
            a.valor,
            a.status,
            a.numeros,
            c.nome as cliente,
            j.nome as jogo
        FROM apostas a
        JOIN clientes c ON a.cliente_id = c.id
        JOIN jogos j ON a.jogo_id = j.id
        WHERE a.id = ?
    ");
    $stmt->bind_param("i", $aposta_id);
    $stmt->execute();
    $aposta = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'message' => 'Aposta cancelada com sucesso',
        'data' => [
            'id' => (int)$aposta['id'],
            'data' => date('d/m/Y H:i', strtotime($aposta['data_criacao'])),
            'cliente' => $aposta['cliente'],
            'jogo' => $aposta['jogo'],
            'valor' => (float)$aposta['valor'],
            'status' => ucfirst($aposta['status']),
            'numeros' => $aposta['numeros']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao cancelar aposta: ' . $e->getMessage(),
        'data' => null
    ]);
} 