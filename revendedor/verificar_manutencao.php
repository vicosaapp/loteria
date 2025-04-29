<?php
/**
 * Verificação de manutenção para o painel do revendedor
 * Este arquivo verifica se o sistema está em modo de manutenção
 * e redireciona usuários não-administradores para a página de manutenção
 */

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Não verificar manutenção na própria página de manutenção para evitar redirecionamento infinito
$paginaAtual = basename($_SERVER['PHP_SELF']);
if ($paginaAtual === 'manutencao.php') {
    return;
}

// Não verificar para administradores
if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin') {
    return;
}

try {
    // Carregar conexão com o banco de dados
    require_once __DIR__ . '/../config/database.php';
    
    // Verificar se o modo de manutenção está ativo
    $stmt = $pdo->query("SELECT modo_manutencao, mensagem_manutencao FROM configuracoes WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Se estiver em manutenção e o usuário não for admin, redirecionar para página de manutenção
    if (isset($config['modo_manutencao']) && $config['modo_manutencao'] == 1) {
        // Registrar tentativa de acesso durante manutenção para análise
        if (isset($_SESSION['usuario_id'])) {
            $userId = $_SESSION['usuario_id'];
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            $pageRequested = $_SERVER['REQUEST_URI'];
            
            // Registrar log de tentativa de acesso durante manutenção
            $logStmt = $pdo->prepare("INSERT INTO logs_manutencao (usuario_id, ip, pagina, data_acesso) VALUES (?, ?, ?, NOW())");
            $logStmt->execute([$userId, $ipAddress, $pageRequested]);
        }
        
        // Redirecionar para a página de manutenção
        header("Location: manutencao.php");
        exit;
    }
} catch (PDOException $e) {
    // Em caso de erro no banco de dados, registrar o erro mas não bloquear o acesso
    error_log("Erro ao verificar modo de manutenção: " . $e->getMessage());
    // Não redirecionar para evitar problemas de acesso caso o banco esteja indisponível
}
?> 