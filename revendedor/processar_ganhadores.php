<?php
// Evitar a inclusão direta do arquivo database.php - deixar para o arquivo principal fazer isso
// require_once '../config/database.php';

// Habilitar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 0); // Desabilitar saída direta de erros

// Array para armazenar logs
$debug_logs = [];

function processar_ganhadores() {
    global $debug_logs, $pdo;
    $debug_logs[] = "Iniciando processamento de ganhadores";
    
    try {
        // Criar tabela ganhadores se não existir
        $sql = "CREATE TABLE IF NOT EXISTS ganhadores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            aposta_id INT NOT NULL,
            concurso_id INT NOT NULL,
            valor_premio DECIMAL(10,2) NOT NULL,
            status ENUM('pendente', 'pago') NOT NULL DEFAULT 'pendente',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_aposta (concurso_id, aposta_id)
        )";
        $pdo->exec($sql);
        $debug_logs[] = "Tabela ganhadores verificada/criada com sucesso";
        
        // Criar tabela valores_jogos se não existir
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
        $debug_logs[] = "Tabela valores_jogos verificada/criada com sucesso";
        
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

        // Verificar valores dos prêmios
        $sql = "SELECT j.nome, vj.* 
                FROM valores_jogos vj 
                JOIN jogos j ON j.id = vj.jogo_id 
                ORDER BY j.nome, vj.dezenas";
        $stmt = $pdo->query($sql);
        $valores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $debug_logs[] = "Valores de prêmios disponíveis:";
        foreach ($valores as $valor) {
            $debug_logs[] = "Jogo: {$valor['nome']} - Dezenas: {$valor['dezenas']} - Prêmio: {$valor['valor_premio']}";
        }
        
        // Verificar a estrutura da tabela apostas_importadas e adicionar a coluna processado se não existir
        try {
            $sql = "SHOW COLUMNS FROM apostas_importadas LIKE 'processado'";
            $stmt = $pdo->query($sql);
            
            if ($stmt->rowCount() == 0) {
                $debug_logs[] = "Coluna 'processado' não encontrada na tabela apostas_importadas. Adicionando...";
                
                $sql = "ALTER TABLE apostas_importadas ADD COLUMN processado TINYINT(1) DEFAULT 0";
                $pdo->exec($sql);
                $debug_logs[] = "Coluna 'processado' adicionada à tabela apostas_importadas";
            }
        } catch (Exception $e) {
            $debug_logs[] = "Erro ao verificar/adicionar coluna 'processado': " . $e->getMessage();
        }
        
        // Verificar a estrutura da tabela apostas_importadas
        $sql = "DESCRIBE apostas_importadas";
        $stmt = $pdo->query($sql);
        $colunas_apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $colunas_names = [];
        
        foreach ($colunas_apostas as $coluna) {
            $colunas_names[] = $coluna['Field'];
        }
        
        $debug_logs[] = "Colunas na tabela apostas_importadas: " . implode(", ", $colunas_names);
        
        // Determinar qual coluna usar para o concurso
        $coluna_concurso = in_array('concurso', $colunas_names) ? 'concurso' : 'concurso_id';
        $debug_logs[] = "Usando coluna '{$coluna_concurso}' para o concurso na tabela apostas_importadas";
        
        // Buscar concursos finalizados que ainda não foram processados
        $sql = "SELECT c.id as concurso_id, c.codigo as numero_concurso, 
                j.id as jogo_id, j.nome as nome_jogo,
                GROUP_CONCAT(ns.numero ORDER BY ns.numero ASC) as numeros_sorteados
                FROM concursos c
                INNER JOIN jogos j ON j.id = c.jogo_id
                INNER JOIN numeros_sorteados ns ON ns.concurso_id = c.id
                WHERE c.status = 'finalizado'
                AND EXISTS (
                    SELECT 1 FROM apostas_importadas ai 
                    WHERE ai.concurso = c.codigo 
                    AND ai.jogo_nome LIKE CONCAT('%', j.nome, '%')
                    AND (ai.processado = 0 OR ai.processado IS NULL)
                )
                GROUP BY c.id, j.id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $debug_logs[] = "Encontrados " . count($concursos) . " concursos para processar";
        
        foreach ($concursos as $concurso) {
            $debug_logs[] = "Processando concurso " . $concurso['numero_concurso'] . " do jogo " . $concurso['nome_jogo'];
            
            // Buscar apostas deste jogo
            $sql = "SELECT ai.id, ai.numeros, ai.usuario_id, u.nome as nome_usuario
                    FROM apostas_importadas ai
                    INNER JOIN usuarios u ON u.id = ai.usuario_id
                    WHERE ai.jogo_nome LIKE CONCAT('%', ?) 
                    AND ai.concurso = ?
                    AND (ai.processado = 0 OR ai.processado IS NULL)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$concurso['nome_jogo'], $concurso['numero_concurso']]);
            $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $debug_logs[] = "Encontradas " . count($apostas) . " apostas para verificar";
            
            // Array com números sorteados
            $numeros_sorteados = explode(',', $concurso['numeros_sorteados']);
            
            foreach ($apostas as $aposta) {
                // Verificar se a aposta possui numeros
                if (empty($aposta['numeros'])) {
                    $debug_logs[] = "Aposta ID " . $aposta['id'] . " não possui números apostados. Pulando...";
                    continue;
                }
                
                // Limpar e padronizar números apostados
                $numeros_apostados = array_map('trim', explode(',', $aposta['numeros']));
                
                if (empty($numeros_apostados)) {
                    $debug_logs[] = "Aposta ID " . $aposta['id'] . " não possui números válidos. Pulando...";
                    continue;
                }
                
                // Contar acertos
                $acertos = count(array_intersect($numeros_apostados, $numeros_sorteados));
                $debug_logs[] = "Aposta ID " . $aposta['id'] . " teve " . $acertos . " acertos";
                
                // Buscar valor do prêmio baseado nos acertos
                try {
                    $sql = "SELECT valor_premio FROM valores_jogos 
                            WHERE jogo_id = ? AND dezenas = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$concurso['jogo_id'], $acertos]);
                    $premio = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($premio && $premio['valor_premio'] > 0) {
                        $debug_logs[] = "Aposta ID " . $aposta['id'] . " tem prêmio de R$ " . $premio['valor_premio'];
                        
                        try {
                            $pdo->beginTransaction();
                            
                            // Atualizar aposta com o valor do prêmio
                            $sql = "UPDATE apostas_importadas 
                                    SET valor_premio = ?, processado = 1 
                                    WHERE id = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$premio['valor_premio'], $aposta['id']]);
                            
                            // Inserir na tabela de ganhadores
                            $sql = "INSERT INTO ganhadores 
                                    (resultado_id, usuario_id, aposta_id, premio, status) 
                                    VALUES (?, ?, ?, ?, 'pendente')";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                $concurso['concurso_id'],
                                $aposta['usuario_id'],
                                $aposta['id'],
                                $premio['valor_premio']
                            ]);
                            
                            $pdo->commit();
                            $debug_logs[] = "Ganhador registrado com sucesso";
                            
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $debug_logs[] = "Erro ao registrar ganhador: " . $e->getMessage();
                            $debug_logs[] = "Tentando apenas marcar como processado...";
                            
                            // Em caso de erro, tentar marcar como processado pelo menos
                            $sql = "UPDATE apostas_importadas SET processado = 1 WHERE id = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$aposta['id']]);
                            $debug_logs[] = "Aposta marcada como processada";
                        }
                    } else {
                        $debug_logs[] = "Aposta ID " . $aposta['id'] . " não tem prêmio para " . $acertos . " acertos";
                        // Marcar como processado mesmo sem prêmio
                        $sql = "UPDATE apostas_importadas SET processado = 1 WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$aposta['id']]);
                    }
                } catch (Exception $e) {
                    $debug_logs[] = "Erro ao buscar prêmio para " . $acertos . " acertos: " . $e->getMessage();
                    // Continuar processando as apostas
                    continue;
                }
            }
        }
        
        $debug_logs[] = "Processamento de ganhadores concluído com sucesso";
        
        // Verificar também as apostas regulares
        $debug_logs[] = "Iniciando verificação de apostas regulares...";
        $logs_apostas_regulares = verificar_apostas_regulares();
        if (is_array($logs_apostas_regulares)) {
            $debug_logs = array_merge($debug_logs, $logs_apostas_regulares);
        }
        
        return $debug_logs;
        
    } catch (Exception $e) {
        $debug_logs[] = "Erro no processamento de ganhadores: " . $e->getMessage();
        throw $e;
    }
}

