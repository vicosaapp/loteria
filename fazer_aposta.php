<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['numeros']) || count($_POST['numeros']) != 6) {
        $erro = "Você deve escolher exatamente 6 números!";
    } else {
        $numeros = array_map('intval', $_POST['numeros']);
        sort($numeros);
        
        // Validar se os números estão entre 1 e 60
        $valido = true;
        foreach ($numeros as $num) {
            if ($num < 1 || $num > 60) {
                $valido = false;
                break;
            }
        }
        
        if ($valido) {
            try {
                $numerosString = implode(',', $numeros);
                $stmt = $pdo->prepare("INSERT INTO apostas (usuario_id, numeros) VALUES (?, ?)");
                $stmt->execute([$_SESSION['usuario_id'], $numerosString]);
                $sucesso = "Aposta realizada com sucesso! Aguarde a aprovação.";
            } catch(PDOException $e) {
                $erro = "Erro ao registrar aposta: " . $e->getMessage();
            }
        } else {
            $erro = "Números inválidos selecionados!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fazer Aposta - Sistema de Loteria</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .numeros-container {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 5px;
            margin: 20px 0;
        }
        .numero-item {
            position: relative;
        }
        .numero-item input[type="checkbox"] {
            display: none;
        }
        .numero-item label {
            display: block;
            padding: 10px;
            text-align: center;
            background: #f0f0f0;
            border-radius: 5px;
            cursor: pointer;
        }
        .numero-item input[type="checkbox"]:checked + label {
            background: #007bff;
            color: white;
        }
        .mensagem {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .erro {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .sucesso {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Fazer Nova Aposta</h2>
        
        <?php if ($erro): ?>
            <div class="mensagem erro"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="mensagem sucesso"><?php echo $sucesso; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="formAposta">
            <p>Escolha 6 números entre 1 e 60:</p>
            <div class="numeros-container">
                <?php for($i = 1; $i <= 60; $i++): ?>
                    <div class="numero-item">
                        <input type="checkbox" name="numeros[]" value="<?php echo $i; ?>" id="num<?php echo $i; ?>">
                        <label for="num<?php echo $i; ?>"><?php echo $i; ?></label>
                    </div>
                <?php endfor; ?>
            </div>
            <button type="submit">Enviar Aposta</button>
        </form>
        
        <p><a href="index.php">Voltar ao Início</a></p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formAposta');
        const checkboxes = form.querySelectorAll('input[type="checkbox"]');
        
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const checked = form.querySelectorAll('input[type="checkbox"]:checked');
                if (checked.length > 6) {
                    this.checked = false;
                    alert('Você só pode escolher 6 números!');
                }
            });
        });
        
        form.addEventListener('submit', function(e) {
            const checked = form.querySelectorAll('input[type="checkbox"]:checked');
            if (checked.length !== 6) {
                e.preventDefault();
                alert('Você deve escolher exatamente 6 números!');
            }
        });
    });
    </script>
</body>
</html> 