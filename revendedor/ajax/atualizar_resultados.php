<?php
// Silenciar todos os erros e saídas
error_reporting(0);
ini_set('display_errors', 0);

// Inicia o buffer de saída e captura quaisquer saídas indesejadas
ob_start();

// Define o tipo de conteúdo e headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Array para armazenar logs
$debug_logs = [];

try {
    // Inclui os arquivos necessários na ordem correta (caminhos absolutos)
    require_once __DIR__ . '/../../config/database.php';
    
    // Verifica se existe uma conexão com o banco
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Falha na conexão com o banco de dados");
    }
    
    // Verifica e cria tabelas necessárias
    $debug_logs[] = "Verificando estrutura das tabelas...";
    
    // Tabela valores_jogos
    $sql = "CREATE TABLE IF NOT EXISTS valores_jogos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        jogo_id INT NOT NULL,
        dezenas INT NOT NULL,
        valor_premio DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_jogo_dezenas (jogo_id, dezenas)
    )";
    $pdo->exec($sql);
    $debug_logs[] = "Tabela valores_jogos verificada";
    
    // Verificar se a coluna quantidade_dezenas existe na tabela jogos
    $sql = "SHOW COLUMNS FROM jogos LIKE 'quantidade_dezenas'";
    $stmt = $pdo->query($sql);
    
    if ($stmt->rowCount() == 0) {
        $debug_logs[] = "Coluna quantidade_dezenas não encontrada na tabela jogos. Adicionando...";
        
        $sql = "ALTER TABLE jogos ADD COLUMN quantidade_dezenas INT DEFAULT 6 AFTER nome";
        $pdo->exec($sql);
        
        // Atualizar valores padrão de quantidade_dezenas para cada jogo
        $updates = [
            ['identificador' => 'megasena', 'dezenas' => 6],
            ['identificador' => 'lotofacil', 'dezenas' => 15],
            ['identificador' => 'quina', 'dezenas' => 5],
            ['identificador' => 'lotomania', 'dezenas' => 20],
            ['identificador' => 'timemania', 'dezenas' => 7],
            ['identificador' => 'diadesorte', 'dezenas' => 7],
            ['identificador' => 'duplasena', 'dezenas' => 6],
            ['identificador' => 'supersete', 'dezenas' => 7],
            ['identificador' => 'maismilionaria', 'dezenas' => 6]
        ];
        
        $sql = "UPDATE jogos SET quantidade_dezenas = ? WHERE identificador_api = ?";
        $stmt = $pdo->prepare($sql);
        
        foreach ($updates as $update) {
            $stmt->execute([$update['dezenas'], $update['identificador']]);
            $debug_logs[] = "Atualizada quantidade_dezenas para {$update['identificador']}: {$update['dezenas']}";
        }
    } else {
        $debug_logs[] = "Coluna quantidade_dezenas já existe na tabela jogos";
    }
    
    // Verificar se existem valores de prêmios cadastrados
    $sql = "SELECT COUNT(*) as total FROM valores_jogos";
    $stmt = $pdo->query($sql);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($total == 0) {
        $debug_logs[] = "Nenhum valor de prêmio cadastrado. Inserindo valores padrão...";
        
        // Obter lista de jogos
        $sql = "SELECT id, nome FROM jogos WHERE status = 1";
        $stmt = $pdo->query($sql);
        $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($jogos as $jogo) {
            if (stripos($jogo['nome'], 'mega') !== false) {
                // Valores para Mega-Sena
                $valores = [
                    ['dezenas' => 4, 'valor' => 50.00],
                    ['dezenas' => 5, 'valor' => 1000.00],
                    ['dezenas' => 6, 'valor' => 10000.00]
                ];
            } elseif (stripos($jogo['nome'], 'lotofacil') !== false) {
                // Valores para Lotofácil
                $valores = [
                    ['dezenas' => 11, 'valor' => 5.00],
                    ['dezenas' => 12, 'valor' => 10.00],
                    ['dezenas' => 13, 'valor' => 25.00],
                    ['dezenas' => 14, 'valor' => 1000.00],
                    ['dezenas' => 15, 'valor' => 5000.00]
                ];
            } else {
                // Valores padrão para outros jogos
                $valores = [
                    ['dezenas' => 3, 'valor' => 5.00],
                    ['dezenas' => 4, 'valor' => 50.00],
                    ['dezenas' => 5, 'valor' => 500.00]
                ];
            }
            
            // Inserir valores para o jogo
            $sql = "INSERT IGNORE INTO valores_jogos (jogo_id, dezenas, valor_premio) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            foreach ($valores as $valor) {
                $stmt->execute([$jogo['id'], $valor['dezenas'], $valor['valor']]);
            }
            
            $debug_logs[] = "Valores para o jogo {$jogo['nome']} inseridos com sucesso";
        }
    }
    
    // Verificar a estrutura da tabela apostas_importadas
    try {
        $sql = "DESCRIBE apostas_importadas";
        $stmt = $pdo->query($sql);
        if ($stmt) {
            $colunas_apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $colunas_names = [];
            
            foreach ($colunas_apostas as $coluna) {
                $colunas_names[] = $coluna['Field'];
            }
            
            $debug_logs[] = "Colunas na tabela apostas_importadas: " . implode(", ", $colunas_names);
            
            // Se não existir coluna 'concurso', criar a coluna
            if (!in_array('concurso', $colunas_names)) {
                $sql = "ALTER TABLE apostas_importadas ADD COLUMN concurso INT NULL";
                $pdo->exec($sql);
                
                // Se existir coluna 'concurso_id', copiar os valores
                if (in_array('concurso_id', $colunas_names)) {
                    $sql = "UPDATE apostas_importadas SET concurso = concurso_id WHERE concurso IS NULL";
                    $pdo->exec($sql);
                    $debug_logs[] = "Copiados valores de 'concurso_id' para 'concurso'";
                }
                
                $debug_logs[] = "Adicionada coluna 'concurso' na tabela apostas_importadas";
            }
            // Se não existir 'processado', criar a coluna
            if (!in_array('processado', $colunas_names)) {
                $sql = "ALTER TABLE apostas_importadas ADD COLUMN processado TINYINT(1) DEFAULT 0";
                $pdo->exec($sql);
                $debug_logs[] = "Adicionada coluna 'processado' na tabela apostas_importadas";
            }
        }
    } catch (Exception $e) {
        $debug_logs[] = "Erro ao verificar estrutura da tabela apostas_importadas: " . $e->getMessage();
    }
    
    // Verificar existência da tabela ganhadores
    try {
        $sql = "SHOW TABLES LIKE 'ganhadores'";
        $stmt = $pdo->query($sql);
        
        if ($stmt->rowCount() == 0) {
            $debug_logs[] = "Tabela ganhadores não encontrada. Criando...";
            
            $sql = "CREATE TABLE IF NOT EXISTS ganhadores (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                resultado_id INT NOT NULL,
                usuario_id INT NOT NULL,
                aposta_id INT NOT NULL,
                premio DECIMAL(10,2) NOT NULL,
                status ENUM('pendente','pago') DEFAULT 'pendente',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (resultado_id),
                INDEX (usuario_id),
                INDEX (aposta_id)
            )";
            $pdo->exec($sql);
            
            // Criar chaves estrangeiras
            try {
                $sql = "ALTER TABLE ganhadores 
                        ADD CONSTRAINT ganhadores_ibfk_1 FOREIGN KEY (resultado_id) REFERENCES resultados (id),
                        ADD CONSTRAINT ganhadores_ibfk_2 FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
                        ADD CONSTRAINT ganhadores_ibfk_3 FOREIGN KEY (aposta_id) REFERENCES apostas (id)";
                $pdo->exec($sql);
                $debug_logs[] = "Chaves estrangeiras adicionadas à tabela ganhadores";
            } catch (Exception $e) {
                $debug_logs[] = "Aviso: Não foi possível adicionar chaves estrangeiras: " . $e->getMessage();
            }
            
            $debug_logs[] = "Tabela ganhadores criada com sucesso";
        }
    } catch (Exception $e) {
        $debug_logs[] = "Erro ao verificar/criar tabela ganhadores: " . $e->getMessage();
    }
    
    // Inclui os arquivos de processamento
    require_once __DIR__ . '/../processar_resultados.php';
    require_once __DIR__ . '/../processar_ganhadores.php';
    
    // Processa os resultados
    $debug_logs[] = "Iniciando atualização de resultados...";
    $logs_resultados = processar_resultados();
    if (is_array($logs_resultados)) {
        $debug_logs = array_merge($debug_logs, $logs_resultados);
    }
    
    // Processa os ganhadores
    $debug_logs[] = "Iniciando processamento de ganhadores...";
    $logs_ganhadores = processar_ganhadores();
    if (is_array($logs_ganhadores)) {
        $debug_logs = array_merge($debug_logs, $logs_ganhadores);
    }
    
    // Limpa qualquer saída anterior
    ob_clean();
    
    // Retorna sucesso com os logs
    echo json_encode([
        'status' => 'success',
        'message' => 'Resultados atualizados com sucesso',
        'logs' => $debug_logs
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Limpa qualquer saída anterior
    ob_clean();
    
    // Retorna erro com os logs
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'logs' => $debug_logs,
        'stack_trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}

// Finaliza e envia a saída do buffer
ob_end_flush(); 