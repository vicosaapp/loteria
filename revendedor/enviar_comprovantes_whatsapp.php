<?php
/**
 * Enviar Comprovantes por WhatsApp - Revendedor
 * 
 * Este script permite aos revendedores enviarem comprovantes de apostas por WhatsApp para os apostadores.
 * Se um apostador tiver várias apostas, todos os comprovantes serão enviados de uma vez.
 */

session_start();
require_once '../config/database.php';

// Verificar a conexão com o banco de dados e as tabelas necessárias
try {
    // Verificar conexão com o banco
    $pdo->query("SELECT 1");
    error_log("Conexão com o banco de dados OK");
    
    // Verificar se as tabelas necessárias existem
    $tabelas_necessarias = ['apostas', 'usuarios', 'jogos'];
    $tabelas_faltando = [];
    
    foreach ($tabelas_necessarias as $tabela) {
        if ($pdo->query("SHOW TABLES LIKE '{$tabela}'")->rowCount() == 0) {
            $tabelas_faltando[] = $tabela;
            error_log("ERRO: Tabela '{$tabela}' não encontrada no banco de dados");
        }
    }
    
    if (!empty($tabelas_faltando)) {
        error_log("ALERTA: As seguintes tabelas estão faltando: " . implode(", ", $tabelas_faltando));
    } else {
        error_log("Todas as tabelas necessárias estão presentes");
        
        // Verificar se há apostas na tabela
        $total_apostas = $pdo->query("SELECT COUNT(*) FROM apostas")->fetchColumn();
        error_log("Total de apostas no sistema: {$total_apostas}");
        
        // Verificar usuários
        $total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        error_log("Total de usuários no sistema: {$total_usuarios}");
        
        // Verificar jogos
        $total_jogos = $pdo->query("SELECT COUNT(*) FROM jogos")->fetchColumn();
        error_log("Total de jogos no sistema: {$total_jogos}");
    }
} catch (PDOException $e) {
    error_log("ERRO CRÍTICO com o banco de dados: " . $e->getMessage());
}

// CORREÇÃO: Limpeza forçada de estados para evitar aberturas automáticas do modal
// Verificar se é um acesso direto à página (sem POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Verificar se existe o parâmetro de reset (limpeza forçada)
    if (isset($_GET['reset']) && $_GET['reset'] == '1') {
        // Limpar TODAS as variáveis de sessão relacionadas ao WhatsApp
        $session_vars = array_keys($_SESSION);
        
        foreach ($session_vars as $var) {
            if (strpos($var, 'whatsapp') !== false || 
                strpos($var, 'comprovante') !== false || 
                strpos($var, 'aposta') !== false || 
                strpos($var, 'post_action') !== false) {
                unset($_SESSION[$var]);
            }
        }
        
        // Limpar também as variáveis específicas
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
        
        // Limpar o localStorage via JavaScript
        echo '<script>
            localStorage.removeItem("processando_whatsapp");
            localStorage.removeItem("last_whatsapp_timestamp");
            localStorage.removeItem("last_post_action");
        </script>';
        
        // Registrar que uma limpeza completa foi realizada
        error_log("Limpeza completa de variáveis de sessão realizada via parâmetro reset=1");
        
        // Redirecionar para a página limpa
        header("Location: enviar_comprovantes_whatsapp.php");
        exit;
    }
    
    // Limpar variáveis padrão em acesso normal
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
    
    // Limpar parâmetros de URL que podem causar redirecionamentos indesejados
    if (isset($_GET['action']) || isset($_GET['processar_lote'])) {
        // Registrar tentativa suspeita
        error_log("Possível acesso direto a parâmetros de ação: " . $_SERVER['REQUEST_URI']);
        
        // Redirecionar para a página limpa
        header("Location: enviar_comprovantes_whatsapp.php");
        exit;
    }
}

// Verifica se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header("Location: ../login.php");
    exit();
}

// Definir variáveis para o layout
$pageTitle = 'Enviar Comprovantes';
$currentPage = 'enviar_comprovantes';

// Mensagens
$mensagem = '';
$erro = '';

// Verificar se há mensagem de sucesso na sessão
if (isset($_SESSION['mensagem_sucesso'])) {
    $mensagem = $_SESSION['mensagem_sucesso'];
    unset($_SESSION['mensagem_sucesso']);
}

// Verificar se há mensagem de erro na URL
if (isset($_GET['erro'])) {
    switch ($_GET['erro']) {
        case 'acesso_invalido':
            $erro = 'Acesso inválido à página de redirecionamento.';
            break;
        case 'sem_url_whatsapp':
            $erro = 'Não foi possível obter a URL do WhatsApp.';
            break;
        default:
            $erro = 'Ocorreu um erro no processamento.';
    }
}

// Botão de emergência visível se detectarmos um problema recorrente
$mostrar_botao_emergencia = false;

// Verificar se houve tentativas repetidas de acessar a página com erros
if (isset($_SESSION['tentativas_erro']) && $_SESSION['tentativas_erro'] > 2) {
    $mostrar_botao_emergencia = true;
} elseif (isset($_GET['erro'])) {
    // Incrementar contador de tentativas com erro
    if (!isset($_SESSION['tentativas_erro'])) {
        $_SESSION['tentativas_erro'] = 1;
    } else {
        $_SESSION['tentativas_erro']++;
    }
}

// Exibir alerta de emergência se necessário
$alerta_emergencia = '';
if ($mostrar_botao_emergencia) {
    $alerta_emergencia = '
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <h5><i class="fas fa-exclamation-triangle"></i> Problemas persistentes detectados</h5>
        <p>Se você está tendo problemas recorrentes com o envio de comprovantes, tente usar o botão de emergência abaixo para limpar todas as variáveis de sessão.</p>
        <div class="mt-2">
            <a href="enviar_comprovantes_whatsapp.php?reset=1" class="btn btn-danger">
                <i class="fas fa-broom"></i> Limpar Sessão (Botão de Emergência)
            </a>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    
    // Resetar contador após exibir o alerta
    $_SESSION['tentativas_erro'] = 0;
}

// Função para gerar comprovantes em PDF
function gerarComprovantePDF($usuario_id, $jogo_nome, $aposta_id = null) {
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    
    // Gerar token de segurança
    $token = md5($usuario_id . $aposta_id . 'loteria_seguranca');
    
    // Construir a URL para o comprovante público
    $url = "{$base_url}/comprovante.php";
    $params = [
        'usuario_id' => $usuario_id,
        'jogo' => $jogo_nome,
        'formato' => 'pdf',
        'token' => $token
    ];
    
    if ($aposta_id) {
        $params['aposta_id'] = $aposta_id;
    }
    
    return $url . '?' . http_build_query($params);
}