// Função para extrair números de uma string de aposta
function extrairNumeros($texto) {
    global $debug_logs;
    $debug_logs[] = "Processando texto: " . $texto;
    
    // Remove o cabeçalho da aposta (ex: "Loterias Mobile: MS")
    $texto = preg_replace('/Loterias Mobile:\s*[A-Z]{2,3}\r?\n\r?\n/', '', $texto);
    
    // Extrai todos os números
    preg_match_all('/\d+/', $texto, $matches);
    
    // Remove duplicatas e ordena
    $numeros = array_unique($matches[0]);
    sort($numeros, SORT_NUMERIC);
    
    $debug_logs[] = "Números extraídos: " . implode(',', $numeros);
    return $numeros;
}

// Função para verificar quantos números foram acertados
function contarAcertos($numeros_apostados, $numeros_sorteados, $jogo_nome = '') {
    global $debug_logs;
    $debug_logs[] = "Verificando acertos para o jogo: " . $jogo_nome;
    $debug_logs[] = "Números apostados: " . (is_array($numeros_apostados) ? implode(',', $numeros_apostados) : $numeros_apostados);
    $debug_logs[] = "Números sorteados: " . $numeros_sorteados;
    
    // Converte string de números sorteados em array
    $sorteados = explode(',', $numeros_sorteados);
    
    // Se os números apostados vierem como string, converte em array
    if (is_string($numeros_apostados)) {
        $apostados = extrairNumeros($numeros_apostados);
    } else {
        $apostados = $numeros_apostados;
    }
    
    // Garante que todos os números são tratados como inteiros
    $sorteados = array_map('intval', $sorteados);
    $apostados = array_map('intval', $apostados);
    
    // Para a Mega-Sena, precisamos considerar apenas os 6 números sorteados
    if (stripos($jogo_nome, 'mega') !== false) {
        $sorteados = array_slice($sorteados, 0, 6);
    }
    
    $acertos = count(array_intersect($apostados, $sorteados));
    $debug_logs[] = "Total de acertos: " . $acertos;
    $debug_logs[] = "Números sorteados considerados: " . implode(',', $sorteados);
    $debug_logs[] = "Números apostados considerados: " . implode(',', $apostados);
    
    return $acertos;
}

