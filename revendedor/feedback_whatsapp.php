<?php
/**
 * Página de feedback para envio de comprovantes por WhatsApp
 * Esta página exibe informações sobre o status do envio e soluções para problemas comuns
 */

session_start();
require_once '../config/database.php';

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header("Location: ../login.php");
    exit();
}

// Verificar se a visita à página resulta de uma ação válida
$is_valid_feedback_visit = false;

// Verificar se temos uma sessão de WhatsApp válida ou uma ação de POST recente
if (isset($_SESSION['whatsapp_urls']) && !empty($_SESSION['whatsapp_urls'])) {
    $is_valid_feedback_visit = true;
} elseif (isset($_SESSION['last_post_action'])) {
    $time_elapsed = time() - $_SESSION['last_post_action'];
    // Permitir acesso se a ação foi nos últimos 5 minutos
    if ($time_elapsed < 300) {
        $is_valid_feedback_visit = true;
    }
} elseif (isset($_GET['proximo']) && $_GET['proximo'] === '1') {
    // Permitir acesso para navegação entre apostadores
    $is_valid_feedback_visit = true;
}

// Se não for uma visita válida, redirecionar para a página principal
if (!$is_valid_feedback_visit) {
    $_SESSION['erro'] = "Você acessou a página de feedback sem ter enviado comprovantes previamente.";
    header("Location: enviar_comprovantes_whatsapp.php?erro=acesso_invalido");
    exit();
}

// Definir variáveis para o layout
$pageTitle = 'Status do Envio';
$currentPage = 'enviar_comprovantes';

// Processar a fila de apostadores
if (isset($_GET['proximo']) && $_GET['proximo'] === '1') {
    // Se marcamos o item atual como processado e precisamos continuar para o próximo
    if (isset($_SESSION['whatsapp_urls']) && count($_SESSION['whatsapp_urls']) > 0) {
        // Remover o primeiro item da fila (já processado)
        array_shift($_SESSION['whatsapp_urls']);
        
        if (count($_SESSION['whatsapp_urls']) > 0) {
            // Ainda temos mais apostadores - atualizar a URL de redirecionamento
            $_SESSION['redirect_whatsapp'] = $_SESSION['whatsapp_urls'][0]['url'];
            
            // Redirecionar para a página de abertura do WhatsApp para o próximo apostador
            header("Location: abrir_whatsapp.php");
            exit;
        }
    }
    
    // Se chegamos aqui, não há mais apostadores na fila
    unset($_SESSION['whatsapp_urls']);
    unset($_SESSION['redirect_whatsapp']);
    $_SESSION['mensagem_sucesso'] = "Todos os comprovantes foram processados com sucesso!";
    header("Location: enviar_comprovantes_whatsapp.php");
    exit;
}

// Verificar o status do envio
$status = isset($_GET['status']) ? $_GET['status'] : 'sucesso';
$telefone = isset($_GET['telefone']) ? $_GET['telefone'] : '';
$whatsapp_url = isset($_SESSION['redirect_whatsapp']) ? $_SESSION['redirect_whatsapp'] : '';
$whatsapp_url_alt = isset($_SESSION['whatsapp_urls'][0]['url_alt']) ? $_SESSION['whatsapp_urls'][0]['url_alt'] : '';

// Se temos as URLs salvas, extrair o telefone para exibição
if (empty($telefone) && isset($_SESSION['whatsapp_urls'][0]['telefone'])) {
    $telefone = $_SESSION['whatsapp_urls'][0]['telefone'];
    
    // Formatar o telefone para exibição
    if (substr($telefone, 0, 2) == '55') {
        $telefone = substr($telefone, 2);
    }
    
    // Formatar como (XX) XXXXX-XXXX
    if (strlen($telefone) == 11) { // Celular com 9 dígitos
        $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    } elseif (strlen($telefone) == 10) { // Telefone fixo
        $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
    }
}

// Iniciar o buffer de saída para o conteúdo da página
ob_start();
?>

