<?php
require_once '../config/database.php';
session_start();

// Verificar se está logado e é administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Iniciar output buffer
ob_start();

// Tentar criar a tabela
try {
    $sql = "
    CREATE TABLE IF NOT EXISTS `fila_envio_comprovantes` (
      `id` int NOT NULL AUTO_INCREMENT,
      `aposta_id` int NOT NULL,
      `status` enum('pendente','enviado','falha') NOT NULL DEFAULT 'pendente',
      `data_enfileiramento` datetime NOT NULL,
      `data_processamento` datetime DEFAULT NULL,
      `tentativas` int NOT NULL DEFAULT '0',
      `ultima_tentativa` datetime DEFAULT NULL,
      `resultado` text,
      PRIMARY KEY (`id`),
      KEY `idx_aposta_id` (`aposta_id`),
      KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
    ";
    
    $pdo->exec($sql);
    
    // Verificar se a constraint existe (pode falhar em algumas versões do MySQL)
    try {
        $pdo->exec("
        ALTER TABLE `fila_envio_comprovantes`
        ADD CONSTRAINT `fk_fila_aposta` FOREIGN KEY (`aposta_id`) REFERENCES `apostas` (`id`) ON DELETE CASCADE;
        ");
    } catch (Exception $e) {
        // Ignorar erro de constraint já existente
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
    }
    
    echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Tabela <strong>fila_envio_comprovantes</strong> criada com sucesso!</div>';
    
    // Verificar se a tabela apostas tem o campo status
    $stmt = $pdo->query("SHOW COLUMNS FROM apostas LIKE 'status'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("
        ALTER TABLE apostas
        ADD COLUMN `status` enum('pendente','aprovada','rejeitada') DEFAULT 'pendente' AFTER `numeros`;
        ");
        echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Campo <strong>status</strong> adicionado à tabela <strong>apostas</strong>!</div>';
    } else {
        echo '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Campo <strong>status</strong> já existe na tabela <strong>apostas</strong>.</div>';
    }
    
    // Atualizar apostas existentes para status aprovada (se não tiverem um status)
    $pdo->exec("
    UPDATE apostas SET status = 'aprovada' WHERE status IS NULL OR status = '';
    ");
    echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Apostas existentes atualizadas para status <strong>aprovada</strong>!</div>';
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Erro: ' . $e->getMessage() . '</div>';
}

// Verificar o cron script
$cron_dir = __DIR__ . '/../cron';
$cron_file = $cron_dir . '/processar_fila_comprovantes.php';

if (!file_exists($cron_dir)) {
    mkdir($cron_dir, 0755, true);
    echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Diretório <strong>cron</strong> criado!</div>';
}

if (!file_exists($cron_file)) {
    echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> O arquivo do processador de fila <strong>' . $cron_file . '</strong> não foi encontrado!</div>';
    echo '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Você precisa criar manualmente o script de processamento da fila.</div>';
} else {
    echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Script de processamento da fila encontrado em <strong>' . $cron_file . '</strong>!</div>';
}

// Informações para configuração do cron job
echo '
<div class="alert alert-info">
    <h5><i class="fas fa-info-circle"></i> Configuração do Cron Job</h5>
    <p>Para que o sistema envie os comprovantes automaticamente, você precisa configurar um cron job no servidor.</p>
    <p>Exemplo de configuração (executar a cada 5 minutos):</p>
    <pre class="bg-dark text-light p-2 rounded">*/5 * * * * php ' . $cron_file . ' > /dev/null 2>&1</pre>
</div>';

// Link para voltar ao painel
echo '<div class="mt-4"><a href="dashboard.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Voltar ao Painel</a></div>';

// Obter conteúdo do buffer
$content = ob_get_clean();

// Definir título da página
$pageTitle = "Instalador - Fila de Comprovantes";

// Incluir o layout
require_once '../includes/admin_layout.php';
?> 