<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

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

try {
    // Buscar jogos ativos
    $stmt = $conn->prepare("
        SELECT 
            id,
            nome,
            descricao,
            valor_minimo,
            valor_maximo,
            status,
            regras
        FROM jogos 
        WHERE status = 'ativo'
        ORDER BY nome ASC
    ");
    $stmt->execute();
    $jogos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Formatar dados para resposta
    $response = [
        'success' => true,
        'message' => '',
        'data' => array_map(function($jogo) {
            return [
                'id' => (int)$jogo['id'],
                'nome' => $jogo['nome'],
                'descricao' => $jogo['descricao'],
                'valor_minimo' => (float)$jogo['valor_minimo'],
                'valor_maximo' => (float)$jogo['valor_maximo'],
                'status' => $jogo['status'],
                'regras' => $jogo['regras']
            ];
        }, $jogos)
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar jogos: ' . $e->getMessage(),
        'data' => null
    ]);
} 