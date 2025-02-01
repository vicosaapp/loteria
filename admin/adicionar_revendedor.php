<?php
require_once '../config/database.php';
session_start();

// Verificar se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Buscar revendedores
$stmt = $pdo->query("SELECT * FROM usuarios WHERE tipo = 'revendedor' ORDER BY nome");
$revendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define constante para segurança
define('ADMIN', true);

// Define a página atual
$currentPage = 'revendedores';

// Carrega a view
ob_start();
include 'views/adicionar_revendedor.view.php';
$content = ob_get_clean();

require_once 'includes/layout.php';
?> 