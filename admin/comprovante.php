<?php
require_once '../config/database.php';
require_once '../vendor/autoload.php';

if (!isset($_GET['usuario_id']) || !isset($_GET['jogo'])) {
    die("Parâmetros inválidos");
}

$usuario_id = $_GET['usuario_id'];
$jogo_nome = urldecode($_GET['jogo']);

// Debug inicial
error_log("Parâmetros recebidos:");
error_log("usuario_id: " . $usuario_id);
error_log("jogo: " . $jogo_nome);

// Primeiro, vamos verificar quais jogos o usuário tem
$jogos_query = "SELECT DISTINCT jogo_nome FROM apostas_importadas WHERE usuario_id = :usuario_id";
$stmt_jogos = $pdo->prepare($jogos_query);
$stmt_jogos->execute([':usuario_id' => $usuario_id]);
$jogos_disponiveis = $stmt_jogos->fetchAll(PDO::FETCH_COLUMN);

error_log("Jogos disponíveis para este usuário:");
error_log(print_r($jogos_disponiveis, true));

// Nova consulta SQL corrigida
$sql = "
    SELECT 
        ai.*, 
        u.nome as apostador_nome, 
        u.whatsapp, 
        r.nome as revendedor_nome 
    FROM apostas_importadas ai 
    LEFT JOIN usuarios u ON ai.usuario_id = u.id 
    LEFT JOIN usuarios r ON ai.revendedor_id = r.id 
    WHERE ai.usuario_id = :usuario_id 
    AND (
        ai.jogo_nome = :jogo_nome 
        OR 
        ai.jogo_nome = :jogo_nome_mobile
    )
    ORDER BY ai.created_at DESC
";

try {
    $stmt = $pdo->prepare($sql);
    
    // Prepara os nomes dos jogos para a busca
    $jogo_nome_mobile = 'Loterias Mobile: ';
    if ($jogo_nome == 'Mega Sena') {
        $jogo_nome_mobile .= 'MS';
    } else if ($jogo_nome == 'LotoFácil') {
        $jogo_nome_mobile .= 'LF';
    }
    
    $params = [
        ':usuario_id' => $usuario_id,
        ':jogo_nome' => $jogo_nome,
        ':jogo_nome_mobile' => $jogo_nome_mobile
    ];
    
    $stmt->execute($params);
    
    // Debug
    error_log("SQL Query executada: " . $sql);
    error_log("Parâmetros da query:");
    error_log(print_r($params, true));
    
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Resultado da consulta:");
    error_log(print_r($apostas, true));
    
} catch (PDOException $e) {
    error_log("Erro na consulta: " . $e->getMessage());
    die("Erro ao buscar apostas");
}

if (empty($apostas)) {
    die("Nenhuma aposta encontrada - Verifique os parâmetros da URL e a consulta SQL");
}

// Verificar se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$aposta_id = $_GET['id'] ?? 0;

// Buscar dados da aposta
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        u.nome as nome_apostador,
        u.whatsapp as whatsapp_apostador,
        j.nome as nome_jogo,
        j.numeros_disponiveis,
        r.nome as nome_revendedor,
        r.whatsapp as whatsapp_revendedor
    FROM apostas a
    JOIN usuarios u ON a.usuario_id = u.id
    JOIN jogos j ON a.tipo_jogo_id = j.id
    LEFT JOIN usuarios r ON u.revendedor_id = r.id
    WHERE a.id = ?
");
$stmt->execute([$aposta_id]);
$aposta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aposta) {
    die('Aposta não encontrada');
}

