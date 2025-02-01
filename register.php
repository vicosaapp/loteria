<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

$erro = '';
$sucesso = '';

// Capturar parâmetros de redirecionamento (caso venha da página de jogos)
$redirect = $_GET['redirect'] ?? '';
$jogo_id = $_GET['jogo_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $whatsapp = $_POST['whatsapp'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';
    
    try {
        // Validações
        if (strlen($senha) < 6) {
            throw new Exception("A senha deve ter pelo menos 6 caracteres");
        }
        
        if ($senha !== $confirma_senha) {
            throw new Exception("As senhas não conferem");
        }
        
        // Verificar se email já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Este email já está cadastrado");
        }
        
        // Criar usuário
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, telefone, whatsapp, senha, tipo) 
            VALUES (?, ?, ?, ?, ?, 'usuario')
        ");
        $stmt->execute([$nome, $email, $telefone, $whatsapp, $senha_hash]);
        
        // Fazer login automático
        $usuario_id = $pdo->lastInsertId();
        $_SESSION['usuario_id'] = $usuario_id;
        $_SESSION['nome'] = $nome;
        $_SESSION['tipo'] = 'usuario';
        
        // Verificar redirecionamento
        if ($redirect == 'apostador/fazer_aposta' && $jogo_id) {
            header("Location: /apostador/fazer_aposta.php?jogo_id=" . $jogo_id);
            exit;
        }
        
        // Redirecionar para o dashboard do apostador
        header("Location: /apostador/dashboard.php");
        exit;
        
    } catch(Exception $e) {
        $erro = $e->getMessage();
    }
}

$pageTitle = 'Cadastro - Sistema de Loteria';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }
        
        .register-container {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
        }
        
        .register-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }
        
        .register-header {
            padding: 2rem;
            text-align: center;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .register-header h1 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .register-header p {
            color: var(--secondary-color);
            font-size: 0.875rem;
        }
        
        .register-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn-register {
            width: 100%;
            padding: 0.875rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-register:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .register-footer {
            padding: 1.5rem 2rem;
            text-align: center;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        
        .register-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .register-footer a:hover {
            color: var(--primary-dark);
        }
        
        .erro-mensagem {
            background: #fee2e2;
            color: var(--danger-color);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sucesso-mensagem {
            background: #dcfce7;
            color: var(--success-color);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        @media (max-width: 640px) {
            .register-container {
                padding: 1rem;
            }
            
            .register-header,
            .register-body,
            .register-footer {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1>Criar Conta</h1>
                <p>Preencha os dados para se cadastrar</p>
            </div>
            
            <div class="register-body">
                <?php if ($erro): ?>
                    <div class="erro-mensagem">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <?php echo $erro; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($sucesso): ?>
                    <div class="sucesso-mensagem">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <?php echo $sucesso; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="register.php<?php echo ($jogo_id ? "?redirect=apostador/fazer_aposta&jogo_id=$jogo_id" : ''); ?>">
                    <div class="form-group">
                        <label for="nome">Nome Completo</label>
                        <input type="text" id="nome" name="nome" class="form-control" required 
                               placeholder="Seu nome completo" value="<?php echo $_POST['nome'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required 
                               placeholder="Seu melhor email" value="<?php echo $_POST['email'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="tel" id="telefone" name="telefone" class="form-control"
                               placeholder="(00) 00000-0000" value="<?php echo $_POST['telefone'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="whatsapp">WhatsApp</label>
                        <input type="tel" id="whatsapp" name="whatsapp" class="form-control"
                               placeholder="(00) 00000-0000" value="<?php echo $_POST['whatsapp'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" class="form-control" required 
                               placeholder="Mínimo 6 caracteres">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirma_senha">Confirmar Senha</label>
                        <input type="password" id="confirma_senha" name="confirma_senha" class="form-control" required 
                               placeholder="Digite a senha novamente">
                    </div>
                    
                    <button type="submit" class="btn-register">
                        Criar Conta
                    </button>
                </form>
            </div>
            
            <div class="register-footer">
                <p>Já tem uma conta? <a href="login.php">Fazer Login</a></p>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/imask"></script>
    <script>
        // Máscara para o campo de WhatsApp
        IMask(document.getElementById('whatsapp'), {
            mask: '(00) 00000-0000'
        });
    </script>
</body>
</html> 