// Processar envio de comprovantes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enviar_comprovantes') {
    try {
        // Marcar que houve uma ação de POST legítima
        $_SESSION['last_post_action'] = time();
        
        // Adicionar um script para registrar a ação no localStorage
        echo '<script>
            localStorage.setItem("last_post_action", Date.now());
        </script>';
        
        // Aumentar o limite de tempo de execução do script para este processo
        set_time_limit(120); // 2 minutos
        
        // Limitar uso de memória
        ini_set('memory_limit', '256M');
        
        // Verificar se foram selecionados apostadores/apostas
        if (isset($_POST['apostas']) && !empty($_POST['apostas'])) {
            $apostas_ids = $_POST['apostas'];
            $revendedor_id = $_SESSION['usuario_id'];
            
            // Limite de processamento para evitar travamentos
            $limite_apostas = 3; // Reduzido de 5 para 3 apostas por lote
            if (count($apostas_ids) > $limite_apostas) {
                // Se tivermos muitas apostas, armazena na sessão para processar em lotes
                $_SESSION['apostas_pendentes'] = $apostas_ids;
                $_SESSION['lote_atual'] = 0;
                $_SESSION['total_lotes'] = ceil(count($apostas_ids) / $limite_apostas);
                $_SESSION['apostas_processadas'] = 0;
                $_SESSION['apostadores_processados'] = 0;
                
                // Redirecionar para processamento em lotes
                header("Location: enviar_comprovantes_whatsapp.php?processar_lote=1");
                exit;
            }
            
            // Debug para ver quais apostas foram selecionadas
            // echo "<pre>Apostas selecionadas: " . print_r($apostas_ids, true) . "</pre>";
            
            // Obter informações das apostas selecionadas (apenas as do revendedor atual)
            $placeholders = str_repeat('?,', count($apostas_ids) - 1) . '?';
            $stmt = $pdo->prepare("
                SELECT 
                    a.id AS aposta_id, 
                    a.usuario_id, 
                    a.numeros,
                    a.valor_aposta,
                    a.valor_premio,
                    u.nome AS apostador_nome,
                    u.whatsapp AS apostador_whatsapp,
                    j.nome AS jogo_nome
                FROM 
                    apostas a
                JOIN 
                    usuarios u ON a.usuario_id = u.id
                JOIN 
                    jogos j ON a.tipo_jogo_id = j.id
                WHERE 
                    a.id IN ({$placeholders})
                    AND a.revendedor_id = ?
                ORDER BY
                    u.id, a.id
            ");
            
            // Adicionar revendedor_id como último parâmetro
            $params = $apostas_ids;
            $params[] = $revendedor_id;
            
            $stmt->execute($params);
            $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupar apostas por apostador
            $apostas_por_apostador = [];
            foreach ($apostas as $aposta) {
                if (empty($aposta['apostador_whatsapp'])) {
                    // Registrar apostadores sem WhatsApp para exibir mensagem
                    if (!isset($_SESSION['apostadores_sem_whatsapp'])) {
                        $_SESSION['apostadores_sem_whatsapp'] = [];
                    }
                    $_SESSION['apostadores_sem_whatsapp'][] = $aposta['apostador_nome'];
                    continue; // Pular apostadores sem WhatsApp
                }
                
                $usuario_id = $aposta['usuario_id'];
                if (!isset($apostas_por_apostador[$usuario_id])) {
                    $apostas_por_apostador[$usuario_id] = [
                        'nome' => $aposta['apostador_nome'],
                        'whatsapp' => $aposta['apostador_whatsapp'],
                        'apostas' => []
                    ];
                }
                
                $apostas_por_apostador[$usuario_id]['apostas'][] = $aposta;
            }
            
            // Preparar e enfileirar mensagens para cada apostador
            $total_apostadores = 0;
            $total_apostas = 0;
            
            foreach ($apostas_por_apostador as $usuario_id => $dados) {
                if (count($dados['apostas']) === 0) {
                    continue;
                }
                
                $total_apostadores++;
                $apostas_count = count($dados['apostas']);
                $total_apostas += $apostas_count;
                
                // Preparar mensagem inicial para o WhatsApp
                $mensagem_whatsapp = "✅ *COMPROVANTES DE APOSTAS* ✅\n\n";
                $mensagem_whatsapp .= "*Apostador:* {$dados['nome']}\n";
                $mensagem_whatsapp .= "*Data:* " . date('d/m/Y H:i') . "\n\n";
                
                if ($apostas_count === 1) {
                    $mensagem_whatsapp .= "Estamos enviando seu comprovante de aposta logo abaixo.\n";
                } else {
                    $mensagem_whatsapp .= "Estamos enviando seus {$apostas_count} comprovantes de apostas logo abaixo.\n";
                }
                
                $mensagem_whatsapp .= "Boa sorte! 🍀\n\n";
                
                // Incluir resumo das apostas
                $mensagem_whatsapp .= "*RESUMO DAS APOSTAS:*\n";
                
                foreach ($dados['apostas'] as $index => $aposta) {
                    $numeros = explode(',', $aposta['numeros']);
                    $numeros_formatados = implode('-', array_map(function($n) { 
                        return str_pad(trim($n), 2, '0', STR_PAD_LEFT); 
                    }, $numeros));
                    
                    $mensagem_whatsapp .= "\n*Aposta " . ($index + 1) . ":*\n";
                    $mensagem_whatsapp .= "- Jogo: {$aposta['jogo_nome']}\n";
                    $mensagem_whatsapp .= "- Números: {$numeros_formatados}\n";
                    $mensagem_whatsapp .= "- Valor: R$ " . number_format($aposta['valor_aposta'], 2, ',', '.') . "\n";
                    $mensagem_whatsapp .= "- Prêmio: R$ " . number_format($aposta['valor_premio'], 2, ',', '.') . "\n";
                }
                
                // Gerar e incluir URLs dos comprovantes na mensagem
                $mensagem_whatsapp .= "\n*LINKS DOS COMPROVANTES:*\n";
                
                foreach ($dados['apostas'] as $index => $aposta) {
                    $comprovante_url = gerarComprovantePDF($aposta['usuario_id'], $aposta['jogo_nome'], $aposta['aposta_id']);
                    $mensagem_whatsapp .= "\nComprovante " . ($index + 1) . ":\n";
                    $mensagem_whatsapp .= $comprovante_url . "\n";
                    
                    // Adicionar à fila de envio se a tabela existir
                    $table_exists = $pdo->query("SHOW TABLES LIKE 'fila_envio_comprovantes'")->rowCount() > 0;
                    
                    if ($table_exists) {
                        $stmt = $pdo->prepare("
                            INSERT INTO fila_envio_comprovantes 
                            (aposta_id, status, data_enfileiramento, tentativas) 
                            VALUES (?, 'pendente', NOW(), 0)
                        ");
                        $stmt->execute([$aposta['aposta_id']]);
                    }
                }
                
                // Preparar URL para WhatsApp
                $telefone = preg_replace('/\D/', '', $dados['whatsapp']);
                
                // Verificar se temos um número de WhatsApp válido
                if (empty($telefone)) {
                    error_log("Apostador {$dados['nome']} (ID: {$usuario_id}) não tem número de WhatsApp válido");
                    continue; // Pular este apostador
                }
                
                // Garantir que o número tenha o código do país (adicionar 55 se não começar com 55)
                if (!preg_match('/^55/', $telefone)) {
                    $telefone = '55' . $telefone;
                }
                
                // Verificar se o número parece ser um número de teste
                if (strpos($telefone, '11111') !== false) {
                    error_log("Detectado possível número de teste: {$telefone} para {$dados['nome']}. Verificar cadastro.");
                }
                
                // Verificar comprimento adequado do número no formato brasileiro (12 ou 13 dígitos com DDD e código do país)
                if (strlen($telefone) < 12 || strlen($telefone) > 13) {
                    error_log("Formato de telefone possivelmente inválido para o WhatsApp: {$telefone} (original: {$dados['whatsapp']})");
                }
                
                // Depuração - verificar o número final que será usado
                error_log("Número de telefone formatado para WhatsApp: {$telefone} (original: {$dados['whatsapp']}) para o apostador {$dados['nome']}");
                
                // Usar a API wa.me que funciona melhor para abrir o aplicativo WhatsApp no celular
                $texto_mensagem = urlencode($mensagem_whatsapp);
                $whatsapp_url = "https://wa.me/{$telefone}?text={$texto_mensagem}";
                
                // Manter a URL web.whatsapp.com como alternativa para uso em desktop
                $whatsapp_url_alt = "https://web.whatsapp.com/send?phone={$telefone}&text={$texto_mensagem}";
                
                // Salvar URL para exibir como botão depois
                if (!isset($_SESSION['whatsapp_urls'])) {
                    $_SESSION['whatsapp_urls'] = [];
                }
                
                // Simplificar o que é armazenado na sessão para reduzir o consumo de memória
                $_SESSION['whatsapp_urls'][] = [
                    'nome' => $dados['nome'],
                    'telefone' => $telefone,
                    'url' => $whatsapp_url, // Usar wa.me para direcionar para o app WhatsApp no celular
                    'url_alt' => $whatsapp_url_alt, // Web.whatsapp.com como alternativa para desktop
                    // Armazenar uma versão reduzida do texto para economizar memória
                    'texto' => substr($mensagem_whatsapp, 0, 1000) . (strlen($mensagem_whatsapp) > 1000 ? "\n[Mensagem truncada...]" : ""),
                    'apostas' => $apostas_count
                ];
            }
            
            if ($total_apostadores > 0) {
                $mensagem = "Foram preparados comprovantes para {$total_apostadores} apostador(es), totalizando {$total_apostas} apostas.";
                
                // Definir flag para indicar que acabamos de processar apostas
                $_SESSION['comprovantes_processados'] = true;
                
                // Redirecionar para a mesma página após processamento para evitar o botão travado
                $_SESSION['mensagem_sucesso'] = $mensagem;
                $_SESSION['comprovantes_processados'] = true;
                
                // Armazenar URL direta para o WhatsApp na sessão para redirecionamento direto
                if (!empty($_SESSION['whatsapp_urls'])) {
                    $_SESSION['redirect_whatsapp'] = $_SESSION['whatsapp_urls'][0]['url'];
                    
                    // Em vez de redirecionar para a mesma página, redirecionar diretamente para 
                    // um script que abre o WhatsApp sem interação adicional
                    header("Location: abrir_whatsapp.php");
                    exit;
                }
                
                // Redirecionar para a página principal com mensagem de sucesso
                $_SESSION['mensagem_sucesso'] = $mensagem;
                $_SESSION['comprovantes_processados'] = true;
                header("Location: enviar_comprovantes_whatsapp.php?action=whatsapp_ready");
                exit;
            } else {
                $erro = "Nenhum apostador válido encontrado com as apostas selecionadas.";
            }
            
        } else {
            $erro = "Nenhuma aposta selecionada.";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao processar apostas: " . $e->getMessage();
        $_SESSION['erro'] = $erro;
        header("Location: enviar_comprovantes_whatsapp.php");
        exit;
    }
}

// Processamento em lotes
if (isset($_GET['processar_lote']) && isset($_SESSION['apostas_pendentes'])) {
    // Aumentar o limite de tempo de execução do script para este processo
    set_time_limit(60); // 1 minuto por lote
    
    // Limitar uso de memória
    ini_set('memory_limit', '256M');
    
    $lote_atual = $_SESSION['lote_atual'];
    $total_lotes = $_SESSION['total_lotes'];
    $limite_apostas = 3;
    
    // Verificar se ainda temos lotes para processar
    if ($lote_atual < $total_lotes) {
        try {
            $revendedor_id = $_SESSION['usuario_id'];
            $apostas_ids = array_slice($_SESSION['apostas_pendentes'], $lote_atual * $limite_apostas, $limite_apostas);
            
            if (!empty($apostas_ids)) {
                // Obter informações das apostas do lote atual
                $placeholders = str_repeat('?,', count($apostas_ids) - 1) . '?';
                $stmt = $pdo->prepare("
                    SELECT 
                        a.id AS aposta_id, 
                        a.usuario_id, 
                        a.numeros,
                        a.valor_aposta,
                        a.valor_premio,
                        u.nome AS apostador_nome,
                        u.whatsapp AS apostador_whatsapp,
                        j.nome AS jogo_nome
                    FROM 
                        apostas a
                    JOIN 
                        usuarios u ON a.usuario_id = u.id
                    JOIN 
                        jogos j ON a.tipo_jogo_id = j.id
                    WHERE 
                        a.id IN ({$placeholders})
                        AND a.revendedor_id = ?
                    ORDER BY
                        u.id, a.id
                ");
                
                // Adicionar revendedor_id como último parâmetro
                $params = $apostas_ids;
                $params[] = $revendedor_id;
                
                $stmt->execute($params);
                $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Agrupar apostas por apostador
                $apostas_por_apostador = [];
                foreach ($apostas as $aposta) {
                    if (empty($aposta['apostador_whatsapp'])) {
                        // Registrar apostadores sem WhatsApp para exibir mensagem
                        if (!isset($_SESSION['apostadores_sem_whatsapp'])) {
                            $_SESSION['apostadores_sem_whatsapp'] = [];
                        }
                        $_SESSION['apostadores_sem_whatsapp'][] = $aposta['apostador_nome'];
                        continue; // Pular apostadores sem WhatsApp
                    }
                    
                    $usuario_id = $aposta['usuario_id'];
                    if (!isset($apostas_por_apostador[$usuario_id])) {
                        $apostas_por_apostador[$usuario_id] = [
                            'nome' => $aposta['apostador_nome'],
                            'whatsapp' => $aposta['apostador_whatsapp'],
                            'apostas' => []
                        ];
                    }
                    
                    $apostas_por_apostador[$usuario_id]['apostas'][] = $aposta;
                }
                
                // Processar apostadores do lote atual
                $lote_apostadores = 0;
                $lote_apostas = 0;
                
                foreach ($apostas_por_apostador as $usuario_id => $dados) {
                    if (count($dados['apostas']) === 0) {
                        continue;
                    }
                    
                    $lote_apostadores++;
                    $apostas_count = count($dados['apostas']);
                    $lote_apostas += $apostas_count;
                    
                    // Preparar mensagem inicial para o WhatsApp
                    $mensagem_whatsapp = "✅ *COMPROVANTES DE APOSTAS* ✅\n\n";
                    $mensagem_whatsapp .= "*Apostador:* {$dados['nome']}\n";
                    $mensagem_whatsapp .= "*Data:* " . date('d/m/Y H:i') . "\n\n";
                    
                    if ($apostas_count === 1) {
                        $mensagem_whatsapp .= "Estamos enviando seu comprovante de aposta logo abaixo.\n";
                    } else {
                        $mensagem_whatsapp .= "Estamos enviando seus {$apostas_count} comprovantes de apostas logo abaixo.\n";
                    }
                    
                    $mensagem_whatsapp .= "Boa sorte! 🍀\n\n";
                    
                    // Incluir resumo das apostas
                    $mensagem_whatsapp .= "*RESUMO DAS APOSTAS:*\n";
                    
                    foreach ($dados['apostas'] as $index => $aposta) {
                        $numeros = explode(',', $aposta['numeros']);
                        $numeros_formatados = implode('-', array_map(function($n) { 
                            return str_pad(trim($n), 2, '0', STR_PAD_LEFT); 
                        }, $numeros));
                        
                        $mensagem_whatsapp .= "\n*Aposta " . ($index + 1) . ":*\n";
                        $mensagem_whatsapp .= "- Jogo: {$aposta['jogo_nome']}\n";
                        $mensagem_whatsapp .= "- Números: {$numeros_formatados}\n";
                        $mensagem_whatsapp .= "- Valor: R$ " . number_format($aposta['valor_aposta'], 2, ',', '.') . "\n";
                        $mensagem_whatsapp .= "- Prêmio: R$ " . number_format($aposta['valor_premio'], 2, ',', '.') . "\n";
                    }
                    
                    // Gerar e incluir URLs dos comprovantes na mensagem
                    $mensagem_whatsapp .= "\n*LINKS DOS COMPROVANTES:*\n";
                    
                    foreach ($dados['apostas'] as $index => $aposta) {
                        $comprovante_url = gerarComprovantePDF($aposta['usuario_id'], $aposta['jogo_nome'], $aposta['aposta_id']);
                        $mensagem_whatsapp .= "\nComprovante " . ($index + 1) . ":\n";
                        $mensagem_whatsapp .= $comprovante_url . "\n";
                        
                        // Adicionar à fila de envio se a tabela existir
                        $table_exists = $pdo->query("SHOW TABLES LIKE 'fila_envio_comprovantes'")->rowCount() > 0;
                        
                        if ($table_exists) {
                            $stmt = $pdo->prepare("
                                INSERT INTO fila_envio_comprovantes 
                                (aposta_id, status, data_enfileiramento, tentativas) 
                                VALUES (?, 'pendente', NOW(), 0)
                            ");
                            $stmt->execute([$aposta['aposta_id']]);
                        }
                    }
                    
                    // Preparar URL para WhatsApp
                    $telefone = preg_replace('/\D/', '', $dados['whatsapp']);
                    
                    // Verificar se temos um número de WhatsApp válido
                    if (empty($telefone)) {
                        error_log("Apostador {$dados['nome']} (ID: {$usuario_id}) não tem número de WhatsApp válido");
                        continue; // Pular este apostador
                    }
                    
                    // Garantir que o número tenha o código do país (adicionar 55 se não começar com 55)
                    if (!preg_match('/^55/', $telefone)) {
                        $telefone = '55' . $telefone;
                    }
                    
                    // Verificar se o número parece ser um número de teste
                    if (strpos($telefone, '11111') !== false) {
                        error_log("Detectado possível número de teste: {$telefone} para {$dados['nome']}. Verificar cadastro.");
                    }
                    
                    // Verificar comprimento adequado do número no formato brasileiro (12 ou 13 dígitos com DDD e código do país)
                    if (strlen($telefone) < 12 || strlen($telefone) > 13) {
                        error_log("Formato de telefone possivelmente inválido para o WhatsApp: {$telefone} (original: {$dados['whatsapp']})");
                    }
                    
                    // Depuração - verificar o número final que será usado
                    error_log("Número de telefone formatado para WhatsApp: {$telefone} (original: {$dados['whatsapp']}) para o apostador {$dados['nome']}");
                    
                    // Usar a API wa.me que funciona melhor para abrir o aplicativo WhatsApp no celular
                    $texto_mensagem = urlencode($mensagem_whatsapp);
                    $whatsapp_url = "https://wa.me/{$telefone}?text={$texto_mensagem}";
                    
                    // Manter a URL web.whatsapp.com como alternativa para uso em desktop
                    $whatsapp_url_alt = "https://web.whatsapp.com/send?phone={$telefone}&text={$texto_mensagem}";
                    
                    // Salvar URL para exibir como botão depois
                    if (!isset($_SESSION['whatsapp_urls'])) {
                        $_SESSION['whatsapp_urls'] = [];
                    }
                    
                    // Simplificar o que é armazenado na sessão para reduzir o consumo de memória
                    $_SESSION['whatsapp_urls'][] = [
                        'nome' => $dados['nome'],
                        'telefone' => $telefone,
                        'url' => $whatsapp_url, // Usar wa.me para direcionar para o app WhatsApp no celular
                        'url_alt' => $whatsapp_url_alt, // Web.whatsapp.com como alternativa para desktop
                        // Armazenar uma versão reduzida do texto para economizar memória
                        'texto' => substr($mensagem_whatsapp, 0, 1000) . (strlen($mensagem_whatsapp) > 1000 ? "\n[Mensagem truncada...]" : ""),
                        'apostas' => $apostas_count
                    ];
                }
                
                // Atualizar contadores na sessão
                $_SESSION['lote_atual']++;
                $_SESSION['apostas_processadas'] += $lote_apostas;
                $_SESSION['apostadores_processados'] += $lote_apostadores;
                
                // Continuar o processamento em lotes
                if ($_SESSION['lote_atual'] < $total_lotes) {
                    // Ainda tem mais lotes para processar
                    $progresso = round(($_SESSION['lote_atual'] / $total_lotes) * 100);
                    $_SESSION['mensagem_progresso'] = "Processando lote {$_SESSION['lote_atual']} de {$total_lotes} ({$progresso}%)... Total de {$_SESSION['apostas_processadas']} apostas processadas até agora.";
                    
                    // Pequena pausa para não sobrecarregar o servidor
                    if ($_SESSION['lote_atual'] > 0 && $_SESSION['lote_atual'] % 3 == 0) {
                        // A cada 3 lotes, adicionamos uma pequena pausa
                        sleep(1);
                    }
                    
                    // Redirecionar para o próximo lote
                    header("Location: enviar_comprovantes_whatsapp.php?processar_lote=1");
                    exit;
                } else {
                    // Finalizou todos os lotes
                    $total_apostadores = $_SESSION['apostadores_processados'];
                    $total_apostas = $_SESSION['apostas_processadas'];
                    
                    $mensagem = "Foram preparados comprovantes para {$total_apostadores} apostador(es), totalizando {$total_apostas} apostas.";
                    
                    // Limpar variáveis de processamento em lote
                    unset($_SESSION['apostas_pendentes']);
                    unset($_SESSION['lote_atual']);
                    unset($_SESSION['total_lotes']);
                    unset($_SESSION['apostas_processadas']);
                    unset($_SESSION['apostadores_processados']);
                    unset($_SESSION['mensagem_progresso']);
                    
                    // Definir flag para indicar que acabamos de processar apostas
                    $_SESSION['comprovantes_processados'] = true;
                    
                    // Armazenar URL direta para o WhatsApp na sessão para redirecionamento direto
                    if (!empty($_SESSION['whatsapp_urls'])) {
                        $_SESSION['redirect_whatsapp'] = $_SESSION['whatsapp_urls'][0]['url'];
                        
                        // Em vez de redirecionar para a mesma página, redirecionar diretamente para 
                        // um script que abre o WhatsApp sem interação adicional
                        header("Location: abrir_whatsapp.php");
                        exit;
                    }
                    
                    // Redirecionar para a página principal com mensagem de sucesso
                    $_SESSION['mensagem_sucesso'] = $mensagem;
                    $_SESSION['comprovantes_processados'] = true;
                    header("Location: enviar_comprovantes_whatsapp.php?action=whatsapp_ready");
                    exit;
                }
            }
        } catch (PDOException $e) {
            $erro = "Erro ao processar lote: " . $e->getMessage();
            $_SESSION['erro'] = $erro;
            
            // Limpar variáveis de processamento em lote em caso de erro
            unset($_SESSION['apostas_pendentes']);
            unset($_SESSION['lote_atual']);
            unset($_SESSION['total_lotes']);
            unset($_SESSION['apostas_processadas']);
            unset($_SESSION['apostadores_processados']);
            unset($_SESSION['mensagem_progresso']);
            
            header("Location: enviar_comprovantes_whatsapp.php");
            exit;
        }
    }
}

// Revendedor atual
$revendedor_id = $_SESSION['usuario_id'];

// Filtros básicos
$filtros = ["a.revendedor_id = ?"]; // Filtro padrão para revendedor atual
$parametros = [$revendedor_id];

// Filtro por data
if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $filtros[] = "DATE(a.created_at) >= ?";
    $parametros[] = $_GET['data_inicio'];
}

if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $filtros[] = "DATE(a.created_at) <= ?";
    $parametros[] = $_GET['data_fim'];
}

// Filtro por apostador
if (isset($_GET['apostador_id']) && !empty($_GET['apostador_id'])) {
    $filtros[] = "a.usuario_id = ?";
    $parametros[] = $_GET['apostador_id'];
}

// Filtro por jogo
if (isset($_GET['jogo_id']) && !empty($_GET['jogo_id'])) {
    $filtros[] = "a.tipo_jogo_id = ?";
    $parametros[] = $_GET['jogo_id'];
}

// Montar cláusula WHERE
$where = "WHERE " . implode(" AND ", $filtros);

// Buscar apostas
$sql = "
    SELECT 
        a.id, 
        a.usuario_id, 
        a.numeros, 
        a.valor_aposta,
        a.created_at, 
        u.nome AS apostador_nome, 
        u.whatsapp AS apostador_whatsapp,
        j.nome AS jogo_nome,
        j.id AS jogo_id
    FROM 
        apostas a
    JOIN 
        usuarios u ON a.usuario_id = u.id
    JOIN 
        jogos j ON a.tipo_jogo_id = j.id
    {$where}
    ORDER BY 
        a.created_at DESC
    LIMIT 100
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    $apostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adicionar depuração para verificar o que está acontecendo
    error_log("Consulta SQL: " . $sql);
    error_log("Parâmetros: " . print_r($parametros, true));
    error_log("Total de apostas encontradas: " . count($apostas));
    
    // Se não encontrou apostas, vamos verificar se as tabelas existem
    if (empty($apostas)) {
        // Verificar se a tabela apostas existe
        $table_exists = $pdo->query("SHOW TABLES LIKE 'apostas'")->rowCount() > 0;
        error_log("Tabela 'apostas' existe: " . ($table_exists ? 'Sim' : 'Não'));
        
        // Verificar se há registros na tabela apostas
        if ($table_exists) {
            $total_apostas = $pdo->query("SELECT COUNT(*) FROM apostas")->fetchColumn();
            error_log("Total de registros na tabela apostas: " . $total_apostas);
            
            // Verificar se há apostas para este revendedor
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM apostas WHERE revendedor_id = ?");
            $stmt->execute([$revendedor_id]);
            $apostas_revendedor = $stmt->fetchColumn();
            error_log("Total de apostas para o revendedor ID {$revendedor_id}: " . $apostas_revendedor);
        }
    }
} catch (PDOException $e) {
    $erro = "Erro ao buscar apostas: " . $e->getMessage();
    error_log("Erro na consulta SQL: " . $e->getMessage());
    $apostas = [];
}

// Buscar apostadores diretamente da tabela de apostas
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.id, 
            u.nome,
            u.whatsapp
        FROM 
            usuarios u 
        WHERE
            u.id IN (
                SELECT DISTINCT usuario_id 
                FROM apostas 
                WHERE revendedor_id = ?
            )
        ORDER BY 
            u.nome
    ");
    $stmt->execute([$revendedor_id]);
    $apostadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adicionar depuração para apostadores
    error_log("Total de apostadores encontrados: " . count($apostadores));
    
    // Se não encontrou apostadores, vamos verificar mais detalhes
    if (empty($apostadores)) {
        // Verificar se há usuários no sistema
        $total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        error_log("Total de usuários no sistema: " . $total_usuarios);
        
        // Verificar se há apostadores que fizeram apostas
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT usuario_id) FROM apostas");
        $stmt->execute();
        $total_apostadores = $stmt->fetchColumn();
        error_log("Total de apostadores com apostas: " . $total_apostadores);
        
        // Verificar a estrutura das tabelas
        error_log("ID do revendedor atual: " . $revendedor_id);
        error_log("Tipo de usuário: " . $_SESSION['tipo']);
    }
} catch (PDOException $e) {
    $erro = "Erro ao buscar apostadores: " . $e->getMessage();
    error_log("Erro na consulta de apostadores: " . $e->getMessage());
    $apostadores = [];
}