<div class="container-fluid mt-4">
    <?php if ($status === 'sucesso'): ?>
    <div class="card border-success mb-4">
        <div class="card-header bg-success text-white">
            <i class="fas fa-check-circle"></i> Comprovantes Processados com Sucesso
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-7">
                    <h4 class="mb-3">O WhatsApp foi aberto para envio?</h4>
                    
                    <div class="d-flex mb-4">
                        <button class="btn btn-success me-2" onclick="respostaSim()">
                            <i class="fas fa-thumbs-up"></i> Sim, os comprovantes foram enviados
                        </button>
                        <button class="btn btn-danger" onclick="respostaNao()">
                            <i class="fas fa-thumbs-down"></i> Não, tive problemas
                        </button>
                    </div>
                    
                    <div id="opcoes-problema" style="display: none;">
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Selecione o problema encontrado:</h5>
                            <div class="list-group mt-2">
                                <button class="list-group-item list-group-item-action" onclick="mostrarSolucao('popup')">
                                    <i class="fas fa-ban"></i> O navegador bloqueou o pop-up
                                </button>
                                <button class="list-group-item list-group-item-action" onclick="mostrarSolucao('whatsapp')">
                                    <i class="fab fa-whatsapp"></i> O WhatsApp abriu, mas não carregou a mensagem
                                </button>
                                <button class="list-group-item list-group-item-action" onclick="mostrarSolucao('telefone')">
                                    <i class="fas fa-phone-alt"></i> Número de telefone incorreto
                                </button>
                                <button class="list-group-item list-group-item-action" onclick="mostrarSolucao('outro')">
                                    <i class="fas fa-question-circle"></i> Outro problema
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="solucao-popup" class="solucao-problema" style="display: none;">
                        <div class="alert alert-info">
                            <h5>Solução para pop-ups bloqueados:</h5>
                            <ol>
                                <li>Procure por um ícone de bloqueio <i class="fas fa-ban"></i> na barra de endereço do seu navegador</li>
                                <li>Clique nele e selecione "Permitir pop-ups para este site"</li>
                                <li>Tente novamente usando o botão abaixo</li>
                            </ol>
                            <div class="mt-3">
                                <a href="<?php echo htmlspecialchars($whatsapp_url); ?>" target="_blank" class="btn btn-success">
                                    <i class="fab fa-whatsapp"></i> Tentar Novamente pelo WhatsApp App
                                </a>
                                <a href="<?php echo htmlspecialchars($whatsapp_url_alt); ?>" target="_blank" class="btn btn-outline-success ms-2">
                                    <i class="fab fa-whatsapp"></i> Tentar pelo WhatsApp Web
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div id="solucao-whatsapp" class="solucao-problema" style="display: none;">
                        <div class="alert alert-info">
                            <h5>Solução para problemas de carregamento:</h5>
                            <ol>
                                <li>Verifique se você está logado no WhatsApp Web</li>
                                <li>Tente a opção alternativa (WhatsApp Web)</li>
                                <li>Ou abra o WhatsApp no seu celular e envie a mensagem manualmente</li>
                            </ol>
                            <div class="mt-3">
                                <a href="<?php echo htmlspecialchars($whatsapp_url_alt); ?>" target="_blank" class="btn btn-success">
                                    <i class="fab fa-whatsapp"></i> Tentar pelo WhatsApp Web
                                </a>
                                <button class="btn btn-outline-success ms-2" onclick="copiarMensagem()">
                                    <i class="far fa-copy"></i> Copiar mensagem para envio manual
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="solucao-telefone" class="solucao-problema" style="display: none;">
                        <div class="alert alert-info">
                            <h5>Solução para números incorretos:</h5>
                            <p>O número <?php echo htmlspecialchars($telefone); ?> parece estar incorreto ou mal formatado.</p>
                            <ol>
                                <li>Vá para "Meus Clientes" e atualize o número de WhatsApp</li>
                                <li>Certifique-se de incluir o código do país (55) e DDD</li>
                                <li>Depois, tente enviar novamente</li>
                            </ol>
                            <div class="mt-3">
                                <a href="clientes.php" class="btn btn-primary">
                                    <i class="fas fa-users"></i> Ir para Meus Clientes
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div id="solucao-outro" class="solucao-problema" style="display: none;">
                        <div class="alert alert-info">
                            <h5>Outras alternativas:</h5>
                            <ol>
                                <li>Tente usar o WhatsApp Web diretamente</li>
                                <li>Você pode copiar a mensagem e enviar manualmente</li>
                                <li>Ou gere o PDF e envie por outro método</li>
                            </ol>
                            <div class="mt-3 text-center">
                                <a href="<?php echo htmlspecialchars($whatsapp_url_alt); ?>" target="_blank" class="btn btn-success">
                                    <i class="fab fa-whatsapp"></i> Tentar pelo WhatsApp Web
                                </a>
                                <button class="btn btn-outline-success ms-2" onclick="copiarMensagem()">
                                    <i class="far fa-copy"></i> Copiar mensagem
                                </button>
                                <a href="enviar_comprovantes_whatsapp.php" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-file-pdf"></i> Gerar PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <i class="fas fa-info-circle"></i> Informações do Envio
                        </div>
                        <div class="card-body">
                            <?php if (isset($_SESSION['whatsapp_urls']) && !empty($_SESSION['whatsapp_urls'])): ?>
                                <p><strong>Destinatário:</strong> <?php echo htmlspecialchars($_SESSION['whatsapp_urls'][0]['nome'] ?? 'Apostador'); ?></p>
                                <p><strong>Telefone:</strong> <?php echo htmlspecialchars($telefone); ?></p>
                                <p><strong>Quantidade de apostas:</strong> <?php echo $_SESSION['whatsapp_urls'][0]['apostas'] ?? '1'; ?></p>
                                <hr>
                                <div class="mb-3">
                                    <small class="text-muted">Preview da mensagem:</small>
                                    <div class="p-2 mt-1 bg-light border rounded">
                                        <pre class="mb-0" style="white-space: pre-wrap; font-size: 12px;"><?php echo htmlspecialchars($_SESSION['whatsapp_urls'][0]['texto'] ?? ''); ?></pre>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Informações de envio não disponíveis ou já processadas.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between">
                        <a href="enviar_comprovantes_whatsapp.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar para Lista de Apostas
                        </a>
                        
                        <?php if (isset($_SESSION['whatsapp_urls']) && count($_SESSION['whatsapp_urls']) > 1): ?>
                        <div>
                            <span class="badge bg-info">
                                <i class="fas fa-users"></i> <?php echo count($_SESSION['whatsapp_urls'])-1; ?> apostadores restantes
                            </span>
                            <a href="feedback_whatsapp.php?proximo=1" class="btn btn-success ms-2">
                                <i class="fas fa-chevron-right"></i> Continuar para Próximo Apostador
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card border-danger mb-4">
        <div class="card-header bg-danger text-white">
            <i class="fas fa-exclamation-triangle"></i> Ocorreu um Problema
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <h4 class="alert-heading">Erro ao processar comprovantes</h4>
                <p>Infelizmente ocorreu um erro durante o processamento dos comprovantes.</p>
                <hr>
                <p class="mb-0">Por favor, tente novamente ou entre em contato com o suporte técnico.</p>
            </div>
            
            <div class="mt-4">
                <a href="enviar_comprovantes_whatsapp.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar para Lista de Apostas
                </a>
                <button class="btn btn-primary ms-2" onclick="window.location.reload()">
                    <i class="fas fa-sync"></i> Tentar Novamente
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    // Variável para armazenar mensagem para copiar
    const mensagemWhatsApp = `<?php echo isset($_SESSION['whatsapp_urls'][0]['texto']) ? 
                                  str_replace(['`', "'", '"'], ['\\`', "\\'", '\\"'], 
                                  $_SESSION['whatsapp_urls'][0]['texto']) : 
                                  "✅ COMPROVANTES DE APOSTAS ✅\n\nSegue comprovante de apostas."; ?>`;
    
    // Verificar se o dispositivo é móvel
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    console.log("Dispositivo móvel detectado:", isMobile);
    
    // Funções de feedback
    function respostaSim() {
        // Mostrar mensagem de sucesso
        Swal.fire({
            title: 'Ótimo!',
            text: 'Agradecemos seu feedback. Os comprovantes foram enviados com sucesso.',
            icon: 'success',
            confirmButtonText: 'Continuar',
            confirmButtonColor: '#28a745'
        }).then((result) => {
            // Redirecionar para o próximo apostador ou limpar a lista
            <?php if (isset($_SESSION['whatsapp_urls']) && count($_SESSION['whatsapp_urls']) > 1): ?>
            window.location.href = "feedback_whatsapp.php?proximo=1";
            <?php else: ?>
            window.location.href = "enviar_comprovantes_whatsapp.php?limpar_lista=1";
            <?php endif; ?>
        });
    }
    
    function respostaNao() {
        // Mostrar opções de problemas
        document.getElementById('opcoes-problema').style.display = 'block';
        
        // Esconder todas as soluções
        document.querySelectorAll('.solucao-problema').forEach(el => {
            el.style.display = 'none';
        });
        
        // Rolar até as opções
        document.getElementById('opcoes-problema').scrollIntoView({ behavior: 'smooth' });
    }
    
    function mostrarSolucao(tipo) {
        // Esconder todas as soluções
        document.querySelectorAll('.solucao-problema').forEach(el => {
            el.style.display = 'none';
        });
        
        // Mostrar a solução específica
        document.getElementById('solucao-' + tipo).style.display = 'block';
        
        // Rolar até a solução
        document.getElementById('solucao-' + tipo).scrollIntoView({ behavior: 'smooth' });
    }
    
    function copiarMensagem() {
        // Criar elemento temporário
        const el = document.createElement('textarea');
        el.value = mensagemWhatsApp;
        el.setAttribute('readonly', '');
        el.style.position = 'absolute';
        el.style.left = '-9999px';
        document.body.appendChild(el);
        
        // Selecionar e copiar
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        
        // Mostrar confirmação
        Swal.fire({
            title: 'Mensagem Copiada!',
            text: 'A mensagem foi copiada para a área de transferência.',
            icon: 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    }
    
    // Inicializar tooltips e popovers
    document.addEventListener('DOMContentLoaded', function() {
        // Aplicar otimizações para dispositivos móveis
        if (isMobile) {
            console.log("Aplicando otimizações para dispositivos móveis");
            
            // Ajustar botões para melhor usabilidade em telas touch
            document.querySelectorAll('.btn:not(.btn-sm)').forEach(btn => {
                btn.classList.add('btn-lg');
                btn.style.marginBottom = '10px';
            });
            
            // Priorizar o app do WhatsApp em dispositivos móveis
            document.querySelectorAll('a[href*="wa.me"]').forEach(link => {
                link.classList.add('btn-success');
                link.classList.remove('btn-outline-success');
                link.innerHTML = '<i class="fab fa-whatsapp fa-lg"></i> Abrir no WhatsApp';
            });
            
            // Reduzir a ênfase nos botões de WhatsApp Web em dispositivos móveis
            document.querySelectorAll('a[href*="web.whatsapp.com"]').forEach(link => {
                link.classList.remove('btn-success');
                link.classList.add('btn-outline-secondary');
                link.style.fontSize = '0.9em';
            });
            
            // Adaptar layout para telas menores
            document.querySelectorAll('.col-md-5, .col-md-7').forEach(col => {
                col.classList.add('mb-4');
            });
        }
        
        var tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(function(tooltip) {
            new bootstrap.Tooltip(tooltip);
        });
    });
</script>

<?php
$content = ob_get_clean();

// Incluir SweetAlert2
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

// Incluir o layout com o conteúdo
include 'includes/layout.php';

// Remover sessão após exibição da página
if ($status === 'sucesso') {
    // Manter a sessão para permitir acesso aos próximos apostadores
} else {
    // Limpar a sessão em caso de erro
    unset($_SESSION['whatsapp_urls']);
    unset($_SESSION['redirect_whatsapp']);
}
?> 