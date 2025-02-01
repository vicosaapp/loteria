<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    // Se a senha foi fornecida, atualiza com a nova senha
    if (!empty($senha)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ? WHERE id = ? AND tipo = 'admin'");
        $stmt->execute([$nome, $email, $senha_hash, $_SESSION['usuario_id']]);
    } else {
        // Se não, mantém a senha atual
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ? AND tipo = 'admin'");
        $stmt->execute([$nome, $email, $_SESSION['usuario_id']]);
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 