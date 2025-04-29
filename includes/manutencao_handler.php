<?php
/**
 * Manipulador central de manutenção para todo o sistema
 * Este arquivo gerencia a lógica de verificação e exibição do modo de manutenção
 */

// Garantir que a sessão esteja iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função para carregar o arquivo de banco de dados
function carregarDatabaseConfig() {
    // Tentativa com caminho absoluto
    $caminho_absoluto = $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
    if (file_exists($caminho_absoluto)) {
        error_log('[Manutenção] Carregando database.php com caminho absoluto: ' . $caminho_absoluto);
        require_once $caminho_absoluto;
        return true;
    }
    
    // Primeira tentativa com caminho relativo (um nível acima)
    $caminho_relativo1 = __DIR__ . '/../config/database.php';
    if (file_exists($caminho_relativo1)) {
        error_log('[Manutenção] Carregando database.php com caminho relativo 1: ' . $caminho_relativo1);
        require_once $caminho_relativo1;
        return true;
    }
    
    // Segunda tentativa com caminho relativo (dois níveis acima)
    $caminho_relativo2 = __DIR__ . '/../../config/database.php';
    if (file_exists($caminho_relativo2)) {
        error_log('[Manutenção] Carregando database.php com caminho relativo 2: ' . $caminho_relativo2);
        require_once $caminho_relativo2;
        return true;
    }
    
    // Terceira tentativa com caminho relativo direto
    error_log('[Manutenção] Tentando último recurso com caminho relativo direto');
    require_once '../config/database.php';
    return true;
}

// Função para verificar se o modo de manutenção está ativo
function verificarModoManutencao() {
    // Não bloquear acesso para admins
    if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin') {
        error_log('[Manutenção] Usuário admin detectado, ignorando modo manutenção');
        return false;
    }

    try {
        // Log para debug
        error_log('[Manutenção] Verificando status do modo manutenção');
        
        // Carregar configuração do banco de dados
        carregarDatabaseConfig();
        
        $database = new Database();
        $pdo = $database->getConnection();
        
        // Verificar se o modo manutenção está ativo
        $stmt = $pdo->query("SELECT modo_manutencao FROM configuracoes WHERE id = 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $resultado = (isset($config['modo_manutencao']) && $config['modo_manutencao'] == 1);
        error_log('[Manutenção] Status do modo manutenção: ' . ($resultado ? 'ATIVO' : 'INATIVO'));
        
        // Retorna true se o modo manutenção estiver ativo
        return $resultado;
    } catch (Exception $e) {
        error_log("[Manutenção] ERRO ao verificar modo de manutenção: " . $e->getMessage());
        return false; // Em caso de erro, permite acesso
    }
}