// Função para verificar apostas regulares
function verificar_apostas_regulares() {
    global $debug_logs, $pdo;
    $debug_logs[] = "Iniciando verificação de apostas regulares...";
    
    try {
        // Verificar a estrutura da tabela apostas e adicionar a coluna processado se não existir
        try {
            $sql = "SHOW COLUMNS FROM apostas LIKE 'processado'";
            $stmt = $pdo->query($sql);
            
            if ($stmt->rowCount() == 0) {
                $debug_logs[] = "Coluna 'processado' não encontrada na tabela apostas. Adicionando...";
                
                $sql = "ALTER TABLE apostas ADD COLUMN processado TINYINT(1) DEFAULT 0";
                $pdo->exec($sql);
                $debug_logs[] = "Coluna 'processado' adicionada à tabela apostas";
            }
            
            // Verificar se existe a coluna concurso
            $sql = "SHOW COLUMNS FROM apostas LIKE 'concurso'";
            $stmt = $pdo->query($sql);
            
            if ($stmt->rowCount() == 0) {
                $debug_logs[] = "Coluna 'concurso' não encontrada na tabela apostas. Adicionando...";
                
                $sql = "ALTER TABLE apostas ADD COLUMN concurso VARCHAR(20) NULL";
                $pdo->exec($sql);
                $debug_logs[] = "Coluna 'concurso' adicionada à tabela apostas";
            }
            
            // Verificar se existe a coluna valor_premio
            $sql = "SHOW COLUMNS FROM apostas LIKE 'valor_premio'";
            $stmt = $pdo->query($sql);
            
            if ($stmt->rowCount() == 0) {
                $debug_logs[] = "Coluna 'valor_premio' não encontrada na tabela apostas. Adicionando...";
                
                $sql = "ALTER TABLE apostas ADD COLUMN valor_premio DECIMAL(10,2) DEFAULT 0";
                $pdo->exec($sql);
                $debug_logs[] = "Coluna 'valor_premio' adicionada à tabela apostas";
            }
        } catch (Exception $e) {
            $debug_logs[] = "Erro ao verificar/adicionar colunas na tabela apostas: " . $e->getMessage();
        }
        
        // Buscar concursos finalizados que ainda não foram processados
        $sql = "SELECT c.id as concurso_id, c.codigo as numero_concurso, 
                j.id as jogo_id, j.nome as nome_jogo,
                GROUP_CONCAT(DISTINCT ns.numero ORDER BY ns.numero ASC) as numeros_sorteados
                FROM concursos c
                INNER JOIN jogos j ON j.id = c.jogo_id
                INNER JOIN numeros_sorteados ns ON ns.concurso_id = c.id
                WHERE c.status = 'finalizado'
                GROUP BY c.id, j.id
                ORDER BY c.data_sorteio DESC
                LIMIT 10";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $debug_logs[] = "Encontrados " . count($concursos) . " concursos para processar apostas regulares";
        
        foreach ($concursos as $concurso) {
            $debug_logs[] = "Processando concurso " . $concurso['numero_concurso'] . " do jogo " . $concurso['nome_jogo'] . " (ID: " . $concurso['jogo_id'] . ")";
            $debug_logs[] = "Números sorteados: " . $concurso['numeros_sorteados'];
            
            // Buscar apostas deste jogo (independente se já foram processadas ou não)
            $sql = "SELECT a.id, a.numeros, a.usuario_id, a.valor_aposta, a.processado, a.concurso, u.nome as nome_usuario
                    FROM apostas a
                    INNER JOIN usuarios u ON u.id = a.usuario_id
                    WHERE a.tipo_jogo_id = ? 
                    AND a.status = 'aprovada'
                    ORDER BY a.id DESC
                    LIMIT 50";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$concurso['jogo_id']]);
            $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $debug_logs[] = "Encontradas " . count($apostas) . " apostas do jogo " . $concurso['nome_jogo'] . " para verificar";
            
            // Array com números sorteados
            $numeros_sorteados = explode(',', $concurso['numeros_sorteados']);
            $debug_logs[] = "Números sorteados (array): " . implode(', ', $numeros_sorteados);
            
            $total_apostas_processadas = 0;
            $total_ganhadores = 0;
            
            foreach ($apostas as $aposta) {
                $debug_logs[] = "Verificando aposta ID " . $aposta['id'] . " - Números: " . $aposta['numeros'] . " - Usuário: " . $aposta['nome_usuario'];
                
                // Verificar se a aposta possui numeros
                if (empty($aposta['numeros'])) {
                    $debug_logs[] = "Aposta ID " . $aposta['id'] . " não possui números apostados. Pulando...";
                    continue;
                }
                
                // Limpar e padronizar números apostados
                $numeros_apostados_raw = explode(',', $aposta['numeros']);
                $numeros_apostados = [];
                
                // Limpar espaços e garantir que são números
                foreach ($numeros_apostados_raw as $num) {
                    $num_limpo = trim($num);
                    if (is_numeric($num_limpo)) {
                        $numeros_apostados[] = (int)$num_limpo;
                    }
                }
                
                if (empty($numeros_apostados)) {
                    $debug_logs[] = "Aposta ID " . $aposta['id'] . " não possui números válidos após limpeza. Pulando...";
                    continue;
                }
                
                $debug_logs[] = "Números apostados após limpeza: " . implode(', ', $numeros_apostados);
                
                // Contar acertos
                $acertos = 0;
                $numeros_acertados = [];
                $numeros_sorteados_int = array_map('intval', $numeros_sorteados);
                
                foreach ($numeros_apostados as $num_apostado) {
                    if (in_array($num_apostado, $numeros_sorteados_int)) {
                        $acertos++;
                        $numeros_acertados[] = $num_apostado;
                    }
                }
                
                $debug_logs[] = "Aposta ID " . $aposta['id'] . " teve " . $acertos . " acertos. Números acertados: " . implode(', ', $numeros_acertados);
                
                // Buscar valor do prêmio baseado nos acertos
                try {
                    $sql = "SELECT valor_premio FROM valores_jogos 
                            WHERE jogo_id = ? AND dezenas = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$concurso['jogo_id'], $acertos]);
                    $premio = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($premio && $premio['valor_premio'] > 0) {
                        $debug_logs[] = "Aposta ID " . $aposta['id'] . " tem prêmio de R$ " . $premio['valor_premio'];
                        
                        try {
                            $pdo->beginTransaction();
                            
                            // Atualizar aposta com o valor do prêmio e o número do concurso
                            $sql = "UPDATE apostas 
                                    SET valor_premio = ?, processado = 1, concurso = ? 
                                    WHERE id = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$premio['valor_premio'], $concurso['numero_concurso'], $aposta['id']]);
                            
                            $pdo->commit();
                            $debug_logs[] = "Ganhador registrado com sucesso";
                            $total_ganhadores++;
                            
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $debug_logs[] = "Erro ao registrar ganhador: " . $e->getMessage();
                            $debug_logs[] = "Tentando apenas marcar como processado...";
                            
                            // Em caso de erro, tentar marcar como processado pelo menos
                            $sql = "UPDATE apostas SET processado = 1 WHERE id = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$aposta['id']]);
                            $debug_logs[] = "Aposta marcada como processada";
                        }
                    } else {
                        $debug_logs[] = "Aposta ID " . $aposta['id'] . " não tem prêmio para " . $acertos . " acertos";
                        // Marcar como processado mesmo sem prêmio
                        // Atualizar o concurso, mas sem prêmio
                        $sql = "UPDATE apostas SET processado = 1, concurso = ? WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$concurso['numero_concurso'], $aposta['id']]);
                    }
                    
                    $total_apostas_processadas++;
                    
                } catch (Exception $e) {
                    $debug_logs[] = "Erro ao buscar prêmio para " . $acertos . " acertos: " . $e->getMessage();
                    // Continuar processando as apostas
                    continue;
                }
            }
            
            $debug_logs[] = "Total de apostas processadas para o concurso " . $concurso['numero_concurso'] . ": " . $total_apostas_processadas;
            $debug_logs[] = "Total de ganhadores encontrados: " . $total_ganhadores;
        }
        
        $debug_logs[] = "Processamento de apostas regulares concluído com sucesso";
        return $debug_logs;
        
    } catch (Exception $e) {
        $debug_logs[] = "Erro no processamento de apostas regulares: " . $e->getMessage();
        throw $e;
    }
}

// Apenas definir a função, não executar nada diretamente
// Se o arquivo for chamado diretamente, não fazer nada
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    // Silenciosamente sair, não enviar nenhuma saída
} else {
    try {
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
            
            $debug_logs[] = "Coluna quantidade_dezenas adicionada e valores padrão configurados";
        } else {
            $debug_logs[] = "Coluna quantidade_dezenas já existe na tabela jogos";
        }
    } catch (Exception $e) {
        $debug_logs[] = "Erro ao verificar/adicionar coluna quantidade_dezenas: " . $e->getMessage();
        throw $e;
    }
} 