// Verificar quantos apostadores foram encontrados
$total_apostadores = count($apostadores);

// Mensagens de status
if (empty($apostas) && empty($erro)) {
    $erro = "Não foram encontradas apostas para este revendedor.";
}

if (empty($apostadores) && empty($erro)) {
    $erro = "Não foram encontrados apostadores associados a este revendedor.";
}

// Buscar jogos para filtro
try {
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            nome 
        FROM 
            jogos 
        ORDER BY 
            nome
    ");
    $stmt->execute();
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar jogos: " . $e->getMessage();
    $jogos = [];
}

// Logo após a definição das variáveis, antes de iniciar o processamento:
// Adicionar depois da linha ~35
// Conteúdo da página
ob_start();

// Exibir o botão de emergência se necessário
if (isset($alerta_emergencia) && !empty($alerta_emergencia)) {
    echo $alerta_emergencia;
}

// Verificar e remover flags/parâmetros após uso
if (isset($_SESSION['comprovantes_processados'])) {
    // Verificar se temos apostas processadas explicitamente pelo usuário
    // Adicionar uma verificação extra para evitar aberturas automáticas
    if (isset($_POST['action']) && $_POST['action'] === 'enviar_comprovantes') {
        $abrir_whatsapp = true;
        unset($_SESSION['comprovantes_processados']); // Limpar imediatamente para evitar loops
        
        // Se temos uma URL direta para o WhatsApp, redirecionar imediatamente
        if (isset($_SESSION['redirect_whatsapp']) && !empty($_SESSION['redirect_whatsapp'])) {
            $redirect_url = $_SESSION['redirect_whatsapp'];
            
            // Não remover da sessão para permitir tentativas alternativas
            // Será removido após uma visita bem-sucedida ao abrir_whatsapp.php
            
            // CORREÇÃO: Remover redirecionamento automático e substituir por botão
            // Isso resolve o problema do popup que aparece e desaparece rapidamente
            echo '<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <h4 class="alert-heading"><i class="fas fa-check-circle"></i> Comprovantes Prontos</h4>
                <p>Seus comprovantes foram processados e estão prontos para envio por WhatsApp.</p>
                <hr>
                <div class="text-center">
                    <a href="abrir_whatsapp.php" class="btn btn-success btn-lg">
                        <i class="fab fa-whatsapp me-2"></i> Enviar via WhatsApp
                    </a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>';
            
            // Registrar apenas para fins de depuração
            echo '<script>
                console.log("URL do WhatsApp disponível:", "' . htmlspecialchars($redirect_url) . '");
                // NÃO usar redirecionamento automático
            </script>';
        }
    } else {
        // Se não foi uma ação explícita do usuário, limpar as flags e não abrir o WhatsApp
        $abrir_whatsapp = false;
        unset($_SESSION['comprovantes_processados']);
        unset($_SESSION['redirect_whatsapp']);
        unset($_SESSION['whatsapp_urls']);
    }
} else {
    // FIXO: Remover qualquer possibilidade de abrir o WhatsApp automaticamente
    $abrir_whatsapp = false;
    
    // Verificar se temos o parâmetro de ação para abrir o WhatsApp
    // Mas APENAS permitir se for resultado de uma ação POST recente
    if (isset($_GET['action']) && $_GET['action'] === 'whatsapp_ready' && 
        isset($_SESSION['last_post_action']) && 
        (time() - $_SESSION['last_post_action'] < 60)) {
        
        $abrir_whatsapp = true;
    }
    
    // Se detectarmos o parâmetro whatsapp_ready, mas não é uma ação válida
    if (isset($_GET['action']) && $_GET['action'] === 'whatsapp_ready' && !$abrir_whatsapp) {
        // Limpar todas as variáveis
        unset($_SESSION['redirect_whatsapp']);
        unset($_SESSION['whatsapp_urls']);
        unset($_SESSION['last_post_action']);
        
        // Redirecionar para a página limpa
        header("Location: enviar_comprovantes_whatsapp.php");
        exit;
    }
    
    // Se detectarmos o parâmetro whatsapp_ready e for uma ação válida
    if ($abrir_whatsapp) {
        // CORREÇÃO: Remover redirecionamento automático e substituir por botão
        // Isso resolve o problema do popup que aparece e desaparece rapidamente
        echo '<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <h4 class="alert-heading"><i class="fas fa-check-circle"></i> Comprovantes Prontos</h4>
            <p>Seus comprovantes foram processados e estão prontos para envio por WhatsApp.</p>
            <hr>
            <div class="text-center">
                <a href="abrir_whatsapp.php" class="btn btn-success btn-lg">
                    <i class="fab fa-whatsapp me-2"></i> Enviar via WhatsApp
                </a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>';
        
        // Criar um indicador no localStorage apenas para fins de controle
        echo "<script>
            localStorage.setItem('processando_whatsapp', 'preparado');
            localStorage.setItem('last_whatsapp_timestamp', Date.now());
            console.log('WhatsApp preparado para abertura manual pelo usuário');
            
            // Limpar parâmetros da URL sem recarregar a página
            if (window.history && window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.delete('action');
                window.history.replaceState({}, '', url.toString());
            }
        </script>";
    }
}

