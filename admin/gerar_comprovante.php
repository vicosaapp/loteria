<?php
// Limpar qualquer saída anterior
ob_clean();
if (ob_get_length()) ob_end_clean();

// Prevenir qualquer saída antes do PDF
error_reporting(0);
ini_set('display_errors', 0);

// Garante que nenhum conteúdo foi enviado antes
if (headers_sent()) {
    die('Já foram enviados headers');
}

// Inicia o buffer de saída
ob_start();

session_start();
require_once '../config/database.php';
require_once '../vendor/setasign/fpdf/fpdf.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Validar parâmetros
if (!isset($_GET['usuario_id']) || !isset($_GET['jogo'])) {
    die('Parâmetros inválidos');
}

$usuario_id = (int)$_GET['usuario_id'];
$jogo = $_GET['jogo'];

// Buscar todas as apostas do usuário para o jogo específico
$sql = "SELECT ai.*, u.nome as apostador, r.nome as revendedor, j.nome as jogo_nome
        FROM apostas_importadas ai
        LEFT JOIN usuarios u ON ai.usuario_id = u.id
        LEFT JOIN usuarios r ON ai.revendedor_id = r.id
        LEFT JOIN jogos j ON j.titulo_importacao = ai.jogo_nome
        WHERE ai.usuario_id = ? 
        AND j.nome = ?
        ORDER BY ai.created_at DESC";

            $stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id, $jogo]);
            $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
if (empty($apostas)) {
    die('Apostas não encontradas');
}

// Usar a primeira aposta para informações gerais
$aposta = $apostas[0];


// Buscar informações do jogo
$sql_jogo = "SELECT j.nome, j.titulo_importacao,
                    vj.valor_premio, vj.dezenas, vj.valor_aposta
             FROM jogos j
             INNER JOIN valores_jogos vj ON j.id = vj.jogo_id
             WHERE j.id = 3 
             AND vj.dezenas = 18 
             AND vj.valor_aposta = 1.00";

$stmt_jogo = $pdo->prepare($sql_jogo);
$stmt_jogo->execute();
$info_jogo = $stmt_jogo->fetch(PDO::FETCH_ASSOC);

// Definir valores fixos para o que não está na tabela
$info_jogo['codigo_concurso'] = '007';

// Calcular o ganho máximo (soma de todas as apostas)
$ganho_maximo = 0;
foreach ($apostas as $aposta) {
    $ganho_maximo += 1600.00; // Valor fixo do prêmio para cada aposta
}

// Processar os números da aposta
$linhas = explode("\n", trim($aposta['numeros']));
array_shift($linhas); // Remove a primeira linha (título do importador)

// Para cada linha de aposta
foreach ($linhas as $i => $linha) {
    // Remover qualquer texto que não seja número
    $linha = preg_replace('/[^0-9\s]/', '', $linha);
    $numeros = array_filter(explode(' ', trim($linha)));
    
    if (!empty($numeros)) {
        echo "<div class='aposta'>";
        echo "<h4>Aposta " . ($i + 1) . "</h4>";
        echo "<div class='numeros'>";
        foreach ($numeros as $numero) {
            // Garantir que o número tenha 2 dígitos
            $numero_formatado = str_pad($numero, 2, '0', STR_PAD_LEFT);
            echo "<span class='numero'>" . $numero_formatado . "</span>";
        }
        echo "</div>";
        echo "</div>";
    }
}

?>
<!DOCTYPE html>
                <html>
                <head>
    <meta charset="utf-8">
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
            background: url('assets/images/bg-comprovante.png') no-repeat center center;
            background-size: cover;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
            position: relative;
            color: #333;
        }
        .overlay {
            background: rgba(255, 255, 255, 0.57);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(0, 123, 255, 0.1);
            border-radius: 10px;
            border: 2px solid #007bff;
        }
        .logo {
            max-width: 400px;
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
            background: rgba(255, 255, 255, 0.9);
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
        .info-row strong {
            color:rgb(0, 0, 0);
        }
        .numbers {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 20px 0;
            justify-content: center;
        }
        .number {
            background:rgb(0, 44, 90);
            color: white;
            padding: 12px;
            border-radius: 50%;
            font-weight: bold;
            min-width: 20px;
            height: 20px;
            line-height: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .game-info {
            background: rgba(0, 123, 255, 0.05);
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            border: 1px solid #007bff;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            color: #495057;
        }
        .aposta-grupo {
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .aposta-grupo h4 {
            color: #0056b3;
            margin: 0 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
        }
        .numbers {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 10px 0;
            justify-content: flex-start;
        }
        .number {
            background: #007bff;
            color: white;
            padding: 10px;
            border-radius: 50%;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            line-height: 18px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
                    </style>
                </head>
                <body>
    <div class="container">
        <div class="overlay">
                    <div class="header">
                <img src="assets/images/logo.png" alt="Logo" class="logo">
                <h2>Nome do jogo:<?php echo $aposta['jogo_nome']; ?></h2>
                    </div>
                    
            <div class="info-block">
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
                    <span>R$ <?php echo number_format($aposta['valor_aposta'], 2, ',', '.'); ?></span>
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
                                <div class="number"><?php echo $numero; ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                    </div>
                    
            <div class="game-info">
                <h3>Informações do Jogo</h3>
                <div class="info-row">
                    <strong>Números Necessários:</strong>
                    <span>15 números</span>
                </div>
                <div class="info-row">
                    <strong>Prêmio por Aposta:</strong>
                    <span>R$ 1.600,00</span>
                </div>
                <div class="info-row">
                    <strong>Ganho Máximo:</strong>
                    <span>R$ <?php echo number_format($ganho_maximo, 2, ',', '.'); ?></span>
                </div>
                <div class="info-row">
                    <strong>Concurso:</strong>
                    <span><?php echo $info_jogo['codigo_concurso']; ?></span>
                </div>
                    </div>
                    
                    <div class="footer">
                <p><strong>ID da aposta:</strong> <?php echo $aposta['id']; ?></p>
                <p>Este comprovante é sua garantia. Guarde-o com cuidado.</p>
                <p><small>Gerado em: <?php echo date('d/m/Y H:i:s'); ?></small></p>
            </div>
        </div>
                    </div>
                </body>
</html>