<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

// Verificar autenticação
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do usuário não fornecido']);
    exit;
}

$usuario_id = intval($_GET['id']);

try {
    // Buscar informações do usuário
    $stmt = $pdo->prepare("
        SELECT 
            u.id, u.nome, u.email, u.telefone, u.tipo, u.status,
            u.data_cadastro, u.ultimo_login
        FROM usuarios u
        WHERE u.id = ?
    ");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        exit;
    }
    
    // Buscar a última aposta do usuário
    $stmt = $pdo->prepare("
        SELECT 
            a.id, a.numeros, a.valor_aposta, a.status, a.data_criacao,
            j.nome as jogo_nome
        FROM apostas a
        JOIN jogos j ON a.tipo_jogo_id = j.id
        WHERE a.usuario_id = ?
        ORDER BY a.data_criacao DESC
        LIMIT 1
    ");
    $stmt->execute([$usuario_id]);
    $aposta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Se não encontrou na tabela apostas, buscar na tabela apostas_importadas
    if (!$aposta) {
        $stmt = $pdo->prepare("
            SELECT 
                ai.id, ai.numeros, ai.valor_aposta, ai.status, ai.data_importacao as data_criacao,
                ai.jogo_nome
            FROM apostas_importadas ai
            WHERE ai.revendedor_id = ? OR ai.usuario_id = ?
            ORDER BY ai.data_importacao DESC
            LIMIT 1
        ");
        $stmt->execute([$usuario_id, $usuario_id]);
        $aposta = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Formatar datas
    if ($usuario['data_cadastro']) {
        $usuario['data_cadastro'] = date('d/m/Y H:i', strtotime($usuario['data_cadastro']));
    }
    
    if ($usuario['ultimo_login']) {
        $usuario['ultimo_login'] = date('d/m/Y H:i', strtotime($usuario['ultimo_login']));
    }
    
    if ($aposta && $aposta['data_criacao']) {
        $aposta['data_criacao'] = date('d/m/Y H:i', strtotime($aposta['data_criacao']));
    }
    
    // Contar apostas do usuário
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM apostas WHERE usuario_id = ?
    ");
    $stmt->execute([$usuario_id]);
    $total_apostas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Contar apostas importadas do usuário (se for revendedor)
    $total_apostas_importadas = 0;
    if ($usuario['tipo'] === 'revendedor') {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM apostas_importadas WHERE revendedor_id = ?
        ");
        $stmt->execute([$usuario_id]);
        $total_apostas_importadas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    // Retornar resultados
    echo json_encode([
        'success' => true,
        'usuario' => $usuario,
        'aposta' => $aposta,
        'total_apostas' => $total_apostas,
        'total_apostas_importadas' => $total_apostas_importadas
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao buscar detalhes do ganhador: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao buscar detalhes: ' . $e->getMessage()
    ]);
} 