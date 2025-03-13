<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    // Validar dados recebidos
    if (empty($_POST['nome']) || empty($_POST['email']) || empty($_POST['senha']) || empty($_POST['tipo'])) {
        throw new Exception('Dados incompletos');
    }

    // Preparar os dados
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $whatsapp = trim($_POST['whatsapp']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $tipo = $_POST['tipo'];
    $comissao = isset($_POST['comissao']) ? floatval($_POST['comissao']) : 0;

    // Verificar se email já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        throw new Exception('Email já cadastrado');
    }

    // Inserir novo usuário
    $sql = "INSERT INTO usuarios (nome, email, whatsapp, senha, tipo, comissao) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$nome, $email, $whatsapp, $senha, $tipo, $comissao]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Erro ao inserir usuário');
    }

} catch (Exception $e) {
    error_log('Erro ao salvar usuário: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 