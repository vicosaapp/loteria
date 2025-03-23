<?php
require_once '../../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Verificar ID da aposta
if (empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da aposta não fornecido']);
    exit;
}

try {
    $aposta_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $revendedor_id = $_SESSION['usuario_id'];
    
    if (!$aposta_id) {
        throw new Exception('ID da aposta inválido');
    }
    
    // Verificar se a aposta pertence ao revendedor e está pendente
    $stmt = $pdo->prepare("
        SELECT a.id 
        FROM apostas a
        JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.id = ? 
        AND u.revendedor_id = ? 
        AND a.status = 'pendente'
    ");
    $stmt->execute([$aposta_id, $revendedor_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Aposta não encontrada, não pertence a este revendedor ou não está pendente');
    }
    
    // Cancelar aposta (mudar status para 'rejeitada')
    $stmt = $pdo->prepare("UPDATE apostas SET status = 'rejeitada' WHERE id = ?");
    $result = $stmt->execute([$aposta_id]);
    
    if (!$result) {
        throw new Exception('Erro ao cancelar a aposta');
    }
    
    echo json_encode(['success' => true, 'message' => 'Aposta cancelada com sucesso']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 