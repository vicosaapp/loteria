<?php
/**
 * Script para fechar o modal através de um botão clicável
 * Versão simplificada do fechar_modal.php para ser usado especificamente por botões
 */

session_start();

// Registrar a informação em log para depuração
error_log("fechar_modal_botao.php acessado através de clique do usuário. Limpando variáveis de sessão.");

// Limpar variáveis de sessão relacionadas ao WhatsApp para garantir que nenhum modal seja exibido novamente
$whatsapp_vars = [
    'whatsapp_urls', 
    'redirect_whatsapp', 
    'comprovantes_processados', 
    'ultimo_processado',
    'apostas_pendentes',
    'lote_atual',
    'total_lotes',
    'apostas_processadas',
    'apostadores_processados',
    'mensagem_progresso',
    'last_post_action'
];

foreach ($whatsapp_vars as $var) {
    if (isset($_SESSION[$var])) {
        unset($_SESSION[$var]);
    }
}

// Definir mensagem de confirmação
$_SESSION['mensagem_sucesso'] = "Voltando para a lista de comprovantes!";

// Redirecionar para a página principal
header("Location: enviar_comprovantes_whatsapp.php?reload=" . time());
exit;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecionando...</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 100px;
        }
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #28a745;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow mx-auto" style="max-width: 500px;">
            <div class="card-header bg-success text-white">
                <h4><i class="fas fa-check-circle"></i> Redirecionando</h4>
            </div>
            <div class="card-body">
                <div class="loader"></div>
                <h5 class="mt-3">Voltando para a lista de comprovantes</h5>
                <p>Aguarde um momento, você será redirecionado automaticamente.</p>
                
                <div class="mt-4">
                    <a href="enviar_comprovantes_whatsapp.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Ir para página principal agora
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 