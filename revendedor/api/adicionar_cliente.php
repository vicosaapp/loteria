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
$nome = isset($data['nome']) ? trim($data['nome']) : '';
$telefone = isset($data['telefone']) ? trim($data['telefone']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';

// Validar dados
if (empty($nome)) {
    echo json_encode([
        'success' => false,
        'message' => 'Nome é obrigatório',
        'data' => null
    ]);
    exit;
}

try {
    // Verificar se já existe cliente com mesmo email ou telefone
    if (!empty($email) || !empty($telefone)) {
        $where = [];
        $params = [];
        $types = "";
        
        if (!empty($email)) {
            $where[] = "email = ?";
            $params[] = $email;
            $types .= "s";
        }
        
        if (!empty($telefone)) {
            $where[] = "telefone = ?";
            $params[] = $telefone;
            $types .= "s";
        }
        
        $where[] = "revendedor_id = ?";
        $params[] = $revendedor_id;
        $types .= "i";
        
        $sql = "SELECT id FROM clientes WHERE " . implode(" OR ", $where);
        $stmt = $conn->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Já existe um cliente cadastrado com este email ou telefone',
                'data' => null
            ]);
            exit;
        }
    }

    // Inserir novo cliente
    $stmt = $conn->prepare("
        INSERT INTO clientes (
            revendedor_id,
            nome,
            telefone,
            email,
            data_cadastro
        ) VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("isss", $revendedor_id, $nome, $telefone, $email);
    $stmt->execute();
    
    $cliente_id = $stmt->insert_id;
    
    // Buscar dados do cliente inserido
    $stmt = $conn->prepare("
        SELECT 
            id,
            nome,
            telefone,
            email,
            data_cadastro
        FROM clientes 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'message' => 'Cliente cadastrado com sucesso',
        'data' => [
            'id' => (int)$cliente['id'],
            'nome' => $cliente['nome'],
            'telefone' => $cliente['telefone'],
            'email' => $cliente['email'],
            'data_cadastro' => date('d/m/Y', strtotime($cliente['data_cadastro']))
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao cadastrar cliente: ' . $e->getMessage(),
        'data' => null
    ]);
} 