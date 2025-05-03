<?php
/**
 * Comprovante de Aposta
 * 
 * Este script gera um comprovante para uma aposta específica.
 * 
 * Parâmetros:
 * - usuario_id: ID do usuário que fez a aposta (obrigatório)
 * - jogo: Nome do jogo apostado (obrigatório)
 * - aposta_id: ID específico da aposta (opcional)
 *   Se fornecido, exibirá apenas essa aposta específica.
 *   Se não fornecido, exibirá a aposta mais recente.
 * 
 * Exemplo de uso:
 * gerar_comprovante.php?usuario_id=123&jogo=Dia+de+Sorte&aposta_id=456
 */

// Limpar qualquer saída anterior
ob_clean();
if (ob_get_length()) ob_end_clean();

// Prevenir qualquer saída antes do PDF
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Garante que nenhum conteúdo foi enviado antes
if (headers_sent()) {
    die('Já foram enviados headers');
}

// Inicia o buffer de saída
ob_start();

session_start();
require_once '../config/database.php';

// Função para transformar o nome do jogo em um nome de classe CSS válido
function sanitizeClassName($nome) {
    // Converter para minúsculas
    $nome = strtolower($nome);
    // Remover acentos
    $nome = preg_replace('/[áàãâä]/u', 'a', $nome);
    $nome = preg_replace('/[éèêë]/u', 'e', $nome);
    $nome = preg_replace('/[íìîï]/u', 'i', $nome);
    $nome = preg_replace('/[óòõôö]/u', 'o', $nome);
    $nome = preg_replace('/[úùûü]/u', 'u', $nome);
    $nome = preg_replace('/[ç]/u', 'c', $nome);
    // Substituir espaços e outros caracteres por traços
    $nome = preg_replace('/[^a-z0-9]/', '-', $nome);
    // Remover traços consecutivos
    $nome = preg_replace('/-+/', '-', $nome);
    // Remover traços no início e no fim
    $nome = trim($nome, '-');
    
    return $nome;
}

// Verifica se é admin ou revendedor
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] !== 'admin' && $_SESSION['tipo'] !== 'revendedor')) {
    header("Location: ../login.php");
    exit();
}

// Verificar parâmetros
if (!isset($_GET['usuario_id']) || empty($_GET['usuario_id'])) {
    die("Usuário não especificado");
}

$usuario_id = $_GET['usuario_id'];
$jogo_nome = isset($_GET['jogo']) ? $_GET['jogo'] : null;
$aposta_id = isset($_GET['aposta_id']) ? $_GET['aposta_id'] : null;

// Buscar dados do cliente
$stmt = $pdo->prepare("SELECT nome, email, whatsapp, telefone FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die("Cliente não encontrado");
}

