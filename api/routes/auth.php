<?php
/**
 * Rotas de autenticação para o App Loteria
 * 
 * Este arquivo contém as rotas relacionadas à autenticação de usuários
 * para o aplicativo móvel Loteria.
 */

// Rota de login
$app->post('/auth/login', function($request, $response) {
    // Obter dados da requisição
    $data = $request->getParsedBody();
    $email = $data['email'] ?? '';
    $senha = $data['senha'] ?? '';
    
    // Validar dados
    if (empty($email) || empty($senha)) {
        return $response->withJson([
            'status' => 'error',
            'message' => 'Email e senha são obrigatórios'
        ], 400);
    }
    
    // Conectar ao banco de dados
    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verificar se o usuário existe
    $query = "SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        return $response->withJson([
            'status' => 'error',
            'message' => 'Usuário não encontrado'
        ], 404);
    }
    
    // Verificar senha
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!password_verify($senha, $usuario['senha'])) {
        return $response->withJson([
            'status' => 'error',
            'message' => 'Senha incorreta'
        ], 401);
    }
    
    // Gerar token JWT
    require_once '../vendor/autoload.php';
    use \Firebase\JWT\JWT;
    
    $key = getenv('JWT_SECRET') ?: 'sua_chave_secreta_aqui';
    $payload = [
        'iss' => 'loteria_api',
        'iat' => time(),
        'exp' => time() + (60 * 60 * 24), // Token válido por 24 horas
        'user' => [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'tipo' => $usuario['tipo']
        ]
    ];
    
    $token = JWT::encode($payload, $key, 'HS256');
    
    // Retornar resposta com token
    return $response->withJson([
        'status' => 'success',
        'message' => 'Login realizado com sucesso',
        'data' => [
            'token' => $token,
            'usuario' => [
                'id' => $usuario['id'],
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'tipo' => $usuario['tipo']
            ]
        ]
    ]);
});

// Rota para registro de usuários
$app->post('/auth/register', function($request, $response) {
    // Obter dados da requisição
    $data = $request->getParsedBody();
    $nome = $data['nome'] ?? '';
    $email = $data['email'] ?? '';
    $senha = $data['senha'] ?? '';
    $telefone = $data['telefone'] ?? '';
    
    // Validar dados
    if (empty($nome) || empty($email) || empty($senha)) {
        return $response->withJson([
            'status' => 'error',
            'message' => 'Nome, email e senha são obrigatórios'
        ], 400);
    }
    
    // Conectar ao banco de dados
    require_once '../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verificar se o email já está em uso
    $query = "SELECT id FROM usuarios WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return $response->withJson([
            'status' => 'error',
            'message' => 'Este email já está em uso'
        ], 409);
    }
    
    // Criptografar senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    
    // Inserir novo usuário
    $query = "INSERT INTO usuarios (nome, email, senha, telefone, tipo, data_cadastro) 
              VALUES (:nome, :email, :senha, :telefone, 'apostador', NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':senha', $senha_hash);
    $stmt->bindParam(':telefone', $telefone);
    
    if (!$stmt->execute()) {
        return $response->withJson([
            'status' => 'error',
            'message' => 'Erro ao cadastrar usuário'
        ], 500);
    }
    
    $usuario_id = $conn->lastInsertId();
    
    // Retornar resposta
    return $response->withJson([
        'status' => 'success',
        'message' => 'Usuário cadastrado com sucesso',
        'data' => [
            'id' => $usuario_id,
            'nome' => $nome,
            'email' => $email
        ]
    ], 201);
});

// Middleware para verificar token JWT
$app->add(function ($request, $handler) {
    // Rotas que não precisam de autenticação
    $public_routes = [
        '/api/auth/login',
        '/api/auth/register',
        '/api/test'
    ];
    
    $route = $request->getUri()->getPath();
    
    // Se for uma rota pública, continua a execução
    if (in_array($route, $public_routes)) {
        return $handler->handle($request);
    }
    
    // Verificar token de autorização
    $authorization = $request->getHeaderLine('Authorization');
    
    if (empty($authorization)) {
        $response = new \Slim\Psr7\Response();
        return $response->withJson([
            'status' => 'error',
            'message' => 'Token de autorização não fornecido'
        ], 401);
    }
    
    // Extrair token do cabeçalho
    $token = str_replace('Bearer ', '', $authorization);
    
    try {
        // Verificar token JWT
        require_once '../vendor/autoload.php';
        use \Firebase\JWT\JWT;
        use \Firebase\JWT\Key;
        
        $key = getenv('JWT_SECRET') ?: 'sua_chave_secreta_aqui';
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        
        // Adicionar dados do usuário à requisição
        $request = $request->withAttribute('usuario', $decoded->user);
        
        // Continuar a execução
        return $handler->handle($request);
    } catch (\Exception $e) {
        $response = new \Slim\Psr7\Response();
        return $response->withJson([
            'status' => 'error',
            'message' => 'Token inválido ou expirado'
        ], 401);
    }
}); 