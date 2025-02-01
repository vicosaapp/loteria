<?php
require_once 'config/database.php';
session_start(); // Garantir que a sessão está iniciada
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Se já estiver logado, redireciona
if (isset($_SESSION['usuario_id'])) {
    $redirect = $_GET['redirect'] ?? '';
    $jogo_id = $_GET['jogo_id'] ?? '';
    
    if ($redirect == 'apostador/fazer_aposta' && $jogo_id) {
        header("Location: ./apostador/fazer_aposta.php?jogo_id=" . $jogo_id);
    } else {
        header("Location: ./index.php");
    }
    exit;
}

// Capturar parâmetros de redirecionamento
$redirect = $_GET['redirect'] ?? '';
$jogo_id = $_GET['jogo_id'] ?? '';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nome'] = $usuario['nome'];
            $_SESSION['tipo'] = $usuario['tipo'];
            
            switch($usuario['tipo']) {
                case 'admin':
                    header("Location: ./admin/index.php");
                    break;
                case 'revendedor':
                    header("Location: ./revendedor/dashboard.php");
                    break;
                default:
                    $redirect = $_GET['redirect'] ?? '';
                    $jogo_id = $_GET['jogo_id'] ?? '';
                    
                    if ($redirect == 'apostador/fazer_aposta' && $jogo_id) {
                        header("Location: ./apostador/fazer_aposta.php?jogo_id=" . $jogo_id);
                    } else {
                        header("Location: ./apostador/dashboard.php");
                    }
            }
            exit;
        } else {
            $erro = "Email ou senha inválidos";
        }
    } catch(PDOException $e) {
        $erro = "Erro ao fazer login: " . $e->getMessage();
    }
}

// Manter os parâmetros no formulário
$formAction = "login.php";
if ($jogo_id) {
    $formAction .= "?jogo_id=" . urlencode($jogo_id);
}

$pageTitle = 'Login - Sistema de Loteria';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-dark: #224abe;
            --secondary-color: #858796;
            --danger-color: #e74a3b;
            --border-radius: 10px;
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('images/bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            z-index: 0;
        }

        .login-container {
            position: relative;
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            z-index: 1;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        .login-header {
            padding: 2.5rem 2rem;
            text-align: center;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .login-header h1 {
            font-size: 2rem;
            margin: 0;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .login-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 1rem;
        }

        .login-body {
            padding: 2.5rem;
        }

        .form-group {
            margin-bottom: 1.8rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            color: #2d3748;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 2.7rem;
            color: var(--secondary-color);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
        }

        .login-footer {
            padding: 2rem;
            text-align: center;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .login-footer p {
            margin: 0;
            color: #4a5568;
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
            margin-left: 5px;
        }

        .login-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .erro-mensagem {
            background: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            border: 1px solid rgba(231, 74, 59, 0.2);
        }

        .social-login {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-btn {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .google-btn {
            background: #fff;
            color: #333;
            border: 2px solid #e2e8f0;
        }

        .facebook-btn {
            background: #1877f2;
            color: white;
        }

        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 640px) {
            .login-container {
                padding: 1rem;
            }
            
            .login-header,
            .login-body,
            .login-footer {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Loteria Online</h1>
                <p>Faça sua aposta e realize seus sonhos</p>
            </div>
            
            <div class="login-body">
                <?php if ($erro): ?>
                    <div class="erro-mensagem">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $erro; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo $formAction; ?>">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control" required 
                               placeholder="Digite seu email" autocomplete="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <i class="fas fa-lock"></i>
                        <input type="password" id="senha" name="senha" class="form-control" required 
                               placeholder="Digite sua senha" autocomplete="current-password">
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Entrar
                    </button>

                    <div class="social-login">
                        <button type="button" class="social-btn google-btn">
                            <i class="fab fa-google"></i> Google
                        </button>
                        <button type="button" class="social-btn facebook-btn">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="login-footer">
                <p>Ainda não tem uma conta? 
                    <a href="register.php">Cadastre-se agora</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html> 