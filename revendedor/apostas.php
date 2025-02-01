<?php
require_once '../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Location: ../login.php');
    exit;
}

// Filtros
$cliente_id = $_GET['cliente_id'] ?? null;
$status = $_GET['status'] ?? null;
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

// Construir query base
$sql = "
    SELECT 
        a.*,
        u.nome as nome_apostador,
        j.nome as nome_jogo
    FROM apostas a
    JOIN usuarios u ON a.usuario_id = u.id
    JOIN jogos j ON a.tipo_jogo_id = j.id
    WHERE u.revendedor_id = ?
";

$params = [$_SESSION['usuario_id']];

// Adicionar filtros
if ($cliente_id) {
    $sql .= " AND u.id = ?";
    $params[] = $cliente_id;
}

if ($status) {
    $sql .= " AND a.status = ?";
    $params[] = $status;
}

$sql .= " AND DATE(a.created_at) BETWEEN ? AND ?";
$params[] = $data_inicio;
$params[] = $data_fim;

$sql .= " ORDER BY a.created_at DESC";

// Buscar apostas
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar clientes para o filtro
$stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE revendedor_id = ? AND tipo = 'usuario' ORDER BY nome");
$stmt->execute([$_SESSION['usuario_id']]);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define a página atual
$currentPage = 'apostas';

// Carrega a view
ob_start();
include 'views/apostas.view.php';
$content = ob_get_clean();

require_once 'includes/layout.php';
?> 