<?php
require_once __DIR__ . '/../../includes/jwt_helper.php';

/**
 * Middleware de autenticação
 * @return array Dados do usuário do token
 */
function autenticarToken() {
    // Verificar se o token foi enviado
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';
    
    if (empty($auth) || !preg_match('/^Bearer\s+(.*)$/', $auth, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'Token não fornecido']);
        exit;
    }
    
    $token = $matches[1];
    
    // Validar token
    $payload = validarToken($token);
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido ou expirado']);
        exit;
    }
    
    return $payload;
}

/**
 * Verifica se o usuário tem o tipo requerido
 * @param array $payload Dados do usuário
 * @param string|array $tipos Tipo(s) permitido(s)
 */
function verificarTipoUsuario($payload, $tipos) {
    $tipos = (array) $tipos;
    
    if (!in_array($payload['tipo'], $tipos)) {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso não autorizado para este tipo de usuário']);
        exit;
    }
} 