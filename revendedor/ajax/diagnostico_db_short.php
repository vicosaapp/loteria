<?php
// Configurações iniciais
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../config/database.php';
session_start();

// Verificar autorização
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Acesso não autorizado']);
    exit;
}

// Função para verificar tabela
function verificar_tabela($pdo, $tabela) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Função para verificar coluna
function verificar_coluna($pdo, $tabela, $coluna) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$tabela` LIKE '$coluna'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Gerar relatório de diagnóstico
try {
    $diagnóstico = [
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'conexao_db' => true,
        'tabelas' => [],
        'recomendacoes' => []
    ];

    // Verificar tabelas principais
    $tabelas_essenciais = [
        'jogos', 'concursos', 'numeros_sorteados', 'apostas', 'usuarios', 'valores_jogos'
    ];

    foreach ($tabelas_essenciais as $tabela) {
        $existe = verificar_tabela($pdo, $tabela);
        $diagnóstico['tabelas'][$tabela] = $existe;
        
        if (!$existe) {
            $diagnóstico['recomendacoes'][] = "Criar tabela $tabela";
            continue;
        }

        // Verificar estrutura da tabela com base em seu tipo
        switch ($tabela) {
            case 'apostas':
                $colunas_necessarias = ['id', 'usuario_id', 'numeros', 'valor_aposta', 'status', 'processado', 'concurso', 'valor_premio'];
                break;
            case 'concursos':
                $colunas_necessarias = ['id', 'codigo', 'jogo_id', 'data_sorteio', 'status'];
                break;
            case 'numeros_sorteados':
                $colunas_necessarias = ['id', 'concurso_id', 'numero'];
                break;
            case 'jogos':
                $colunas_necessarias = ['id', 'nome', 'codigo', 'status'];
                break;
            case 'valores_jogos':
                $colunas_necessarias = ['id', 'jogo_id', 'dezenas', 'valor_premio'];
                break;
            default:
                $colunas_necessarias = [];
        }

        foreach ($colunas_necessarias as $coluna) {
            $existe_coluna = verificar_coluna($pdo, $tabela, $coluna);
            $diagnóstico['tabelas'][$tabela . '_colunas'][$coluna] = $existe_coluna;
            
            if (!$existe_coluna) {
                $diagnóstico['recomendacoes'][] = "Adicionar coluna $coluna na tabela $tabela";
            }
        }
    }

    // Verificar dados básicos
    $consultas = [
        'total_jogos' => "SELECT COUNT(*) FROM jogos",
        'total_concursos' => "SELECT COUNT(*) FROM concursos",
        'total_apostas' => "SELECT COUNT(*) FROM apostas",
        'apostas_processadas' => "SELECT COUNT(*) FROM apostas WHERE processado = 1",
        'apostas_nao_processadas' => "SELECT COUNT(*) FROM apostas WHERE processado = 0 OR processado IS NULL",
        'total_valores_premio' => "SELECT COUNT(*) FROM valores_jogos"
    ];

    foreach ($consultas as $chave => $sql) {
        try {
            $stmt = $pdo->query($sql);
            $diagnóstico['dados'][$chave] = $stmt->fetchColumn();
        } catch (PDOException $e) {
            $diagnóstico['dados'][$chave] = 'Erro: ' . $e->getMessage();
        }
    }

    // Verificar permissões de diretórios importantes
    $diretorios = [
        'uploads' => '../../uploads',
        'temp' => '../../temp',
        'logs' => '../../logs'
    ];

    foreach ($diretorios as $nome => $caminho) {
        $diagnóstico['diretorios'][$nome] = [
            'existe' => file_exists($caminho),
            'permissao_escrita' => is_dir($caminho) && is_writable($caminho)
        ];
        
        if (!file_exists($caminho)) {
            $diagnóstico['recomendacoes'][] = "Criar diretório $caminho";
        } elseif (!is_writable($caminho)) {
            $diagnóstico['recomendacoes'][] = "Conceder permissão de escrita ao diretório $caminho";
        }
    }

    // Retornar diagnóstico
    header('Content-Type: application/json');
    echo json_encode($diagnóstico, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error', 
        'message' => 'Erro ao gerar diagnóstico: ' . $e->getMessage()
    ]);
} 