<?php
// Configurações iniciais
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

// Verificar autorização
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado']);
    exit;
}

// Funções utilitárias
function verificar_tabela($pdo, $tabela) {
    try {
        return $pdo->query("SHOW TABLES LIKE '$tabela'")->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function verificar_coluna($pdo, $tabela, $coluna) {
    try {
        return $pdo->query("SHOW COLUMNS FROM `$tabela` LIKE '$coluna'")->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function contar_registros($pdo, $tabela, $condicao = '') {
    try {
        $sql = "SELECT COUNT(*) FROM $tabela" . ($condicao ? " WHERE $condicao" : "");
        return $pdo->query($sql)->fetchColumn();
    } catch (PDOException $e) {
        return "Erro: " . $e->getMessage();
    }
}

// Estrutura de tabelas esperadas (colunas principais)
$estrutura_tabelas = [
    'jogos' => ['id', 'nome', 'codigo', 'quantidade_dezenas', 'status'],
    'concursos' => ['id', 'codigo', 'jogo_id', 'data_sorteio', 'status'],
    'numeros_sorteados' => ['id', 'concurso_id', 'numero'],
    'apostas' => ['id', 'usuario_id', 'jogo_id', 'numeros', 'valor_aposta', 'status', 'processado', 'concurso_id', 'valor_premio'],
    'usuarios' => ['id', 'nome', 'email', 'tipo'],
    'valores_jogos' => ['id', 'jogo_id', 'dezenas', 'valor_premio']
];

// Iniciar diagnóstico
try {
    $diagnostico = [
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'banco_dados' => [
            'conexao' => $pdo ? 'ok' : 'falha',
            'versao_mysql' => $pdo ? $pdo->query('SELECT VERSION()')->fetchColumn() : 'desconhecido'
        ],
        'tabelas' => [],
        'estatisticas' => [],
        'recomendacoes' => []
    ];

    // Verificar tabelas e estrutura
    foreach ($estrutura_tabelas as $tabela => $colunas) {
        $existe = verificar_tabela($pdo, $tabela);
        $diagnostico['tabelas'][$tabela] = ['existe' => $existe, 'colunas' => []];
        
        if (!$existe) {
            $diagnostico['recomendacoes'][] = "Criar tabela '$tabela'";
            continue;
        }

        // Verificar colunas
        foreach ($colunas as $coluna) {
            $existe_coluna = verificar_coluna($pdo, $tabela, $coluna);
            $diagnostico['tabelas'][$tabela]['colunas'][$coluna] = $existe_coluna;
            
            if (!$existe_coluna) {
                $diagnostico['recomendacoes'][] = "Adicionar coluna '$coluna' à tabela '$tabela'";
            }
        }
        
        // Contar registros
        $diagnostico['estatisticas']["total_$tabela"] = contar_registros($pdo, $tabela);
    }
    
    // Estatísticas específicas
    if (verificar_tabela($pdo, 'apostas')) {
        $diagnostico['estatisticas']['apostas_processadas'] = contar_registros($pdo, 'apostas', 'processado = 1');
        $diagnostico['estatisticas']['apostas_nao_processadas'] = contar_registros($pdo, 'apostas', 'processado = 0 OR processado IS NULL');
        $diagnostico['estatisticas']['apostas_com_premio'] = contar_registros($pdo, 'apostas', 'valor_premio > 0');
    }
    
    if (verificar_tabela($pdo, 'concursos')) {
        $diagnostico['estatisticas']['concursos_abertos'] = contar_registros($pdo, 'concursos', "status = 'aberto'");
        $diagnostico['estatisticas']['concursos_fechados'] = contar_registros($pdo, 'concursos', "status = 'fechado'");
        $diagnostico['estatisticas']['concursos_finalizados'] = contar_registros($pdo, 'concursos', "status = 'finalizado'");
        
        // Últimos concursos
        try {
            $stmt = $pdo->query("SELECT c.id, c.codigo, j.nome AS jogo, c.data_sorteio, c.status 
                FROM concursos c 
                JOIN jogos j ON c.jogo_id = j.id 
                ORDER BY c.id DESC LIMIT 5");
            $diagnostico['ultimos_concursos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $diagnostico['ultimos_concursos'] = "Erro: " . $e->getMessage();
        }
    }
    
    // Verificar permissões de diretórios
    $diretorios = ['../../uploads', '../../temp', '../../logs'];
    foreach ($diretorios as $dir) {
        $caminho_relativo = str_replace('../../', '', $dir);
        $diagnostico['diretorios'][$caminho_relativo] = [
            'existe' => file_exists($dir),
            'permissao_escrita' => is_dir($dir) && is_writable($dir)
        ];
        
        if (!file_exists($dir)) {
            $diagnostico['recomendacoes'][] = "Criar diretório '$caminho_relativo'";
        } elseif (!is_writable($dir)) {
            $diagnostico['recomendacoes'][] = "Conceder permissão de escrita ao diretório '$caminho_relativo'";
        }
    }
    
    // Informações do ambiente
    $diagnostico['ambiente'] = [
        'php_versao' => phpversion(),
        'servidor' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
        'sistema_operacional' => PHP_OS,
        'memoria_limite' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ];

    // Retornar diagnóstico
    echo json_encode($diagnostico, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Erro ao gerar diagnóstico: ' . $e->getMessage()
    ]);
} 