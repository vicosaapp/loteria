<?php
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function isAdmin() {
    return isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('/');
    }
}

function sistema_disponivel() {
    global $pdo;
    
    // Buscar configurações
    $stmt = $pdo->query("SELECT * FROM configuracoes ORDER BY id DESC LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar status do sistema
    if ($config['status_sistema'] !== 'ativo') {
        return false;
    }
    
    // Verificar dia da semana
    $dia_atual = date('N'); // 1 (Segunda) até 7 (Domingo)
    $dias_permitidos = explode(',', $config['dias_funcionamento']);
    if (!in_array($dia_atual, $dias_permitidos)) {
        return false;
    }
    
    // Verificar horário
    $hora_atual = date('H:i:s');
    if ($hora_atual < $config['horario_inicio'] || $hora_atual > $config['horario_fim']) {
        return false;
    }
    
    return true;
} 