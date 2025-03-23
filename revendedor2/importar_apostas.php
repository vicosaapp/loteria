<?php
// Forçar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
session_start();

// Verificar se é revendedor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Location: ../login.php');
    exit;
}

try {
    // Debug da conexão
    if (!$pdo) {
        throw new Exception("Erro na conexão com o banco de dados");
    }

    // Buscar jogos disponíveis
    $stmt = $pdo->query("SELECT id, nome FROM jogos WHERE status = 1 ORDER BY nome");
    $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar apostadores vinculados ao revendedor atual
    $stmt = $pdo->prepare("
        SELECT id, nome, whatsapp, telefone 
        FROM usuarios 
        WHERE tipo = 'usuario' 
        AND revendedor_id = ?
        ORDER BY nome ASC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $apostadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar dados do revendedor
    $stmtRevendedor = $pdo->prepare("
        SELECT id, nome 
        FROM usuarios 
        WHERE id = ? 
        LIMIT 1
    ");
    $stmtRevendedor->execute([$_SESSION['usuario_id']]);
    $revendedor = $stmtRevendedor->fetch(PDO::FETCH_ASSOC);

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
        $revendedor_id = $_SESSION['usuario_id']; // Usa o ID do revendedor logado
        $whatsapp = !empty($_POST['whatsapp']) ? $_POST['whatsapp'] : null;
        
        // Tratar valor da aposta
        $valor_aposta = str_replace(['R$', ' '], '', $_POST['valor_aposta']);
        $valor_aposta = str_replace(',', '.', $valor_aposta);
        $valor_aposta = number_format(floatval($valor_aposta), 2, '.', '');
        
        // Tratar valor do prêmio
        $valor_premio = str_replace(['R$', ' '], '', $_POST['valor_premiacao']);
        $valor_premio = str_replace('.', '', $valor_premio); // Remove pontos de milhar
        $valor_premio = str_replace(',', '.', $valor_premio); // Converte vírgula em ponto
        $valor_premio = number_format(floatval($valor_premio), 2, '.', '');
        
        // Verificar se o apostador pertence ao revendedor atual
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND revendedor_id = ?");
        $stmt->execute([$usuario_id, $revendedor_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Este apostador não está vinculado à sua conta");
        }
        
        // Preparar os dados para inserção
        $dados = [
            'revendedor_id' => $_SESSION['usuario_id'],
            'usuario_id' => $usuario_id,
            'jogo_nome' => trim(explode("\n", $_POST['apostas'])[0]),
            'numeros' => $_POST['apostas'],
            'valor_aposta' => $valor_aposta,
            'valor_premio' => $valor_premio,
            'whatsapp' => $whatsapp
        ];

        // Inserir no banco de dados
        $stmt = $pdo->prepare("
            INSERT INTO apostas_importadas 
            (revendedor_id, usuario_id, jogo_nome, numeros, valor_aposta, valor_premio, whatsapp, created_at)
            VALUES 
            (:revendedor_id, :usuario_id, :jogo_nome, :numeros, :valor_aposta, :valor_premio, :whatsapp, NOW())
        ");
        $stmt->execute($dados);
        
        header('Location: importar_apostas.php?success=1');
        exit;
        
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Define a página atual para o menu
$currentPage = 'importar_apostas';

// Carrega a view
ob_start();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-import"></i> Importar Apostas
        </h1>
    </div>
        
    <div class="debug-info" style="background:#f4f6f9; padding: 0px; margin: 0px 0; border-radius: 0px; max-height: 0px; overflow-y: auto;">
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Aposta importada com sucesso!</div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" class="mt-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="apostador" class="form-label">Apostador</label>
                        <select class="form-control form-select" id="apostador" name="apostador" required onchange="atualizarWhatsApp()">
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
                        <label for="whatsapp" class="form-label">WhatsApp</label>
                        <input type="text" class="form-control" id="whatsapp" name="whatsapp" readonly>
                    </div>
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

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Valor por Aposta</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <select id="valor_aposta" name="valor_aposta" class="form-control" required>
                                <option value="">Selecione o valor</option>
                            </select>
                        </div>
                        <small class="form-text text-muted">Os valores disponíveis dependem do número de dezenas da aposta.</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quantidade de Dezenas</label>
                        <input type="text" id="qtd_dezenas" class="form-control" readonly>
                        <small class="form-text text-muted">Quantidade de dezenas detectada automaticamente.</small>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Cole as apostas aqui</label>
                    <textarea id="apostas" name="apostas" class="form-control" rows="10"></textarea>
                    <small class="form-text text-muted">Na primeira linha, coloque o nome do jogo. Nas linhas seguintes, coloque os números.</small>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-secondary" onclick="visualizarApostas()">
                        <i class="fas fa-eye"></i> Visualizar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Dados
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para visualização -->
<div class="modal fade" id="visualizarModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visualizar Apostas</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="resumoApostas"></div>
            </div>
        </div>
    </div>
</div>

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

// Função para formatar valor
function formatarValor(valor) {
    return valor.replace(/[^\d,]/g, '').replace(',', '.');
}

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
                
                premiacaoInput.value = valorPremio.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                // Atualizar informações de debug
                const valorBaseAposta = parseFloat(data.jogo.debug.valor_base_aposta);
                const valorBasePremio = parseFloat(data.jogo.debug.valor_base_premio);
                
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

    // Definição dos preços por jogo e número de dezenas
    const precosLotofacil = {
        17: [
            { valor: 1.00, premio: 7000.00 },
            { valor: 1.50, premio: 10500.00 },
            { valor: 2.00, premio: 14000.00 },
            { valor: 2.50, premio: 17500.00 },
            { valor: 3.00, premio: 21000.00 },
            { valor: 3.50, premio: 24500.00 },
            { valor: 4.00, premio: 28000.00 },
            { valor: 4.30, premio: 30000.00 }
        ],
        18: [
            { valor: 1.00, premio: 1500.00 },
            { valor: 1.50, premio: 2250.00 },
            { valor: 2.00, premio: 3000.00 },
            { valor: 3.00, premio: 4500.00 },
            { valor: 5.00, premio: 7500.00 },
            { valor: 7.00, premio: 10500.00 },
            { valor: 10.00, premio: 15000.00 },
            { valor: 15.00, premio: 22500.00 },
            { valor: 20.00, premio: 30000.00 }
        ],
        19: [
            { valor: 1.00, premio: 600.00 },
            { valor: 1.50, premio: 900.00 },
            { valor: 2.00, premio: 1200.00 },
            { valor: 3.00, premio: 1800.00 },
            { valor: 5.00, premio: 3000.00 },
            { valor: 7.00, premio: 4200.00 },
            { valor: 10.00, premio: 6000.00 },
            { valor: 15.00, premio: 9000.00 },
            { valor: 20.00, premio: 12000.00 },
            { valor: 25.00, premio: 15000.00 },
            { valor: 50.00, premio: 30000.00 }
        ],
        20: [
            { valor: 1.00, premio: 140.00 },
            { valor: 1.50, premio: 210.00 },
            { valor: 2.00, premio: 280.00 },
            { valor: 3.00, premio: 420.00 },
            { valor: 5.00, premio: 700.00 },
            { valor: 7.00, premio: 980.00 },
            { valor: 10.00, premio: 1400.00 },
            { valor: 25.00, premio: 3500.00 },
            { valor: 50.00, premio: 7000.00 },
            { valor: 100.00, premio: 14000.00 }
        ],
        21: [
            { valor: 1.00, premio: 50.00 },
            { valor: 1.50, premio: 75.00 },
            { valor: 2.00, premio: 100.00 },
            { valor: 3.00, premio: 150.00 },
            { valor: 5.00, premio: 250.00 },
            { valor: 7.00, premio: 350.00 },
            { valor: 10.00, premio: 500.00 },
            { valor: 15.00, premio: 750.00 },
            { valor: 25.00, premio: 1250.00 },
            { valor: 50.00, premio: 2500.00 },
            { valor: 100.00, premio: 5000.00 }
        ],
        22: [
            { valor: 1.00, premio: 13.00 },
            { valor: 1.50, premio: 19.50 },
            { valor: 2.00, premio: 26.00 },
            { valor: 3.00, premio: 39.00 },
            { valor: 5.00, premio: 65.00 },
            { valor: 7.00, premio: 91.00 },
            { valor: 15.00, premio: 195.00 },
            { valor: 20.00, premio: 260.00 },
            { valor: 25.00, premio: 325.00 },
            { valor: 50.00, premio: 650.00 },
            { valor: 100.00, premio: 1300.00 }
        ],
        23: [
            { valor: 5.00, premio: 25.00 },
            { valor: 10.00, premio: 50.00 },
            { valor: 25.00, premio: 125.00 },
            { valor: 50.00, premio: 250.00 },
            { valor: 100.00, premio: 500.00 }
        ]
    };

    const precosDiaDeSorte = {
        15: [
            { valor: 1.00, premio: 265.00 },
            { valor: 1.50, premio: 397.50 },
            { valor: 2.00, premio: 530.00 },
            { valor: 3.00, premio: 795.00 },
            { valor: 5.00, premio: 1325.00 },
            { valor: 10.00, premio: 2650.00 },
            { valor: 15.00, premio: 3975.00 },
            { valor: 20.00, premio: 5300.00 },
            { valor: 25.00, premio: 6625.00 },
            { valor: 50.00, premio: 13250.00 },
            { valor: 100.00, premio: 26500.00 }
        ],
        16: [
            { valor: 1.00, premio: 152.00 },
            { valor: 1.50, premio: 228.00 },
            { valor: 2.00, premio: 304.00 },
            { valor: 3.00, premio: 456.00 },
            { valor: 5.00, premio: 760.00 },
            { valor: 10.00, premio: 1520.00 },
            { valor: 15.00, premio: 2280.00 },
            { valor: 20.00, premio: 3040.00 },
            { valor: 25.00, premio: 3800.00 },
            { valor: 50.00, premio: 7600.00 },
            { valor: 100.00, premio: 15200.00 }
        ],
        17: [
            { valor: 1.00, premio: 90.00 },
            { valor: 1.50, premio: 135.00 },
            { valor: 2.00, premio: 180.00 },
            { valor: 3.00, premio: 270.00 },
            { valor: 5.00, premio: 450.00 },
            { valor: 10.00, premio: 900.00 },
            { valor: 15.00, premio: 1350.00 },
            { valor: 20.00, premio: 1800.00 },
            { valor: 25.00, premio: 2250.00 },
            { valor: 50.00, premio: 4500.00 },
            { valor: 100.00, premio: 9000.00 }
        ],
        18: [
            { valor: 1.00, premio: 55.00 },
            { valor: 1.50, premio: 82.50 },
            { valor: 2.00, premio: 110.00 },
            { valor: 3.00, premio: 165.00 },
            { valor: 5.00, premio: 275.00 },
            { valor: 10.00, premio: 550.00 },
            { valor: 15.00, premio: 825.00 },
            { valor: 20.00, premio: 1100.00 },
            { valor: 25.00, premio: 1375.00 },
            { valor: 50.00, premio: 2750.00 },
            { valor: 100.00, premio: 5500.00 }
        ],
        19: [
            { valor: 1.00, premio: 36.00 },
            { valor: 1.50, premio: 54.00 },
            { valor: 3.00, premio: 108.00 },
            { valor: 5.00, premio: 180.00 },
            { valor: 10.00, premio: 360.00 },
            { valor: 15.00, premio: 540.00 },
            { valor: 20.00, premio: 720.00 },
            { valor: 25.00, premio: 900.00 },
            { valor: 50.00, premio: 1800.00 },
            { valor: 100.00, premio: 3600.00 }
        ],
        20: [
            { valor: 1.00, premio: 23.00 },
            { valor: 1.50, premio: 34.50 },
            { valor: 3.00, premio: 69.00 },
            { valor: 5.00, premio: 115.00 },
            { valor: 10.00, premio: 230.00 },
            { valor: 15.00, premio: 345.00 },
            { valor: 20.00, premio: 460.00 },
            { valor: 25.00, premio: 575.00 },
            { valor: 50.00, premio: 1150.00 },
            { valor: 100.00, premio: 2300.00 }
        ],
        21: [
            { valor: 1.00, premio: 16.00 },
            { valor: 1.50, premio: 24.00 },
            { valor: 3.00, premio: 48.00 },
            { valor: 5.00, premio: 80.00 },
            { valor: 10.00, premio: 160.00 },
            { valor: 15.00, premio: 240.00 },
            { valor: 20.00, premio: 320.00 },
            { valor: 25.00, premio: 400.00 },
            { valor: 50.00, premio: 800.00 },
            { valor: 100.00, premio: 1600.00 }
        ],
        22: [
            { valor: 5.00, premio: 55.00 },
            { valor: 5.50, premio: 60.50 },
            { valor: 10.00, premio: 110.00 },
            { valor: 15.00, premio: 165.00 },
            { valor: 20.00, premio: 220.00 },
            { valor: 25.00, premio: 275.00 },
            { valor: 50.00, premio: 550.00 },
            { valor: 100.00, premio: 1100.00 }
        ]
    };

    const precosMaisMilionaria = {
        10: [
            { valor: 1.00, premio: 2000.00 },
            { valor: 1.50, premio: 3000.00 },
            { valor: 2.00, premio: 4000.00 },
            { valor: 3.00, premio: 6000.00 },
            { valor: 4.00, premio: 8000.00 },
            { valor: 5.00, premio: 10000.00 },
            { valor: 10.00, premio: 20000.00 },
            { valor: 15.00, premio: 30000.00 }
        ],
        15: [
            { valor: 1.00, premio: 350.00 },
            { valor: 1.50, premio: 525.00 },
            { valor: 2.00, premio: 700.00 },
            { valor: 3.00, premio: 1050.00 },
            { valor: 4.00, premio: 1400.00 },
            { valor: 5.00, premio: 1750.00 },
            { valor: 10.00, premio: 3500.00 },
            { valor: 15.00, premio: 5250.00 },
            { valor: 20.00, premio: 7000.00 },
            { valor: 25.00, premio: 8750.00 },
            { valor: 50.00, premio: 17500.00 },
            { valor: 86.00, premio: 30000.00 }
        ],
        20: [
            { valor: 1.00, premio: 135.00 },
            { valor: 1.50, premio: 202.50 },
            { valor: 2.00, premio: 270.00 },
            { valor: 3.00, premio: 405.00 },
            { valor: 4.00, premio: 540.00 },
            { valor: 5.00, premio: 675.00 },
            { valor: 10.00, premio: 1350.00 },
            { valor: 15.00, premio: 2025.00 },
            { valor: 20.00, premio: 2700.00 },
            { valor: 25.00, premio: 3375.00 },
            { valor: 50.00, premio: 6750.00 },
            { valor: 100.00, premio: 13500.00 }
        ],
        25: [
            { valor: 1.00, premio: 45.00 },
            { valor: 1.50, premio: 67.50 },
            { valor: 2.00, premio: 90.00 },
            { valor: 3.00, premio: 135.00 },
            { valor: 4.00, premio: 180.00 },
            { valor: 5.00, premio: 225.00 },
            { valor: 10.00, premio: 450.00 },
            { valor: 15.00, premio: 615.00 },
            { valor: 20.00, premio: 900.00 },
            { valor: 25.00, premio: 1125.00 },
            { valor: 50.00, premio: 2250.00 },
            { valor: 100.00, premio: 4500.00 }
        ],
        30: [
            { valor: 1.00, premio: 15.00 },
            { valor: 1.50, premio: 22.50 },
            { valor: 2.00, premio: 30.00 },
            { valor: 3.00, premio: 45.00 },
            { valor: 4.00, premio: 60.00 },
            { valor: 5.00, premio: 75.00 },
            { valor: 10.00, premio: 150.00 },
            { valor: 15.00, premio: 225.00 },
            { valor: 20.00, premio: 300.00 },
            { valor: 25.00, premio: 375.00 },
            { valor: 50.00, premio: 750.00 },
            { valor: 100.00, premio: 1500.00 }
        ],
        35: [
            { valor: 1.00, premio: 6.00 },
            { valor: 1.50, premio: 9.00 },
            { valor: 2.00, premio: 12.00 },
            { valor: 3.00, premio: 18.00 },
            { valor: 4.00, premio: 24.00 },
            { valor: 5.00, premio: 30.00 },
            { valor: 10.00, premio: 60.00 },
            { valor: 15.00, premio: 90.00 },
            { valor: 20.00, premio: 120.00 },
            { valor: 25.00, premio: 150.00 },
            { valor: 50.00, premio: 300.00 },
            { valor: 100.00, premio: 600.00 }
        ]
    };

    const precosMegaSena = {
        20: [
            { valor: 1.00, premio: 800.00 },
            { valor: 1.50, premio: 1200.00 },
            { valor: 2.00, premio: 1600.00 },
            { valor: 3.00, premio: 2400.00 },
            { valor: 5.00, premio: 4000.00 },
            { valor: 7.00, premio: 5600.00 },
            { valor: 10.00, premio: 8000.00 },
            { valor: 15.00, premio: 12000.00 },
            { valor: 20.00, premio: 16000.00 },
            { valor: 25.00, premio: 20000.00 },
            { valor: 37.50, premio: 30000.00 }
        ],
        25: [
            { valor: 1.00, premio: 167.00 },
            { valor: 1.50, premio: 250.50 },
            { valor: 2.00, premio: 334.00 },
            { valor: 3.00, premio: 501.00 },
            { valor: 5.00, premio: 835.00 },
            { valor: 7.00, premio: 1169.00 },
            { valor: 10.00, premio: 1670.00 },
            { valor: 15.00, premio: 2505.00 },
            { valor: 20.00, premio: 3340.00 },
            { valor: 25.00, premio: 4175.00 },
            { valor: 50.00, premio: 8350.00 },
            { valor: 100.00, premio: 16700.00 }
        ],
        30: [
            { valor: 1.00, premio: 56.00 },
            { valor: 1.50, premio: 84.00 },
            { valor: 2.00, premio: 112.00 },
            { valor: 3.00, premio: 168.00 },
            { valor: 5.00, premio: 280.00 },
            { valor: 7.00, premio: 392.00 },
            { valor: 10.00, premio: 560.00 },
            { valor: 15.00, premio: 840.00 },
            { valor: 20.00, premio: 1120.00 },
            { valor: 25.00, premio: 1400.00 },
            { valor: 50.00, premio: 2800.00 },
            { valor: 100.00, premio: 5600.00 }
        ],
        35: [
            { valor: 1.00, premio: 22.00 },
            { valor: 1.50, premio: 33.00 },
            { valor: 2.00, premio: 44.00 },
            { valor: 3.00, premio: 66.00 },
            { valor: 5.00, premio: 110.00 },
            { valor: 7.00, premio: 154.00 },
            { valor: 10.00, premio: 220.00 },
            { valor: 15.00, premio: 330.00 },
            { valor: 20.00, premio: 440.00 },
            { valor: 25.00, premio: 550.00 },
            { valor: 50.00, premio: 1100.00 },
            { valor: 100.00, premio: 2200.00 }
        ],
        40: [
            { valor: 5.00, premio: 45.00 },
            { valor: 5.50, premio: 49.50 },
            { valor: 10.00, premio: 90.00 },
            { valor: 15.00, premio: 135.00 },
            { valor: 20.00, premio: 180.00 },
            { valor: 25.00, premio: 225.00 },
            { valor: 50.00, premio: 450.00 },
            { valor: 100.00, premio: 900.00 }
        ],
        45: [
            { valor: 5.00, premio: 15.00 },
            { valor: 5.50, premio: 16.50 },
            { valor: 10.00, premio: 30.00 },
            { valor: 15.00, premio: 45.00 },
            { valor: 20.00, premio: 60.00 },
            { valor: 25.00, premio: 75.00 },
            { valor: 50.00, premio: 150.00 },
            { valor: 100.00, premio: 300.00 }
        ]
    };

    const precosQuina = {
        20: [
            { valor: 1.00, premio: 800.00 },
            { valor: 1.50, premio: 1200.00 },
            { valor: 2.00, premio: 1600.00 },
            { valor: 3.00, premio: 2400.00 },
            { valor: 5.00, premio: 4000.00 },
            { valor: 10.00, premio: 8000.00 },
            { valor: 15.00, premio: 12000.00 },
            { valor: 20.00, premio: 16000.00 },
            { valor: 25.00, premio: 20000.00 },
            { valor: 37.50, premio: 30000.00 }
        ],
        25: [
            { valor: 1.00, premio: 260.00 },
            { valor: 1.50, premio: 390.00 },
            { valor: 2.00, premio: 520.00 },
            { valor: 3.00, premio: 780.00 },
            { valor: 5.00, premio: 1300.00 },
            { valor: 10.00, premio: 2600.00 },
            { valor: 15.00, premio: 3900.00 },
            { valor: 20.00, premio: 5200.00 },
            { valor: 25.00, premio: 6500.00 },
            { valor: 50.00, premio: 13000.00 },
            { valor: 100.00, premio: 26000.00 }
        ],
        30: [
            { valor: 1.00, premio: 115.00 },
            { valor: 1.50, premio: 172.50 },
            { valor: 2.00, premio: 230.00 },
            { valor: 3.00, premio: 345.00 },
            { valor: 5.00, premio: 575.00 },
            { valor: 10.00, premio: 1150.00 },
            { valor: 15.00, premio: 1725.00 },
            { valor: 20.00, premio: 2300.00 },
            { valor: 25.00, premio: 2875.00 },
            { valor: 50.00, premio: 5750.00 },
            { valor: 100.00, premio: 11500.00 }
        ],
        35: [
            { valor: 1.00, premio: 55.00 },
            { valor: 1.50, premio: 82.50 },
            { valor: 2.00, premio: 110.00 },
            { valor: 3.00, premio: 165.00 },
            { valor: 5.00, premio: 275.00 },
            { valor: 10.00, premio: 550.00 },
            { valor: 15.00, premio: 825.00 },
            { valor: 20.00, premio: 1100.00 },
            { valor: 25.00, premio: 1375.00 },
            { valor: 50.00, premio: 2750.00 },
            { valor: 100.00, premio: 5500.00 }
        ],
        40: [
            { valor: 1.00, premio: 26.00 },
            { valor: 1.50, premio: 39.00 },
            { valor: 2.00, premio: 52.00 },
            { valor: 3.00, premio: 78.00 },
            { valor: 5.00, premio: 130.00 },
            { valor: 10.00, premio: 260.00 },
            { valor: 15.00, premio: 390.00 },
            { valor: 20.00, premio: 520.00 },
            { valor: 25.00, premio: 650.00 },
            { valor: 50.00, premio: 1300.00 },
            { valor: 100.00, premio: 2600.00 }
        ],
        45: [
            { valor: 5.00, premio: 65.00 },
            { valor: 10.00, premio: 130.00 },
            { valor: 15.00, premio: 195.00 },
            { valor: 20.00, premio: 260.00 },
            { valor: 25.00, premio: 325.00 },
            { valor: 35.00, premio: 585.00 },
            { valor: 50.00, premio: 650.00 },
            { valor: 100.00, premio: 1300.00 }
        ],
        50: [
            { valor: 5.00, premio: 25.00 },
            { valor: 5.50, premio: 27.50 },
            { valor: 10.00, premio: 50.00 },
            { valor: 50.00, premio: 250.00 },
            { valor: 100.00, premio: 500.00 }
        ]
    };

    const precosLotomania = {
        55: [
            { valor: 1.00, premio: 15000.00 },
            { valor: 1.50, premio: 22500.00 },
            { valor: 2.00, premio: 30000.00 }
        ],
        60: [
            { valor: 1.00, premio: 10000.00 },
            { valor: 1.50, premio: 15000.00 },
            { valor: 2.00, premio: 20000.00 },
            { valor: 2.50, premio: 25000.00 },
            { valor: 3.00, premio: 30000.00 }
        ],
        65: [
            { valor: 1.00, premio: 2000.00 },
            { valor: 1.50, premio: 3000.00 },
            { valor: 2.00, premio: 4000.00 },
            { valor: 2.50, premio: 5000.00 },
            { valor: 3.00, premio: 6000.00 },
            { valor: 5.00, premio: 10000.00 },
            { valor: 7.00, premio: 14000.00 },
            { valor: 10.00, premio: 20000.00 },
            { valor: 15.00, premio: 30000.00 }
        ],
        70: [
            { valor: 1.00, premio: 520.00 },
            { valor: 1.50, premio: 780.00 },
            { valor: 2.00, premio: 1040.00 },
            { valor: 3.00, premio: 1560.00 },
            { valor: 5.00, premio: 2600.00 },
            { valor: 7.00, premio: 3640.00 },
            { valor: 10.00, premio: 5200.00 },
            { valor: 15.00, premio: 7800.00 },
            { valor: 20.00, premio: 10400.00 },
            { valor: 25.00, premio: 13000.00 },
            { valor: 50.00, premio: 26000.00 },
            { valor: 58.00, premio: 30000.00 }
        ],
        75: [
            { valor: 1.00, premio: 280.00 },
            { valor: 1.50, premio: 420.00 },
            { valor: 2.00, premio: 560.00 },
            { valor: 3.00, premio: 840.00 },
            { valor: 5.00, premio: 1400.00 },
            { valor: 7.00, premio: 1960.00 },
            { valor: 10.00, premio: 2800.00 },
            { valor: 15.00, premio: 4200.00 },
            { valor: 20.00, premio: 5600.00 },
            { valor: 25.00, premio: 7000.00 },
            { valor: 50.00, premio: 14000.00 },
            { valor: 100.00, premio: 28000.00 }
        ],
        80: [
            { valor: 1.00, premio: 77.00 },
            { valor: 1.50, premio: 115.00 },
            { valor: 2.00, premio: 154.00 },
            { valor: 3.00, premio: 231.00 },
            { valor: 5.00, premio: 385.00 },
            { valor: 7.00, premio: 539.00 },
            { valor: 10.00, premio: 770.00 },
            { valor: 15.00, premio: 1155.00 },
            { valor: 20.00, premio: 1540.00 },
            { valor: 25.00, premio: 1925.00 },
            { valor: 50.00, premio: 3850.00 },
            { valor: 100.00, premio: 7700.00 }
        ],
        85: [
            { valor: 5.00, premio: 75.00 },
            { valor: 5.50, premio: 82.50 },
            { valor: 10.00, premio: 150.00 },
            { valor: 15.00, premio: 225.00 },
            { valor: 20.00, premio: 300.00 },
            { valor: 25.00, premio: 375.00 },
            { valor: 50.00, premio: 750.00 },
            { valor: 100.00, premio: 1150.00 }
        ]
    };

    const precosTimemania = {
        20: [
            { valor: 1.00, premio: 2000.00 },
            { valor: 1.50, premio: 3000.00 },
            { valor: 2.00, premio: 4000.00 },
            { valor: 3.00, premio: 6000.00 },
            { valor: 5.00, premio: 10000.00 },
            { valor: 10.00, premio: 20000.00 },
            { valor: 15.00, premio: 30000.00 }
        ],
        25: [
            { valor: 1.00, premio: 900.00 },
            { valor: 1.50, premio: 1350.00 },
            { valor: 2.00, premio: 1800.00 },
            { valor: 3.00, premio: 2700.00 },
            { valor: 5.00, premio: 4500.00 },
            { valor: 10.00, premio: 9000.00 },
            { valor: 15.00, premio: 13500.00 },
            { valor: 20.00, premio: 18000.00 },
            { valor: 25.00, premio: 22500.00 },
            { valor: 34.00, premio: 30000.00 }
        ],
        30: [
            { valor: 1.00, premio: 320.00 },
            { valor: 1.50, premio: 480.00 },
            { valor: 2.00, premio: 640.00 },
            { valor: 3.00, premio: 960.00 },
            { valor: 5.00, premio: 1600.00 },
            { valor: 10.00, premio: 3200.00 },
            { valor: 15.00, premio: 4800.00 },
            { valor: 20.00, premio: 6400.00 },
            { valor: 25.00, premio: 8000.00 },
            { valor: 50.00, premio: 16000.00 },
            { valor: 94.00, premio: 30000.00 }
        ],
        35: [
            { valor: 1.00, premio: 120.00 },
            { valor: 1.50, premio: 180.00 },
            { valor: 2.00, premio: 240.00 },
            { valor: 3.00, premio: 360.00 },
            { valor: 5.00, premio: 600.00 },
            { valor: 10.00, premio: 1200.00 },
            { valor: 15.00, premio: 1800.00 },
            { valor: 20.00, premio: 2400.00 },
            { valor: 25.00, premio: 3000.00 },
            { valor: 50.00, premio: 6000.00 },
            { valor: 100.00, premio: 12000.00 }
        ],
        40: [
            { valor: 1.00, premio: 65.00 },
            { valor: 1.50, premio: 97.50 },
            { valor: 2.00, premio: 130.00 },
            { valor: 3.00, premio: 195.00 },
            { valor: 5.00, premio: 325.00 },
            { valor: 10.00, premio: 650.00 },
            { valor: 15.00, premio: 975.00 },
            { valor: 20.00, premio: 1300.00 },
            { valor: 25.00, premio: 1625.00 },
            { valor: 50.00, premio: 3250.00 },
            { valor: 100.00, premio: 6500.00 }
        ],
        45: [
            { valor: 5.00, premio: 160.00 },
            { valor: 5.50, premio: 176.00 },
            { valor: 10.00, premio: 320.00 },
            { valor: 15.00, premio: 480.00 },
            { valor: 20.00, premio: 640.00 },
            { valor: 25.00, premio: 800.00 },
            { valor: 50.00, premio: 1600.00 },
            { valor: 100.00, premio: 3200.00 }
        ],
        50: [
            { valor: 5.00, premio: 80.00 },
            { valor: 5.50, premio: 88.00 },
            { valor: 10.00, premio: 160.00 },
            { valor: 15.00, premio: 240.00 },
            { valor: 20.00, premio: 320.00 },
            { valor: 25.00, premio: 400.00 },
            { valor: 50.00, premio: 800.00 },
            { valor: 100.00, premio: 1600.00 }
        ],
        55: [
            { valor: 5.00, premio: 50.00 },
            { valor: 5.50, premio: 55.00 },
            { valor: 10.00, premio: 100.00 },
            { valor: 15.00, premio: 150.00 },
            { valor: 20.00, premio: 200.00 },
            { valor: 25.00, premio: 250.00 },
            { valor: 50.00, premio: 500.00 },
            { valor: 100.00, premio: 1000.00 }
        ]
    };

    // Função para contar dezenas em uma linha de aposta
    function contarDezenas(linha) {
        const numeros = linha.match(/\d+/g);
        return numeros ? numeros.length : 0;
    }

    // Função para processar o texto das apostas
    function processarApostas() {
        const textarea = document.getElementById('apostas');
        const texto = textarea.value.trim();
        const linhas = texto.split('\n').filter(linha => linha.trim());
        
        if (linhas.length >= 1) {
            const nomeJogoLinha = linhas[0].trim().toUpperCase(); // Nome do jogo na primeira linha
            let nomeJogo = '';
            
            // Extrai o código do jogo (QN, DI, MM, MS, LM, etc)
            if (nomeJogoLinha.includes('QN')) nomeJogo = 'QN';
            else if (nomeJogoLinha.includes('DI')) nomeJogo = 'DI';
            else if (nomeJogoLinha.includes('MM')) nomeJogo = 'MM';
            else if (nomeJogoLinha.includes('MS')) nomeJogo = 'MS';
            else if (nomeJogoLinha.includes('LF')) nomeJogo = 'LF';
            else if (nomeJogoLinha.includes('LF')) nomeJogo = 'LF';
            else if (nomeJogoLinha.includes('LM')) nomeJogo = 'LM';
            else if (nomeJogoLinha.includes('TM')) nomeJogo = 'TM';
            
            // Se houver uma segunda linha com números
            if (linhas.length >= 2) {
                const primeiraAposta = linhas[1]; // A segunda linha é a primeira aposta
                const numDezenas = contarDezenas(primeiraAposta);
                
                // Atualiza o campo de quantidade de dezenas
                document.getElementById('qtd_dezenas').value = numDezenas + ' dezenas';
                
                // Atualiza as opções de valor baseado no nome do jogo e número de dezenas
                atualizarOpcoesValor(nomeJogo, numDezenas);
                
                console.log('Jogo detectado:', nomeJogo);
                console.log('Dezenas detectadas:', numDezenas);
            } else {
                document.getElementById('qtd_dezenas').value = '0 dezenas';
                atualizarOpcoesValor(nomeJogo, 0);
            }
        } else {
            document.getElementById('qtd_dezenas').value = '0 dezenas';
            atualizarOpcoesValor(nomeJogo, 0);
        }
    }

    // Função para atualizar as opções de valor baseado no nome do jogo e número de dezenas
    function atualizarOpcoesValor(nomeJogo, numDezenas) {
        const selectValor = document.getElementById('valor_aposta');
        selectValor.innerHTML = '<option value="">Selecione o valor</option>';
        
        console.log('Atualizando valores para:', nomeJogo, numDezenas);
        
        let precos = [];
        
        // Determina os preços baseado no nome do jogo
        if (nomeJogo && numDezenas > 0) {
            switch(nomeJogo) {
                case 'LF': // Lotofácil
                    precos = precosLotofacil[numDezenas] || [];
                    break;
                case 'DI': // Dia de Sorte
                    precos = precosDiaDeSorte[numDezenas] || [];
                    break;
                case 'MM': // Mais Milionária
                    precos = precosMaisMilionaria[numDezenas] || [];
                    break;
                case 'MS': // Mega Sena
                    precos = precosMegaSena[numDezenas] || [];
                    break;
                case 'QN': // Quina
                    precos = precosQuina[numDezenas] || [];
                    break;
                case 'LM': // Lotomania
                    precos = precosLotomania[numDezenas] || [];
                    break;
                case 'TM': // Timemania
                    precos = precosTimemania[numDezenas] || [];
                    break;
            }
        }
        
        console.log('Preços encontrados:', precos.length);
        
        if (precos.length > 0) {
            precos.forEach(preco => {
                const option = document.createElement('option');
                option.value = preco.valor.toFixed(2);
                option.textContent = `R$ ${preco.valor.toFixed(2)} → R$ ${preco.premio.toFixed(2)}`;
                option.dataset.premio = preco.premio.toFixed(2);
                selectValor.appendChild(option);
            });
        } else {
            const option = document.createElement('option');
            option.value = "";
            if (!nomeJogo) {
                option.textContent = "Selecione um jogo válido";
            } else if (numDezenas === 0) {
                option.textContent = "Digite os números da aposta";
            } else {
                option.textContent = "Número de dezenas inválido para este jogo";
            }
            selectValor.appendChild(option);
        }

        // Atualizar o valor da premiação quando mudar o valor da aposta
        selectValor.onchange = function() {
            const option = this.options[this.selectedIndex];
            if (option && option.dataset.premio) {
                document.getElementById('valor_premiacao').value = 
                    parseFloat(option.dataset.premio).toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
            } else {
                document.getElementById('valor_premiacao').value = '0,00';
            }
        };
    }

    // Event Listeners
    document.getElementById('apostas').addEventListener('input', processarApostas);
    document.getElementById('apostas').addEventListener('paste', function(e) {
        setTimeout(processarApostas, 0);
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
<style>
.numero-bolinha {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background-color: #4e73df;
    color: white;
    border-radius: 50%;
    margin: 2px;
    font-weight: 600;
    font-size: 13px;
    padding: 0;
    line-height: 1;
    text-align: center;
}

.aposta-item {
    padding: 15px;
    border-bottom: 1px solid #e3e6f0;
}

.aposta-item:last-child {
    border-bottom: none;
}

.apostas-preview {
    max-height: 500px;
    overflow-y: auto;
}

.alert-info {
    background-color: #e3f2fd;
    border-color: #bee5eb;
    color: #0c5460;
    margin-bottom: 20px;
}

.gap-1 {
    gap: 0.25rem !important;
}

.modal-lg {
    max-width: 800px;
}

.text-primary {
    color: #4e73df !important;
}

#valor_aposta {
    text-align: right;
}

.text-danger {
    color: #dc3545 !important;
}
</style>
`);
</script>

<style>
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
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

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 