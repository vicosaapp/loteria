<?php
// Iniciar sessão para verificar se o usuário é admin
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

// Verificar se é uma requisição AJAX para status de manutenção
if (isset($_GET['check_status']) && $_GET['check_status'] === 'ajax') {
    try {
        // Verificar se o banco de dados já está incluído
        if (!isset($pdo)) {
            require_once __DIR__ . '/../config/database.php';
        }
        
        $stmt = $pdo->query("SELECT modo_manutencao FROM configuracoes WHERE id = 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode(['modo_manutencao' => $config['modo_manutencao'], 'time' => time()]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Verificar se o modo manutenção ainda está ativo
if (!verificarModoManutencao()) {
    header("Location: /revendedor/");
    exit;
}

// Exibir a página de manutenção
echo exibirPaginaManutencao('revendedor');
?> 