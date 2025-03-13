<?php
// Forçar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    try {
        // Validar campos obrigatórios
        if (empty($_POST['apostador'])) {
            throw new Exception("Selecione um apostador");
        }
        
        if (empty($_POST['valor_aposta']) || empty($_POST['apostas'])) {
            throw new Exception("Valor da aposta e apostas são obrigatórios");
        }

        // Processar valores
        $usuario_id = intval($_POST['apostador']);
        $revendedor_id = !empty($_POST['revendedor']) ? intval($_POST['revendedor']) : null;
        $whatsapp = !empty($_POST['whatsapp']) ? $_POST['whatsapp'] : null;
        
        // Tratar valor da aposta
        $valor_aposta = str_replace(['R$', ' '], '', $_POST['valor_aposta']);
        $valor_aposta = str_replace(',', '.', $valor_aposta);
        $valor_aposta = number_format(floatval($valor_aposta), 2, '.', '');
        
        // Tratar valor do prêmio
        $valor_premio = str_replace(['R$', ' '], '', $_POST['valor_premiacao']);
        $valor_premio = str_replace(',', '.', $valor_premio);
        $valor_premio = number_format(floatval($valor_premio), 2, '.', '');
        
        // Preparar SQL
        $sql = "INSERT INTO apostas_importadas 
                (usuario_id, jogo_nome, numeros, valor_aposta, valor_premio, revendedor_id, whatsapp) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?)";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $usuario_id,
            trim(explode("\n", $_POST['apostas'])[0]), // Nome do jogo
            $_POST['apostas'],
            $valor_aposta,
            $valor_premio,
            $revendedor_id,
            $whatsapp
        ]);
        
        header('Location: importar_apostas.php?success=1');
        exit;
        
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>

  
        <h2>Importar Apostas</h2>
        
        <div class="debug-info" style="background:#f4f6f9; padding: 0px; margin: 0px 0; border-radius: 0px; max-height: 0px; overflow-y: auto;">
        </div>
        
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

                <div class="col-md-6 mb-3">
                    <label>Valor da premiação</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">R$</span>
                        </div>
                        <input type="text" id="valor_premiacao" name="valor_premiacao" class="form-control" value="0,00" readonly>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Valor por Aposta</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">R$</span>
                        </div>
                        <input type="number" id="valor_aposta" name="valor_aposta" class="form-control" value="1.00" step="1.00">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Cole as apostas aqui</label>
                <textarea id="apostas" name="apostas" class="form-control" rows="10"></textarea>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-secondary" onclick="visualizarApostas()">
                    <i class="fas fa-eye"></i> Visualizar
                </button>
                <button type="button" id="btnSalvarDados" class="btn btn-primary">Salvar Dados</button>
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

    <!-- Adicione um elemento para mostrar o valor da premiação -->
    <div id="premio-info" style="margin: 10px 0;"></div>

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
    // Funçãoremo para atualizar WhatsApp
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

    // Função para formatar valor
    function formatarValor(valor) {
        return valor.replace(/[^\d,]/g, '').replace(',', '.');
    }

    // Função para mostrar debug
  //
  //   function showDebug(title, data) {
  //       const debugArea = document.getElementById('debugArea');
  //       const debugText = `=== ${title} ===\n${JSON.stringify(data, null, 2)}\n\n`;
  //       debugArea.textContent += debugText;
  //   }

    // Função para formatar valor em moeda brasileira
    function formatarMoeda(valor) {
        return valor.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Função para calcular premiação
    async function calcularPremiacao() {
        const textarea = document.querySelector('textarea');
        const valorApostaInput = document.querySelector('input[id^="valor_aposta"]');
        const premiacaoInput = document.querySelector('input[name="valor_premiacao"]');
        const debugDiv = document.querySelector('.debug-info');
        const texto = textarea.value.trim();
        
        debugDiv.innerHTML = ''; // Limpar debug anterior
        
        try {
            if (!texto || !valorApostaInput.value) {
                premiacaoInput.value = '0,00';
                return;
            }

            const response = await fetch('ajax/buscar_jogo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    nome: texto,
                    valor_aposta: valorApostaInput.value
                })
            });

            const data = await response.json();
            
            if (data.success) {
                // Formatar o valor do prêmio para exibição
                const valorPremio = parseFloat(data.jogo.valor_premio);
                
                // Dividir por 100 para corrigir os dígitos extras
                const valorPremioCorrigido = valorPremio / 100;
                
                premiacaoInput.value = valorPremioCorrigido.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                // Atualizar informações de debug
                const valorBaseAposta = parseFloat(data.jogo.debug.valor_base_aposta);
                const valorBasePremio = parseFloat(data.jogo.debug.valor_base_premio) / 100;
                
                debugDiv.innerHTML = `
                    <p>Valor base da aposta: R$ ${valorBaseAposta.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                    <p>Valor base do prêmio: R$ ${valorBasePremio.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                    <p>Multiplicador: ${data.jogo.debug.multiplicador}</p>
                `;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Erro:', error);
            premiacaoInput.value = '0,00';
            debugDiv.innerHTML = `<p class="error">Erro: ${error.message}</p>`;
        }
    }

    // Adicionar os event listeners
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.querySelector('textarea');
        const valorApostaInput = document.querySelector('input[id^="valor_aposta"]');
        
        if (textarea) {
            textarea.addEventListener('input', debounce(calcularPremiacao, 500));
            textarea.addEventListener('paste', () => setTimeout(calcularPremiacao, 100));
        }
        
        if (valorApostaInput) {
            valorApostaInput.addEventListener('input', debounce(calcularPremiacao, 500));
        }
    });

    // Função debounce para evitar múltiplas chamadas
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

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

    // Evento do botão salvar
    document.addEventListener('DOMContentLoaded', function() {
        const textArea = document.querySelector('textarea');
        const valorInput = document.querySelector('input[type="number"]');
        const premiacaoInput = document.querySelector('input[name="valor_premiacao"]');
        
        // Função para atualizar premiação
        async function atualizarPremiacao() {
            try {
                if (!textArea.value.trim() || !valorInput.value) {
                    premiacaoInput.value = '0,00';
                    return;
                }

                const resultado = await calcularPremiacao(
                    textArea.value.trim(),
                    parseFloat(valorInput.value)
                );

                if (premiacaoInput && resultado.valor_premio) {
                    premiacaoInput.value = formatarMoeda(resultado.valor_premio);
                }
            } catch (error) {
                console.error('Erro ao atualizar premiação:', error);
                premiacaoInput.value = '0,00';
            }
        }

        // Eventos que disparam o cálculo da premiação
        if (textArea) {
            // Quando colar o texto
            textArea.addEventListener('paste', function(e) {
                setTimeout(atualizarPremiacao, 100);
            });

            // Quando digitar/modificar o texto
            textArea.addEventListener('input', atualizarPremiacao);
        }

        // Quando mudar o valor da aposta
        if (valorInput) {
            valorInput.addEventListener('input', atualizarPremiacao);
        }

        // Mantém o evento do botão salvar
        const btnSalvar = document.querySelector('#btnSalvarDados');
        if (btnSalvar) {
            btnSalvar.addEventListener('click', async function(e) {
                e.preventDefault();
                
                try {
                    // Primeiro atualiza a premiação
                    await atualizarPremiacao();
                    
                    // Se chegou aqui, a premiação foi calculada com sucesso
                    // Agora podemos enviar o formulário
                    this.closest('form').submit();
                } catch (error) {
                    console.error('Erro ao salvar:', error);
                    alert('Erro ao salvar os dados. Por favor, verifique os valores e tente novamente.');
                }
            });
        }
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 