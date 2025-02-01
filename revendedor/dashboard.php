<?php
require_once '../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Location: ../login.php');
    exit;
}

// Buscar dados do revendedor
$stmt = $pdo->prepare("
    SELECT nome, email, whatsapp, comissao 
    FROM usuarios 
    WHERE id = ? AND tipo = 'revendedor'
");
$stmt->execute([$_SESSION['usuario_id']]);
$revendedor = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar estatísticas
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(COUNT(DISTINCT u.id), 0) as total_clientes,
        COALESCE(COUNT(a.id), 0) as total_apostas,
        COALESCE(SUM(CASE WHEN a.status = 'aprovada' THEN a.valor_aposta ELSE 0 END), 0) as total_apostas_aprovadas,
        COALESCE(SUM(CASE WHEN a.status = 'pendente' THEN 1 ELSE 0 END), 0) as apostas_pendentes
    FROM usuarios u 
    LEFT JOIN apostas a ON u.id = a.usuario_id
    WHERE u.revendedor_id = ?
");
$stmt->execute([$_SESSION['usuario_id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Garantir que todos os valores estatísticos sejam numéricos
$stats['total_clientes'] = (int)$stats['total_clientes'];
$stats['total_apostas'] = (int)$stats['total_apostas'];
$stats['total_apostas_aprovadas'] = (float)$stats['total_apostas_aprovadas'];
$stats['apostas_pendentes'] = (int)$stats['apostas_pendentes'];

// Buscar últimas apostas
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        u.nome as nome_apostador,
        j.nome as nome_jogo
    FROM apostas a
    JOIN usuarios u ON a.usuario_id = u.id
    JOIN jogos j ON a.tipo_jogo_id = j.id
    WHERE u.revendedor_id = ?
    ORDER BY a.created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['usuario_id']]);
$ultimas_apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define a página atual
$currentPage = 'dashboard';

// Carrega a view
ob_start();
include 'views/dashboard.view.php';
$content = ob_get_clean();

require_once 'includes/layout.php';
?> 