// Converter string de números em array
$numeros_apostados = explode(',', $aposta['numeros']);
$numeros_disponiveis = range(1, $aposta['numeros_disponiveis']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilhete #<?php echo $aposta['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
            .bilhete { border: none !important; }
        }
        
        body {
            background: #f0f0f0;
            font-family: 'Courier New', monospace;
        }
        
        .bilhete {
            width: 80mm;
            margin: 20px auto;
            padding: 10px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border: 1px dashed #000;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 10px;
            padding: 10px;
            border-bottom: 2px dashed #000;
        }
        
        .logo-container img {
            max-width: 120px;
            height: auto;
        }
        
        .titulo {
            text-align: center;
            font-size: 1.2em;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
        }
        
        .info-linha {
            font-size: 0.9em;
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
        }
        
        .numeros-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 5px;
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #ddd;
        }
        
        .numero {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8em;
            font-weight: bold;
            border: 1px solid #ccc;
            border-radius: 50%;
        }
        
        .numero.selecionado {
            background: #000;
            color: white;
            border-color: #000;
        }
        
        .separador {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        
        .codigo-barras {
            text-align: center;
            margin: 10px 0;
            padding: 10px 0;
            border-top: 2px dashed #000;
            border-bottom: 2px dashed #000;
        }
        
        .codigo-barras img {
            max-width: 100%;
            height: auto;
        }
        
        .rodape {
            text-align: center;
            font-size: 0.8em;
            margin-top: 10px;
        }
        
        .status-selo {
            position: absolute;
            top: 10px;
            right: 10px;
            transform: rotate(15deg);
            font-size: 1.2em;
            font-weight: bold;
            padding: 5px 15px;
            border: 2px solid;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="bilhete position-relative">
            <!-- Status Selo -->
            <?php
            $status_class = [
                'pendente' => 'text-warning border-warning',
                'aprovada' => 'text-success border-success',
                'rejeitada' => 'text-danger border-danger'
            ][$aposta['status']];
            ?>
            <div class="status-selo <?php echo $status_class; ?>">
                <?php echo strtoupper($aposta['status']); ?>
            </div>

            <!-- Cabeçalho -->
            <div class="logo-container">
                <img src="../assets/img/logo.png" alt="Logo">
                <div class="titulo">
                    <?php echo htmlspecialchars($aposta['nome_jogo']); ?>
                </div>
            </div>

            <!-- Informações da Aposta -->
            <div class="info-linha">
                <span>Bilhete #<?php echo str_pad($aposta['id'], 6, '0', STR_PAD_LEFT); ?></span>
                <span><?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></span>
            </div>

            <div class="info-linha">
                <span>Valor:</span>
                <span>R$ <?php echo number_format($aposta['valor_aposta'], 2, ',', '.'); ?></span>
            </div>

            <div class="separador"></div>

            <!-- Grid de Números -->
            <div class="numeros-grid">
                <?php foreach ($numeros_disponiveis as $numero): ?>
                    <div class="numero <?php echo in_array($numero, $numeros_apostados) ? 'selecionado' : ''; ?>">
                        <?php echo str_pad($numero, 2, '0', STR_PAD_LEFT); ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="separador"></div>

            <!-- Informações do Apostador -->
            <div style="font-size: 0.8em;">
                <div>Apostador: <?php echo htmlspecialchars($aposta['nome_apostador']); ?></div>
                <div>WhatsApp: <?php echo htmlspecialchars($aposta['whatsapp_apostador']); ?></div>
                <?php if ($aposta['nome_revendedor']): ?>
                    <div>Revendedor: <?php echo htmlspecialchars($aposta['nome_revendedor']); ?></div>
                <?php endif; ?>
            </div>

            <!-- Código de Barras -->
            <div class="codigo-barras">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php 
                    echo urlencode('Aposta #' . $aposta['id'] . ' - ' . $aposta['nome_jogo']); 
                ?>" alt="QR Code">
                <div style="font-size: 0.8em; margin-top: 5px;">
                    <?php echo str_pad($aposta['id'], 12, '0', STR_PAD_LEFT); ?>
                </div>
            </div>

            <!-- Rodapé -->
            <div class="rodape">
                Boa Sorte!<br>
                Guarde seu bilhete em local seguro
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </button>
        </div>
    </div>
</body>
</html> 