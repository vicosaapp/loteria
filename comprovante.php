<?php
/**
 * Visualização pública de comprovante de apostas
 * Este arquivo permite que apostadores visualizem seus comprovantes através de um link público
 */

session_start();
require_once 'config/database.php';

// Definir variáveis
$usuario_id = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
$jogo_nome = isset($_GET['jogo']) ? $_GET['jogo'] : '';
$aposta_id = isset($_GET['aposta_id']) ? (int)$_GET['aposta_id'] : 0;
$formato = isset($_GET['formato']) ? $_GET['formato'] : 'html';
$token = isset($_GET['token']) ? $_GET['token'] : '';

// Verificar token de segurança (hash simples para evitar acesso não autorizado)
$token_esperado = md5($usuario_id . $aposta_id . 'loteria_seguranca');
$token_valido = ($token === $token_esperado);

if (!$token_valido && !isset($_SESSION['usuario_id'])) {
    // Se o token não for válido e não houver sessão ativa, exibir erro
    header('Content-Type: text/html; charset=utf-8');
    echo '<div style="text-align: center; margin-top: 50px; font-family: Arial, sans-serif;">';
    echo '<h1>Acesso não autorizado</h1>';
    echo '<p>O link de acesso ao comprovante é inválido ou expirou.</p>';
    echo '<p>Entre em contato com seu revendedor para obter um novo link.</p>';
    echo '</div>';
    exit;
}

