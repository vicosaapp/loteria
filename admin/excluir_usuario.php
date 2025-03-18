<?php
require_once '../config/database.php';
session_start();

// Verificar se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Iniciar transação
        $pdo->beginTransaction();
        
        // Primeiro, excluir todas as apostas vinculadas ao revendedor
        $stmt = $pdo->prepare("DELETE FROM apostas_importadas WHERE revendedor_id = ?");
        $stmt->execute([$id]);
        
        // Depois, excluir o usuário
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        
        // Confirmar transação
        $pdo->commit();
        
        $_SESSION['sucesso'] = "Usuário e suas apostas foram excluídos com sucesso.";
    } catch (Exception $e) {
        // Em caso de erro, desfaz todas as alterações
        $pdo->rollBack();
        $_SESSION['erro'] = "Erro ao excluir usuário: " . $e->getMessage();
    }
} else {
    $_SESSION['erro'] = "ID do usuário não fornecido.";
}

// Redirecionar de volta para a lista de usuários
header('Location: usuarios.php');
exit; 