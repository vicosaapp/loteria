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
    // Parâmetros de paginação
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;

    // Filtros
    $where = ["a.revendedor_id = ?"]; 
    $params = [$revendedor_id];
    $types = "i";

    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $where[] = "a.status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }

    if (isset($_GET['cliente_id']) && !empty($_GET['cliente_id'])) {
        $where[] = "a.cliente_id = ?";
        $params[] = (int)$_GET['cliente_id'];
        $types .= "i";
    }

    if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
        $where[] = "DATE(a.data_criacao) >= ?";
        $params[] = $_GET['data_inicio'];
        $types .= "s";
    }

    if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
        $where[] = "DATE(a.data_criacao) <= ?";
        $params[] = $_GET['data_fim'];
        $types .= "s";
    }

    // Contagem total para paginação
    $sql_count = "
        SELECT COUNT(*) as total 
        FROM apostas a
        WHERE " . implode(" AND ", $where);
    
    $stmt = $conn->prepare($sql_count);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];

    // Buscar apostas
    $sql = "
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
        WHERE " . implode(" AND ", $where) . "
        ORDER BY a.data_criacao DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types . "ii", ...[...$params, $limit, $offset]);
    $stmt->execute();
    $apostas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Formatar dados para resposta
    $response = [
        'success' => true,
        'message' => '',
        'data' => [
            'total' => (int)$total,
            'pagina_atual' => $page,
            'total_paginas' => ceil($total / $limit),
            'apostas' => array_map(function($aposta) {
                return [
                    'id' => (int)$aposta['id'],
                    'data' => date('d/m/Y H:i', strtotime($aposta['data_criacao'])),
                    'cliente' => $aposta['cliente'],
                    'jogo' => $aposta['jogo'],
                    'valor' => (float)$aposta['valor'],
                    'status' => ucfirst($aposta['status']),
                    'numeros' => $aposta['numeros']
                ];
            }, $apostas)
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar apostas: ' . $e->getMessage(),
        'data' => null
    ]);
} 