// FIXO: Verificação final para garantir que não temos resíduos de estado que possam causar redirecionamento automático
if (isset($_SESSION['whatsapp_urls']) && isset($_SESSION['redirect_whatsapp']) && !isset($_SESSION['last_post_action'])) {
    // Isto é suspeito - temos URLs mas não houve ação recente
    unset($_SESSION['whatsapp_urls']);
    unset($_SESSION['redirect_whatsapp']);
    
    // Registrar a ocorrência para análise
    error_log("Detectada situação suspeita: URLs do WhatsApp presentes sem ação recente");
}

// Verificar se há mensagem de erro sobre pop-ups bloqueados
if (isset($_GET['erro']) && $_GET['erro'] === 'popup_bloqueado') {
    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Popup bloqueado!</h4>
        <p>Seu navegador bloqueou a janela do WhatsApp. Para resolver:</p>
        <ol>
            <li>Procure pelo ícone de bloqueio <i class="fas fa-ban text-danger"></i> na barra de endereço</li>
            <li>Clique nele e selecione "Permitir pop-ups para este site"</li>
            <li>Então, tente novamente usando o botão abaixo</li>
        </ol>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        <hr>
        <div class="text-center">
            <a href="abrir_whatsapp.php" class="btn btn-success btn-lg">
                <i class="fab fa-whatsapp me-2"></i> Tentar Enviar via WhatsApp Novamente
            </a>
        </div>
    </div>';
}

// Verificar se há uma mensagem de erro sobre acesso direto inválido
if (isset($_GET['erro']) && $_GET['erro'] === 'acesso_invalido') {
    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Acesso Inválido!</h4>
        <p>Detectamos uma tentativa de acessar diretamente a página de redirecionamento para o WhatsApp.</p>
        <p>Para usar esta funcionalidade corretamente:</p>
        <ol>
            <li>Selecione as apostas que deseja enviar</li>
            <li>Clique no botão "Enviar Comprovantes Selecionados"</li>
            <li>O sistema processará e abrirá o WhatsApp automaticamente</li>
        </ol>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>';
    
    // Limpar quaisquer variáveis de sessão relacionadas ao WhatsApp para evitar comportamentos inesperados
    unset($_SESSION['redirect_whatsapp']);
    unset($_SESSION['whatsapp_urls']);
    unset($_SESSION['comprovantes_processados']);
    unset($_SESSION['last_post_action']);
}

// Verificar se há erro genérico sobre WhatsApp
if (isset($_GET['erro']) && $_GET['erro'] === 'sem_url_whatsapp') {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h4 class="alert-heading"><i class="fas fa-exclamation-circle"></i> Erro no Processamento!</h4>
        <p>Não foi possível gerar o link para o WhatsApp. Verifique se:</p>
        <ul>
            <li>Você selecionou apostas válidas</li>
            <li>Os apostadores possuem WhatsApp cadastrado</li>
            <li>O sistema está funcionando corretamente</li>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>';
}

// Adicionar SweetAlert2 para melhorar o feedback visual
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
// Adicionar Animate.css para efeitos visuais
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />';

?>

