<?php
session_start();
require_once '../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND tipo = 'admin' LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Login bem sucedido
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];
            
            // Redireciona para o dashboard
            header('Location: http://loteria.test/admin/dashboard.php');
            exit;
        } else {
            // Login falhou
            $_SESSION['erro'] = 'Email ou senha incorretos';
            header('Location: http://loteria.test/admin/login.php');
            exit;
        }
    } catch(PDOException $e) {
        $_SESSION['erro'] = 'Erro ao tentar fazer login';
        header('Location: http://loteria.test/admin/login.php');
        exit;
    }
} else {
    header('Location: http://loteria.test/admin/login.php');
    exit;
}
?> 