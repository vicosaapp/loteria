<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

$resultados = [
    'status' => 'success',
    'message' => 'Verificação de tabelas concluída',
    'acoes' => [],
    'erros' => []
];

try {
    // Verificar conexão com o banco
    if (!$pdo) {
        throw new Exception("Falha na conexão com o banco de dados");
    }

    // Iniciar transação
    $pdo->beginTransaction();

    // 1. Verificar e criar tabela de concursos
    $pdo->exec("CREATE TABLE IF NOT EXISTS `concursos` (
        `id` int NOT NULL AUTO_INCREMENT,
        `jogo_id` int NOT NULL,
        `codigo` varchar(20) NOT NULL,
        `data_sorteio` datetime NOT NULL,
        `status` enum('aguardando','finalizado') NOT NULL DEFAULT 'aguardando',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `valor_acumulado` decimal(15,2) DEFAULT '0.00',
        `data_proximo_concurso` date DEFAULT NULL,
        `valor_estimado_proximo` decimal(15,2) DEFAULT '0.00',
        PRIMARY KEY (`id`),
        KEY `jogo_id` (`jogo_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    $resultados['acoes'][] = "Verificada tabela concursos";

    // Verificar se a tabela jogos existe
    $tabelas = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tabelas[] = $row[0];
    }

    if (!in_array('jogos', $tabelas)) {
        // Criar tabela jogos
        $pdo->exec("CREATE TABLE IF NOT EXISTS `jogos` (
            `id` int NOT NULL AUTO_INCREMENT,
            `nome` varchar(255) NOT NULL,
            `quantidade_dezenas` int DEFAULT '6',
            `identificador_api` varchar(20) DEFAULT NULL,
            `titulo_importacao` varchar(100) DEFAULT NULL,
            `identificador_importacao` varchar(100) DEFAULT NULL,
            `numeros_total` int NOT NULL DEFAULT '100',
            `minimo_numeros` int NOT NULL DEFAULT '10',
            `maximo_numeros` int NOT NULL DEFAULT '25',
            `acertos_premio` int NOT NULL DEFAULT '10',
            `valor_aposta` decimal(10,2) NOT NULL DEFAULT '0.00',
            `valor_premio` decimal(10,2) NOT NULL DEFAULT '0.00',
            `numeros_disponiveis` int NOT NULL DEFAULT '60',
            `total_numeros` int NOT NULL DEFAULT '60',
            `dezenas` int NOT NULL DEFAULT '0',
            `dezenas_premiar` int NOT NULL DEFAULT '0',
            `valor` decimal(10,2) NOT NULL DEFAULT '0.00',
            `premio` decimal(10,2) NOT NULL DEFAULT '0.00',
            `status` tinyint(1) DEFAULT '1',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `data_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `numero_concurso` varchar(10) DEFAULT NULL,
            `valor_acumulado` decimal(15,2) DEFAULT '0.00',
            `data_proximo_concurso` date DEFAULT NULL,
            `valor_estimado_proximo` decimal(15,2) DEFAULT '0.00',
            `api_nome` varchar(50) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
        $resultados['acoes'][] = "Criada tabela jogos";

        // Inserir alguns jogos iniciais
        $stmt = $pdo->prepare("INSERT INTO jogos (nome, identificador_api, quantidade_dezenas) VALUES (?, ?, ?)");
        $stmt->execute(['Mega-Sena', 'megasena', 6]);
        $stmt->execute(['Lotofácil', 'lotofacil', 15]);
        $stmt->execute(['Quina', 'quina', 5]);
        $stmt->execute(['Lotomania', 'lotomania', 20]);
        $stmt->execute(['Timemania', 'timemania', 7]);
        $stmt->execute(['+Milionária', 'maismilionaria', 6]);
        $stmt->execute(['Dia de Sorte', 'diadesorte', 7]);
        $resultados['acoes'][] = "Inseridos jogos iniciais";
    } else {
        // Verificar se existem jogos cadastrados
        $stmt = $pdo->query("SELECT COUNT(*) FROM jogos");
        $total_jogos = $stmt->fetchColumn();
        
        if ($total_jogos == 0) {
            // Inserir alguns jogos iniciais
            $stmt = $pdo->prepare("INSERT INTO jogos (nome, identificador_api, quantidade_dezenas) VALUES (?, ?, ?)");
            $stmt->execute(['Mega-Sena', 'megasena', 6]);
            $stmt->execute(['Lotofácil', 'lotofacil', 15]);
            $stmt->execute(['Quina', 'quina', 5]);
            $stmt->execute(['Lotomania', 'lotomania', 20]);
            $stmt->execute(['Timemania', 'timemania', 7]);
            $stmt->execute(['+Milionária', 'maismilionaria', 6]);
            $stmt->execute(['Dia de Sorte', 'diadesorte', 7]);
            $resultados['acoes'][] = "Inseridos jogos iniciais (tabela existia mas estava vazia)";
        }
    }

    // 2. Verificar e criar tabela de números sorteados
    $pdo->exec("CREATE TABLE IF NOT EXISTS `numeros_sorteados` (
        `id` int NOT NULL AUTO_INCREMENT,
        `concurso_id` int NOT NULL,
        `numero` int NOT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `concurso_id` (`concurso_id`),
        CONSTRAINT `numeros_sorteados_ibfk_1` FOREIGN KEY (`concurso_id`) REFERENCES `concursos` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    $resultados['acoes'][] = "Verificada tabela numeros_sorteados";

    // Verificar estrutura das tabelas
    // Verificar se jogos tem as colunas necessárias
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM jogos LIKE 'valor_acumulado'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE jogos ADD COLUMN valor_acumulado decimal(15,2) DEFAULT '0.00'");
            $resultados['acoes'][] = "Adicionada coluna valor_acumulado à tabela jogos";
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM jogos LIKE 'data_proximo_concurso'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE jogos ADD COLUMN data_proximo_concurso date DEFAULT NULL");
            $resultados['acoes'][] = "Adicionada coluna data_proximo_concurso à tabela jogos";
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM jogos LIKE 'valor_estimado_proximo'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE jogos ADD COLUMN valor_estimado_proximo decimal(15,2) DEFAULT '0.00'");
            $resultados['acoes'][] = "Adicionada coluna valor_estimado_proximo à tabela jogos";
        }

        // Verificar se concursos tem as colunas necessárias
        $stmt = $pdo->query("SHOW COLUMNS FROM concursos LIKE 'valor_acumulado'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE concursos ADD COLUMN valor_acumulado decimal(15,2) DEFAULT '0.00'");
            $resultados['acoes'][] = "Adicionada coluna valor_acumulado à tabela concursos";
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM concursos LIKE 'data_proximo_concurso'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE concursos ADD COLUMN data_proximo_concurso date DEFAULT NULL");
            $resultados['acoes'][] = "Adicionada coluna data_proximo_concurso à tabela concursos";
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM concursos LIKE 'valor_estimado_proximo'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE concursos ADD COLUMN valor_estimado_proximo decimal(15,2) DEFAULT '0.00'");
            $resultados['acoes'][] = "Adicionada coluna valor_estimado_proximo à tabela concursos";
        }
    } catch (Exception $e) {
        $resultados['erros'][] = "Erro ao verificar/adicionar colunas: " . $e->getMessage();
    }

    // Confirmar transação
    $pdo->commit();
    $resultados['message'] = "Verificação e criação de tabelas concluída com sucesso";

} catch (Exception $e) {
    // Reverter transação em caso de erro
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $resultados['status'] = 'error';
    $resultados['message'] = $e->getMessage();
}

echo json_encode($resultados, JSON_PRETTY_PRINT); 