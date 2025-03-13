<?php
/**
 * Script de deploy automático via GitHub Webhook
 */

// Configurações
$secret = "Patto6917--"; // A mesma chave configurada no GitHub
$repo_url = "https://github.com/vicosaapp/loteria.git"; // URL do seu repositório
$branch = "main"; // Branch que será implantada
$local_dir = __DIR__; // Diretório do site no servidor
$log_file = __DIR__ . "/deploy_log.txt"; // Arquivo de log

// Função para registrar logs
function logMessage($message) {
    global $log_file;
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message" . PHP_EOL, FILE_APPEND);
}

// Log inicial
logMessage("Script de deploy iniciado");

// Para testes, permitir GET com parâmetro test
$is_test_mode = isset($_GET['test']);
if ($is_test_mode) {
    logMessage("Modo de teste ativado via GET");
} else if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logMessage("Método não permitido: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo "Método não permitido";
    exit;
}

// Informações do sistema para diagnóstico
$system_info = "PHP Version: " . phpversion() . "\n";
$system_info .= "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
$system_info .= "User: " . exec('whoami') . "\n";
$system_info .= "Current Directory: " . getcwd() . "\n";
logMessage("Informações do sistema:\n" . $system_info);

// Se estiver em modo de teste, pular verificação de assinatura
if (!$is_test_mode) {
    // Obter payload e assinatura
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';

    logMessage("Payload recebido: " . substr($payload, 0, 100) . "...");
    logMessage("Assinatura recebida: " . $signature);

    // Verificar assinatura (segurança)
    if ($secret) {
        $hash = 'sha1=' . hash_hmac('sha1', $payload, $secret);
        logMessage("Hash calculado: " . $hash);
        
        if (!hash_equals($hash, $signature)) {
            logMessage("Assinatura inválida");
            http_response_code(403);
            echo "Assinatura inválida";
            exit;
        }
    }

    // Decodificar payload
    $data = json_decode($payload, true);

    // Verificar se é o branch correto
    $git_branch = isset($data['ref']) ? str_replace('refs/heads/', '', $data['ref']) : '';
    logMessage("Branch detectada: " . $git_branch);

    if ($git_branch !== $branch) {
        logMessage("Branch ignorada: $git_branch");
        echo "Branch ignorada: $git_branch";
        exit;
    }
}

// Executar deploy
try {
    logMessage("Iniciando deploy do branch $branch");
    
    // Verificar se o Git está instalado
    exec("which git 2>&1", $output, $return_code);
    logMessage("Verificação do Git: " . implode("\n", $output) . " (código: $return_code)");
    
    if ($return_code !== 0) {
        // Tentar outro comando para verificar o Git
        exec("git --version 2>&1", $output, $return_code);
        logMessage("Verificação alternativa do Git: " . implode("\n", $output) . " (código: $return_code)");
        
        if ($return_code !== 0) {
            throw new Exception("Git não está instalado ou acessível no servidor");
        }
    }
    
    // Verificar usuário e permissões
    logMessage("Usuário atual: " . exec('whoami'));
    logMessage("Permissões do diretório: " . exec("ls -la $local_dir | head -n 5"));
    
    // Verificar se o diretório .git existe
    if (!file_exists("$local_dir/.git")) {
        // Clonar o repositório se não existir
        logMessage("Repositório não encontrado. Clonando...");
        exec("git clone -b $branch $repo_url $local_dir 2>&1", $output, $return_code);
        logMessage("Resultado do clone: " . implode("\n", $output) . " (código: $return_code)");
    } else {
        // Verificar estado do repositório
        logMessage("Repositório encontrado. Verificando estado...");
        exec("cd $local_dir && git status 2>&1", $output, $return_code);
        logMessage("Status do Git: " . implode("\n", $output));
        
        // Atualizar o repositório existente
        logMessage("Atualizando repositório...");
        
        // Salvar arquivos que não devem ser sobrescritos
        exec("cd $local_dir && git stash -u 2>&1", $output, $return_code);
        logMessage("Stash: " . implode("\n", $output) . " (código: $return_code)");
        
        // Puxar as alterações
        exec("cd $local_dir && git fetch origin && git reset --hard origin/$branch 2>&1", $output, $return_code);
        logMessage("Pull: " . implode("\n", $output) . " (código: $return_code)");
    }
    
    // Verificar resultado
    if ($return_code !== 0) {
        throw new Exception("Erro ao executar comandos git: " . implode("\n", $output));
    }
    
    // Ajustar permissões
    exec("chmod -R 755 $local_dir 2>&1", $output, $return_code);
    logMessage("Permissões de diretórios: " . implode("\n", $output) . " (código: $return_code)");
    
    exec("find $local_dir -type f -name '*.php' -exec chmod 644 {} \\; 2>&1", $output, $return_code);
    logMessage("Permissões de arquivos PHP: " . implode("\n", $output) . " (código: $return_code)");
    
    logMessage("Deploy concluído com sucesso!");
    echo "Deploy concluído com sucesso!";
    
} catch (Exception $e) {
    logMessage("Erro no deploy: " . $e->getMessage());
    http_response_code(500);
    echo "Erro no deploy: " . $e->getMessage();
}

// Se estiver em modo de teste, mostrar o log
if ($is_test_mode) {
    echo "<h1>Log de Deploy</h1>";
    echo "<pre>";
    if (file_exists($log_file)) {
        echo htmlspecialchars(file_get_contents($log_file));
    } else {
        echo "Arquivo de log não encontrado.";
    }
    echo "</pre>";
    
    // Mostrar informações adicionais para diagnóstico
    echo "<h2>Informações do Sistema</h2>";
    echo "<pre>";
    echo htmlspecialchars($system_info);
    echo "</pre>";
    
    echo "<h2>Verificação do Git</h2>";
    echo "<pre>";
    exec("which git 2>&1", $output);
    echo "which git: " . htmlspecialchars(implode("\n", $output)) . "\n\n";
    
    exec("git --version 2>&1", $output);
    echo "git --version: " . htmlspecialchars(implode("\n", $output)) . "\n\n";
    
    echo "</pre>";
}
?> 