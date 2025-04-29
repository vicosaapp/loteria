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

// Filtros
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

// Buscar comissão do revendedor
$stmt = $pdo->prepare("SELECT comissao FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$taxa_comissao = $stmt->fetchColumn();

// Buscar estatísticas de comissões
$stmt = $pdo->prepare("
    SELECT 
        COUNT(a.id) as total_apostas,
        SUM(a.valor_aposta) as total_apostado,
        SUM(a.valor_aposta * (? / 100)) as total_comissao,
        DATE(a.created_at) as data
    FROM apostas a
    JOIN usuarios u ON a.usuario_id = u.id
    WHERE u.revendedor_id = ? 
    AND a.status = 'aprovada'
    AND DATE(a.created_at) BETWEEN ? AND ?
    GROUP BY DATE(a.created_at)
    ORDER BY data DESC
");

$stmt->execute([$taxa_comissao, $_SESSION['usuario_id'], $data_inicio, $data_fim]);
$comissoes_diarias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totais
$total_geral_apostas = 0;
$total_geral_apostado = 0;
$total_geral_comissao = 0;

foreach ($comissoes_diarias as $comissao) {
    $total_geral_apostas += $comissao['total_apostas'];
    $total_geral_apostado += $comissao['total_apostado'];
    $total_geral_comissao += $comissao['total_comissao'];
}

// Define a página atual
$currentPage = 'comissoes';

// Carrega a view
ob_start();
include 'views/comissoes.view.php';
$content = ob_get_clean();

require_once 'includes/layout.php';
?> 