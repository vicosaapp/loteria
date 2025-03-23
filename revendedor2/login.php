<?php
require_once '../config/database.php';
session_start();

// Se já estiver logado, redireciona para o painel
if (isset($_SESSION['usuario_id']) && $_SESSION['tipo'] === 'revendedor') {
    header("Location: index.php");
    exit();
}

// Processar o formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);
    
    try {
        // Buscar usuário
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND tipo = 'revendedor'");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Verifica se está ativo
            if (!$usuario['status']) {
                $erro = "Sua conta está bloqueada. Entre em contato com o administrador.";
            } else {
                // Login bem sucedido
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nome'] = $usuario['nome'];
                $_SESSION['tipo'] = 'revendedor';
                
                header("Location: index.php");
                exit();
            }
        } else {
            $erro = "Email ou senha incorretos";
        }
    } catch (PDOException $e) {
        $erro = "Erro ao fazer login. Tente novamente mais tarde.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Revendedor - LotoMinas</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-green: #03a64d;
            --secondary-green: #2d8e59;
            --primary-gradient: linear-gradient(135deg, #03a64d, #2d8e59);
            --hover-gradient: linear-gradient(135deg, #2d8e59, #03a64d);
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fc;
            font-family: 'Poppins', sans-serif;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            padding: 0;
        }

        .login-header {
            background: var(--primary-gradient);
            padding: 2rem;
            text-align: center;
            color: white;
        }

        .login-header img {
            height: 60px;
            margin-bottom: 1rem;
        }

        .login-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .login-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-control {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(3, 166, 77, 0.25);
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            padding: 0.8rem;
            font-weight: 600;
            width: 100%;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--hover-gradient);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(3, 166, 77, 0.3);
        }

        .login-footer {
            text-align: center;
            padding: 1rem 2rem 2rem;
        }

        .login-footer a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .login-footer a:hover {
            color: var(--secondary-green);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .input-group-text {
            background: transparent;
            border-left: none;
            cursor: pointer;
        }

        .form-control.password {
            border-right: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <img src="../assets/img/logo.png" alt="LotoMinas" class="img-fluid">
                <h4>Área do Revendedor</h4>
            </div>
            
            <div class="login-body">
                <?php if (isset($erro)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="email" name="email" required 
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="senha" class="form-label">Senha</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control password" id="senha" name="senha" required>
                            <span class="input-group-text" onclick="togglePassword()">
                                <i class="fas fa-eye" id="togglePassword"></i>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Entrar
                    </button>
                </form>
            </div>
            
            <div class="login-footer">
                <a href="../">
                    <i class="fas fa-arrow-left"></i> Voltar para página inicial
                </a>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const senha = document.getElementById('senha');
            const toggleIcon = document.getElementById('togglePassword');
            
            if (senha.type === 'password') {
                senha.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                senha.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html> 