<div class="container-fluid">
    <h1 class="mt-4">Enviar Comprovantes por WhatsApp</h1>
    
    <?php if (!empty($mensagem)): ?>
    <div class="alert alert-success"><?php echo $mensagem; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($erro)): ?>
    <div class="alert alert-danger"><?php echo $erro; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['mensagem_progresso'])): ?>
        <div class="alert alert-info">
            <i class="fas fa-sync fa-spin"></i> <?php echo $_SESSION['mensagem_progresso']; ?>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter"></i> Filtros
        </div>
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3 mb-3">
                    <label for="data_inicio">Data Início:</label>
                    <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?php echo $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days')); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="data_fim">Data Fim:</label>
                    <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?php echo $_GET['data_fim'] ?? date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="apostador_id">Apostador:</label>
                    <select id="apostador_id" name="apostador_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($apostadores as $apostador): 
                            $selected = (isset($_GET['apostador_id']) && $_GET['apostador_id'] == $apostador['id']) ? 'selected' : '';
                            $tem_whatsapp = !empty($apostador['whatsapp']);
                            $nome_exibicao = htmlspecialchars($apostador['nome']);
                            
                            if (!$tem_whatsapp) {
                                $nome_exibicao .= " (Sem WhatsApp)";
                            }
                        ?>
                        <option value="<?php echo $apostador['id']; ?>" <?php echo $selected; ?>><?php echo $nome_exibicao; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="jogo_id">Jogo:</label>
                    <select id="jogo_id" name="jogo_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($jogos as $jogo): 
                            $selected = (isset($_GET['jogo_id']) && $_GET['jogo_id'] == $jogo['id']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $jogo['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($jogo['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="enviar_comprovantes_whatsapp.php" class="btn btn-secondary">Limpar Filtros</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fab fa-whatsapp"></i> Enviar Comprovantes
        </div>
        <div class="card-body">
            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger mb-3">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-success mb-3">
                    <i class="fas fa-check-circle"></i> <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <?php if ($total_apostadores > 0): ?>
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle"></i> Selecione as apostas para enviar comprovantes por WhatsApp. Apenas apostadores com WhatsApp cadastrado receberão os comprovantes.
                    <br>Total de apostadores encontrados: <?php echo $total_apostadores; ?>
                    <br><small><strong>Nota:</strong> Os números de WhatsApp devem estar no formato internacional, por exemplo: +55 35 99781-5465</small>
                </div>

                <?php error_log("Exibindo tabela de apostas com {$total_apostadores} apostadores."); ?>
                
                <form method="POST" id="form-enviar-comprovantes">
                    <!-- Botão de envio no topo da tabela -->
                    <div class="mb-3 text-center">
                        <input type="hidden" name="action" value="enviar_comprovantes">
                        <button type="submit" class="btn btn-success btn-lg" onclick="return submitWhatsApp();">
                            <i class="fab fa-whatsapp"></i> Enviar Comprovantes Selecionados
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="tabela-apostas">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selecionar-todos">
                                    </th>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Apostador</th>
                                    <th>WhatsApp</th>
                                    <th>Jogo</th>
                                    <th>Números</th>
                                    <th>Valor</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($apostas)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Nenhuma aposta encontrada</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($apostas as $aposta): 
                                    $numeros = explode(',', $aposta['numeros']);
                                    $numeros_formatados = implode(' - ', array_map(function($n) { 
                                        return str_pad(trim($n), 2, '0', STR_PAD_LEFT); 
                                    }, $numeros));
                                ?>
                                <tr>
                                    <td>
                                    <?php if (!empty($aposta['apostador_whatsapp'])): ?>
                                        <input type="checkbox" name="apostas[]" value="<?php echo $aposta['id']; ?>" class="aposta-checkbox">
                                    <?php else: ?>
                                        <i class="fas fa-ban text-danger" title="Sem WhatsApp"></i>
                                    <?php endif; ?>
                                    </td>
                                    <td><?php echo $aposta['id']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($aposta['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($aposta['apostador_nome']); ?></td>
                                    <td>
                                    <?php if (!empty($aposta['apostador_whatsapp'])): ?>
                                        <?php echo htmlspecialchars($aposta['apostador_whatsapp']); ?>
                                    <?php else: ?>
                                        <span class="text-danger">Não cadastrado</span>
                                    <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($aposta['jogo_nome']); ?></td>
                                    <td><?php echo $numeros_formatados; ?></td>
                                    <td>R$ <?php echo number_format($aposta['valor_aposta'], 2, ',', '.'); ?></td>
                                    <td>
                                                                <a href="../admin/gerar_comprovante.php?usuario_id=<?php echo $aposta['usuario_id']; ?>&jogo=<?php echo urlencode($aposta['jogo_nome']); ?>&aposta_id=<?php echo $aposta['id']; ?>&formato=pdf" target="_blank" class="btn btn-sm btn-primary">                            <i class="fas fa-file-pdf"></i>                        </a>                    <?php if (!empty($aposta['apostador_whatsapp'])): ?>                        <a href="javascript:void(0)" onclick="enviarComprovanteIndividual(<?php echo $aposta['id']; ?>)" class="btn btn-sm btn-success">                            <i class="fab fa-whatsapp"></i>                        </a>                    <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Botão de envio na parte inferior da tabela -->
                    <div class="mt-3 text-center">
                        <input type="hidden" name="action" value="enviar_comprovantes">
                        <button type="submit" class="btn btn-success btn-lg" onclick="return submitWhatsApp();">
                            <i class="fab fa-whatsapp"></i> Enviar Comprovantes Selecionados
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Nenhum apostador encontrado vinculado a este revendedor. Você precisa ter clientes cadastrados com apostas para poder enviar comprovantes.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Mensagem de ajuda para revendedores sem apostas -->
<?php if (empty($apostas) && empty($apostadores)): ?>
<div class="card mt-4">
    <div class="card-header bg-info text-white">
        <i class="fas fa-info-circle"></i> Como criar apostas para seus clientes
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5>Passos para criar apostas:</h5>
                <ol>
                    <li>Primeiro, cadastre seus clientes em <a href="clientes.php" class="text-primary">Meus Clientes</a></li>
                    <li>Certifique-se de incluir o número de WhatsApp do cliente</li>
                    <li>Em seguida, crie apostas para esses clientes</li>
                    <li>Depois, volte a esta página para enviar os comprovantes</li>
                </ol>
            </div>
            <div class="col-md-6">
                <div class="alert alert-secondary">
                    <h5><i class="fas fa-database"></i> Informações do Banco de Dados:</h5>
                    <ul class="small mb-0">
                        <li>Verifique se o banco de dados está corretamente configurado</li>
                        <li>Se você acabou de instalar o sistema, pode ser necessário importar os dados iniciais</li>
                        <li>Em caso de problemas, entre em contato com o suporte técnico</li>
                    </ul>
                </div>
                <div class="text-center mt-3">
                    <a href="diagnostico_db.php" class="btn btn-outline-primary">
                        <i class="fas fa-stethoscope"></i> Executar Diagnóstico do Banco de Dados
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    // Função para validar e enviar o formulário
    function submitWhatsApp() {
        const apostasChecked = document.querySelectorAll("input[name=\"apostas[]\"]:checked");
        
        console.log("Função submitWhatsApp() chamada");
        console.log("Apostas selecionadas:", apostasChecked.length);
        
        if (apostasChecked.length === 0) {
            // Usar SweetAlert2 para um alerta mais bonito
            Swal.fire({
                title: 'Nenhuma aposta selecionada',
                text: 'Por favor, selecione pelo menos uma aposta para enviar.',
                icon: 'warning',
                confirmButtonText: 'OK',
                confirmButtonColor: '#28a745'
            });
            return false;
        }
        
        // Verificar se há apostadores sem WhatsApp
        let semWhatsApp = false;
        apostasChecked.forEach(function(checkbox) {
            const row = checkbox.closest('tr');
            const whatsappCell = row.querySelector('td:nth-child(5)');
            if (whatsappCell && whatsappCell.textContent.includes('Não cadastrado')) {
                semWhatsApp = true;
            }
        });
        
        if (semWhatsApp) {
            return Swal.fire({
                title: 'Apostadores sem WhatsApp',
                text: 'Alguns apostadores selecionados não possuem WhatsApp cadastrado. Deseja continuar mesmo assim?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, continuar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    return continuarEnvio(apostasChecked);
                }
                return false;
            });
        }
        
        return continuarEnvio(apostasChecked);
    }
    
    // Função auxiliar para continuar o processo de envio
    function continuarEnvio(apostasChecked) {
        // Limite máximo de apostas que podem ser processadas de uma vez
        const limite_maximo = 8; // Reduzido para 8 apostas
        
        // Exibir aviso se tivermos muitas apostas
        if (apostasChecked.length > 5) {
            // Informar sobre o processamento em lotes
            if (apostasChecked.length > limite_maximo) {
                Swal.fire({
                    title: 'Muitas apostas selecionadas',
                    text: `Você selecionou ${apostasChecked.length} apostas, mas o sistema pode processar no máximo ${limite_maximo} apostas de uma vez. Por favor, selecione menos apostas.`,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#28a745'
                });
                return false;
            }
            
            // CORREÇÃO: Interromper o envio do formulário até que o usuário responda
            Swal.fire({
                title: 'Processamento em lotes',
                text: `Você selecionou ${apostasChecked.length} apostas. O processamento será realizado em lotes para evitar sobrecarga do sistema. Deseja continuar?`,
                icon: 'question',
                showCancelButton: true,
                allowOutsideClick: false, // Impedir clique fora do modal para fechá-lo
                allowEscapeKey: false, // Impedir tecla ESC para fechá-lo
                confirmButtonText: 'Sim, continuar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#28a745',
                reverseButtons: true, // Botão de confirmar à direita e cancelar à esquerda
                width: '32em', // Aumentar a largura do modal
                padding: '2em', // Adicionar mais espaço interno
                backdrop: `rgba(0,0,0,0.5)`, // Escurecer o fundo para destacar o modal
                customClass: {
                    confirmButton: 'btn btn-lg btn-success',
                    cancelButton: 'btn btn-lg btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    mostrarCarregamento(apostasChecked.length);
                    
                    // Adicionar campo que sinaliza para abrir WhatsApp após processamento
                    let openWhatsAppField = document.querySelector('input[name="open_whatsapp"]');
                    if (!openWhatsAppField) {
                        openWhatsAppField = document.createElement('input');
                        openWhatsAppField.type = 'hidden';
                        openWhatsAppField.name = 'open_whatsapp';
                        openWhatsAppField.value = '1';
                        document.getElementById("form-enviar-comprovantes").appendChild(openWhatsAppField);
                    }
                    
                    // Submeter o formulário manualmente após confirmação
                    document.getElementById("form-enviar-comprovantes").submit();
                }
            });
            
            // Retornar false para impedir o envio automático do formulário
            return false;
        }
        
        // Para poucas apostas, mostrar diretamente o carregamento
        mostrarCarregamento(apostasChecked.length);
        
        // Adicionar campo que sinaliza para abrir WhatsApp após processamento
        // (mesmo para poucas apostas)
        let openWhatsAppField = document.querySelector('input[name="open_whatsapp"]');
        if (!openWhatsAppField) {
            openWhatsAppField = document.createElement('input');
            openWhatsAppField.type = 'hidden';
            openWhatsAppField.name = 'open_whatsapp';
            openWhatsAppField.value = '1';
            document.getElementById("form-enviar-comprovantes").appendChild(openWhatsAppField);
        }
        
        return true;
    }
    
    // Função para mostrar tela de carregamento
    function mostrarCarregamento(numApostas) {
        // Definir flag de processamento no localStorage
        localStorage.setItem("processando_whatsapp", "true");
        localStorage.setItem("last_whatsapp_timestamp", Date.now());
        localStorage.setItem("last_post_action", Date.now()); // Registrar ação explícita do usuário
        
        // Mostrar overlay de carregamento com mais informações
        const overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
        overlay.style.zIndex = '9999';
        overlay.style.display = 'flex';
        overlay.style.flexDirection = 'column';
        overlay.style.justifyContent = 'center';
        overlay.style.alignItems = 'center';
        
        const spinnerContainer = document.createElement('div');
        spinnerContainer.style.backgroundColor = 'white';
        spinnerContainer.style.borderRadius = '8px';
        spinnerContainer.style.padding = '20px';
        spinnerContainer.style.maxWidth = '80%';
        spinnerContainer.style.textAlign = 'center';
        
        const heading = document.createElement('h4');
        heading.textContent = 'Processando Comprovantes';
        heading.style.marginBottom = '15px';
        spinnerContainer.appendChild(heading);
        
        const spinner = document.createElement('div');
        spinner.className = 'spinner-border text-success';
        spinner.setAttribute('role', 'status');
        spinner.style.width = '3rem';
        spinner.style.height = '3rem';
        spinner.style.marginBottom = '15px';
        spinnerContainer.appendChild(spinner);
        
        const spinnerText = document.createElement('div');
        spinnerText.className = 'mt-3';
        spinnerText.innerHTML = '<strong class="animate__animated animate__pulse animate__infinite">Preparando ' + numApostas + ' comprovante(s)</strong><br>' +
                              '<small class="text-muted">Isso pode levar alguns segundos...</small><br>' +
                              '<small class="text-muted">Você será redirecionado automaticamente para o WhatsApp.</small>';
        spinnerContainer.appendChild(spinnerText);
        
        overlay.appendChild(spinnerContainer);
        document.body.appendChild(overlay);
        
        // Marcar como processando...
        const submitButtons = document.querySelectorAll('.btn-success');
        submitButtons.forEach(function(btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando comprovantes...';
        });
        
        // Adicionar campo de ação para indicar que estamos enviando comprovantes
        let actionInput = document.querySelector('input[name="action"]');
        if (!actionInput) {
            actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'enviar_comprovantes';
            document.getElementById("form-enviar-comprovantes").appendChild(actionInput);
        } else {
            actionInput.value = 'enviar_comprovantes';
        }
        
        // Definir um timeout para voltar o botão ao normal caso algo dê errado
        setTimeout(function() {
            // Se o formulário ainda estiver na página após 30 segundos, restaurar os botões
            if (document.getElementById("form-enviar-comprovantes")) {
                const overlay = document.getElementById('loading-overlay');
                if (overlay) {
                    overlay.querySelector('.spinner-border').classList.remove('spinner-border');
                    
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-warning';
                    alertDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> O processamento está demorando mais que o esperado. Por favor, tente novamente ou selecione menos apostas.';
                    
                    if (spinnerText) {
                        spinnerText.innerHTML = '';
                        spinnerText.appendChild(alertDiv);
                    }
                    
                    const btnContainer = document.createElement('div');
                    btnContainer.className = 'mt-3';
                    const cancelBtn = document.createElement('button');
                    cancelBtn.className = 'btn btn-danger';
                    cancelBtn.textContent = 'Cancelar';
                    cancelBtn.onclick = function() {
                        if (overlay) document.body.removeChild(overlay);
                        
                        submitButtons.forEach(function(btn) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fab fa-whatsapp"></i> Enviar Comprovantes Selecionados';
                        });
                        
                        localStorage.removeItem("processando_whatsapp");
                    };
                    btnContainer.appendChild(cancelBtn);
                    spinnerText.appendChild(btnContainer);
                }
            }
        }, 25000); // 25 segundos
        
        console.log("Formulário enviado!");
        return true;
    }

    document.addEventListener("DOMContentLoaded", function() {
        console.log("DOM carregado - Verificando elementos na página");
        
        // Verificar se há botões do WhatsApp presentes na página
        const whatsappButtons = document.querySelectorAll(".btn-whatsapp-send");
        console.log("Botões de WhatsApp encontrados no carregamento:", whatsappButtons.length);
        
        // Adicionar manipuladores de eventos para cada botão de WhatsApp
        whatsappButtons.forEach(button => {
            button.addEventListener("click", function(e) {
                console.log("Botão WhatsApp clicado:", this.href);
                // Tentar abrir em uma nova janela
                const newWindow = window.open(this.href, "_blank");
                if (!newWindow || newWindow.closed || typeof newWindow.closed === "undefined") {
                    alert("O navegador bloqueou a abertura da janela do WhatsApp. Por favor, verifique as configurações do seu navegador e permita pop-ups para este site.");
                }
            });
        });
        
        // Prevenir loops de recarregamento verificando última tentativa
        const lastWhatsAppAttempt = localStorage.getItem("last_whatsapp_attempt");
        const now = Date.now();
        
        // Se houve tentativa recente (últimos 5 segundos), ignorar
        if (lastWhatsAppAttempt && (now - parseInt(lastWhatsAppAttempt) < 5000)) {
            console.log("Tentativa recente detectada, ignorando abertura automática");
            localStorage.removeItem("last_whatsapp_attempt");
            return;
        }
        
        // Verificar se devemos abrir automaticamente
        var shouldOpenWhatsApp = <?php echo $abrir_whatsapp ? 'true' : 'false'; ?>;
        console.log("Abrir WhatsApp automaticamente:", shouldOpenWhatsApp);
        
        if (shouldOpenWhatsApp && whatsappButtons.length > 0) {
            console.log("Abrindo WhatsApp automaticamente após processamento...");
            
            // Marcar timestamp da tentativa atual
            localStorage.setItem("last_whatsapp_attempt", now.toString());
            
            // Pequeno atraso para garantir que a página esteja pronta
            setTimeout(() => {
                try {
                    const firstButton = whatsappButtons[0];
                    console.log("Abrindo WhatsApp para:", firstButton.href);
                    window.open(firstButton.href, "_blank");
                    
                    // Limpar flags após tentativa
                    localStorage.removeItem("processando_whatsapp");
                } catch (error) {
                    console.error("Erro ao abrir WhatsApp:", error);
                }
            }, 1500);
            
            // Remover parâmetro da URL sem recarregar a página
            if (window.history && window.history.replaceState) {
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.delete("action");
                    window.history.replaceState({}, "", url.toString());
                } catch (error) {
                    console.error("Erro ao manipular URL:", error);
                }
            }
        }

        // Adicionando evento de input para filtro dinâmico
        const filtroInput = document.getElementById('filtro-apostador');
        if (filtroInput) {
            filtroInput.addEventListener('input', filtrarApostadores);
        }
        
        // Adicionar manipuladores de eventos para botões de cópia
        document.querySelectorAll(".btn-copy-text").forEach(btn => {
            btn.addEventListener("click", function() {
                const texto = this.getAttribute("data-text");
                copiarTexto(texto);
            });
        });
        
        // Manipular seleção de todas as apostas
        const selecionarTodos = document.getElementById('selecionar-todos');
        const checkboxes = document.querySelectorAll('.aposta-checkbox');
        
        if (selecionarTodos) {
            console.log("Inicializando manipulador de 'selecionar-todos'");
            console.log(`Total de checkboxes encontrados: ${checkboxes.length}`);
            
            selecionarTodos.addEventListener('change', function() {
                const isChecked = this.checked;
                console.log(`Checkbox 'selecionar-todos' alterado para: ${isChecked}`);
                
                // Selecionar apenas as apostas que têm WhatsApp cadastrado
                let totalMarcados = 0;
                checkboxes.forEach(function(checkbox) {
                    // Verificar se o checkbox está em uma linha com WhatsApp cadastrado
                    const row = checkbox.closest('tr');
                    const whatsappCell = row.querySelector('td:nth-child(5)');
                    
                    // Só marca se não tiver o texto "Não cadastrado" na célula de WhatsApp
                    if (!whatsappCell || !whatsappCell.textContent.includes('Não cadastrado')) {
                        checkbox.checked = isChecked;
                        if (isChecked) totalMarcados++;
                    }
                });
                
                console.log(`Total de apostas marcadas: ${totalMarcados}`);
            });
            
            // Atualizar o estado do checkbox "selecionar-todos" quando os checkboxes individuais são alterados
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const totalCheckboxes = document.querySelectorAll('.aposta-checkbox:not(:disabled)').length;
                    const totalChecked = document.querySelectorAll('.aposta-checkbox:checked').length;
                    
                    console.log(`Checkbox individual alterado. Total marcados: ${totalChecked}/${totalCheckboxes}`);
                    
                    // Se todos estiverem marcados, marca o "selecionar-todos"
                    selecionarTodos.checked = totalChecked === totalCheckboxes;
                    
                    // Se algum estiver marcado, mas não todos, deixa o "selecionar-todos" em estado indeterminado
                    if (totalChecked > 0 && totalChecked < totalCheckboxes) {
                        selecionarTodos.indeterminate = true;
                    } else {
                        selecionarTodos.indeterminate = false;
                    }
                });
            });
            
            // Verificar o estado inicial no carregamento da página
            const totalCheckboxes = checkboxes.length;
            if (totalCheckboxes > 0) {
                const totalChecked = document.querySelectorAll('.aposta-checkbox:checked').length;
                
                selecionarTodos.checked = totalChecked === totalCheckboxes && totalCheckboxes > 0;
                if (totalChecked > 0 && totalChecked < totalCheckboxes) {
                    selecionarTodos.indeterminate = true;
                }
                
                console.log(`Estado inicial: ${totalChecked} de ${totalCheckboxes} apostas selecionadas`);
                
                // Adicionar uma dica visual para o usuário saber que o checkbox funciona
                if (totalChecked === 0) {
                    // Adicionar um pequeno tooltip na coluna de título do selecionar-todos
                    const headerCell = selecionarTodos.closest('th');
                    if (headerCell) {
                        headerCell.title = "Clique para selecionar todas as apostas";
                        // Adicionar um pequeno ícone de ajuda
                        const infoSpan = document.createElement('span');
                        infoSpan.className = "text-primary ms-1";
                        infoSpan.innerHTML = '<i class="fas fa-info-circle fa-xs"></i>';
                        infoSpan.title = "Clique para selecionar todas as apostas de uma vez";
                        headerCell.appendChild(infoSpan);
                    }
                }
            }
            
            // Forçar uma verificação extra de todas as caixas de seleção para garantir consistência
            setTimeout(() => {
                const checkedBoxes = document.querySelectorAll('.aposta-checkbox:checked').length;
                console.log(`Verificação final: ${checkedBoxes} de ${checkboxes.length} apostas selecionadas`);
            }, 500);
        } else {
            console.error("Elemento 'selecionar-todos' não encontrado na página!");
            // Log adicional para verificar se temos apostas na página
            const temApostas = document.querySelectorAll('.aposta-checkbox').length > 0;
            console.log(`Existem checkbox de apostas na página? ${temApostas ? 'Sim' : 'Não'}`);
            console.log(`Total de linhas na tabela: ${document.querySelectorAll('#tabela-apostas tbody tr').length}`);
            
            // Verificar se existe a tabela
            const tabelaApostas = document.getElementById('tabela-apostas');
            console.log(`Tabela de apostas encontrada? ${tabelaApostas ? 'Sim' : 'Não'}`);
            
            // Verificar se há mensagem de erro na página
            const mensagensErro = document.querySelectorAll('.alert-danger');
            if (mensagensErro.length > 0) {
                console.log("Mensagens de erro encontradas na página:");
                mensagensErro.forEach((msg, i) => {
                    console.log(`Erro ${i+1}: ${msg.textContent.trim()}`);
                });
            }
        }
    });

    // Função para copiar texto para a área de transferência
    function copiarTexto(texto) {
        // Criar um elemento temporário
        const el = document.createElement("textarea");
        el.value = texto;
        document.body.appendChild(el);
        el.select();
        document.execCommand("copy");
        document.body.removeChild(el);
        
        // Feedback visual simples
        alert("Mensagem copiada para a área de transferência");
    }
    
    // Função para exibir/esconder a mensagem
    function toggleMensagem(button) {
        const preview = button.nextElementSibling;
        if (preview.style.display === 'none') {
            preview.style.display = 'block';
            button.innerHTML = '<i class="fas fa-eye-slash"></i> Ocultar mensagem';
        } else {
            preview.style.display = 'none';
            button.innerHTML = '<i class="fas fa-eye"></i> Visualizar mensagem';
        }
    }
    
    // Função para filtrar apostadores
    function filtrarApostadores() {
        const termo = document.getElementById('filtro-apostador').value.toLowerCase();
        const cards = document.querySelectorAll('.card-apostador');
        let contadorVisivel = 0;
        
        cards.forEach(card => {
            const nome = card.getAttribute('data-nome');
            if (nome.includes(termo)) {
                card.style.display = 'block';
                contadorVisivel++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Atualizar contador
        document.getElementById('contador-apostadores').textContent = contadorVisivel + ' apostador' + (contadorVisivel !== 1 ? 'es' : '');
    }

    // Função depurada e melhorada para selecionar todas as apostas
    document.addEventListener("DOMContentLoaded", function() {
        console.log("DOM carregado - Inicializando manipuladores de eventos");
        
        // Melhorar a seleção de todas as apostas
        const selecionarTodos = document.getElementById('selecionar-todos');
        const checkboxes = document.querySelectorAll('.aposta-checkbox');
        
        if (selecionarTodos) {
            console.log(`Inicializando 'selecionar-todos' - ${checkboxes.length} apostas encontradas`);
            
            // Adicionar um efeito visual quando clicar no checkbox de selecionar todos
            selecionarTodos.addEventListener('click', function() {
                // Adicionar feedback visual para indicar que algo está acontecendo
                const headerCell = this.closest('th');
                if (headerCell) {
                    headerCell.style.backgroundColor = '#e8f4ff';
                    setTimeout(() => {
                        headerCell.style.backgroundColor = '';
                    }, 300);
                }
            });
            
            // Aplicar a seleção quando o estado mudar
            selecionarTodos.addEventListener('change', function() {
                const isChecked = this.checked;
                console.log(`Checkbox 'selecionar-todos' alterado para: ${isChecked}`);
                
                // Contador para debug
                let totalMarcados = 0;
                let totalDisponiveis = 0;
                
                // Selecionar apenas as apostas que têm WhatsApp cadastrado
                checkboxes.forEach(function(checkbox) {
                    // Verificar se o checkbox está em uma linha com WhatsApp cadastrado
                    const row = checkbox.closest('tr');
                    if (!row) return; // Segurança extra
                    
                    const whatsappCell = row.querySelector('td:nth-child(5)');
                    const temWhatsapp = whatsappCell && !whatsappCell.textContent.includes('Não cadastrado');
                    
                    if (temWhatsapp) {
                        totalDisponiveis++;
                        checkbox.checked = isChecked;
                        
                        // Highlight visual na linha ao marcar/desmarcar
                        if (isChecked) {
                            totalMarcados++;
                            row.classList.add('table-success');
                            row.style.transition = 'background-color 0.3s';
                        } else {
                            row.classList.remove('table-success');
                        }
                    }
                });
                
                console.log(`Total: ${totalMarcados}/${totalDisponiveis} apostas marcadas com WhatsApp disponível`);
                
                // Atualizar contador na interface se existir um elemento para isso
                const contadorSelecionadas = document.getElementById('contador-selecionadas');
                if (contadorSelecionadas) {
                    contadorSelecionadas.textContent = totalMarcados;
                    contadorSelecionadas.closest('.badge').style.display = totalMarcados > 0 ? '' : 'none';
                }
            });
            
            // Monitorar alterações em checkboxes individuais
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    // Adicionar/remover highlight visual na linha
                    const row = this.closest('tr');
                    if (this.checked) {
                        row.classList.add('table-success');
                    } else {
                        row.classList.remove('table-success');
                    }
                    
                    // Atualizar estado do "selecionar-todos"
                    atualizarEstadoSelecionarTodos();
                });
            });
            
            // Função para atualizar o estado do checkbox "selecionar-todos"
            function atualizarEstadoSelecionarTodos() {
                // Verificar apenas checkboxes disponíveis (com WhatsApp)
                const checkboxesDisponiveis = Array.from(checkboxes).filter(checkbox => {
                    const row = checkbox.closest('tr');
                    if (!row) return false;
                    
                    const whatsappCell = row.querySelector('td:nth-child(5)');
                    return whatsappCell && !whatsappCell.textContent.includes('Não cadastrado');
                });
                
                const totalDisponiveis = checkboxesDisponiveis.length;
                const totalChecked = checkboxesDisponiveis.filter(cb => cb.checked).length;
                
                console.log(`Estado atualizado: ${totalChecked}/${totalDisponiveis} apostas selecionadas`);
                
                // Atualizar contador na interface se existir
                const contadorSelecionadas = document.getElementById('contador-selecionadas');
                if (contadorSelecionadas) {
                    contadorSelecionadas.textContent = totalChecked;
                    contadorSelecionadas.closest('.badge').style.display = totalChecked > 0 ? '' : 'none';
                }
                
                // Atualizar estado do checkbox principal
                if (totalChecked === 0) {
                    selecionarTodos.checked = false;
                    selecionarTodos.indeterminate = false;
                } else if (totalChecked === totalDisponiveis) {
                    selecionarTodos.checked = true;
                    selecionarTodos.indeterminate = false;
                } else {
                    selecionarTodos.checked = false;
                    selecionarTodos.indeterminate = true;
                }
            }
            
            // Verificar estado inicial
            atualizarEstadoSelecionarTodos();
            
            // Adicionar dica visual para o usuário na coluna de título
            const headerCell = selecionarTodos.closest('th');
            if (headerCell) {
                headerCell.title = "Clique para selecionar todas as apostas com WhatsApp";
                headerCell.style.cursor = "pointer";
                
                // Adicionar um ícone de ajuda para informar o usuário
                const infoSpan = document.createElement('span');
                infoSpan.className = "text-primary ms-1";
                infoSpan.innerHTML = '<i class="fas fa-info-circle fa-xs"></i>';
                infoSpan.title = "Seleciona todas as apostas com WhatsApp cadastrado";
                headerCell.appendChild(infoSpan);
                
                // Adicionar um contador de selecionadas
                const contadorSpan = document.createElement('span');
                contadorSpan.className = "badge bg-success rounded-pill ms-2";
                contadorSpan.style.display = "none";
                contadorSpan.innerHTML = '<span id="contador-selecionadas">0</span>';
                headerCell.appendChild(contadorSpan);
            }
        } else {
            console.error("Elemento 'selecionar-todos' não encontrado na página!");
            // Log adicional para verificar se temos apostas na página
            const temApostas = document.querySelectorAll('.aposta-checkbox').length > 0;
            console.log(`Existem checkbox de apostas na página? ${temApostas ? 'Sim' : 'Não'}`);
            console.log(`Total de linhas na tabela: ${document.querySelectorAll('#tabela-apostas tbody tr').length}`);
            
            // Verificar se existe a tabela
            const tabelaApostas = document.getElementById('tabela-apostas');
            console.log(`Tabela de apostas encontrada? ${tabelaApostas ? 'Sim' : 'Não'}`);
            
            // Verificar se há mensagem de erro na página
            const mensagensErro = document.querySelectorAll('.alert-danger');
            if (mensagensErro.length > 0) {
                console.log("Mensagens de erro encontradas na página:");
                mensagensErro.forEach((msg, i) => {
                    console.log(`Erro ${i+1}: ${msg.textContent.trim()}`);
                });
            }
        }
    });

    // Função para enviar comprovante individual
    function enviarComprovanteIndividual(apostaId) {
        console.log("Enviando comprovante individual para aposta ID:", apostaId);
        
        // Limpar todas as seleções
        document.querySelectorAll('.aposta-checkbox').forEach(function(checkbox) {
            checkbox.checked = false;
        });
        
        // Selecionar apenas a aposta desejada
        const checkbox = document.querySelector('input[name="apostas[]"][value="' + apostaId + '"]');
        if (checkbox) {
            checkbox.checked = true;
            
            // Mostrar carregamento antes de submeter
            mostrarCarregamento(1);
            
            // Adicionar campo de ação para indicar que estamos enviando comprovantes
            let actionInput = document.querySelector('input[name="action"]');
            if (!actionInput) {
                actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'enviar_comprovantes';
                document.getElementById("form-enviar-comprovantes").appendChild(actionInput);
            } else {
                actionInput.value = 'enviar_comprovantes';
            }
            
            // Adicionar campo que sinaliza para abrir WhatsApp após processamento
            let openWhatsAppField = document.querySelector('input[name="open_whatsapp"]');
            if (!openWhatsAppField) {
                openWhatsAppField = document.createElement('input');
                openWhatsAppField.type = 'hidden';
                openWhatsAppField.name = 'open_whatsapp';
                openWhatsAppField.value = '1';
                document.getElementById("form-enviar-comprovantes").appendChild(openWhatsAppField);
            }
            
            // Registrar timestamp da ação do usuário para validação
            localStorage.setItem("last_post_action", Date.now());
            
            // Enviar o formulário manualmente
            document.getElementById('form-enviar-comprovantes').submit();
        } else {
            console.error("Checkbox para aposta ID " + apostaId + " não encontrado!");
            alert("Não foi possível enviar o comprovante. Tente novamente ou atualize a página.");
        }
        
        return false; // Impedir ação padrão do link
    }

    // Adicionando evento de input para filtro dinâmico
    document.addEventListener('DOMContentLoaded', function() {
        // Detectar se o dispositivo é móvel
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        console.log("Dispositivo móvel detectado:", isMobile);
        
        // Otimizações para dispositivos móveis
        if (isMobile) {
            // Ajustar tamanho dos botões para melhor usabilidade em telas touch
            document.querySelectorAll('.btn').forEach(btn => {
                if (!btn.classList.contains('btn-sm')) {
                    btn.classList.add('btn-lg'); 
                }
            });
            
            // Aumentar os checkboxes para melhor clicabilidade em telas touch
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.style.width = '24px';
                checkbox.style.height = '24px';
            });
        }
        
        const filtroInput = document.getElementById('filtro-apostador');
        if (filtroInput) {
            filtroInput.addEventListener('input', filtrarApostadores);
        }
        
        // Adicionar manipuladores de eventos para botões de cópia
        document.querySelectorAll(".btn-copy-text").forEach(btn => {
            btn.addEventListener("click", function() {
                const texto = this.getAttribute("data-text");
                copiarTexto(texto);
            });
        });

        // Verificar se devemos abrir o WhatsApp automaticamente
        var shouldOpenWhatsApp = <?php echo $abrir_whatsapp ? 'true' : 'false'; ?>;
        console.log("Abrir WhatsApp automaticamente:", shouldOpenWhatsApp);
        
        if (shouldOpenWhatsApp) {
            const whatsappButtons = document.querySelectorAll(".btn-whatsapp-send");
            if (whatsappButtons.length > 0) {
            console.log("Abrindo WhatsApp automaticamente após processamento...");
                
                // Pequeno atraso para garantir que a página esteja pronta
                setTimeout(() => {
                    try {
                        const firstButton = whatsappButtons[0];
                        console.log("Abrindo WhatsApp App para:", firstButton.href);
                        
                        // Tentar abrir diretamente
                        const whatsappWindow = window.open(firstButton.href, "_blank");
                        
                        // Verificar se o pop-up foi bloqueado
                        if (!whatsappWindow || whatsappWindow.closed || typeof whatsappWindow.closed === "undefined") {
                            // Se foi bloqueado, mostrar alerta com instruções claras
                            alert("ATENÇÃO: O navegador bloqueou a abertura do WhatsApp.\n\n" +
                                  "Para resolver:\n" +
                                  "1. Procure por um ícone de bloqueio na barra de endereço do navegador\n" +
                                  "2. Clique nele e permita pop-ups para este site\n" +
                                  "3. Depois, clique no botão 'Enviar via WhatsApp App' manualmente");
                            
                            // Destacar visualmente os botões do WhatsApp para ajudar o usuário
                            document.querySelectorAll(".btn-whatsapp-send").forEach(btn => {
                                btn.classList.add("btn-lg");
                                btn.classList.add("animate__animated");
                                btn.classList.add("animate__pulse");
                                btn.classList.add("animate__infinite");
                                btn.style.boxShadow = "0 0 15px rgba(37, 211, 102, 0.8)";
                            });
                        }
                        
                        // Limpar flags após tentativa
                        localStorage.removeItem("processando_whatsapp");
                    } catch (error) {
                        console.error("Erro ao abrir WhatsApp:", error);
                        alert("Ocorreu um erro ao tentar abrir o WhatsApp: " + error.message + "\n\nPor favor, clique manualmente no botão 'Enviar via WhatsApp App'");
                    }
                }, 1500);
            } else {
                console.error("Nenhum botão de WhatsApp encontrado na página após processamento");
                alert("Não foi possível encontrar os links do WhatsApp. Por favor, atualize a página e tente novamente.");
            }
        }
        
        // Corrigir botão "Voltar para a lista" no modal
        const botaoVoltarLista = document.querySelector('a[href="enviar_comprovantes_whatsapp.php"].btn');
        if (botaoVoltarLista) {
            botaoVoltarLista.addEventListener('click', function(e) {
                e.preventDefault();
                console.log("Botão Voltar para a lista clicado");
                window.location.href = "enviar_comprovantes_whatsapp.php";
            });
        }
    });
