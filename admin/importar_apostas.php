<?php
// Forçar exibição de erros


require_once 'includes/header.php';
require_once '../config/database.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

try {
    // Debug da conexão
    if (!$pdo) {
        throw new Exception("Erro na conexão com o banco de dados");
    }

    // Buscar jogos disponíveis
    $stmt = $pdo->query("SELECT id, nome FROM jogos WHERE status = 1 ORDER BY nome");
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar apostadores
    $stmt = $pdo->query("
        SELECT id, nome, whatsapp, telefone 
        FROM usuarios 
        WHERE tipo = 'usuario' 
        ORDER BY nome ASC
    ");
    $apostadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar admin
    $stmtAdmin = $pdo->query("
        SELECT id, nome 
        FROM usuarios 
        WHERE tipo = 'admin' 
        LIMIT 1
    ");
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

    // Buscar revendedores
    $stmt = $pdo->query("
        SELECT id, nome 
        FROM usuarios 
        WHERE tipo = 'revendedor' 
        ORDER BY nome ASC
    ");
    $revendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Criar array para dados dos apostadores em JavaScript
    $apostadoresData = [];
    foreach ($apostadores as $apostador) {
        $apostadoresData[$apostador['id']] = [
            'whatsapp' => $apostador['whatsapp'] ?: $apostador['telefone'] ?: ''
        ];
    }

} catch (Exception $e) {
    error_log("Erro na importação de apostas: " . $e->getMessage());
    $error = "Erro ao carregar os dados. Por favor, tente novamente.";
}

// Array para armazenar mensagens de debug
$debug_messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_POST); // Debug dos dados recebidos
    
    try {
        // Preparar os valores
        $usuario_id = $_POST['apostador'];
        $revendedor_id = !empty($_POST['revendedor']) ? $_POST['revendedor'] : null;
        $valor_aposta = str_replace(',', '.', $_POST['valor_aposta']);
        $valor_premio = str_replace(',', '.', $_POST['valor_premio']);
        $whatsapp = $_POST['whatsapp'];
        $apostas = $_POST['apostas'];
        
        // Debug dos valores formatados
        echo "\nValores processados:\n";
        echo "usuario_id: $usuario_id\n";
        echo "revendedor_id: " . var_export($revendedor_id, true) . "\n";
        echo "valor_aposta: $valor_aposta\n";
        echo "valor_premio: $valor_premio\n";
        
        // Processar apostas
        $linhas = explode("\n", trim($apostas));
        $jogo_nome = trim($linhas[0]);
        array_shift($linhas);
        
        foreach ($linhas as $linha) {
            if (empty(trim($linha))) continue;
            
            $sql = "INSERT INTO apostas_importadas 
                    (usuario_id, jogo_nome, numeros, valor_aposta, valor_premio, revendedor_id, whatsapp) 
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?)";
            
            echo "\nExecutando SQL:\n$sql\n";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $usuario_id,
                $jogo_nome,
                trim($linha),
                $valor_aposta,
                $valor_premio,
                $revendedor_id,
                $whatsapp
            ]);
            
            if ($result) {
                $last_id = $pdo->lastInsertId();
                echo "\nAposta inserida com ID: $last_id\n";
                
                // Verificar o registro inserido
                $check = $pdo->query("SELECT * FROM apostas_importadas WHERE id = $last_id")->fetch(PDO::FETCH_ASSOC);
                echo "Registro inserido:\n";
                print_r($check);
            }
        }
        
        echo "</pre>";
        //header('Location: gerenciar_apostas.php');
        //exit;
        
    } catch (Exception $e) {
        echo "\nERRO: " . $e->getMessage() . "\n";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Apostas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <!-- Área de Debug -->
        <?php if (!empty($debug_messages)): ?>
            <div class="alert alert-info">
                <h4>Debug Information:</h4>
                <pre><?php echo implode("\n", $debug_messages); ?></pre>
            </div>
        <?php endif; ?>

        <h2>Importar Apostas</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-4">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="apostador">Apostador</label>
                    <select class="form-control" id="apostador" name="apostador" required onchange="atualizarWhatsApp()">
                        <option value="">Selecione um apostador</option>
                        <?php foreach ($apostadores as $apostador): ?>
                            <option value="<?php echo $apostador['id']; ?>" 
                                    data-whatsapp="<?php echo htmlspecialchars($apostador['whatsapp'] ?: $apostador['telefone'] ?: ''); ?>">
                                <?php echo htmlspecialchars($apostador['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="whatsapp">WhatsApp</label>
                    <input type="text" class="form-control" id="whatsapp" name="whatsapp" readonly>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="revendedor">Revendedor</label>
                    <select class="form-control" id="revendedor" name="revendedor">
                        <option value="">Selecione um revendedor</option>
                        <?php foreach ($revendedores as $revendedor): ?>
                            <option value="<?php echo $revendedor['id']; ?>">
                                <?php echo htmlspecialchars($revendedor['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                        <!-- Opção oculta para o admin -->
                        <option value="<?php echo $admin['id']; ?>" style="display:none;">
                            <?php echo htmlspecialchars($admin['nome']); ?>
                        </option>
                    </select>
                </div>

            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="valor_aposta">Valor por Aposta</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">R$</span>
                        </div>
                        <input type="text" 
                               class="form-control" 
                               id="valor_aposta" 
                               name="valor_aposta" 
                               required 
                               onkeyup="formatarMoeda(this)"
                               value="0,00"
                               pattern="^\d*\.?\d{0,2}$"
                               title="Digite um valor válido com até duas casas decimais">
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="valor_premio">Valor da Premiação</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">R$</span>
                        </div>
                        <input type="text" 
                               class="form-control" 
                               id="valor_premio" 
                               name="valor_premio" 
                               required 
                               onkeyup="formatarMoeda(this)"
                               value="0,00"
                               pattern="^\d*\.?\d{0,2}$"
                               title="Digite um valor válido com até duas casas decimais">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="apostas">Cole as apostas aqui</label>
                <textarea class="form-control" id="apostas" name="apostas" rows="10" required></textarea>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-secondary" onclick="visualizarApostas()">
                    <i class="fas fa-eye"></i> Visualizar
                </button>
                <button type="button" id="btnSalvar" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Apostas
                </button>
            </div>
        </form>
    </div>

    <!-- Modal para visualização -->
    <div class="modal fade" id="visualizarModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Visualizar Apostas</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="resumoApostas"></div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .card {
        border: 2px solid #4e73df;
        border-radius: 10px;
        margin-top: 20px;
    }

    .form-control {
        border: 1px solid #d1d3e2;
        border-radius: 5px;
        padding: 0.375rem 0.75rem;
    }

    select.form-control {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%234e73df' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 12px;
        padding-right: 2rem;
    }

    .btn {
        padding: 0.375rem 1rem;
        font-size: 0.9rem;
        border-radius: 5px;
        margin-right: 10px;
    }

    .btn-secondary {
        background-color: #858796;
        border-color: #858796;
        color: white;
    }

    .btn-primary {
        background-color: #4e73df;
        border-color: #4e73df;
    }

    textarea {
        resize: vertical;
    }

    .input-group-text {
        background-color: #4e73df;
        color: white;
        border: 1px solid #4e73df;
    }

    #valor_aposta {
        text-align: right;
    }

    .apostas-preview {
        max-height: 400px;
        overflow-y: auto;
    }

    .aposta-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
        margin-bottom: 10px;
    }

    .aposta-item:last-child {
        border-bottom: none;
    }
    </style>

    <script>
    // Função para atualizar WhatsApp
    function atualizarWhatsApp() {
        const apostadorSelect = document.getElementById('apostador');
        const whatsappInput = document.getElementById('whatsapp');
        const selectedOption = apostadorSelect.options[apostadorSelect.selectedIndex];
        
        if (selectedOption) {
            whatsappInput.value = selectedOption.dataset.whatsapp || '';
        } else {
            whatsappInput.value = '';
        }
    }

    // Função para formatar moeda
    function formatarMoeda(input) {
        let valor = input.value.replace(/\D/g, '');
        valor = (parseInt(valor) / 100).toFixed(2);
        valor = valor.replace(".", ",");
        valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
        input.value = valor;
    }

    // Função para converter valor para o formato correto antes de enviar
    function getValorFormatado(valor) {
        // Remove todos os caracteres exceto números e vírgula
        valor = valor.replace(/[^\d,]/g, '');
        // Substitui vírgula por ponto para cálculos
        valor = valor.replace(',', '.');
        // Converte para float
        return parseFloat(valor);
    }

    // Adicionando evento de click diretamente
    document.getElementById('btnSalvar').addEventListener('click', function() {
        // Debug
        console.log('Botão clicado');
        
        // Pegar valores
        const apostador = document.getElementById('apostador').value;
        const valorAposta = getValorFormatado(document.getElementById('valor_aposta').value);
        const valorPremio = getValorFormatado(document.getElementById('valor_premio').value);
        const apostasTexto = document.getElementById('apostas').value.trim();
        
        // Debug
        console.log('Apostador:', apostador);
        console.log('Valor:', valorAposta);
        console.log('Valor Premio:', valorPremio);
        console.log('Apostas:', apostasTexto);
        
        // Validação básica
        if (!apostador || !valorAposta || !valorPremio || !apostasTexto) {
            alert('Preencha todos os campos obrigatórios');
            return;
        }
        
        // Processar apostas
        const linhas = apostasTexto.split('\n').filter(linha => linha.trim());
        const nomeJogo = linhas[0].trim();
        const apostas = linhas.slice(1)
            .filter(linha => linha.trim())
            .map(linha => linha.trim().split(/\s+/).map(Number));
        
        // Preparar dados
        const dados = {
            jogo: nomeJogo,
            apostas: apostas,
            apostador_id: apostador,
            whatsapp: document.getElementById('whatsapp').value,
            revendedor_id: document.getElementById('revendedor').value || null,
            valor_aposta: valorAposta,
            valor_premio: valorPremio
        };
        
        // Debug
        console.log('Dados para envio:', dados);
        
        // Enviar dados
        fetch('ajax/salvar_apostas_importadas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dados)
        })
        .then(response => {
            console.log('Resposta recebida:', response);
            return response.json();
        })
        .then(data => {
            console.log('Dados processados:', data);
            if (data.success) {
                alert('Apostas salvas com sucesso!');
                window.location.href = 'gerenciar_apostas.php';
            } else {
                alert(data.error || 'Erro ao salvar apostas');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar a requisição');
        });
    });

    function visualizarApostas() {
        const texto = document.getElementById('apostas').value.trim();
        const linhas = texto.split('\n').filter(linha => linha.trim());
        
        if (linhas.length < 2) {
            Swal.fire('Erro', 'Formato inválido', 'error');
            return;
        }
        
        const nomeJogo = linhas[0];
        const apostas = linhas.slice(1).filter(linha => linha.trim());
        
        let html = `
            <div class="alert alert-info">
                <strong>Jogo:</strong> ${nomeJogo}<br>
                <strong>Total de Apostas:</strong> ${apostas.length}
            </div>
            <div class="apostas-preview">
        `;
        
        apostas.forEach((aposta, index) => {
            html += `
                <div class="aposta-item">
                    <strong>Aposta ${index + 1}:</strong><br>
                    ${aposta}
                </div>
            `;
        });
        
        html += '</div>';
        
        document.getElementById('resumoApostas').innerHTML = html;
        $('#visualizarModal').modal('show');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const btnSalvar = document.querySelector('button.btn.btn-primary');
        
        if (btnSalvar) {
            btnSalvar.onclick = function(e) {
                e.preventDefault();
                
                // Pegar apostas (ignorando a primeira linha)
                const linhas = document.querySelector('textarea').value.split('\n');
                const apostas = linhas.slice(1).filter(linha => linha.trim());
                
                // Contar a quantidade de dezenas apostadas
                const quantidadeDezenas = apostas.length;
                console.log('Quantidade de dezenas apostadas:', quantidadeDezenas);
                
                const dados = {
                    apostador_id: document.getElementById('apostador').value,
                    whatsapp: document.getElementById('whatsapp').value,
                    revendedor_id: document.getElementById('revendedor').value,
                    valor_premio_2: document.querySelector('input[name="premio"]').value.replace(/\./g, '').replace(',', '.'),
                    apostas: apostas,
                    quantidade_dezenas: quantidadeDezenas
                };

                console.log('Dados sendo enviados:', dados);

                fetch('ajax/salvar_apostas_importadas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dados)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Resposta do servidor:', data);
                    if (data.success) {
                        alert('Apostas salvas com sucesso!');
                        window.location.href = 'gerenciar_apostas.php';
                    } else {
                        alert('Erro: ' + (data.error || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao salvar: ' + error);
                });
            };
        }
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 