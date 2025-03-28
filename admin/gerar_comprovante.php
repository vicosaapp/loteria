<?php
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

// Verifica se é admin ou revendedor
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['tipo'], ['admin', 'revendedor'])) {
    header("Location: ../login.php");
    exit();
}

// Validar parâmetros
if (!isset($_GET['usuario_id']) || !isset($_GET['jogo'])) {
    die('Parâmetros inválidos');
}

$usuario_id = (int)$_GET['usuario_id'];
$jogo = $_GET['jogo'];

// Debug
error_log("Buscando apostas para usuário_id: " . $usuario_id . " e jogo: " . $jogo);

// Buscar todas as apostas do usuário para o jogo específico
$sql = "SELECT 
            ai.*, 
            u.nome as apostador, 
            r.nome as revendedor, 
            COALESCE(j.nome, ai.jogo_nome) as jogo_nome
        FROM apostas_importadas ai
        LEFT JOIN usuarios u ON ai.usuario_id = u.id
        LEFT JOIN usuarios r ON ai.revendedor_id = r.id
        LEFT JOIN jogos j ON j.titulo_importacao = ai.jogo_nome
        WHERE ai.usuario_id = :usuario_id 
        AND (
            ai.jogo_nome = :jogo 
            OR ai.jogo_nome LIKE :jogo_mobile
            OR j.nome = :jogo_nome
        )
        ORDER BY ai.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':usuario_id' => $usuario_id,
    ':jogo' => $jogo,
    ':jogo_mobile' => "Loterias Mobile: %" . $jogo,
    ':jogo_nome' => $jogo
]);
$apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug
error_log("SQL: " . $sql);
error_log("Parâmetros: " . json_encode([
    ':usuario_id' => $usuario_id,
    ':jogo' => $jogo,
    ':jogo_mobile' => "Loterias Mobile: %" . $jogo,
    ':jogo_nome' => $jogo
]));
error_log("Apostas encontradas: " . count($apostas));

if (empty($apostas)) {
    // Tentar buscar em apostas normais
    $sql = "SELECT 
                a.*, 
                u.nome as apostador,
                r.nome as revendedor,
                j.nome as jogo_nome,
                a.valor_aposta,
                a.numeros
            FROM apostas a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            LEFT JOIN usuarios r ON u.revendedor_id = r.id
            LEFT JOIN jogos j ON a.tipo_jogo_id = j.id
            WHERE a.usuario_id = :usuario_id 
            AND (j.nome = :jogo OR j.titulo_importacao = :jogo)
            ORDER BY a.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':usuario_id' => $usuario_id,
        ':jogo' => $jogo
    ]);
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug
    error_log("Buscando em apostas normais");
    error_log("Apostas normais encontradas: " . count($apostas));
}

if (empty($apostas)) {
    die('Apostas não encontradas para o usuário ' . $usuario_id . ' e jogo ' . $jogo);
}

// Usar a primeira aposta para informações gerais
$aposta = $apostas[0];

// Buscar informações do jogo
$sql_jogo = "SELECT 
                j.nome, 
                j.titulo_importacao,
                COALESCE(vj.valor_premio, 1600.00) as valor_premio,
                COALESCE(vj.dezenas, 15) as dezenas,
                COALESCE(vj.valor_aposta, 2.00) as valor_aposta
            FROM jogos j
            LEFT JOIN valores_jogos vj ON j.id = vj.jogo_id
            WHERE j.nome = :jogo 
            OR j.titulo_importacao = :jogo
            LIMIT 1";

$stmt_jogo = $pdo->prepare($sql_jogo);
$stmt_jogo->execute([':jogo' => $jogo]);
$info_jogo = $stmt_jogo->fetch(PDO::FETCH_ASSOC);

if (!$info_jogo) {
    // Valores padrão caso não encontre na tabela
    $info_jogo = [
        'nome' => $jogo,
        'valor_premio' => 1600.00,
        'dezenas' => 15,
        'valor_aposta' => 2.00
    ];
}

// Debug
error_log("Informações do jogo: " . json_encode($info_jogo));

