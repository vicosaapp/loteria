<?php
/**
 * Script de redirecionamento direto para o WhatsApp
 * Este arquivo tem um único propósito: abrir o WhatsApp mediante clique do usuário
 * 
 * IMPORTANTE: Este arquivo NUNCA deve redirecionar automaticamente o usuário.
 * Sempre deve exigir um clique intencional em um botão para abrir o WhatsApp.
 * Isso resolve o problema do popup que "pisca" e desaparece rapidamente.
 * 
 * Versão simplicada e robusta, sem tentativas automáticas ou contagens regressivas
 */

session_start();

// Verificar se há uma ação explícita do usuário para evitar aberturas acidentais
$can_proceed = false;

// Verificar se há uma URL de WhatsApp válida na sessão
if (isset($_SESSION['redirect_whatsapp']) && !empty($_SESSION['redirect_whatsapp'])) {
    // Se estamos vindo de uma ação explícita pós seleção de apostas, permitir o redirecionamento
    if (isset($_POST['action']) && $_POST['action'] === 'enviar_comprovantes') {
        $can_proceed = true;
        // Registrar que temos uma ação POST válida
        $_SESSION['last_post_action'] = time();
    } elseif (isset($_SESSION['last_post_action'])) {
        $time_elapsed = time() - $_SESSION['last_post_action'];
        
        // Permitir o redirecionamento até 5 minutos após a ação original
        if ($time_elapsed < 300) {
            $can_proceed = true;
        } else {
            // Ação muito antiga, registrar e limpar
            error_log("Tentativa de acesso a abrir_whatsapp.php com ação expirada. Tempo decorrido: {$time_elapsed}s");
            unset($_SESSION['last_post_action']);
        }
    }
}

// Se não puder prosseguir, redirecionar para a página principal
if (!$can_proceed) {
    // Adicionar um log para depuração
    error_log("Acesso a abrir_whatsapp.php rejeitado. POST: " . json_encode($_POST) . ", SESSION: " . json_encode(array_keys($_SESSION)));
    
    // Redirecionar de volta para a página principal
    header("Location: enviar_comprovantes_whatsapp.php?erro=acesso_invalido");
    exit;
}

// Continuar com o redirecionamento para WhatsApp (ação legítima)
$whatsapp_url = $_SESSION['redirect_whatsapp'];

// URL alternativa (wa.me vs web.whatsapp.com)
$whatsapp_url_alt = isset($_SESSION['whatsapp_urls'][0]['url_alt']) ? 
                    $_SESSION['whatsapp_urls'][0]['url_alt'] : 
                    str_replace('wa.me', 'web.whatsapp.com/send', $whatsapp_url);

// Nome do apostador para personalização
$apostador_nome = isset($_SESSION['whatsapp_urls'][0]['nome']) ? 
                 $_SESSION['whatsapp_urls'][0]['nome'] : 
                 "apostador";

// Armazenar o primeiro item processado para movê-lo após o processamento
if (isset($_SESSION['whatsapp_urls']) && !empty($_SESSION['whatsapp_urls'])) {
    $_SESSION['ultimo_processado'] = $_SESSION['whatsapp_urls'][0];
}

