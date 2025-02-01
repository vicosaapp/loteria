<?php
session_start();
require_once '../config/database.php';

// Define a página atual para o menu
$currentPage = 'configuracoes';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

try {
    // Buscar configurações atuais
    $stmt = $pdo->query("SELECT * FROM configuracoes ORDER BY id DESC LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar dados do admin
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro ao buscar informações: " . $e->getMessage());
}

ob_start();
include 'views/configuracoes.view.php';
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 