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
    // Tentar buscar em apostas
    $stmt = $pdo->prepare("SELECT a.numeros, u.nome, u.email, u.whatsapp, u.telefone FROM apostas a JOIN usuarios u ON a.usuario_id = u.id WHERE a.usuario_id = ? LIMIT 1");
    $stmt->execute([$usuario_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$cliente) {
    // Não encontrou o cliente, usar um placeholder
    $cliente = [
        'nome' => 'Cliente não cadastrado',
        'email' => '',
        'whatsapp' => '',
        'telefone' => ''
    ];
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
                a.valor_aposta as valor,
                a.valor_premio as valor_premio,
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
                    a.valor_aposta as valor,
                    a.valor_premio as valor_premio,
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
                    ai.valor_aposta as valor,
                    ai.valor_premio as valor_premio,
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
                a.valor_aposta as valor,
                a.valor_premio as valor_premio,
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
            margin: 0; 
            padding: 20px;
            background-color: #f5f5f5;
        }
        .comprovante { 
            max-width: 400px; 
            margin: 0 auto; 
            background-color: #ffe45c;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        .comprovante::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'%3E%3Ccircle cx=\'50\' cy=\'50\' r=\'40\' fill=\'%23fed930\' opacity=\'0.3\'/%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 80px;
            opacity: 0.5;
            z-index: 0;
        }
        .logo-container {
            text-align: center; 
            margin-bottom: 15px; 
            position: relative; 
            z-index: 1;
        }
        .logo-img {
            max-width: 150px; 
            height: auto;
        }
        .info-item { 
            margin-bottom: 8px; 
            display: flex;
            position: relative;
            z-index: 1;
        }
        .info-label { 
            font-weight: bold; 
            width: 160px;
            font-size: 14px;
            flex-shrink: 0;
        }
        .info-value {
            font-size: 14px;
            flex-grow: 1;
            font-weight: bold;
        }
        .divisor {
            border-top: 1px dashed #aaa;
            margin: 15px 0;
            position: relative;
            z-index: 1;
        }
        .jogo-nome {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin: 15px 0;
            position: relative;
            z-index: 1;
        }
        .numeros { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 4px; 
            justify-content: center;
            margin: 10px 0 15px 0;
            position: relative;
            z-index: 1;
        }
        .numero { 
            width: 36px; 
            height: 36px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            background: #6030b1; 
            color: white; 
            border-radius: 50%; 
            font-weight: bold;
            font-size: 15px;
        }
        .footer { 
            margin-top: 20px; 
            text-align: center; 
            font-size: 14px;
            color: #555;
            position: relative;
            z-index: 1;
        }
        .bottom-info {
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: none;
            }
            .comprovante {
                box-shadow: none;
                width: 100%;
                max-width: none;
                border-radius: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="comprovante">
        <div class="logo-container">
            <img src="/img_app/logo.png" alt="Logo Loto Minas" class="logo-img">
        </div>';

// Para cada jogo, mostrar as apostas correspondentes
// Vamos exibir apenas a primeira aposta para simplificar
if (!empty($apostas_por_jogo)) {
    reset($apostas_por_jogo);
    $jogo_nome = key($apostas_por_jogo);
    $apostas_jogo = current($apostas_por_jogo);
    $aposta = $apostas_jogo[0]; // Pegar a primeira aposta
    
    // Processar os números da aposta
    $numeros_array = [];
    if (strpos($aposta['numeros'], ',') !== false) {
        $numeros_array = explode(',', $aposta['numeros']);
    } else {
        $numeros_array = preg_split('/\s+/', trim($aposta['numeros']));
    }
    $numeros_array = array_filter($numeros_array, 'is_numeric');
    
    // Gerar o ID único da aposta
    $aposta_id = $aposta['id'];
    
    // Obter data e hora da aposta
    $data_emissao = !empty($aposta['created_at']) 
        ? date('d/m/Y H:i:s', strtotime($aposta['created_at'])) 
        : date('d/m/Y H:i:s');
    
    // Buscar informações do concurso
    $stmt = $pdo->prepare("
        SELECT c.codigo, c.data_sorteio
        FROM jogos j
        LEFT JOIN concursos c ON j.id = c.jogo_id AND c.status = 'pendente'
        WHERE j.nome = ?
        ORDER BY c.data_sorteio ASC
        LIMIT 1
    ");
    $stmt->execute([$jogo_nome]);
    $concurso_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $concurso_numero = $concurso_info ? $concurso_info['codigo'] : 'N/A';
    $data_sorteio = !empty($concurso_info['data_sorteio']) 
        ? date('d/m/Y', strtotime($concurso_info['data_sorteio'])) 
        : date('d/m/Y');
    $hora_sorteio = !empty($concurso_info['data_sorteio']) 
        ? date('H:i', strtotime($concurso_info['data_sorteio'])) 
        : '20:00';
    
    // Usar o valor_premio da aposta específica
    $premio_estimado = $aposta['valor_premio'];
    
    $html .= '
        <div class="info-item">
            <span class="info-label">ID APOSTA:</span>
            <span class="info-value">' . $aposta_id . '</span>
        </div>
        <div class="info-item">
            <span class="info-label">EMITIDO EM:</span>
            <span class="info-value">' . $data_emissao . '</span>
        </div>
        <div class="info-item">
            <span class="info-label">PARTICIPANTE:</span>
            <span class="info-value">' . htmlspecialchars(strtoupper($cliente['nome'])) . '</span>
        </div>
        <div class="info-item">
            <span class="info-label">CONCURSO:</span>
            <span class="info-value">' . $concurso_numero . '</span>
        </div>
        <div class="info-item">
            <span class="info-label">DATA DO SORTEIO:</span>
            <span class="info-value">' . $data_sorteio . '</span>
        </div>
        <div class="info-item">
            <span class="info-label">HORA DO SORTEIO:</span>
            <span class="info-value">' . $hora_sorteio . '</span>
        </div>
        
        <div class="divisor"></div>
        
        <div class="jogo-nome">' . htmlspecialchars($jogo_nome) . '</div>
        
        <div class="numeros">';
    
    foreach ($numeros_array as $numero) {
        $numero_formatado = str_pad(trim($numero), 2, '0', STR_PAD_LEFT);
        $html .= '<div class="numero">' . $numero_formatado . '</div>';
    }
    
    $html .= '
        </div>
        
        <div class="divisor"></div>
        
        <div class="bottom-info">
            <div class="info-item">
                <span class="info-label">QTD DEZENAS:</span>
                <span class="info-value">' . count($numeros_array) . '</span>
            </div>
            <div class="info-item">
                <span class="info-label">VALOR APOSTADO:</span>
                <span class="info-value">R$ ' . number_format($aposta['valor'], 2, ',', '.') . '</span>
            </div>
            <div class="info-item">
                <span class="info-label">VALOR DO PRÊMIO:</span>
                <span class="info-value">R$ ' . number_format($premio_estimado, 2, ',', '.') . '</span>
            </div>
        </div>
        
        <div class="footer">
            <p>Aplicativo Loto Minas ( lotominas.site )</p>
            <p class="no-print"><button onclick="window.print();" style="padding: 10px 20px; background: #6030b1; color: white; border: none; border-radius: 5px; cursor: pointer;">Imprimir Comprovante</button></p>
        </div>';
}

$html .= '
    </div>
    
    <script>
        window.onload = function() {
            // Comentado para não imprimir automaticamente
            // window.print();
        }
    </script>
</body>
</html>';

echo $html;