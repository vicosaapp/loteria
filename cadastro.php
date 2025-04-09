<?php
// Forçar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
session_start();

// Se o usuário já está logado, redireciona para a área apropriada
if (isset($_SESSION['usuario_id'])) {
    $tipoUsuario = $_SESSION['tipo'] ?? '';
    
    if ($tipoUsuario === 'admin') {
        header('Location: admin/index.php');
        exit;
    } elseif ($tipoUsuario === 'revendedor') {
        header('Location: revendedor/index.php');
        exit;
    } elseif ($tipoUsuario === 'usuario') {
        header('Location: apostador/index.php');
        exit;
    }
}

$erro = null;
$sucesso = null;

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar campos obrigatórios
        $camposObrigatorios = ['nome', 'email', 'whatsapp', 'senha', 'confirmar_senha'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($_POST[$campo])) {
                throw new Exception("O campo " . ucfirst(str_replace('_', ' ', $campo)) . " é obrigatório.");
            }
        }
        
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $whatsapp = trim($_POST['whatsapp']);
        $senha = $_POST['senha'];
        $confirmarSenha = $_POST['confirmar_senha'];
        $revendedor_id = isset($_POST['revendedor_id']) ? intval($_POST['revendedor_id']) : null;
        
        // Verificar se as senhas coincidem
        if ($senha !== $confirmarSenha) {
            throw new Exception("As senhas não coincidem.");
        }
        
        // Verificar se o email já está em uso
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Este e-mail já está em uso. Por favor, escolha outro ou faça login.");
        }
        
        // Verificar se o revendedor existe, se foi informado
        if ($revendedor_id) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = 'revendedor'");
            $stmt->execute([$revendedor_id]);
            if (!$stmt->fetch()) {
                throw new Exception("Revendedor inválido.");
            }
        }
        
        // Preparar e inserir o novo usuário
        $senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, whatsapp, senha, tipo, revendedor_id, created_at) 
            VALUES (?, ?, ?, ?, 'usuario', ?, NOW())
        ");
        
        $stmt->execute([$nome, $email, $whatsapp, $senhaCriptografada, $revendedor_id]);
        
        $sucesso = "Cadastro realizado com sucesso! Agora você pode fazer login.";
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Buscar revendedores ativos para a lista de seleção
try {
    $stmt = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo = 'revendedor' AND status = 1 ORDER BY nome");
    $revendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Erro ao buscar revendedores: " . $e->getMessage());
    $revendedores = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Apostador - Sistema de Loteria</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #4e73df;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo {
            max-width: 200px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-8 col-lg-6">
                <div class="logo-container">
                    <a href="index.php">
                        <img src="assets/img/logo.png" alt="Logo Loteria" class="logo">
                    </a>
                </div>
                
                <?php if ($sucesso): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $sucesso; ?>
                        <div class="mt-3">
                            <a href="login.php" class="btn btn-success">Ir para o Login</a>
                        </div>
                    </div>
                <?php else: ?>
                
                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h4 class="mb-0 text-center">Cadastro de Apostador</h4>
                    </div>
                    <div class="card-body">
                        
                        <?php if ($erro): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $erro; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="cadastro.php">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo $_POST['nome'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="whatsapp" class="form-label">WhatsApp</label>
                                <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="<?php echo $_POST['whatsapp'] ?? ''; ?>" required>
                                <div class="form-text">Informe com DDD, apenas números.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="revendedor_id" class="form-label">Revendedor</label>
                                <select class="form-select" id="revendedor_id" name="revendedor_id">
                                    <option value="">Selecione um revendedor (opcional)</option>
                                    <?php foreach ($revendedores as $revendedor): ?>
                                        <option value="<?php echo $revendedor['id']; ?>" <?php echo (isset($_POST['revendedor_id']) && $_POST['revendedor_id'] == $revendedor['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($revendedor['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Se você já tem um revendedor, selecione-o aqui.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                            </div>
                            
                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" class="btn btn-primary">Cadastrar</button>
                            </div>
                            
                            <div class="text-center">
                                <p class="mb-0">Já tem uma conta? <a href="login.php">Fazer Login</a></p>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="text-center">
                    <a href="index.php" class="btn btn-link">Voltar para a página inicial</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para formatação do campo WhatsApp -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const whatsappInput = document.getElementById('whatsapp');
            
            if (whatsappInput) {
                whatsappInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    
                    if (value.length > 11) {
                        value = value.substring(0, 11);
                    }
                    
                    if (value.length > 2 && value.length <= 6) {
                        value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                    } else if (value.length > 6) {
                        value = value.replace(/^(\d{2})(\d{5})(\d)/g, '($1) $2-$3');
                    }
                    
                    e.target.value = value;
                });
            }
        });
    </script>
</body>
</html> 