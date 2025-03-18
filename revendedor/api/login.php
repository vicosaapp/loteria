<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Responder imediatamente para requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido',
        'data' => null
    ]);
    exit;
}

// Obter dados do POST
$data = json_decode(file_get_contents('php://input'), true);
$email = isset($data['email']) ? $data['email'] : '';
$senha = isset($data['senha']) ? $data['senha'] : '';

if (empty($email) || empty($senha)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email e senha são obrigatórios',
        'data' => null
    ]);
    exit;
}

try {
    // Buscar revendedor
    $stmt = $conn->prepare("SELECT id, nome, senha FROM revendedores WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email ou senha inválidos',
            'data' => null
        ]);
        exit;
    }

    $revendedor = $result->fetch_assoc();

    // Verificar senha
    if (!password_verify($senha, $revendedor['senha'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Email ou senha inválidos',
            'data' => null
        ]);
        exit;
    }

    // Gerar token
    $token = bin2hex(random_bytes(32));

    // Salvar token
    $stmt = $conn->prepare("UPDATE revendedores SET token = ? WHERE id = ?");
    $stmt->bind_param("si", $token, $revendedor['id']);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'data' => [
            'id' => (int)$revendedor['id'],
            'nome' => $revendedor['nome'],
            'token' => $token
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao realizar login: ' . $e->getMessage(),
        'data' => null
    ]);
} 