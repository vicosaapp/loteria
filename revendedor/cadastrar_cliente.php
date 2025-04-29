<?php
require_once '../config/database.php';

// Verificar o modo de manutenção
require_once __DIR__ . '/../includes/verificar_manutencao.php';

// Verificar se não há sessão ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $whatsapp = $_POST['whatsapp'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, senha, whatsapp, tipo, revendedor_id) 
            VALUES (?, ?, ?, ?, 'usuario', ?)
        ");
        
        $stmt->execute([$nome, $email, $senha, $whatsapp, $_SESSION['usuario_id']]);
        
        $mensagem = "Cliente cadastrado com sucesso!";
        $tipo_mensagem = "success";
    } catch(PDOException $e) {
        $mensagem = "Erro ao cadastrar cliente: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}
?>

<!-- HTML do formulário de cadastro -->
<!-- Adicione o formulário com os campos nome, email, senha e whatsapp --> 