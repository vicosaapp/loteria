<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $sql = "UPDATE usuarios SET nome = ?, email = ?";
    $params = [$data['nome'], $data['email']];
    
    // Adiciona senha à query apenas se uma nova senha foi fornecida
    if (!empty($data['senha'])) {
        $sql .= ", senha = ?";
        $params[] = password_hash($data['senha'], PASSWORD_DEFAULT);
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $_SESSION['usuario_id'];
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Atualiza a sessão
    $_SESSION['nome'] = $data['nome'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Perfil atualizado com sucesso!'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar perfil: ' . $e->getMessage()
    ]);
} 