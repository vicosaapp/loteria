<?php
/**
 * Processador da fila de envio de comprovantes via WhatsApp
 * 
 * Este script deve ser executado periodicamente via cron job para processar
 * a fila de comprovantes pendentes de envio.
 * 
 * Exemplo de configuração do cron job:
 * # Executar a cada 5 minutos:
 * # */5 * * * * php /caminho/para/processar_fila_comprovantes.php > /dev/null 2>&1
 */

// Configurações
$max_tentativas = 3; // Número máximo de tentativas de envio
$intervalo_minutos = 5; // Intervalo mínimo entre tentativas em minutos
$limite_processamento = 10; // Número máximo de comprovantes a processar por execução

// Iniciar script
echo "Iniciando processamento da fila de comprovantes...\n";
$inicio = microtime(true);

// Carregar configurações do banco de dados
require_once __DIR__ . '/../config/database.php';

// Função para enviar mensagem via WhatsApp usando uma API fictícia
// (esta função deve ser adaptada para a API real que você utiliza)
function enviarComprovanteWhatsApp($telefone, $mensagem, $comprovante_url = null) {
    // Substituir esta implementação pela sua API de WhatsApp
    // Por exemplo, API do WhatsApp Business, Twilio, ChatAPI, etc.
    
    // Simulação para testes
    $success = true;
    $response = "Mensagem enviada com sucesso para $telefone";
    
    // Exemplo de como seria usando cURL para uma API externa
    /*
    $api_url = "https://sua-api-whatsapp.com/send";
    $api_token = "seu-token-de-api";
    
    $data = [
        'phone' => $telefone,
        'message' => $mensagem,
        'media_url' => $comprovante_url
    ];
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_token
    ]);
    
    $response = curl_exec($ch);
    $success = $response !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200;
    
    if (!$success) {
        $response = curl_error($ch);
    }
    
    curl_close($ch);
    */
    
    return [
        'success' => $success,
        'message' => $response
    ];
}

// Buscar apostas na fila para processamento
$query = "
    SELECT 
        f.id AS fila_id, 
        f.aposta_id,
        f.tentativas,
        a.numeros,
        a.valor_aposta,
        a.valor_premio,
        u.nome AS apostador_nome,
        u.whatsapp AS apostador_whatsapp,
        j.nome AS jogo_nome
    FROM 
        fila_envio_comprovantes f
    JOIN 
        apostas a ON f.aposta_id = a.id
    JOIN 
        usuarios u ON a.usuario_id = u.id
    JOIN 
        jogos j ON a.tipo_jogo_id = j.id
    WHERE 
        f.status = 'pendente' 
        AND (f.ultima_tentativa IS NULL OR f.ultima_tentativa < DATE_SUB(NOW(), INTERVAL ? MINUTE))
        AND f.tentativas < ?
    ORDER BY 
        f.data_enfileiramento ASC
    LIMIT ?
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$intervalo_minutos, $max_tentativas, $limite_processamento]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Encontrados " . count($itens) . " item(s) para processamento.\n";
    
    foreach ($itens as $item) {
        echo "Processando comprovante para aposta #{$item['aposta_id']}...\n";
        
        // Verificar se o apostador tem WhatsApp
        if (empty($item['apostador_whatsapp'])) {
            echo "Apostador sem WhatsApp cadastrado. Marcando como falha.\n";
            
            $updateStmt = $pdo->prepare("
                UPDATE fila_envio_comprovantes 
                SET status = 'falha', 
                    data_processamento = NOW(), 
                    ultima_tentativa = NOW(), 
                    tentativas = tentativas + 1,
                    resultado = 'Apostador sem WhatsApp cadastrado'
                WHERE id = ?
            ");
            $updateStmt->execute([$item['fila_id']]);
            continue;
        }
        
        // Preparar mensagem para enviar
        $numeros = explode(',', $item['numeros']);
        $numeros_formatados = implode(' - ', array_map(function($n) { 
            return str_pad($n, 2, '0', STR_PAD_LEFT); 
        }, $numeros));
        
        $mensagem = "✅ *COMPROVANTE DE APOSTA* ✅\n\n";
        $mensagem .= "*Jogo:* {$item['jogo_nome']}\n";
        $mensagem .= "*Números:* $numeros_formatados\n";
        $mensagem .= "*Valor:* R$ " . number_format($item['valor_aposta'], 2, ',', '.') . "\n";
        $mensagem .= "*Prêmio:* R$ " . number_format($item['valor_premio'], 2, ',', '.') . "\n";
        $mensagem .= "*Apostador:* {$item['apostador_nome']}\n";
        $mensagem .= "*Data:* " . date('d/m/Y H:i') . "\n\n";
        $mensagem .= "Boa sorte! 🍀";
        
        // Gerar URL do comprovante
        $comprovante_url = "https://" . $_SERVER['HTTP_HOST'] . "/admin/gerar_comprovante.php?aposta_id=" . $item['aposta_id'];
        
        // Tentar enviar a mensagem
        $telefone = preg_replace('/\D/', '', $item['apostador_whatsapp']);
        $resultado = enviarComprovanteWhatsApp($telefone, $mensagem, $comprovante_url);
        
        // Atualizar o registro da fila
        if ($resultado['success']) {
            echo "Comprovante enviado com sucesso para {$telefone}.\n";
            
            $updateStmt = $pdo->prepare("
                UPDATE fila_envio_comprovantes 
                SET status = 'enviado', 
                    data_processamento = NOW(), 
                    ultima_tentativa = NOW(), 
                    tentativas = tentativas + 1,
                    resultado = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$resultado['message'], $item['fila_id']]);
        } else {
            echo "Falha ao enviar comprovante: {$resultado['message']}\n";
            
            $status = ($item['tentativas'] + 1 >= $max_tentativas) ? 'falha' : 'pendente';
            
            $updateStmt = $pdo->prepare("
                UPDATE fila_envio_comprovantes 
                SET status = ?, 
                    ultima_tentativa = NOW(), 
                    tentativas = tentativas + 1,
                    resultado = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$status, $resultado['message'], $item['fila_id']]);
        }
    }
    
    // Calcular tempo de execução
    $tempo = microtime(true) - $inicio;
    echo "Processamento concluído em " . number_format($tempo, 2) . " segundos.\n";
    
} catch (Exception $e) {
    echo "Erro durante o processamento: " . $e->getMessage() . "\n";
} 