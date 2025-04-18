<?php
// Configurações iniciais
header('Content-Type: application/json');
require_once '../../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

// Função para verificar se uma tabela existe
function verificarTabelaExiste($pdo, $tabela) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Função para verificar se uma coluna existe em uma tabela
function verificarColunaExiste($pdo, $tabela, $coluna) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$tabela` LIKE '$coluna'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Verificar conexão com o banco
$resultado = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'banco_dados' => [],
    'arquivos' => [],
    'caminhos' => [],
    'debug' => []
];

// Informações do banco de dados
try {
    if (!$pdo) {
        throw new Exception("Conexão com o banco falhou");
    }
    
    $resultado['banco_dados']['conexao'] = 'ok';
    
    // Verificar tabelas
    $tabelas_alvo = ['jogos', 'concursos', 'numeros_sorteados'];
    $tabelas_status = [];
    
    foreach ($tabelas_alvo as $tabela) {
        $existe = verificarTabelaExiste($pdo, $tabela);
        $tabelas_status[$tabela] = [
            'existe' => $existe,
            'colunas' => []
        ];
        
        if ($existe) {
            // Verificar colunas
            $stmt = $pdo->query("SHOW COLUMNS FROM `$tabela`");
            $colunas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $tabelas_status[$tabela]['colunas'] = $colunas;
            
            // Verificar registros
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$tabela`");
            $tabelas_status[$tabela]['registros'] = $stmt->fetchColumn();
        }
    }
    
    $resultado['banco_dados']['tabelas'] = $tabelas_status;
    
    // Verificação específica para jogos
    if (verificarTabelaExiste($pdo, 'jogos')) {
        $stmt = $pdo->query("SELECT id, nome, quantidade_dezenas FROM jogos");
        $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultado['banco_dados']['jogos'] = $jogos;
    }
    
    // Verificar concursos recentes
    if (verificarTabelaExiste($pdo, 'concursos')) {
        $stmt = $pdo->query("SELECT id, jogo_id, codigo, data_sorteio, status FROM concursos ORDER BY id DESC LIMIT 5");
        $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultado['banco_dados']['concursos_recentes'] = $concursos;
    }
    
} catch (Exception $e) {
    $resultado['banco_dados']['conexao'] = 'falha';
    $resultado['banco_dados']['erro'] = $e->getMessage();
}

// Verificação de arquivos
$arquivos_alvo = [
    'ajax/salvar_resultado.php',
    'ajax/criar_tabelas_resultado.php',
    'resultados.php'
];

$diretorio_atual = __DIR__;
$diretorio_base = realpath(dirname(__DIR__));
$resultado['caminhos']['diretorio_atual'] = $diretorio_atual;
$resultado['caminhos']['diretorio_base'] = $diretorio_base;

foreach ($arquivos_alvo as $arquivo) {
    $caminho_completo = $diretorio_base . '/' . $arquivo;
    $resultado['arquivos'][$arquivo] = [
        'existe' => file_exists($caminho_completo),
        'tamanho' => file_exists($caminho_completo) ? filesize($caminho_completo) : 0,
        'caminho' => $caminho_completo
    ];
}

// Verificar permissões
$diretorios_permissao = [
    $diretorio_base,
    $diretorio_base . '/ajax'
];

foreach ($diretorios_permissao as $diretorio) {
    $resultado['caminhos']['permissoes'][$diretorio] = [
        'existe' => is_dir($diretorio),
        'leitura' => is_readable($diretorio),
        'escrita' => is_writable($diretorio)
    ];
}

// Informações do ambiente
$resultado['debug']['php_version'] = phpversion();
$resultado['debug']['server'] = $_SERVER['SERVER_SOFTWARE'];
$resultado['debug']['document_root'] = $_SERVER['DOCUMENT_ROOT'];
$resultado['debug']['script_filename'] = $_SERVER['SCRIPT_FILENAME'];
$resultado['debug']['session'] = [
    'ativa' => session_status() === PHP_SESSION_ACTIVE,
    'usuario_id' => $_SESSION['usuario_id'] ?? 'não definido',
    'tipo' => $_SESSION['tipo'] ?? 'não definido'
];

// Verificar se o arquivo salvar_resultado.php tem o caminho correto para o banco de dados
if (isset($resultado['arquivos']['ajax/salvar_resultado.php']['existe']) && 
    $resultado['arquivos']['ajax/salvar_resultado.php']['existe']) {
    
    $conteudo = file_get_contents($diretorio_base . '/ajax/salvar_resultado.php');
    $resultado['arquivos']['ajax/salvar_resultado.php']['inclui_database'] = 
        strpos($conteudo, 'require_once') !== false && 
        strpos($conteudo, 'database.php') !== false;
}

// Retornar resultado em JSON
echo json_encode($resultado, JSON_PRETTY_PRINT);
?> 