</script>

<?php
$content = ob_get_clean();

// Exibir botões para WhatsApp se existirem URLs salvas na sessão
if (isset($_SESSION['whatsapp_urls']) && !empty($_SESSION['whatsapp_urls'])) {
    $total_apostadores = count($_SESSION['whatsapp_urls']);
    $total_apostas = 0;
    foreach ($_SESSION['whatsapp_urls'] as $url_data) {
        $total_apostas += isset($url_data['apostas']) ? intval($url_data['apostas']) : 0;
    }
    
    // Adicionar painel de guia/ajuda
    $content .= '
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-info-circle"></i> Guia de Envio de Comprovantes
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> Prontos para Envio</h5>
                        <p class="mb-0">Foram preparados <strong>' . $total_apostas . '</strong> comprovantes para <strong>' . $total_apostadores . '</strong> apostador(es).</p>
                        <hr>
                        <div class="text-center">
                            <span class="badge bg-success rounded-pill"><i class="fas fa-thumbs-up"></i> Tudo pronto!</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-primary">
                        <h5><i class="fas fa-question-circle"></i> Como Enviar</h5>
                        <ol class="small mb-0">
                            <li>Clique no botão verde <strong>"Enviar via WhatsApp Web"</strong></li>
                            <li>O WhatsApp Web abrirá em nova aba</li>
                            <li>Clique no botão "Enviar" do WhatsApp</li>
                            <li>Volte para esta tela e repita para outros apostadores</li>
                        </ol>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Problemas Comuns</h5>
                        <ul class="small mb-0">
                            <li>Bloqueio de pop-ups: Permita pop-ups neste site</li>
                            <li>WhatsApp Web desconectado: Faça login antes</li>
                            <li>Número incorreto: Verifique o cadastro do apostador</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>';
    
    // Continuar com o código original para exibir os botões
    $content .= '
    <div class="card mt-4" id="whatsapp-buttons">
        <div class="card-header bg-success text-white">
            <i class="fab fa-whatsapp"></i> Comprovantes prontos para envio
        </div>
        <div class="card-body">
            <p class="alert alert-info">
                <i class="fas fa-info-circle"></i> Se os botões não abrirem o WhatsApp automaticamente, 
                tente as opções alternativas ou copie a mensagem e envie manualmente.
            </p>';
            
    // Barra de busca e filtro para muitos comprovantes
    if (count($_SESSION['whatsapp_urls']) > 3) {
        $content .= '
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" id="filtro-apostador" class="form-control" placeholder="Buscar apostador...">
                        <button class="btn btn-outline-secondary" type="button" onclick="filtrarApostadores()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <span class="badge bg-primary rounded-pill" id="contador-apostadores">
                        ' . count($_SESSION['whatsapp_urls']) . ' apostadores
                    </span>
                </div>
            </div>';
    }
            
    $content .= '<div class="row" id="lista-comprovantes">';
    
    foreach ($_SESSION['whatsapp_urls'] as $index => $url_data) {
        // Garantir que temos todas as chaves necessárias
        $url_alt = isset($url_data['url_alt']) ? $url_data['url_alt'] : $url_data['url'];
        $texto = isset($url_data['texto']) ? $url_data['texto'] : "Comprovante de aposta";
        $telefone = isset($url_data['telefone']) ? $url_data['telefone'] : "";
        
        // Verificar se o telefone está correto e reconstruir a URL se necessário
        if (!empty($telefone) && (strpos($url_data['url'], $telefone) === false || strpos($url_data['url'], '5531111111111111') !== false)) {
            // A URL não contém o número correto, vamos reconstruí-la
            error_log("Corrigindo URL do WhatsApp para {$url_data['nome']}. URL atual não contém o número correto.");
            $texto_mensagem = urlencode($texto);
            $url_data['url'] = "https://wa.me/{$telefone}?text={$texto_mensagem}";
            $url_data['url_alt'] = "https://web.whatsapp.com/send?phone={$telefone}&text={$texto_mensagem}";
            
            // Atualizar na sessão
            $_SESSION['whatsapp_urls'][$index]['url'] = $url_data['url'];
            $_SESSION['whatsapp_urls'][$index]['url_alt'] = $url_data['url_alt'];
        }
        
        $content .= '
                <div class="col-md-6 mb-4 card-apostador" data-nome="' . strtolower(htmlspecialchars($url_data['nome'])) . '">
                    <div class="card h-100 border-success">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                            <strong>' . htmlspecialchars($url_data['nome']) . '</strong> 
                            <span class="badge bg-primary">' . $url_data['apostas'] . ' aposta' . ($url_data['apostas'] > 1 ? 's' : '') . '</span>
                            </div>
                            <span class="badge bg-success rounded-pill whatsapp-badge">
                                <i class="fab fa-whatsapp"></i> ' . htmlspecialchars(formatarTelefone($url_data['telefone'])) . '
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="' . htmlspecialchars($url_data['url']) . '" target="_blank" class="btn btn-success btn-lg btn-whatsapp-send mb-2">
                                    <i class="fab fa-whatsapp"></i> Enviar via WhatsApp App
                                </a>
                                <a href="' . htmlspecialchars($url_alt) . '" target="_blank" class="btn btn-outline-success btn-whatsapp-alt mb-2">
                                    <i class="fab fa-whatsapp"></i> Alternativa: WhatsApp Web
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-copy-text" 
                                        data-text="' . htmlspecialchars(str_replace('"', '&quot;', $texto)) . '">
                                    <i class="far fa-copy"></i> Copiar mensagem
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm mt-2" onclick="toggleMensagem(this)">
                                    <i class="fas fa-eye"></i> Visualizar mensagem
                                </button>
                                <div class="mensagem-preview mt-2" style="display:none;">
                                    <div class="card">
                                        <div class="card-body bg-light small">
                                            <pre class="mb-0" style="white-space: pre-wrap;">' . htmlspecialchars($texto) . '</pre>
                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-light text-center">
                            <small class="text-muted">Gerado em: ' . date('d/m/Y H:i') . '</small>
                        </div>
                    </div>
                </div>';
    }
    
    $content .= '
            </div>
            <div class="mt-3">
                <div class="alert alert-warning">
                    <strong>Dica:</strong> Se os botões não funcionarem, você pode:
                    <ol>
                        <li>Verificar se o bloqueador de pop-ups do navegador está desativado</li>
                        <li>Abrir o WhatsApp Web em outra aba antes de clicar nos botões</li>
                        <li>Usar a opção "Copiar mensagem" e colar diretamente no WhatsApp</li>
                    </ol>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <button type="button" onclick="abrirTodosWhatsApp(); return false;" class="btn btn-primary mb-3 w-100">
                            <i class="fas fa-external-link-alt"></i> Abrir Todos via WhatsApp Web
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button type="button" onclick="abrirTodosWhatsAppAlt(); return false;" class="btn btn-outline-primary mb-3 w-100">
                            <i class="fas fa-external-link-alt"></i> Alternativa: WhatsApp App
                        </button>
                    </div>
                </div>
                
                <form method="POST" action="enviar_comprovantes_whatsapp.php">
                    <button type="submit" class="btn btn-secondary">Limpar lista de envio</button>
                    <input type="hidden" name="limpar_lista" value="1">
                </form>
            </div>
        </div>
    </div>';
}

