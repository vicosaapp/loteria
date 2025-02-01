<?php
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$aposta_id = $data['id'] ?? 0;

try {
    // Verificar se a aposta pertence a um cliente do revendedor
    $stmt = $pdo->prepare("
        SELECT a.id 
        FROM apostas a
        JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.id = ? AND u.revendedor_id = ? AND a.status = 'pendente'
    ");
    $stmt->execute([$aposta_id, $_SESSION['usuario_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Aposta não encontrada ou não está pendente');
    }
    
    // Criar notificação para o admin
    $stmt = $pdo->prepare("
        INSERT INTO notificacoes (usuario_id, tipo, referencia_id, mensagem, status)
        VALUES (?, 'solicitacao_aprovacao', ?, ?, 'pendente')
    ");
    
    $stmt->execute([
        $_SESSION['usuario_id'],
        $aposta_id,
        'Solicitação de aprovação de aposta'
    ]);
    
    echo json_encode(['success' => true]);
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 