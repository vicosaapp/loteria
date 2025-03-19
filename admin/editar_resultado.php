<?php
require_once '../config/database.php';
session_start();

// Verificar se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Buscar resultado
$resultado = null;
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM resultados WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resultado) {
            throw new Exception("Resultado não encontrado.");
        }
    } catch (Exception $e) {
        $_SESSION['erro'] = $e->getMessage();
        header('Location: gerenciar_resultados.php');
        exit;
    }
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar dados
        $id = $_POST['id'];
        $jogo = trim($_POST['jogo']);
        $data_sorteio = $_POST['data_sorteio'];
        $numeros = trim($_POST['numeros']);
        $processado = isset($_POST['processado']) ? 1 : 0;

        // Validações básicas
        if (empty($jogo) || empty($data_sorteio) || empty($numeros)) {
            throw new Exception("Todos os campos são obrigatórios.");
        }

        // Validar formato dos números
        $numeros_array = explode(' ', $numeros);
        foreach ($numeros_array as $numero) {
            if (!is_numeric($numero) || strlen($numero) !== 2) {
                throw new Exception("Os números devem estar no formato correto (ex: 01 02 03).");
            }
        }

        // Atualizar no banco
        $stmt = $pdo->prepare("
            UPDATE resultados 
            SET jogo = ?, 
                data_sorteio = ?, 
                numeros = ?,
                processado = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$jogo, $data_sorteio, $numeros, $processado, $id]);
        
        $_SESSION['sucesso'] = "Resultado atualizado com sucesso!";
        header('Location: gerenciar_resultados.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['erro'] = "Erro ao atualizar resultado: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Resultado - LotoMinas</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 15px 20px;
        }

        .btn-voltar {
            background: #95a5a6;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .btn-voltar:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .form-check-input:checked {
            background-color: #2ecc71;
            border-color: #2ecc71;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Editar Resultado
                </h5>
                <a href="gerenciar_resultados.php" class="btn btn-voltar text-white">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>
            
            <div class="card-body">
                <?php if (isset($_SESSION['erro'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php 
                        echo $_SESSION['erro'];
                        unset($_SESSION['erro']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="editar_resultado.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $resultado['id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jogo</label>
                            <select class="form-select" name="jogo" required>
                                <option value="">Selecione o jogo</option>
                                <?php
                                $jogos = [
                                    'Loterias Mobile: LM',
                                    'Loterias Mobile: TM',
                                    'Loterias Mobile: QN',
                                    'Loterias Mobile: MM',
                                    'Loterias Mobile: MS',
                                    'Loterias Mobile: LF',
                                    'Loterias Mobile: DI'
                                ];
                                foreach ($jogos as $jogo) {
                                    $selected = ($resultado['jogo'] === $jogo) ? 'selected' : '';
                                    echo "<option value=\"$jogo\" $selected>$jogo</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data do Sorteio</label>
                            <input type="date" class="form-control" name="data_sorteio" 
                                   value="<?php echo $resultado['data_sorteio']; ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Números Sorteados</label>
                        <input type="text" class="form-control" name="numeros" 
                               value="<?php echo htmlspecialchars($resultado['numeros']); ?>" required
                               placeholder="Ex: 01 02 03 04 05">
                        <div class="form-text">Digite os números separados por espaço</div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="processado" 
                                   <?php echo $resultado['processado'] ? 'checked' : ''; ?>>
                            <label class="form-check-label">Resultado processado</label>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 