// Calcular o ganho máximo (soma de todas as apostas)
$ganho_maximo = count($apostas) * $info_jogo['valor_premio'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Comprovante de Aposta - <?php echo $jogo; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
            position: relative;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px solid #007bff;
        }
        .logo {
            max-width: 200px;
            width: 100%;
            height: auto;
            margin: 0 auto;
            display: block;
            padding: 10px;
        }
        .header h2 {
            color: #0056b3;
            margin: 10px 0 0;
        }
        .info-block {
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-row strong {
            color: #000;
        }
        .aposta-grupo {
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        .aposta-grupo h4 {
            color: #0056b3;
            margin-bottom: 10px;
        }
        .numbers {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        .number {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #007bff;
            color: white;
            border-radius: 50%;
            font-weight: bold;
        }
        .game-info {
            margin-top: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #666;
        }
        .validation-code {
            text-align: center;
            font-family: monospace;
            font-size: 1.2em;
            margin: 20px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        .qr-code img {
            max-width: 150px;
            height: auto;
        }
        .warning {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 10px;
            color: #856404;
            text-align: center;
        }
        .total-info {
            margin-top: 20px;
            padding: 15px;
            background: #e9ecef;
            border-radius: 10px;
            text-align: center;
        }
        .total-info h3 {
            color: #0056b3;
            margin-bottom: 10px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .container {
                box-shadow: none;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="../assets/images/logo.png" alt="Logo" class="logo">
            <h2><?php echo $aposta['jogo_nome']; ?></h2>
        </div>
        
        <div class="info-block">
            <div class="info-row">
                <strong>Comprovante Nº:</strong>
                <span><?php echo str_pad($aposta['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="info-row">
                <strong>Data e hora:</strong>
                <span><?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></span>
            </div>
            <div class="info-row">
                <strong>Apostador:</strong>
                <span><?php echo $aposta['apostador']; ?></span>
            </div>
            <div class="info-row">
                <strong>Revendedor:</strong>
                <span><?php echo $aposta['revendedor']; ?></span>
            </div>
            <div class="info-row">
                <strong>Valor da Aposta:</strong>
                <span>R$ <?php echo number_format($info_jogo['valor_aposta'], 2, ',', '.'); ?></span>
            </div>
            <div class="info-row">
                <strong>Total de Apostas:</strong>
                <span><?php echo count($apostas); ?></span>
            </div>
            <div class="info-row">
                <strong>Valor Total:</strong>
                <span>R$ <?php echo number_format($info_jogo['valor_aposta'] * count($apostas), 2, ',', '.'); ?></span>
            </div>
        </div>
        
        <div class="info-block">
            <h3>Apostas Realizadas</h3>
            <?php foreach ($apostas as $index => $aposta_atual): ?>
                <div class="aposta-grupo">
                    <h4>Aposta <?php echo $index + 1; ?></h4>
                    <div class="numbers">
                        <?php 
                        $numeros_aposta = array_filter(explode(' ', trim($aposta_atual['numeros'])));
                        sort($numeros_aposta, SORT_NUMERIC);
                        foreach ($numeros_aposta as $numero): 
                        ?>
                            <div class="number"><?php echo str_pad($numero, 2, '0', STR_PAD_LEFT); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="game-info">
            <h3>Informações do Jogo</h3>
            <div class="info-row">
                <strong>Nome do Jogo:</strong>
                <span><?php echo $info_jogo['nome']; ?></span>
            </div>
            <div class="info-row">
                <strong>Números Necessários:</strong>
                <span><?php echo $info_jogo['dezenas']; ?> números</span>
            </div>
            <div class="info-row">
                <strong>Prêmio por Acerto:</strong>
                <span>R$ <?php echo number_format($info_jogo['valor_premio'], 2, ',', '.'); ?></span>
            </div>
            <div class="info-row">
                <strong>Ganho Máximo Possível:</strong>
                <span>R$ <?php echo number_format($ganho_maximo, 2, ',', '.'); ?></span>
            </div>
        </div>

        <div class="total-info">
            <h3>Resumo da Aposta</h3>
            <p><strong>Total de Jogos:</strong> <?php echo count($apostas); ?></p>
            <p><strong>Valor Total Investido:</strong> R$ <?php echo number_format($info_jogo['valor_aposta'] * count($apostas), 2, ',', '.'); ?></p>
            <p><strong>Ganho Máximo Possível:</strong> R$ <?php echo number_format($ganho_maximo, 2, ',', '.'); ?></p>
        </div>

        <div class="validation-code">
            <strong>Código de Validação:</strong><br>
            <?php echo strtoupper(substr(md5($aposta['id'] . $aposta['created_at']), 0, 16)); ?>
        </div>

        <div class="qr-code">
            <img src="https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=<?php 
                echo urlencode(json_encode([
                    'id' => $aposta['id'],
                    'jogo' => $aposta['jogo_nome'],
                    'data' => $aposta['created_at'],
                    'apostador' => $aposta['apostador']
                ]));
            ?>" alt="QR Code">
        </div>

        <div class="warning">
            <p><strong>ATENÇÃO:</strong> Este comprovante é sua garantia. Guarde-o em local seguro.</p>
            <p>Em caso de premiação, este documento será necessário para receber o prêmio.</p>
        </div>
        
        <div class="footer">
            <p><strong>ID da aposta:</strong> <?php echo str_pad($aposta['id'], 8, '0', STR_PAD_LEFT); ?></p>
            <p>Gerado em: <?php echo date('d/m/Y H:i:s'); ?></p>
            <p>Este comprovante tem validade legal e não necessita de assinatura.</p>
        </div>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimir
        </button>
        <button onclick="history.back()" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </button>
    </div>

    <script>
        window.onload = function() {
            // Adicionar botões de Bootstrap
            var style = document.createElement('style');
            style.textContent = `
                .btn {
                    display: inline-block;
                    padding: 0.375rem 0.75rem;
                    font-size: 1rem;
                    font-weight: 400;
                    line-height: 1.5;
                    text-align: center;
                    text-decoration: none;
                    vertical-align: middle;
                    cursor: pointer;
                    border: 1px solid transparent;
                    border-radius: 0.25rem;
                    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
                }
                .btn-primary {
                    color: #fff;
                    background-color: #007bff;
                    border-color: #007bff;
                }
                .btn-secondary {
                    color: #fff;
                    background-color: #6c757d;
                    border-color: #6c757d;
                }
                .btn:hover {
                    opacity: 0.9;
                }
            `;
            document.head.appendChild(style);
        }
    </script>
</body>
</html>