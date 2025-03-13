<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/jwt_helper.php';

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Obter dados do POST
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$senha = $data['senha'] ?? '';

if (empty($email) || empty($senha)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email e senha são obrigatórios']);
    exit;
}

try {
    $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar usuário
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Email ou senha inválidos']);
        exit;
    }
    
    if ($usuario['status'] !== 'ativo') {
        http_response_code(403);
        echo json_encode(['error' => 'Conta bloqueada ou inativa']);
        exit;
    }
    
    // Gerar token JWT
    $payload = [
        'user_id' => $usuario['id'],
        'email' => $usuario['email'],
        'tipo' => $usuario['tipo']
    ];
    
    $token = gerarToken($payload);
    
    // Retornar resposta de sucesso
    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'tipo' => $usuario['tipo']
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar ao banco de dados']);
    exit;
} 