// Consultar informações da aposta
try {
    if ($aposta_id > 0) {
        // Consulta específica para uma aposta
        $stmt = $pdo->prepare("
            SELECT 
                a.id, a.numeros, a.valor_aposta, a.valor_premio, a.created_at,
                u.nome AS apostador_nome, u.telefone AS apostador_telefone, u.whatsapp AS apostador_whatsapp,
                r.nome AS revendedor_nome, r.telefone AS revendedor_telefone, r.whatsapp AS revendedor_whatsapp,
                j.nome AS jogo_nome, j.descricao AS jogo_descricao
            FROM 
                apostas a
            JOIN 
                usuarios u ON a.usuario_id = u.id
            JOIN 
                jogos j ON a.tipo_jogo_id = j.id
            LEFT JOIN 
                usuarios r ON a.revendedor_id = r.id
            WHERE 
                a.id = ? 
                AND a.usuario_id = ?
        ");
        $stmt->execute([$aposta_id, $usuario_id]);
    } else {
        // Consulta para todas as apostas de um usuário em um jogo
        $stmt = $pdo->prepare("
            SELECT 
                a.id, a.numeros, a.valor_aposta, a.valor_premio, a.created_at,
                u.nome AS apostador_nome, u.telefone AS apostador_telefone, u.whatsapp AS apostador_whatsapp,
                r.nome AS revendedor_nome, r.telefone AS revendedor_telefone, r.whatsapp AS revendedor_whatsapp,
                j.nome AS jogo_nome, j.descricao AS jogo_descricao
            FROM 
                apostas a
            JOIN 
                usuarios u ON a.usuario_id = u.id
            JOIN 
                jogos j ON a.tipo_jogo_id = j.id
            LEFT JOIN 
                usuarios r ON a.revendedor_id = r.id
            WHERE 
                a.usuario_id = ? 
                AND j.nome = ?
            ORDER BY 
                a.created_at DESC
        ");
        $stmt->execute([$usuario_id, $jogo_nome]);
    }
    
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($apostas)) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<div style="text-align: center; margin-top: 50px; font-family: Arial, sans-serif;">';
        echo '<h1>Comprovante não encontrado</h1>';
        echo '<p>Não foi possível encontrar o comprovante solicitado.</p>';
        echo '<p>Verifique se o link está correto ou entre em contato com seu revendedor.</p>';
        echo '</div>';
        exit;
    }
    
    // Determinar formato de saída
    if ($formato === 'pdf') {
        // Redirecionar para a versão admin que gera o PDF (que já está implementada)
        // Adicionamos o token para garantir acesso
        header("Location: admin/gerar_comprovante.php?usuario_id={$usuario_id}&jogo={$jogo_nome}&aposta_id={$aposta_id}&formato=pdf&public_token={$token_esperado}");
        exit;
    } else {
        // Formato HTML - exibir comprovante diretamente
        $aposta = $apostas[0]; // Usar a primeira aposta como referência
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprovante de Aposta - <?php echo htmlspecialchars($aposta['jogo_nome']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .comprovante {
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        .info {
            margin-bottom: 15px;
        }
        .info-label {
            font-weight: bold;
        }
        .numeros {
            text-align: center;
            margin: 20px 0;
            font-size: 18px;
            letter-spacing: 2px;
        }
        .numero {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            background-color: #28a745;
            color: white;
            margin: 0 3px;
        }
        .footer {
            font-size: 12px;
            text-align: center;
            margin-top: 20px;
            color: #777;
        }
        .action-buttons {
            text-align: center;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
        }
        .btn-outline {
            background-color: transparent;
            border: 1px solid #28a745;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">LotoMinas</div>
        <h1>Comprovante de Aposta</h1>
    </div>

    <?php foreach($apostas as $index => $aposta): 
        // Formatar números
        $numeros = explode(',', $aposta['numeros']);
        $numeros = array_map('trim', $numeros);
        sort($numeros, SORT_NUMERIC);
    ?>
    <div class="comprovante">
        <div class="info">
            <p><span class="info-label">Apostador:</span> <?php echo htmlspecialchars($aposta['apostador_nome']); ?></p>
            <p><span class="info-label">Jogo:</span> <?php echo htmlspecialchars($aposta['jogo_nome']); ?></p>
            <p><span class="info-label">Data:</span> <?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></p>
            <p><span class="info-label">Valor da aposta:</span> R$ <?php echo number_format($aposta['valor_aposta'], 2, ',', '.'); ?></p>
            <p><span class="info-label">Valor do prêmio:</span> R$ <?php echo number_format($aposta['valor_premio'], 2, ',', '.'); ?></p>
        </div>

        <div class="numeros">
            <?php foreach($numeros as $numero): ?>
            <span class="numero"><?php echo str_pad($numero, 2, '0', STR_PAD_LEFT); ?></span>
            <?php endforeach; ?>
        </div>
        
        <?php if ($index === 0): // Exibir apenas para a primeira aposta ?>
        <div class="info">
            <p><span class="info-label">Revendedor:</span> <?php echo htmlspecialchars($aposta['revendedor_nome']); ?></p>
            <?php if (!empty($aposta['revendedor_telefone']) || !empty($aposta['revendedor_whatsapp'])): ?>
            <p><span class="info-label">Contato:</span> 
                <?php echo !empty($aposta['revendedor_whatsapp']) ? htmlspecialchars($aposta['revendedor_whatsapp']) : htmlspecialchars($aposta['revendedor_telefone']); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="action-buttons">
        <a href="comprovante.php?usuario_id=<?php echo $usuario_id; ?>&jogo=<?php echo urlencode($jogo_nome); ?>&aposta_id=<?php echo $aposta_id; ?>&formato=pdf&token=<?php echo $token_esperado; ?>" class="btn">Download PDF</a>
        <a href="#" onclick="window.print(); return false;" class="btn btn-outline">Imprimir</a>
    </div>

    <div class="footer">
        <p>Este é um comprovante digital de sua aposta. Em caso de dúvidas, entre em contato com seu revendedor.</p>
        <p>© <?php echo date('Y'); ?> LotoMinas - Todos os direitos reservados</p>
    </div>
</body>
</html>
<?php
    }
} catch (PDOException $e) {
    // Log do erro
    error_log("Erro ao gerar comprovante: " . $e->getMessage());
    
    // Exibir mensagem de erro amigável
    header('Content-Type: text/html; charset=utf-8');
    echo '<div style="text-align: center; margin-top: 50px; font-family: Arial, sans-serif;">';
    echo '<h1>Erro ao gerar comprovante</h1>';
    echo '<p>Ocorreu um erro ao tentar gerar o comprovante. Por favor, tente novamente mais tarde.</p>';
    echo '<p>Se o problema persistir, entre em contato com seu revendedor.</p>';
    echo '</div>';
    exit;
} 