<?php
require_once '../../config/database.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

if (!sistema_disponivel()) {
    echo json_encode([
        'success' => false, 
        'message' => 'Sistema fora do horário de funcionamento'
    ]);
    exit;
}

try {
    $horario_inicio = $_POST['horario_inicio'];
    $horario_fim = $_POST['horario_fim'];
    $dias_funcionamento = implode(',', $_POST['dias_funcionamento']);
    $status_sistema = $_POST['status_sistema'];
    
    $stmt = $pdo->prepare("
        UPDATE configuracoes 
        SET horario_inicio = ?,
            horario_fim = ?,
            dias_funcionamento = ?,
            status_sistema = ?,
            updated_by = ?
        WHERE id = 1
    ");
    
    $stmt->execute([
        $horario_inicio,
        $horario_fim,
        $dias_funcionamento,
        $status_sistema,
        $_SESSION['usuario_id']
    ]);
    
    echo json_encode(['success' => true]);
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 