<?php
// Script para verificar se o modo de manutenção está ativo

// Verificar se não há sessão ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Importar o manipulador de manutenção
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/manutencao_handler.php';

// Verificar se o modo de manutenção está ativo
if (verificarModoManutencao()) {
    // Determinar para qual página de manutenção redirecionar com base no caminho atual
    $script_path = $_SERVER['SCRIPT_NAME'];
    $maintenance_url = '/manutencao.php'; // Página padrão
    
    if (strpos($script_path, '/revendedor/') !== false) {
        $maintenance_url = '/revendedor/manutencao.php';
    } elseif (strpos($script_path, '/apostador/') !== false) {
        $maintenance_url = '/apostador/manutencao.php';
    } elseif (strpos($script_path, '/admin/') !== false) {
        // Admins não são bloqueados, mas para consistência
        $maintenance_url = '/admin/manutencao.php';
    }
    
    error_log("[Manutenção] Redirecionando para: " . $maintenance_url . " | Script atual: " . $script_path);
    
    header("Location: " . $maintenance_url);
    exit;
}
?> 