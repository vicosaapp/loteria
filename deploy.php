<?php
/**
 * Script de deploy automático para o projeto Loteria
 * Este script é acionado por um webhook do GitHub quando há um push no repositório
 */

// Configurações
$secret = "sua_chave_secreta_aqui"; // Chave secreta para validar o webhook
$repo_dir = __DIR__; // Diretório do repositório no servidor
$branch = "main"; // Branch que será implantada
$log_file = __DIR__ . "/deploy_log.txt"; // Arquivo de log

// Função para registrar logs
function logMessage($message) {
    global $log_file;
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message" . PHP_EOL, FILE_APPEND);
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método não permitido";
    exit;
}

// Obter payload e assinatura
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';

// Verificar assinatura (segurança)
if ($secret) {
    $hash = 'sha1=' . hash_hmac('sha1', $payload, $secret);
    if (!hash_equals($hash, $signature)) {
        http_response_code(403);
        logMessage("Assinatura inválida: $signature");
        echo "Assinatura inválida";
        exit;
    }
}

// Decodificar payload
$data = json_decode($payload, true);

// Verificar se é o branch correto
$git_branch = isset($data['ref']) ? str_replace('refs/heads/', '', $data['ref']) : '';
if ($git_branch !== $branch) {
    logMessage("Branch ignorada: $git_branch");
    echo "Branch ignorada: $git_branch";
    exit;
}

// Executar comandos de deploy
try {
    // Mudar para o diretório do repositório
    chdir($repo_dir);
    
    // Comandos a serem executados
    $commands = [
        'git reset --hard HEAD',
        'git pull origin ' . $branch,
        'chmod -R 755 .',
        'find . -type f -name "*.php" -exec chmod 644 {} \;'
    ];
    
    // Executar comandos
    $output = [];
    foreach ($commands as $command) {
        exec($command . ' 2>&1', $output, $return_code);
        logMessage("Comando: $command, Código: $return_code");
        
        if ($return_code !== 0) {
            throw new Exception("Erro ao executar: $command");
        }
    }
    
    // Log de sucesso
    logMessage("Deploy concluído com sucesso: " . implode("\n", $output));
    echo "Deploy concluído com sucesso!";
    
} catch (Exception $e) {
    // Log de erro
    logMessage("Erro no deploy: " . $e->getMessage());
    http_response_code(500);
    echo "Erro no deploy: " . $e->getMessage();
} 