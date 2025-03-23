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
$cliente_id = isset($data['cliente_id']) ? (int)$data['cliente_id'] : 0;
$jogo_id = isset($data['jogo_id']) ? (int)$data['jogo_id'] : 0;
$valor = isset($data['valor']) ? (float)$data['valor'] : 0;
$numeros = isset($data['numeros']) ? $data['numeros'] : '';

// Validar dados
if ($cliente_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Cliente inválido',
        'data' => null
    ]);
    exit;
}

if ($jogo_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Jogo inválido',
        'data' => null
    ]);
    exit;
}

if ($valor <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Valor inválido',
        'data' => null
    ]);
    exit;
}

if (empty($numeros)) {
    echo json_encode([
        'success' => false,
        'message' => 'Números são obrigatórios',
        'data' => null
    ]);
    exit;
}

try {
    // Verificar se o cliente pertence ao revendedor
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE id = ? AND revendedor_id = ?");
    $stmt->bind_param("ii", $cliente_id, $revendedor_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cliente não encontrado',
            'data' => null
        ]);
        exit;
    }

    // Verificar se o jogo está ativo
    $stmt = $conn->prepare("
        SELECT 
            id,
            valor_minimo,
            valor_maximo
        FROM jogos 
        WHERE id = ? AND status = 'ativo'
    ");
    $stmt->bind_param("i", $jogo_id);
    $stmt->execute();
    $jogo = $stmt->get_result()->fetch_assoc();
    
    if (!$jogo) {
        echo json_encode([
            'success' => false,
            'message' => 'Jogo não encontrado ou inativo',
            'data' => null
        ]);
        exit;
    }

    // Validar valor mínimo e máximo
    if ($valor < $jogo['valor_minimo'] || $valor > $jogo['valor_maximo']) {
        echo json_encode([
            'success' => false,
            'message' => sprintf(
                'O valor deve estar entre R$ %.2f e R$ %.2f',
                $jogo['valor_minimo'],
                $jogo['valor_maximo']
            ),
            'data' => null
        ]);
        exit;
    }

    // Inserir aposta
    $stmt = $conn->prepare("
        INSERT INTO apostas (
            revendedor_id,
            cliente_id,
            jogo_id,
            valor,
            numeros,
            status,
            data_criacao
        ) VALUES (?, ?, ?, ?, ?, 'aprovada', NOW())
    ");
    $stmt->bind_param("iiids", $revendedor_id, $cliente_id, $jogo_id, $valor, $numeros);
    $stmt->execute();
    
    $aposta_id = $stmt->insert_id;
    
    // Buscar dados da aposta inserida
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
        'message' => 'Aposta registrada com sucesso',
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
        'message' => 'Erro ao registrar aposta: ' . $e->getMessage(),
        'data' => null
    ]);
} 