// Construir a página de redirecionamento sem elementos auto-executáveis
// SEM contagem regressiva, SEM redirecionamento automático
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enviar Comprovante via WhatsApp</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f5f5f5;
        }
        .card {
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .success-icon {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 15px;
        }
        .btn-whatsapp {
            background-color: #25D366;
            border-color: #25D366;
            font-size: 1.2rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .btn-whatsapp:hover {
            background-color: #128C7E;
            border-color: #128C7E;
        }
        .animate-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7);
            }
            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(37, 211, 102, 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(37, 211, 102, 0);
            }
        }
        .debug {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 20px;
            text-align: left;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h3><i class="fab fa-whatsapp"></i> Enviar Comprovante via WhatsApp</h3>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <i class="fas fa-check-circle success-icon"></i>
                <h4>Comprovante pronto para envio</h4>
                <p>Clique no botão abaixo para enviar comprovante para ' . htmlspecialchars($apostador_nome) . '</p>
            </div>
            
            <div class="d-grid gap-2">
                <a href="' . htmlspecialchars($whatsapp_url) . '" class="btn btn-whatsapp animate-pulse" target="_blank" id="whatsapp-app-link">
                    <i class="fab fa-whatsapp me-2"></i> Abrir WhatsApp App
                </a>
                
                <a href="' . htmlspecialchars($whatsapp_url_alt) . '" class="btn btn-outline-success" target="_blank" id="whatsapp-web-link">
                    <i class="fab fa-whatsapp"></i> Alternativa: WhatsApp Web
                </a>
                
                <a href="fechar_modal.php" class="btn btn-outline-secondary mt-3" id="fechar-modal-botao">
                    <i class="fas fa-arrow-right"></i> Continuar para Feedback
                </a>
            </div>
            
            <div class="alert alert-warning mt-4">
                <p><i class="fas fa-exclamation-triangle"></i> <strong>Se o WhatsApp não abrir:</strong></p>
                <ul class="text-start mb-0">
                    <li>Verifique se seu navegador está permitindo pop-ups</li>
                    <li>Tente usar o botão "WhatsApp Web" como alternativa</li>
                    <li>Copie o link abaixo e cole em uma nova aba</li>
                </ul>
            </div>
            
            <div class="input-group mt-3">
                <input type="text" class="form-control" value="' . htmlspecialchars($whatsapp_url) . '" id="whatsapp-url" readonly>
                <button class="btn btn-outline-secondary" type="button" onclick="copiarURL()">
                    <i class="fas fa-copy"></i> Copiar
                </button>
            </div>
            
            <div class="debug" id="debug-info">
                <p><strong>Informações de depuração:</strong></p>
                <p>URL WhatsApp App: <code>' . htmlspecialchars($whatsapp_url) . '</code></p>
                <p>URL WhatsApp Web: <code>' . htmlspecialchars($whatsapp_url_alt) . '</code></p>
                <p>Apostador: <code>' . htmlspecialchars($apostador_nome) . '</code></p>
            </div>
        </div>
    </div>

    <script>
        // Função para copiar URL para área de transferência
        function copiarURL() {
            const urlField = document.getElementById("whatsapp-url");
            urlField.select();
            document.execCommand("copy");
            alert("Link copiado para a área de transferência!");
        }
        
        // Registrar cliques nos links para melhor rastreamento
        document.addEventListener("DOMContentLoaded", function() {
            // Link para WhatsApp App
            document.getElementById("whatsapp-app-link").addEventListener("click", function(e) {
                console.log("Botão WhatsApp App clicado");
                // Permitir que o link abra normalmente em nova aba
            });
            
            // Link para WhatsApp Web
            document.getElementById("whatsapp-web-link").addEventListener("click", function(e) {
                console.log("Botão WhatsApp Web clicado");
                // Permitir que o link abra normalmente em nova aba
            });
            
            // Botão para fechar modal
            document.getElementById("fechar-modal-botao").addEventListener("click", function(e) {
                console.log("Botão Fechar Modal clicado");
                // Adicionando redirecionamento de backup após um pequeno atraso
                setTimeout(function() {
                    window.location.href = "fechar_modal.php";
                }, 300);
            });
        });
    </script>
</body>
</html>';
exit;

// Esse código NUNCA será executado se o usuário tiver uma URL de WhatsApp válida na sessão
// Serve apenas como fallback de segurança caso alguém tente acessar o script diretamente
// sem ter configurado antes as variáveis de sessão necessárias
header("Location: enviar_comprovantes_whatsapp.php?erro=sem_url_whatsapp");
exit;
?> 