// Buscar dados das apostas
try {
    if ($aposta_id) {
        // Se temos um ID específico, buscamos apenas essa aposta
        $stmt = $pdo->prepare("
            SELECT 
                a.id,
                a.usuario_id,
                a.numeros,
                a.valor_aposta/100 as valor,
                a.valor_premio/100 as valor_premio,
                a.created_at,
                j.nome as jogo_nome
            FROM 
                apostas a
                JOIN jogos j ON a.tipo_jogo_id = j.id
            WHERE 
                a.id = ?
        ");
        $stmt->execute([$aposta_id]);
    } else if ($jogo_nome) {
        // Se temos um nome de jogo, buscamos todas as apostas desse jogo
        if ($jogo_nome == 'Normal') {
            // Apostas normais
            $stmt = $pdo->prepare("
                SELECT 
                    a.id,
                    a.usuario_id,
                    a.numeros,
                    a.valor_aposta/100 as valor,
                    a.valor_premio/100 as valor_premio,
                    a.created_at,
                    j.nome as jogo_nome
                FROM 
                    apostas a
                    JOIN jogos j ON a.tipo_jogo_id = j.id
                WHERE 
                    a.usuario_id = ?
                ORDER BY 
                    a.created_at DESC
            ");
            $stmt->execute([$usuario_id]);
        } else {
            // Apostas importadas
            $stmt = $pdo->prepare("
                SELECT 
                    ai.id,
                    ai.usuario_id,
                    ai.numeros,
                    ai.valor_aposta/100 as valor,
                    ai.valor_premio/100 as valor_premio,
                    ai.created_at,
                    ai.jogo_nome
                FROM 
                    apostas_importadas ai
                WHERE 
                    ai.usuario_id = ? AND
                    ai.jogo_nome LIKE ?
                ORDER BY 
                    ai.created_at DESC
            ");
            $stmt->execute([$usuario_id, "%$jogo_nome%"]);
        }
    } else {
        // Se não temos nenhum dos dois, buscamos todas as apostas do usuário
        $stmt = $pdo->prepare("
            SELECT 
                a.id,
                a.usuario_id,
                a.numeros,
                a.valor_aposta/100 as valor,
                a.valor_premio/100 as valor_premio,
                a.created_at,
                j.nome as jogo_nome
            FROM 
                apostas a
                JOIN jogos j ON a.tipo_jogo_id = j.id
            WHERE 
                a.usuario_id = ?
            ORDER BY 
                a.created_at DESC
        ");
        $stmt->execute([$usuario_id]);
    }

    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar apostas: " . $e->getMessage());
}

if (empty($apostas)) {
    die("Nenhuma aposta encontrada para esse cliente");
}

// Agrupar apostas por jogo
$apostas_por_jogo = [];
foreach ($apostas as $aposta) {
    $nome_jogo = $aposta['jogo_nome'];
    if (!isset($apostas_por_jogo[$nome_jogo])) {
        $apostas_por_jogo[$nome_jogo] = [];
    }
    $apostas_por_jogo[$nome_jogo][] = $aposta;
}

// Gerar HTML para o comprovante
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comprovante de Apostas</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            margin: 40px; 
            background-color: #f8f9fa;
        }
        .comprovante { 
            max-width: 800px; 
            margin: 0 auto; 
            border: 2px solid #007bff; 
            padding: 20px; 
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .header { 
            text-align: center; 
            border-bottom: 2px solid #007bff; 
            padding-bottom: 20px; 
            margin-bottom: 20px; 
        }
        .header h1 { 
            color: #007bff; 
            margin: 0; 
        }
        .info-grupo { 
            margin-bottom: 20px; 
        }
        .info-grupo h2 { 
            color: #0056b3; 
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .info-item { 
            margin-bottom: 10px; 
            display: flex;
        }
        .info-label { 
            font-weight: bold; 
            color: #555; 
            width: 150px;
            flex-shrink: 0;
        }
        .info-value {
            flex-grow: 1;
        }
        .jogo-container {
            margin-bottom: 30px;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            background: #f9f9f9;
        }
        .jogo-header {
            font-weight: bold;
            color: #0056b3;
            font-size: 18px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .aposta-container {
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-radius: 5px;
            border-left: 3px solid #007bff;
        }
        .numeros { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 8px; 
            margin-top: 10px; 
        }
        .numero { 
            width: 36px; 
            height: 36px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            background: #007bff; 
            color: white; 
            border-radius: 50%; 
            font-weight: bold;
            font-size: 14px;
        }
        .footer { 
            margin-top: 30px; 
            text-align: center; 
            padding-top: 20px; 
            border-top: 1px solid #ddd;
            color: #666; 
            font-size: 14px;
        }
        .aposta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .aposta-valor {
            font-weight: bold;
            color: #28a745;
        }
        @media print {
            body {
                margin: 0;
                background: none;
            }
            .comprovante {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="comprovante">
        <div class="header">
            <h1>Comprovante de Apostas</h1>
            <p>Sistema de Loteria</p>
        </div>
        
        <div class="info-grupo">
            <h2>Dados do Apostador</h2>
            <div class="info-item">
                <span class="info-label">Nome:</span>
                <span class="info-value">' . htmlspecialchars($cliente['nome']) . '</span>
            </div>';

if ($cliente['email']) {
    $html .= '
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value">' . htmlspecialchars($cliente['email']) . '</span>
            </div>';
}

if ($cliente['whatsapp']) {
    $html .= '
            <div class="info-item">
                <span class="info-label">WhatsApp:</span>
                <span class="info-value">' . htmlspecialchars($cliente['whatsapp']) . '</span>
            </div>';
} else if ($cliente['telefone']) {
    $html .= '
            <div class="info-item">
                <span class="info-label">Telefone:</span>
                <span class="info-value">' . htmlspecialchars($cliente['telefone']) . '</span>
            </div>';
}

$html .= '
        </div>
        
        <div class="info-grupo">
            <h2>Apostas Realizadas</h2>';

// Para cada jogo, mostrar as apostas correspondentes
foreach ($apostas_por_jogo as $jogo_nome => $apostas_jogo) {
    $html .= '
            <div class="jogo-container">
                <div class="jogo-header">' . htmlspecialchars($jogo_nome) . '</div>';
    
    foreach ($apostas_jogo as $aposta) {
        // Processar os números da aposta
        $numeros_array = explode("\n", $aposta['numeros']);
        if (count($numeros_array) > 1 && !empty($numeros_array[0]) && !is_numeric(trim($numeros_array[0]))) {
            // Provavelmente é uma aposta importada com cabeçalho
            array_shift($numeros_array);
        }
        
        $numeros_html = '';
        foreach ($numeros_array as $linha) {
            // Remover espaços extras e dividir por espaços
            $numeros = preg_split('/\s+/', trim($linha));
            $numeros = array_filter($numeros, 'is_numeric');
            
            if (!empty($numeros)) {
                $numeros_html .= '<div class="numeros">';
                foreach ($numeros as $numero) {
                    $numeros_html .= '<div class="numero">' . str_pad(trim($numero), 2, '0', STR_PAD_LEFT) . '</div>';
                }
                $numeros_html .= '</div>';
            }
        }
        
        if (empty($numeros_html)) {
            // Fallback para o formato anterior
            $numeros = explode(',', $aposta['numeros']);
            $numeros_html = '<div class="numeros">';
            foreach ($numeros as $numero) {
                if (is_numeric(trim($numero))) {
                    $numeros_html .= '<div class="numero">' . str_pad(trim($numero), 2, '0', STR_PAD_LEFT) . '</div>';
                }
            }
            $numeros_html .= '</div>';
        }
        
        $html .= '
                <div class="aposta-container">
                    <div class="aposta-info">
                        <span>Data: ' . date('d/m/Y H:i', strtotime($aposta['created_at'])) . '</span>
                        <span class="aposta-valor">Valor: R$ ' . number_format($aposta['valor'], 2, ',', '.') . '</span>
                    </div>
                    ' . $numeros_html . '
                </div>';
    }
    
    $html .= '
            </div>';
}

$html .= '
        </div>
        
        <div class="footer">
            <p>Este comprovante é válido como confirmação das suas apostas.</p>
            <p>Data de emissão: ' . date('d/m/Y H:i:s') . '</p>
            <p class="no-print"><button onclick="window.print();" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Imprimir Comprovante</button></p>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            // Imprimir automaticamente ao carregar
            // window.print();
        }
    </script>
</body>
</html>';

echo $html;