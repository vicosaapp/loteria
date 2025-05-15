<?php
/**
 * Script para fechar o modal e redirecionar para a lista de comprovantes
 * Este arquivo é usado como solução alternativa quando o botão "Voltar para a lista" não funciona
 */

session_start();

// Registrar a informação em log para depuração
error_log("fechar_modal.php acessado. Limpando variáveis de sessão do WhatsApp.");

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

// Limpar também o localStorage via JavaScript
echo '<script>
    // Limpar variáveis de localStorage relacionadas ao WhatsApp
    localStorage.removeItem("processando_whatsapp");
    localStorage.removeItem("last_whatsapp_timestamp");
    localStorage.removeItem("last_post_action");
    console.log("Variáveis de localStorage limpas");
</script>';

// Definir mensagem de confirmação
$_SESSION['mensagem_sucesso'] = "Operação concluída com sucesso!";

// Redirecionar para a página principal com um parâmetro para forçar recarga
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
                <h4><i class="fas fa-check-circle"></i> Fechando janela</h4>
            </div>
            <div class="card-body">
                <div class="loader"></div>
                <h5 class="mt-3">Redirecionando para a lista de comprovantes</h5>
                <p>Aguarde um momento, você será redirecionado automaticamente.</p>
                
                <div class="mt-4">
                    <a href="enviar_comprovantes_whatsapp.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Ir para página principal agora
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Redirecionar para a página principal após 1 segundo
        setTimeout(function() {
            window.location.href = "enviar_comprovantes_whatsapp.php";
        }, 1000);
        
        // Tentar fechar qualquer modal usando JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Removendo elementos modais...");
            
            try {
                // Remover qualquer backdrop de modal
                var backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 0) {
                    backdrops.forEach(function(backdrop) {
                        backdrop.remove();
                        console.log("Modal backdrop removido");
                    });
                }
                
                // Forçar remoção da classe modal-open do body
                document.body.classList.remove('modal-open');
                document.body.style.overflow = 'auto';
                document.body.style.paddingRight = '0';
                console.log("Classes de modal removidas do body");
                
                // Tentar fechar qualquer modal do Bootstrap
                if (typeof bootstrap !== 'undefined') {
                    var modals = document.querySelectorAll('.modal');
                    if (modals.length > 0) {
                        modals.forEach(function(modal) {
                            var modalInstance = bootstrap.Modal.getInstance(modal);
                            if (modalInstance) {
                                modalInstance.hide();
                                console.log("Modal Bootstrap fechado");
                            }
                        });
                    }
                }
                
                // Remover diretamente os elementos modal
                document.querySelectorAll('.modal').forEach(function(modal) {
                    modal.style.display = 'none';
                    modal.classList.remove('show');
                    console.log("Modal oculto");
                });
            } catch (e) {
                console.error("Erro ao manipular elementos do DOM:", e);
            }
        });
    </script>
</body>
</html> 