<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// Responder imediatamente para requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

// Buscar dados do dashboard
try {
    // Total de clientes
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM clientes WHERE revendedor_id = ?");
    $stmt->bind_param("i", $revendedor_id);
    $stmt->execute();
    $total_clientes = $stmt->get_result()->fetch_assoc()['total'];

    // Total de apostas e valores
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_apostas,
            SUM(CASE WHEN status != 'cancelada' THEN valor ELSE 0 END) as total_vendas,
            SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as apostas_pendentes,
            SUM(CASE WHEN status = 'paga' THEN 1 ELSE 0 END) as apostas_pagas
        FROM apostas 
        WHERE revendedor_id = ?
    ");
    $stmt->bind_param("i", $revendedor_id);
    $stmt->execute();
    $apostas = $stmt->get_result()->fetch_assoc();

    // Últimas apostas
    $stmt = $conn->prepare("
        SELECT 
            a.id,
            a.data_criacao,
            c.nome as cliente,
            j.nome as jogo,
            a.valor,
            a.status
        FROM apostas a
        JOIN clientes c ON a.cliente_id = c.id
        JOIN jogos j ON a.jogo_id = j.id
        WHERE a.revendedor_id = ?
        ORDER BY a.data_criacao DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $revendedor_id);
    $stmt->execute();
    $ultimas_apostas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Formatar dados para resposta
    $response = [
        'success' => true,
        'message' => '',
        'data' => [
            'total_clientes' => (int)$total_clientes,
            'total_apostas' => (int)$apostas['total_apostas'],
            'total_vendas' => number_format((float)$apostas['total_vendas'], 2, '.', ''),
            'apostas_pendentes' => (int)$apostas['apostas_pendentes'],
            'apostas_pagas' => (int)$apostas['apostas_pagas'],
            'comissao' => '20.00',
            'ultimas_apostas' => array_map(function($aposta) {
                return [
                    'id' => (int)$aposta['id'],
                    'data' => date('d/m/Y H:i', strtotime($aposta['data_criacao'])),
                    'cliente' => $aposta['cliente'],
                    'jogo' => $aposta['jogo'],
                    'valor' => number_format((float)$aposta['valor'], 2, '.', ''),
                    'status' => ucfirst($aposta['status'])
                ];
            }, $ultimas_apostas)
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar dados do dashboard: ' . $e->getMessage(),
        'data' => null
    ]);
} 