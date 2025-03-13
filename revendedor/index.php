<?php
require_once '../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Location: login.php');
    exit;
}

// Redirecionar para o dashboard
header('Location: dashboard.php');
exit;
?> 