// Função para obter a mensagem de manutenção
function obterMensagemManutencao() {
    $mensagem_padrao = 'Sistema em manutenção. Por favor, tente novamente mais tarde.';
    
    try {
        // Log para debug
        error_log('[Manutenção] Obtendo mensagem de manutenção');
        
        // Carregar configuração do banco de dados
        carregarDatabaseConfig();
        
        $database = new Database();
        $pdo = $database->getConnection();
        
        // Buscar a mensagem de manutenção no banco de dados
        $stmt = $pdo->query("SELECT mensagem_manutencao FROM configuracoes WHERE id = 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Retorna a mensagem personalizada ou a mensagem padrão
        if ($config && !empty($config['mensagem_manutencao'])) {
            error_log('[Manutenção] Mensagem personalizada encontrada');
            return $config['mensagem_manutencao'];
        } else {
            error_log('[Manutenção] Usando mensagem padrão');
            return $mensagem_padrao;
        }
    } catch (Exception $e) {
        error_log("[Manutenção] ERRO ao obter mensagem de manutenção: " . $e->getMessage());
        return $mensagem_padrao;
    }
}

// Função para renderizar a página de manutenção
function exibirPaginaManutencao($area = 'geral') {
    error_log('[Manutenção] Exibindo página de manutenção para área: ' . $area);
    
    // Obter a mensagem de manutenção
    $mensagem_manutencao = obterMensagemManutencao();
    
    // Define a URL de login baseada na área
    switch ($area) {
        case 'apostador':
            $login_url = '/apostador/login.php';
            $assets_prefix = '/apostador/';
            $ajax_url = '/apostador/manutencao.php';
            break;
        case 'revendedor':
            $login_url = '/revendedor/login.php';
            $assets_prefix = '/revendedor/';
            $ajax_url = '/revendedor/manutencao.php';
            break;
        case 'admin':
            $login_url = '/admin/login.php';
            $assets_prefix = '/admin/';
            $ajax_url = '/admin/manutencao.php';
            break;
        default:
            $login_url = '/login.php';
            $assets_prefix = '/';
            $ajax_url = '/manutencao.php';
            break;
    }
    
    error_log('[Manutenção] URLs configuradas: login=' . $login_url . ', assets=' . $assets_prefix . ', ajax=' . $ajax_url);
    
    // Buffer de saída para retornar o HTML
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema em Manutenção</title>
    <link rel="stylesheet" href="<?php echo $assets_prefix; ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        .maintenance-container {
            max-width: 600px;
            padding: 40px;
            text-align: center;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .maintenance-icon {
            font-size: 80px;
            color: #f6c23e;
            margin-bottom: 30px;
        }
        h1 {
            color: #4e73df;
            margin-bottom: 20px;
        }
        p {
            color: #6c757d;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn-login {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            color: white;
        }
        .refresh-message {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            display: none;
        }
    </style>
    <!-- Adicionar meta refresh para forçar atualização a cada 5 segundos -->
    <meta http-equiv="refresh" content="5">
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>
        <h1>Sistema em Manutenção</h1>
        <p><?php echo htmlspecialchars($mensagem_manutencao); ?></p>
        <a href="<?php echo $login_url; ?>" class="btn btn-login">
            <i class="fas fa-sign-in-alt me-2"></i> Área de Login
        </a>
    </div>
    
    <div class="refresh-message">
        Verificando status do sistema...
    </div>

    <script>
    // Função para verificar o status do modo de manutenção
    function checkMaintenanceStatus() {
        const refreshMessage = document.querySelector('.refresh-message');
        refreshMessage.style.display = 'block';
        
        // Adicionar um timestamp para evitar cache
        const timestamp = new Date().getTime();
        
        fetch('<?php echo $ajax_url; ?>?check_status=ajax&t=' + timestamp)
            .then(response => response.json())
            .then(data => {
                refreshMessage.style.display = 'none';
                
                // Se o modo de manutenção for desativado, redirecionar para a página inicial
                if (data.modo_manutencao == 0) {
                    window.location.href = '<?php echo $assets_prefix; ?>';
                }
            })
            .catch(error => {
                console.error('Erro ao verificar status:', error);
                refreshMessage.style.display = 'none';
            });
    }

    // Verificar status a cada 5 segundos
    setInterval(checkMaintenanceStatus, 5000);
    
    // Verificar status na carga inicial imediatamente
    setTimeout(checkMaintenanceStatus, 500);
    </script>
</body>
</html>
    <?php
    return ob_get_clean();
}

// Endpoint AJAX para verificar o status do modo de manutenção
function verificarStatusManutencaoAjax() {
    if (isset($_GET['check_status']) && $_GET['check_status'] == 'ajax') {
        error_log('[Manutenção] Processando requisição AJAX para verificar status');
        
        try {
            // Carregar configuração do banco de dados
            carregarDatabaseConfig();
            
            $database = new Database();
            $pdo = $database->getConnection();
            
            $stmt = $pdo->query("SELECT modo_manutencao FROM configuracoes WHERE id = 1");
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode(['modo_manutencao' => $config['modo_manutencao'], 'time' => time()]);
            error_log('[Manutenção] Resposta AJAX enviada: modo_manutencao=' . $config['modo_manutencao']);
            exit;
        } catch (Exception $e) {
            error_log('[Manutenção] ERRO na requisição AJAX: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
    return false;
}
?> 