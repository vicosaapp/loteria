<?php
require_once '../config/database.php';
session_start();

// Verificar se já está logado
if (isset($_SESSION['usuario_id']) && $_SESSION['tipo'] === 'revendedor') {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LotoMinas - Área do Revendedor</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #00a65a, #008d4c);
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }

        .login-title {
            color: #2c3e50;
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 2px solid #e0e6ed;
            background: #f8f9fa;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(0, 166, 90, 0.25);
            border-color: #00a65a;
            background: #ffffff;
        }

        .input-group-text {
            background-color: #00a65a;
            border: none;
            color: white;
            border-radius: 10px 0 0 10px;
            width: 46px;
            justify-content: center;
        }

        .btn-login {
            background-color: #00a65a;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            color: white;
            font-size: 1.1rem;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-login:hover {
            background-color: #008d4c;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-show-password {
            border: 2px solid #e0e6ed;
            border-radius: 0 10px 10px 0;
            padding: 0 15px;
            background: #f8f9fa;
            color: #6c757d;
            transition: all 0.3s ease;
        }

        .btn-show-password:hover {
            background: #e9ecef;
            color: #495057;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 2rem;
            color: #00a65a;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: #008d4c;
            transform: translateX(-3px);
        }

        /* Animação de fade-in */
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

        .login-container {
            animation: fadeIn 0.6s ease-out;
        }

        /* Responsividade */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .login-title {
                font-size: 1.5rem;
            }

            .login-logo {
                max-width: 160px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../assets/img/logo.png" alt="LotoMinas" class="login-logo">
            <h2 class="login-title">Área do Revendedor</h2>
        </div>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" class="form-control" name="email" required 
                           placeholder="Seu email">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Senha</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" name="senha" id="senha" 
                           required placeholder="Sua senha">
                    <button type="button" class="btn btn-show-password" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Entrar
            </button>

            <a href="../" class="back-link">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar para página inicial
            </a>
        </form>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                senhaInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html> 