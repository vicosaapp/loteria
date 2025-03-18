<?php
header('Content-Type: application/json');
require_once('../includes/conexao.php');
require_once('../includes/funcoes.php');

// Verifica o método da requisição
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));

// Função de autenticação do revendedor
function autenticarRevendedor($email, $senha) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM revendedores WHERE email = ? AND status = 1");
    $stmt->execute([$email]);
    $revendedor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($revendedor && password_verify($senha, $revendedor['senha'])) {
        // Gera token de acesso
        $token = bin2hex(random_bytes(32));
        
        // Salva o token no banco
        $stmt = $pdo->prepare("UPDATE revendedores SET api_token = ? WHERE id = ?");
        $stmt->execute([$token, $revendedor['id']]);
        
        return [
            'status' => 'success',
            'token' => $token,
            'revendedor' => [
                'id' => $revendedor['id'],
                'nome' => $revendedor['nome'],
                'email' => $revendedor['email'],
                'saldo' => $revendedor['saldo']
            ]
        ];
    }
    
    return ['status' => 'error', 'message' => 'Credenciais inválidas'];
}

// Rotas da API
switch($request[0]) {
    case 'login':
        if ($method == 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(autenticarRevendedor($data['email'], $data['senha']));
        }
        break;
        
    case 'perfil':
        // Implementar verificação do token
        if ($method == 'GET') {
            // Retornar dados do perfil
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Rota não encontrada']);
        break;
}