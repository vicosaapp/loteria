<?php
require_once '../config/database.php';

// Verificar o modo de manutenção
require_once __DIR__ . '/verificar_manutencao.php';

// Verificar se não há sessão ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Location: ../login.php');
    exit;
}

// Buscar clientes do revendedor
$stmt = $pdo->prepare("
    SELECT 
        u.*,
        COALESCE(COUNT(DISTINCT a.id), 0) as total_apostas,
        COALESCE(SUM(CASE WHEN a.status = 'aprovada' THEN a.valor_aposta ELSE 0 END), 0) as total_apostado
    FROM usuarios u
    LEFT JOIN apostas a ON u.id = a.usuario_id
    WHERE u.revendedor_id = ?
    GROUP BY u.id
    ORDER BY u.nome
");
$stmt->execute([$_SESSION['usuario_id']]);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define a página atual
$currentPage = 'clientes';

// Carrega a view
ob_start();
include 'views/clientes.view.php';
$content = ob_get_clean();

require_once 'includes/layout.php';
?> 