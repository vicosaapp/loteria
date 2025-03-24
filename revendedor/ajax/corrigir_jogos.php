<?php
// Habilitar exibição de erros para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configuração do banco de dados
require_once '../../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    die('Acesso não autorizado');
}

try {
    $debug_logs = [];
    
    // 1. Verificar se a tabela jogos existe
    $tabela_existe = $pdo->query("SHOW TABLES LIKE 'jogos'")->rowCount() > 0;
    
    if (!$tabela_existe) {
        // Criar tabela jogos
        $pdo->exec("
            CREATE TABLE jogos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                api_nome VARCHAR(50),
                identificador_api VARCHAR(50),
                identificador_importacao VARCHAR(50),
                status TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        echo "Tabela jogos criada com sucesso.<br>";
    }
    
    // 2. Verificar e adicionar colunas necessárias
    $colunas = [
        'api_nome' => 'VARCHAR(50)',
        'identificador_api' => 'VARCHAR(50)',
        'identificador_importacao' => 'VARCHAR(50)',
        'status' => 'TINYINT(1) DEFAULT 1'
    ];
    
    foreach ($colunas as $coluna => $definicao) {
        $coluna_existe = $pdo->query("SHOW COLUMNS FROM jogos LIKE '$coluna'")->rowCount() > 0;
        
        if (!$coluna_existe) {
            $pdo->exec("ALTER TABLE jogos ADD COLUMN $coluna $definicao");
            echo "Coluna $coluna adicionada à tabela jogos.<br>";
        }
    }
    
    // 3. Inserir ou atualizar jogos padrão
    $jogos_padrao = [
        [
            'nome' => 'Mega-Sena',
            'api_nome' => 'megasena',
            'identificador_api' => 'megasena',
            'identificador_importacao' => 'Loterias Mobile: MS'
        ],
        [
            'nome' => 'Lotofácil',
            'api_nome' => 'lotofacil',
            'identificador_api' => 'lotofacil',
            'identificador_importacao' => 'Loterias Mobile: LF'
        ],
        [
            'nome' => 'Quina',
            'api_nome' => 'quina',
            'identificador_api' => 'quina',
            'identificador_importacao' => 'Loterias Mobile: QN'
        ],
        [
            'nome' => 'Lotomania',
            'api_nome' => 'lotomania',
            'identificador_api' => 'lotomania',
            'identificador_importacao' => 'Loterias Mobile: LM'
        ],
        [
            'nome' => 'Timemania',
            'api_nome' => 'timemania',
            'identificador_api' => 'timemania',
            'identificador_importacao' => 'Loterias Mobile: TM'
        ],
        [
            'nome' => 'Dia de Sorte',
            'api_nome' => 'diadesorte',
            'identificador_api' => 'diadesorte',
            'identificador_importacao' => 'Loterias Mobile: DI'
        ],
        [
            'nome' => '+Milionária',
            'api_nome' => 'maismilionaria',
            'identificador_api' => 'maismilionaria',
            'identificador_importacao' => 'Loterias Mobile: MM'
        ]
    ];
    
    foreach ($jogos_padrao as $jogo) {
        // Verificar se o jogo já existe
        $stmt = $pdo->prepare("SELECT id FROM jogos WHERE nome = ?");
        $stmt->execute([$jogo['nome']]);
        
        if ($stmt->rowCount() > 0) {
            // Atualizar jogo existente
            $stmt = $pdo->prepare("
                UPDATE jogos 
                SET api_nome = ?, 
                    identificador_api = ?,
                    identificador_importacao = ?
                WHERE nome = ?
            ");
            $stmt->execute([
                $jogo['api_nome'],
                $jogo['identificador_api'],
                $jogo['identificador_importacao'],
                $jogo['nome']
            ]);
            echo "Jogo {$jogo['nome']} atualizado.<br>";
        } else {
            // Inserir novo jogo
            $stmt = $pdo->prepare("
                INSERT INTO jogos (
                    nome, 
                    api_nome, 
                    identificador_api,
                    identificador_importacao,
                    status
                ) VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $jogo['nome'],
                $jogo['api_nome'],
                $jogo['identificador_api'],
                $jogo['identificador_importacao']
            ]);
            echo "Jogo {$jogo['nome']} inserido.<br>";
        }
    }
    
    // 4. Atualizar jogos existentes que possam estar sem api_nome
    $pdo->exec("
        UPDATE jogos 
        SET api_nome = CASE 
            WHEN nome LIKE '%Mega-Sena%' THEN 'mega-sena'
            WHEN nome LIKE '%Lotofácil%' THEN 'lotofacil'
            WHEN nome LIKE '%Quina%' THEN 'quina'
            WHEN nome LIKE '%Lotomania%' THEN 'lotomania'
            WHEN nome LIKE '%Timemania%' THEN 'timemania'
            WHEN nome LIKE '%Dia de Sorte%' THEN 'dia-de-sorte'
            WHEN nome LIKE '%+Milionária%' THEN 'mais-milionaria'
            ELSE LOWER(REPLACE(nome, ' ', '-'))
        END,
        identificador_api = CASE 
            WHEN nome LIKE '%Mega-Sena%' THEN 'megasena'
            WHEN nome LIKE '%Lotofácil%' THEN 'lotofacil'
            WHEN nome LIKE '%Quina%' THEN 'quina'
            WHEN nome LIKE '%Lotomania%' THEN 'lotomania'
            WHEN nome LIKE '%Timemania%' THEN 'timemania'
            WHEN nome LIKE '%Dia de Sorte%' THEN 'diadesorte'
            WHEN nome LIKE '%+Milionária%' THEN 'maismilionaria'
            ELSE LOWER(REPLACE(REPLACE(nome, ' ', ''), '-', ''))
        END
        WHERE api_nome IS NULL OR identificador_api IS NULL
    ");
    
    echo "<br>Estrutura da tabela jogos corrigida com sucesso!";
    
    // 5. Exibir estrutura atual da tabela
    echo "<br><br>Estrutura atual da tabela jogos:<br>";
    $colunas = $pdo->query("SHOW COLUMNS FROM jogos")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($colunas);
    echo "</pre>";
    
    // 6. Exibir jogos cadastrados
    echo "<br>Jogos cadastrados:<br>";
    $jogos = $pdo->query("SELECT * FROM jogos")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($jogos);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
    error_log("Erro ao corrigir estrutura da tabela jogos: " . $e->getMessage());
} 