// Limpar a lista de envio se solicitado
if (isset($_POST['limpar_lista'])) {
    unset($_SESSION['whatsapp_urls']);
    $_SESSION['mensagem_sucesso'] = "Lista de envio limpa com sucesso!";
    header("Location: enviar_comprovantes_whatsapp.php");
    exit;
}

// Atualizar estrutura de dados antiga se necessário
if (isset($_SESSION['whatsapp_urls']) && !empty($_SESSION['whatsapp_urls'])) {
    // Verificar se há URLs com números de telefone incorretos (para testes ou corrompidos)
    foreach ($_SESSION['whatsapp_urls'] as $key => $url_data) {
        // Verificar se o telefone parece ser um número de teste
        if (isset($url_data['telefone']) && (
            $url_data['telefone'] == '5531111111111111' || 
            $url_data['telefone'] == '553111111111111' || 
            $url_data['telefone'] == '55311111111111' ||
            preg_match('/11111/', $url_data['telefone'])
        )) {
            // Remover esse item ou corrigir baseado nos outros dados
            error_log("Detectado número de telefone de teste: {$url_data['telefone']} para {$url_data['nome']}. Removendo da lista.");
            unset($_SESSION['whatsapp_urls'][$key]);
        }
        
        // Verificar e adicionar campos que faltam para compatibilidade
        if (isset($_SESSION['whatsapp_urls'][$key]) && !isset($url_data['url_alt'])) {
            $telefone = $url_data['telefone'];
            $texto_mensagem = "";
            
            // Se não temos o texto original, criamos com base nas informações disponíveis
            if (isset($url_data['url'])) {
                // Tentar extrair o texto da URL original
                $url_parts = parse_url($url_data['url']);
                if (isset($url_parts['query'])) {
                    parse_str($url_parts['query'], $query_params);
                    $texto_mensagem = isset($query_params['text']) ? $query_params['text'] : '';
                }
            }
            
            $_SESSION['whatsapp_urls'][$key]['url_alt'] = "https://wa.me/{$telefone}?text={$texto_mensagem}";
            
            // Se ainda não temos o texto, criamos um genérico
            if (!isset($url_data['texto'])) {
                $_SESSION['whatsapp_urls'][$key]['texto'] = "✅ *COMPROVANTES DE APOSTAS* ✅\n\nSegue comprovante de aposta para {$url_data['nome']}.";
            }
        }
    }
    
    // Se todos foram removidos, limpar a sessão completamente
    if (empty($_SESSION['whatsapp_urls'])) {
        unset($_SESSION['whatsapp_urls']);
        $_SESSION['erro'] = "Todos os números de WhatsApp foram considerados inválidos. Por favor, tente novamente ou verifique os números cadastrados.";
    }
}

