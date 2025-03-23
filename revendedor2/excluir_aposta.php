<?php
require_once '../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Location: ../login.php');
    exit;
}

// Verificar se foi fornecido um ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: apostas.php');
    exit;
}

try {
    // Verificar se a aposta pertence ao revendedor atual
    $stmt = $pdo->prepare("
        SELECT id 
        FROM apostas_importadas 
        WHERE id = ? AND revendedor_id = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['usuario_id']]);
    
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Aposta não encontrada ou você não tem permissão para excluí-la.";
        header('Location: apostas.php');
        exit;
    }
    
    // Excluir a aposta
    $stmt = $pdo->prepare("DELETE FROM apostas_importadas WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    $_SESSION['success'] = "Aposta excluída com sucesso!";
    
} catch (Exception $e) {
    error_log("Erro ao excluir aposta: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao excluir a aposta. Por favor, tente novamente.";
}

header('Location: apostas.php');
exit; 