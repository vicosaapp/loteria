<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Importar o manipulador de manutenção (caminho relativo)
require_once __DIR__ . '/../includes/manutencao_handler.php';

// Se o usuário for admin, redirecionar para o dashboard
if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin') {
    header("Location: /admin/dashboard.php");
    exit;
}

// Verificar se é uma requisição AJAX
if (verificarStatusManutencaoAjax()) {
    exit; // A função já trata a saída e o exit
}

// Verificar se o modo manutenção ainda está ativo
if (!verificarModoManutencao()) {
    header("Location: /apostador/");
    exit;
}

// Exibir a página de manutenção
echo exibirPaginaManutencao('apostador');
?>
