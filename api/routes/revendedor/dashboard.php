<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../middleware/auth.php';

// Autenticar usuário
$usuario = autenticarToken();

// Verificar se é revendedor
verificarTipoUsuario($usuario, 'revendedor');

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

try {
    $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar dados do dashboard
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_apostas,
            SUM(valor) as valor_total,
            DATE(data_aposta) as data
        FROM apostas 
        WHERE revendedor_id = ? 
        AND data_aposta >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
        GROUP BY DATE(data_aposta)
        ORDER BY data_aposta DESC
    ");
    
    $stmt->execute([$usuario['user_id']]);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retornar dados
    echo json_encode([
        'success' => true,
        'data' => [
            'resumo_semanal' => $dados,
            'usuario' => [
                'id' => $usuario['user_id'],
                'email' => $usuario['email']
            ]
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar dados do dashboard']);
    exit;
} 