// Adicionar botão de emergência caso o modal ainda apareça acidentalmente
if (isset($_SESSION['whatsapp_urls']) || isset($_SESSION['redirect_whatsapp'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show mb-0 position-fixed w-100" style="z-index:9999; top:0; left:0;">
        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Detecção de Modal Ativo!</h4>
        <p>Se você ainda estiver vendo o modal de WhatsApp, use um dos botões abaixo para encerrar o processo:</p>
        <div class="d-flex gap-2 mb-2">
            <a href="fechar_modal.php" class="btn btn-danger">
                <i class="fas fa-times-circle"></i> Fechar Modal Forçadamente
            </a>
            <a href="enviar_comprovantes_whatsapp.php?reset=1" class="btn btn-warning">
                <i class="fas fa-sync"></i> Recarregar Página Limpa
            </a>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>';
}

// Função para formatar número de telefone para exibição
function formatarTelefone($telefone) {
    // Remover o código do país (55) para exibição
    if (substr($telefone, 0, 2) == '55') {
        $telefone = substr($telefone, 2);
    }
    
    // Formatar como (XX) XXXXX-XXXX
    if (strlen($telefone) == 11) { // Celular com 9 dígitos
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    } elseif (strlen($telefone) == 10) { // Telefone fixo
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
    } else {
        return $telefone; // Retornar como está se não conseguir formatar
    }
}

// Incluir o layout com o conteúdo
include 'includes/layout.php';

// Mostrar avisos sobre apostadores sem WhatsApp, se houver
if (isset($_SESSION['apostadores_sem_whatsapp']) && !empty($_SESSION['apostadores_sem_whatsapp'])) {
    echo '
    <div class="modal fade" id="modalApostadoresSemWhatsApp" tabindex="-1" aria-labelledby="modalApostadoresSemWhatsAppLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-warning">
            <h5 class="modal-title" id="modalApostadoresSemWhatsAppLabel">
                <i class="fas fa-exclamation-triangle"></i> Apostadores sem WhatsApp
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <p>Os seguintes apostadores não têm número de WhatsApp cadastrado e não receberão comprovantes:</p>
            <ul>';
    
    foreach (array_unique($_SESSION['apostadores_sem_whatsapp']) as $apostador) {
        echo '<li>' . htmlspecialchars($apostador) . '</li>';
    }
    
    echo '
            </ul>
            <p>Para cadastrar ou atualizar o número de WhatsApp de um apostador, vá para a seção <strong>Meus Clientes</strong>.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendi</button>
          </div>
        </div>
      </div>
    </div>
    
    <script>
        // Mostrar modal ao carregar a página
        document.addEventListener("DOMContentLoaded", function() {
            const modal = new bootstrap.Modal(document.getElementById("modalApostadoresSemWhatsApp"));
            modal.show();
        });
    </script>';
    
    // Limpar a lista após exibir
    unset($_SESSION['apostadores_sem_whatsapp']);
}

// Scripts para gerenciar WhatsApp
if (isset($_SESSION['whatsapp_urls']) && !empty($_SESSION['whatsapp_urls'])) {
    // Definir a variável para controlar a abertura automática dos links
    $abrir_whatsapp = isset($_SESSION['comprovantes_processados']) && $_SESSION['comprovantes_processados'] === true;
    
    // Limpar flag de processamento após uso
    if (isset($_SESSION['comprovantes_processados'])) {
        unset($_SESSION['comprovantes_processados']);
    }
    
    // Adicionar a biblioteca Animate.css para efeitos visuais dos botões
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />';
    
    // Se houver URL de redirecionamento do WhatsApp, mostrar um modal com instruções
    if (isset($_SESSION['redirect_whatsapp']) && !empty($_SESSION['redirect_whatsapp'])) {
        $whatsapp_url = htmlspecialchars($_SESSION['redirect_whatsapp']);
        $whatsapp_alt_url = isset($_SESSION['whatsapp_urls'][0]['url_alt']) ? 
                          htmlspecialchars($_SESSION['whatsapp_urls'][0]['url_alt']) : 
                          str_replace('wa.me', 'web.whatsapp.com/send', $whatsapp_url);
        
        echo '<div class="modal fade" id="modalWhatsAppRedirect" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fab fa-whatsapp"></i> Enviar Comprovantes por WhatsApp
                </h5>
              </div>
              <div class="modal-body">
                <div class="alert alert-success">
                    <h4 class="mb-3"><i class="fas fa-check-circle"></i> Comprovantes processados com sucesso!</h4>
                    <p>Clique no botão abaixo para abrir o WhatsApp e enviar os comprovantes:</p>
                </div>
                
                <div class="text-center mb-4">
                    <a href="' . $whatsapp_url . '" class="btn btn-success btn-lg animate__animated animate__pulse animate__infinite" target="_blank">
                        <i class="fab fa-whatsapp fa-lg me-2"></i> Abrir WhatsApp App
                    </a>
                    
                    <div class="mt-3">
                        <small class="text-muted">Se o botão acima não funcionar, tente:</small><br>
                        <a href="' . $whatsapp_alt_url . '" class="btn btn-outline-success mt-2" target="_blank">
                            <i class="fab fa-whatsapp"></i> Alternativa: WhatsApp Web
                        </a>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> Se os botões não funcionarem:</h5>
                    <ol>
                        <li>Verifique se seu navegador permite pop-ups para este site</li>
                        <li>Copie o link abaixo e cole na barra de endereços:</li>
                    </ol>
                    <div class="input-group">
                        <input type="text" class="form-control" value="' . $whatsapp_url . '" id="whatsappUrlField" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copiarURL()">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>
                    <script>
                        function copiarURL() {
                            var copyText = document.getElementById("whatsappUrlField");
                            copyText.select();
                            copyText.setSelectionRange(0, 99999);
                            document.execCommand("copy");
                            alert("Link copiado para a área de transferência!");
                        }
                    </script>
                </div>
              </div>
              <div class="modal-footer">
                <a href="enviar_comprovantes_whatsapp.php" class="btn btn-secondary">Voltar para a lista</a>
              </div>
            </div>
          </div>
        </div>';
        
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var modal = new bootstrap.Modal(document.getElementById("modalWhatsAppRedirect"));
                modal.show();
                
                // Tentativa automática de abrir o WhatsApp após 1 segundo
                setTimeout(function() {
                    window.open("' . $whatsapp_url . '", "_blank");
                }, 1000);
            });
        </script>';
    }
    
    // Scripts em JavaScript separados do PHP
    ?>
    <script>
        // Função para abrir todos os links do WhatsApp Web
        function abrirTodosWhatsApp() {
            const links = document.querySelectorAll(".btn-whatsapp-send");
            if (links && links.length > 0) {
                console.log("Tentando abrir todos os links do WhatsApp App (" + links.length + " encontrados)");
                
                // Detectar se estamos em um dispositivo móvel
                const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                
                if (isMobile) {
                    // Em dispositivos móveis, abrimos apenas o primeiro link para evitar comportamentos inesperados
                    alert("Em dispositivos móveis, os comprovantes serão enviados um por vez para evitar problemas com o WhatsApp.");
                    window.location.href = links[0].href;
                    return false;
                }
                
                // Em desktop, continuamos com o comportamento anterior
                // Tentar abrir o primeiro link para verificar se o navegador permite pop-ups
                const testWindow = window.open(links[0].href, "_blank");
                
                // Verificar se o pop-up foi bloqueado
                if (!testWindow || testWindow.closed || typeof testWindow.closed === "undefined") {
                    // Se foi bloqueado, mostrar alerta com instruções claras
                    alert("ATENÇÃO: O navegador bloqueou a abertura do WhatsApp.\n\n" +
                          "Para resolver:\n" +
                          "1. Procure por um ícone de bloqueio na barra de endereço do navegador\n" +
                          "2. Clique nele e permita pop-ups para este site\n" +
                          "3. Depois, tente novamente");
                    
                    // Destacar visualmente os botões do WhatsApp para ajudar o usuário
                    document.querySelectorAll(".btn-whatsapp-send").forEach(btn => {
                        btn.classList.add("btn-lg");
                        btn.classList.add("animate__animated");
                        btn.classList.add("animate__pulse");
                        btn.classList.add("animate__infinite");
                        btn.style.boxShadow = "0 0 15px rgba(37, 211, 102, 0.8)";
                    });
                    
                    return false;
                }
                
                // Se chegou aqui, conseguiu abrir o primeiro pop-up, então continuamos com os demais
                for (let i = 1; i < links.length; i++) {
                    ((index) => {
                        setTimeout(() => {
                            console.log("Abrindo WhatsApp App " + (index + 1) + " de " + links.length);
                            window.open(links[index].href, "_blank");
                        }, index * 2000); // 2 segundos entre cada abertura
                    })(i);
                }
            } else {
                console.error("Nenhum link do WhatsApp encontrado");
                alert("Nenhum link do WhatsApp encontrado. Por favor, atualize a página e tente novamente.");
            }
            return false;
        }
        
        // Função para abrir todos os links do WhatsApp alternativo
        function abrirTodosWhatsAppAlt() {
            const links = document.querySelectorAll(".btn-whatsapp-alt");
            if (links && links.length > 0) {
                console.log("Tentando abrir todos os links alternativos do WhatsApp Web (" + links.length + " encontrados)");
                
                // Detectar se estamos em um dispositivo móvel
                const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                
                if (isMobile) {
                    // Em dispositivos móveis, não recomendamos o uso do WhatsApp Web
                    alert("Em dispositivos móveis, recomendamos usar o aplicativo WhatsApp em vez do WhatsApp Web.");
                    return false;
                }
                
                // Em desktop, continuamos com o comportamento anterior
                // ... resto do código existente ...
            }
        }

        // Função para copiar texto para a área de transferência
        function copiarTexto(texto) {
            // Criar um elemento temporário
            const el = document.createElement("textarea");
            el.value = texto;
            document.body.appendChild(el);
            el.select();
            document.execCommand("copy");
            document.body.removeChild(el);
            
            // Feedback visual simples
            alert("Mensagem copiada para a área de transferência");
        }
        
        // Função para exibir/esconder a mensagem
        function toggleMensagem(button) {
            const preview = button.nextElementSibling;
            if (preview.style.display === 'none') {
                preview.style.display = 'block';
                button.innerHTML = '<i class="fas fa-eye-slash"></i> Ocultar mensagem';
            } else {
                preview.style.display = 'none';
                button.innerHTML = '<i class="fas fa-eye"></i> Visualizar mensagem';
            }
        }
        
        // Função para filtrar apostadores
        function filtrarApostadores() {
            const termo = document.getElementById('filtro-apostador').value.toLowerCase();
            const cards = document.querySelectorAll('.card-apostador');
            let contadorVisivel = 0;
            
            cards.forEach(card => {
                const nome = card.getAttribute('data-nome');
                if (nome.includes(termo)) {
                    card.style.display = 'block';
                    contadorVisivel++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Atualizar contador
            document.getElementById('contador-apostadores').textContent = contadorVisivel + ' apostador' + (contadorVisivel !== 1 ? 'es' : '');
        }
        
        // Adicionando evento de input para filtro dinâmico
        document.addEventListener('DOMContentLoaded', function() {
            // Detectar se o dispositivo é móvel
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            console.log("Dispositivo móvel detectado:", isMobile);
            
            // Otimizações para dispositivos móveis
            if (isMobile) {
                // Ajustar tamanho dos botões para melhor usabilidade em telas touch
                document.querySelectorAll('.btn').forEach(btn => {
                    if (!btn.classList.contains('btn-sm')) {
                        btn.classList.add('btn-lg'); 
                    }
                });
                
                // Aumentar os checkboxes para melhor clicabilidade em telas touch
                document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                    checkbox.style.width = '24px';
                    checkbox.style.height = '24px';
                });
            }
            
            const filtroInput = document.getElementById('filtro-apostador');
            if (filtroInput) {
                filtroInput.addEventListener('input', filtrarApostadores);
            }
            
            // Adicionar manipuladores de eventos para botões de cópia
            document.querySelectorAll(".btn-copy-text").forEach(btn => {
                btn.addEventListener("click", function() {
                    const texto = this.getAttribute("data-text");
                    copiarTexto(texto);
                });
            });

            // Verificar se devemos abrir o WhatsApp automaticamente
            var shouldOpenWhatsApp = <?php echo $abrir_whatsapp ? 'true' : 'false'; ?>;
            console.log("Abrir WhatsApp automaticamente:", shouldOpenWhatsApp);
            
            if (shouldOpenWhatsApp) {
                const whatsappButtons = document.querySelectorAll(".btn-whatsapp-send");
                if (whatsappButtons.length > 0) {
                console.log("Abrindo WhatsApp automaticamente após processamento...");
                    
                    // Pequeno atraso para garantir que a página esteja pronta
                    setTimeout(() => {
                        try {
                            const firstButton = whatsappButtons[0];
                            console.log("Abrindo WhatsApp App para:", firstButton.href);
                            
                            // Tentar abrir diretamente
                            const whatsappWindow = window.open(firstButton.href, "_blank");
                            
                            // Verificar se o pop-up foi bloqueado
                            if (!whatsappWindow || whatsappWindow.closed || typeof whatsappWindow.closed === "undefined") {
                                // Se foi bloqueado, mostrar alerta com instruções claras
                                alert("ATENÇÃO: O navegador bloqueou a abertura do WhatsApp.\n\n" +
                                      "Para resolver:\n" +
                                      "1. Procure por um ícone de bloqueio na barra de endereço do navegador\n" +
                                      "2. Clique nele e permita pop-ups para este site\n" +
                                      "3. Depois, clique no botão 'Enviar via WhatsApp App' manualmente");
                                
                                // Destacar visualmente os botões do WhatsApp para ajudar o usuário
                                document.querySelectorAll(".btn-whatsapp-send").forEach(btn => {
                                    btn.classList.add("btn-lg");
                                    btn.classList.add("animate__animated");
                                    btn.classList.add("animate__pulse");
                                    btn.classList.add("animate__infinite");
                                    btn.style.boxShadow = "0 0 15px rgba(37, 211, 102, 0.8)";
                                });
                            }
                            
                            // Limpar flags após tentativa
                            localStorage.removeItem("processando_whatsapp");
                        } catch (error) {
                            console.error("Erro ao abrir WhatsApp:", error);
                            alert("Ocorreu um erro ao tentar abrir o WhatsApp: " + error.message + "\n\nPor favor, clique manualmente no botão 'Enviar via WhatsApp App'");
                        }
                    }, 1500);
                } else {
                    console.error("Nenhum botão de WhatsApp encontrado na página após processamento");
                    alert("Não foi possível encontrar os links do WhatsApp. Por favor, atualize a página e tente novamente.");
                }
            }
            
            // Corrigir botão "Voltar para a lista" no modal
            const botaoVoltarLista = document.querySelector('a[href="enviar_comprovantes_whatsapp.php"].btn');
            if (botaoVoltarLista) {
                botaoVoltarLista.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log("Botão Voltar para a lista clicado");
                    window.location.href = "enviar_comprovantes_whatsapp.php";
                });
            }
        });
    </script>
    <?php
}
?> 