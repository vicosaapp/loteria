<?php
session_start();
require_once '../../config/database.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Acesso negado']));
}

// Pega os dados do POST
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Validações básicas
    if (empty($data['id']) || empty($data['nome']) || empty($data['email'])) {
        throw new Exception('Dados incompletos');
    }

    // Verifica se o email já existe para outro usuário
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $stmt->execute([$data['email'], $data['id']]);
    if ($stmt->rowCount() > 0) {
        throw new Exception('Este email já está em uso');
    }

    // Prepara a atualização
    if (!empty($data['senha'])) {
        // Se tem senha nova, atualiza com a senha
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ?, senha = ? WHERE id = ? AND tipo = 'usuario'");
        $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);
        $stmt->execute([$data['nome'], $data['email'], $data['telefone'], $senha_hash, $data['id']]);
    } else {
        // Se não tem senha nova, atualiza só nome, email e telefone
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ? WHERE id = ? AND tipo = 'usuario'");
        $stmt->execute([$data['nome'], $data['email'], $data['telefone'], $data['id']]);
    }

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Nenhuma alteração realizada');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 