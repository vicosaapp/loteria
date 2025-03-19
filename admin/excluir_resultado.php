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
        // Verificar se o resultado existe
        $stmt = $pdo->prepare("SELECT id FROM resultados WHERE id = ?");
        $stmt->execute([$id]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Resultado não encontrado.");
        }
        
        // Excluir o resultado
        $stmt = $pdo->prepare("DELETE FROM resultados WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['sucesso'] = "Resultado excluído com sucesso.";
    } catch (Exception $e) {
        $_SESSION['erro'] = "Erro ao excluir resultado: " . $e->getMessage();
    }
} else {
    $_SESSION['erro'] = "ID do resultado não fornecido.";
}

header('Location: gerenciar_resultados.php');
exit; 