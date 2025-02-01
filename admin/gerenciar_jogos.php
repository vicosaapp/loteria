<?php
require_once 'includes/header.php';
require_once '../config/database.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Define a página atual para o menu
$currentPage = 'jogos';

// Buscar todos os jogos com seus valores
$stmt = $pdo->query("
    SELECT j.*, 
           GROUP_CONCAT(CONCAT(v.valor_aposta, ':', v.dezenas, ':', v.valor_premio) SEPARATOR '|') as valores_info
    FROM jogos j
    LEFT JOIN valores_jogos v ON j.id = v.jogo_id
    GROUP BY j.id
    ORDER BY j.id DESC
");
$jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-gamepad"></i> Gerenciar Jogos</h1>
            <p>Gerencie os jogos disponíveis no sistema</p>
        </div>
        <button onclick="abrirModal()" class="btn-create">
            <i class="fas fa-plus-circle"></i>
            <span>Novo Jogo</span>
        </button>
    </div>

    <div class="cards-grid">
        <?php foreach($jogos as $jogo): ?>
            <div class="game-card">
                <div class="card-header">
                    <h3><?php echo htmlspecialchars($jogo['nome']); ?></h3>
                    <span class="status-badge <?php echo $jogo['status'] ? 'active' : 'inactive'; ?>">
                        <?php echo $jogo['status'] ? 'Ativo' : 'Inativo'; ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="label">Números:</span>
                        <span class="value">Min: <?php echo $jogo['minimo_numeros']; ?> | Max: <?php echo $jogo['maximo_numeros']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Acertos para Prêmio:</span>
                        <span class="value"><?php echo $jogo['acertos_premio']; ?> números</span>
                    </div>
                    
                    <div class="valores-section">
                        <h4>Valores e Premiações</h4>
                        <table class="valores-table">
                            <thead>
                                <tr>
                                    <th>Aposta</th>
                                    <th>Dezenas</th>
                                    <th>Prêmio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (!empty($jogo['valores_info'])) {
                                    foreach(explode('|', $jogo['valores_info']) as $valor_info) {
                                        list($valor_aposta, $dezenas, $valor_premio) = explode(':', $valor_info);
                                        // Convertendo os valores para o formato correto
                                        $valor_aposta = floatval($valor_aposta) * 100; // Multiplica por 100
                                        $valor_premio = floatval($valor_premio) * 100; // Multiplica por 100
                                ?>
                                    <tr>
                                        <td>R$ <?php echo number_format($valor_aposta, 2, ',', '.'); ?></td>
                                        <td><?php echo $dezenas; ?></td>
                                        <td>R$ <?php echo number_format($valor_premio, 2, ',', '.'); ?></td>
                                    </tr>
                                <?php 
                                    }
                                } 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-actions">
                    <button class="btn-edit" onclick='editarJogo(<?php echo json_encode($jogo); ?>)'>
                        <i class="fas fa-edit"></i>
                        <span>Editar</span>
                    </button>
                    <button class="btn-delete" onclick="excluirJogo(<?php echo $jogo['id']; ?>)">
                        <i class="fas fa-trash"></i>
                        <span>Excluir</span>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal" id="jogoModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Novo Jogo</h2>
            <button type="button" class="close" onclick="fecharModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <form id="jogoForm" onsubmit="prepararDadosParaSalvar(event)">
                <input type="hidden" id="jogoId">
                
                <div class="form-group">
                    <label for="nome">Nome do Jogo</label>
                    <input type="text" id="nome" class="form-control" required>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">Configuração Básica</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Mínimo de Números</label>
                            <div class="input-suffix">
                                <input type="number" id="minimo_numeros" class="form-control" required min="1" onchange="gerarTabelaPrecos()">
                                <span>números</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Máximo de Números</label>
                            <div class="input-suffix">
                                <input type="number" id="maximo_numeros" class="form-control" required min="1" onchange="gerarTabelaPrecos()">
                                <span>números</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Acertos para Prêmio</label>
                        <div class="input-suffix">
                            <input type="number" id="acertos_premio" class="form-control" required min="1">
                            <span>acertos</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Status do Jogo</label>
                        <select id="status" class="form-control">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">Tabela de Valores e Premiações</h3>
                    <div class="valores-container">
                        <div class="novo-valor-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Valor da Aposta</label>
                                    <div class="input-prefix">
                                        <span>R$</span>
                                        <input type="text" id="novo_valor_aposta" class="form-control money" placeholder="0,00">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Quantidade de Dezenas</label>
                                    <div class="input-suffix">
                                        <input type="number" id="novo_dezenas" class="form-control" min="1">
                                        <span>números</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Valor do Prêmio</label>
                                    <div class="input-prefix">
                                        <span>R$</span>
                                        <input type="text" id="novo_valor_premio" class="form-control money" placeholder="0,00">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="button" class="btn btn-add" onclick="adicionarValorTabela()">
                                        <i class="fas fa-plus"></i> Adicionar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <table class="table-valores">
                            <thead>
                                <tr>
                                    <th>Valor da Aposta</th>
                                    <th>Dezenas</th>
                                    <th>Valor do Prêmio</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="valoresTableBody">
                                <!-- Valores serão adicionados aqui -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" onclick="prepararDadosParaSalvar(event)">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Grid de Cards */
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    padding: 20px;
}

/* Card do Jogo */
.game-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    overflow: hidden;
    border: 2px solid #4e73df; /* Borda azul sempre visível */
    transition: all 0.3s ease;
}

.game-card:hover {
    box-shadow: 0 4px 8px rgba(78,115,223,0.2);
    transform: translateY(-2px);
}

.card-header {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    color: white;
    font-size: 1.2rem;
    flex-grow: 1;
    text-align: center;
}

.card-body {
    padding: 20px;
}

.status-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.85em;
    color: white;
    font-weight: 500;
    margin-left: 10px;
    white-space: nowrap;
}

.status-badge.active {
    background: #1cc88a;
}

.status-badge.inactive {
    background: #e74a3b;
}

/* Informações do Jogo */
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solidrgb(197, 197, 197);
}

.info-row:last-child {
    border-bottom: none;
}

.info-row .label {
    color: #4e73df;
    font-weight: 600;
}

.info-row .value {
    color: #5a5c69;
}

/* Números */
.numbers-title {
    margin: 20px 0 15px;
    font-weight: 600;
    color: #2c3e50;
    text-transform: uppercase;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
}

.numbers-grid {
    display: grid;
    grid-template-columns: repeat(10, 1fr);
    gap: 6px;
    padding: 15px;
    background: rgba(255,255,255,0.9);
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.number {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #4e73df;
    border-radius: 50%;
    font-size: 0.85rem;
    color: #4e73df;
    background: white;
    transition: all 0.2s ease;
    cursor: pointer;
}

.number:hover {
    background: #4e73df;
    color: white;
    transform: scale(1.1);
}

/* Botões de Ação */
.card-actions {
    display: flex;
    gap: 10px;
    padding: 15px 20px;
    background: #f8f9fc;
    border-top: 1px solid #e3e6f0;
}

.btn-edit, .btn-delete {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 5px;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn-edit {
    background: #4e73df;
}

.btn-edit:hover {
    background: #2e59d9;
}

.btn-delete {
    background: #e74a3b;
}

.btn-delete:hover {
    background: #d52a1a;
}

/* Responsividade */
@media (max-width: 480px) {
    .cards-grid {
        grid-template-columns: 1fr;
    }
    
    .numbers-grid {
        grid-template-columns: repeat(8, 1fr);
    }
}

/* Botão Novo Jogo */
.btn-create {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #00ff7f; /* Verde fluorescente */
    color: #000; /* Texto preto para melhor contraste */
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(0,255,127,0.3);
}

.btn-create:hover {
    background: #00ff95; /* Verde fluorescente um pouco mais claro no hover */
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,255,127,0.4);
}

.btn-create i {
    font-size: 1.1rem;
}

/* Estilos do Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
    z-index: 1050;
}

.modal-content {
    position: relative;
    background: #fff;
    margin: 30px auto;
    width: 90%;
    max-width: 800px;
    border-radius: 15px;
    box-shadow: 0 15px 30px rgba(0,0,0,0.2);
    animation: modalSlideIn 0.3s ease;
}

.modal-header {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    padding: 20px 25px;
    border-radius: 15px 15px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h2 {
    color: white;
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.modal-body {
    padding: 25px;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}

.form-section {
    background: #f8f9fc;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 1px solid #e3e6f0;
}

.form-section-title {
    color: #4e73df;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e3e6f0;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: #5a5c69;
    font-weight: 600;
    margin-bottom: 8px;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e3e6f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s ease;
}

.form-control:focus {
    border-color: #4e73df;
    box-shadow: 0 0 0 3px rgba(78,115,223,0.1);
    outline: none;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #e3e6f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn {
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: #4e73df;
    color: white;
}

.btn-primary:hover {
    background: #2e59d9;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #858796;
    color: white;
}

.btn-secondary:hover {
    background: #717384;
    transform: translateY(-1px);
}

.close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.2s ease;
    padding: 0;
}

.close:hover {
    opacity: 1;
}

/* Animações */
@keyframes modalSlideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Responsividade */
@media (max-width: 768px) {
    .modal-content {
        margin: 15px;
        width: calc(100% - 30px);
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}

/* Tabela de Valores */
.table-valores {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.table-valores th {
    background: #f8f9fc;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #4e73df;
    border-bottom: 2px solid #e3e6f0;
}

.table-valores td {
    padding: 12px;
    text-align: center;
    border: 1px solid #e3e6f0;
    font-weight: 700; /* Negrito para todos os valores */
}

.btn-add {
    background: #1cc88a;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 15px;
    transition: all 0.2s ease;
}

.btn-add:hover {
    background: #169b6b;
    transform: translateY(-1px);
}

.input-suffix, .input-prefix {
    position: relative;
    display: flex;
    align-items: center;
}

.input-suffix input {
    padding-right: 60px;
}

.input-suffix span {
    position: absolute;
    right: 15px;
    color: #6e707e;
    background: #f8f9fc;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85em;
}

/* Adicione estes estilos */
.novo-valor-form {
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    border: 1px solid #e3e6f0;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    align-items: end;
}

.input-prefix {
    position: relative;
}

.input-prefix span {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6e707e;
}

.input-prefix input {
    padding-left: 35px;
}

.btn-add {
    height: 45px;
    width: 100%;
    justify-content: center;
}

.table-valores {
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.table-valores th, .table-valores td {
    text-align: center;
}

.btn-remove {
    background: #e74a3b;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-remove:hover {
    background: #d52a1a;
}

.valores-section {
    margin-top: 20px;
    background: #d9d9d9;
    padding: 15px;
    border-radius: 8px;
}

.valores-section h4 {
    color: #4e73df;
    margin: 0 0 15px 0;
    font-size: 1.1rem;
    padding-bottom: 10px;
    border-bottom: 2px solid #e3e6f0;
}

.valores-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 5px;
    overflow: hidden;
}

.valores-table th,
.valores-table td {
    padding: 12px;
    text-align: center;
    border: 1px solid #e3e6f0;
}

/* Cores diferentes para cada cabeçalho */
.valores-table th:nth-child(1) {
    background:rgb(8, 66, 240); /* Azul para Aposta */
}

.valores-table th:nth-child(2) {
    background:rgba(0, 160, 35, 0.94); /* Verde para Dezenas */
}

.valores-table th:nth-child(3) {
    background:rgb(223, 71, 0); /* Amarelo para Prêmio */
}

.valores-table th {
    color: white;
    font-weight: 500;
}

/* Cores suaves correspondentes para as células */
.valores-table td:nth-child(1) {
    color: #4e73df;
    font-weight: 700; /* Negrito para todos os valores */
}

.valores-table td:nth-child(2) {
    color: #1cc88a;
    font-weight: 700; /* Negrito para todos os valores */
}

.valores-table td:nth-child(3) {
    color: #f6c23e;
    font-weight: 700; /* Negrito para todos os valores */
}

.valores-table tr:nth-child(even) {
    background: #f8f9fc;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Adicione esta função de debug no início do arquivo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Modal element:', document.getElementById('jogoModal'));
});

function editarJogo(jogo) {
    console.log('Função editarJogo chamada', jogo);
    
    const modal = document.getElementById('jogoModal');
    if (!modal) {
        console.error('Modal não encontrado!');
        return;
    }
    
    try {
        // Preencher os campos
        document.getElementById('modalTitle').textContent = 'Editar Jogo';
        document.getElementById('jogoId').value = jogo.id;
        document.getElementById('nome').value = jogo.nome;
        document.getElementById('minimo_numeros').value = jogo.minimo_numeros;
        document.getElementById('maximo_numeros').value = jogo.maximo_numeros;
        document.getElementById('acertos_premio').value = jogo.acertos_premio;
        document.getElementById('status').value = jogo.status;
        
        // Limpar tabela de valores
        const tbody = document.getElementById('valoresTableBody');
        tbody.innerHTML = '';
        
        // Adicionar valores existentes
        if (jogo.valores && jogo.valores.length > 0) {
            const valoresAgrupados = {};
            
            // Agrupar por valor_aposta
            jogo.valores.forEach(valor => {
                if (!valoresAgrupados[valor.valor_aposta]) {
                    valoresAgrupados[valor.valor_aposta] = [];
                }
                valoresAgrupados[valor.valor_aposta].push(valor);
            });
            
            // Adicionar cada grupo de valores
            Object.entries(valoresAgrupados).forEach(([valorAposta, premiacoes]) => {
                adicionarValorTabela();
                const ultimaLinha = tbody.querySelector('tr:last-child');
                if (ultimaLinha) {
                    const inputValorAposta = ultimaLinha.querySelector('td:first-child input');
                    inputValorAposta.value = formatarMoeda(valorAposta);
                    
                    premiacoes.forEach(premiacao => {
                        const inputPremiacao = ultimaLinha.querySelector(`input[data-numeros="${premiacao.dezenas}"]`);
                        if (inputPremiacao) {
                            inputPremiacao.value = formatarMoeda(premiacao.valor_premio);
                        }
                    });
                }
            });
        } else {
            adicionarValorTabela();
        }
        
        // Exibir o modal
        modal.style.display = 'block';
        console.log('Modal aberto');
        
    } catch (error) {
        console.error('Erro ao editar jogo:', error);
        alert('Erro ao abrir o modal de edição: ' + error.message);
    }
}

function excluirJogo(id) {
    if(confirm('Tem certeza que deseja excluir este jogo?')) {
        // Implementar exclusão
        alert('Excluir jogo ' + id);
    }
}

$(document).ready(function() {
    $('#btnNovoJogo').click(function() {
        // Implementar criação de novo jogo
        alert('Criar novo jogo');
    });
});

function salvarJogo(formData) {
    fetch('ajax/salvar_jogo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Jogo salvo com sucesso!');
            window.location.reload();
        } else {
            throw new Error(data.message || 'Erro ao salvar o jogo');
        }
    })
    .catch(error => {
        throw new Error('Erro ao salvar: ' + error.message);
    });
}

function abrirModal() {
    // Limpar formulário
    document.getElementById('jogoForm').reset();
    document.getElementById('modalTitle').textContent = 'Novo Jogo';
    document.getElementById('jogoId').value = '';
    document.getElementById('valoresTableBody').innerHTML = '';
    adicionarValorTabela();
    
    // Exibir modal
    document.getElementById('jogoModal').style.display = 'block';
}

function fecharModal() {
    document.getElementById('jogoModal').style.display = 'none';
}

function formatarMoeda(valor) {
    if (!valor) return '';
    valor = valor.toString().replace(/\D/g, '');
    valor = (parseInt(valor) / 100).toFixed(2);
    valor = valor.replace('.', ',');
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    return valor;
}

function gerarTabelaPrecos() {
    const minimo = parseInt(document.getElementById('minimo_numeros').value) || 0;
    const maximo = parseInt(document.getElementById('maximo_numeros').value) || 0;
    
    if (minimo > 0 && maximo > 0 && minimo <= maximo) {
        atualizarCabecalhosPremiacoes(minimo, maximo);
    }
}

function atualizarCabecalhosPremiacoes(minimo, maximo) {
    const tbody = document.getElementById('valoresTableBody');
    const rows = tbody.getElementsByTagName('tr');
    
    Array.from(rows).forEach(row => {
        const premiacoesCell = row.querySelector('.premiacoes');
        if (premiacoesCell) {
            const grid = document.createElement('div');
            grid.className = 'premiacoes-grid';
            
            for (let i = minimo; i <= maximo; i++) {
                const item = document.createElement('div');
                item.className = 'premiacao-item';
                item.innerHTML = `
                    <label>${i} números</label>
                    <input type="text" class="form-control money" 
                           placeholder="R$ 0,00" 
                           data-numeros="${i}">
                `;
                grid.appendChild(item);
            }
            
            premiacoesCell.innerHTML = '';
            premiacoesCell.appendChild(grid);
            
            // Inicializar máscaras monetárias
            const moneyInputs = premiacoesCell.querySelectorAll('.money');
            moneyInputs.forEach(input => {
                input.addEventListener('input', function() {
                    formatarMoeda(this);
                });
            });
        }
    });
}

function adicionarValorTabela() {
    const valorApostaInput = document.getElementById('novo_valor_aposta');
    const dezenasInput = document.getElementById('novo_dezenas');
    const valorPremioInput = document.getElementById('novo_valor_premio');
    
    // Remove formatação para validação
    const valorAposta = valorApostaInput.value.replace(/\D/g, '');
    const dezenas = dezenasInput.value;
    const valorPremio = valorPremioInput.value.replace(/\D/g, '');
    
    // Validações
    if (!valorAposta || valorAposta === '0') {
        alert('Por favor, insira um valor válido para a aposta');
        valorApostaInput.focus();
        return;
    }
    
    if (!dezenas || dezenas === '0') {
        alert('Por favor, insira uma quantidade válida de dezenas');
        dezenasInput.focus();
        return;
    }
    
    if (!valorPremio || valorPremio === '0') {
        alert('Por favor, insira um valor válido para o prêmio');
        valorPremioInput.focus();
        return;
    }
    
    const minimo = parseInt(document.getElementById('minimo_numeros').value);
    const maximo = parseInt(document.getElementById('maximo_numeros').value);
    
    if (parseInt(dezenas) < minimo || parseInt(dezenas) > maximo) {
        alert(`A quantidade de dezenas deve estar entre ${minimo} e ${maximo}`);
        dezenasInput.focus();
        return;
    }
    
    // Adicionar à tabela
    const tbody = document.getElementById('valoresTableBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>R$ ${formatarMoeda(valorAposta/100)}</td>
        <td>${dezenas}</td>
        <td>R$ ${formatarMoeda(valorPremio/100)}</td>
        <td>
            <button type="button" class="btn-remove" onclick="this.closest('tr').remove()">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    
    // Limpar campos
    valorApostaInput.value = '';
    dezenasInput.value = '';
    valorPremioInput.value = '';
    valorApostaInput.focus();
}

// Adicionar máscara de moeda aos campos
document.addEventListener('DOMContentLoaded', function() {
    const moneyInputs = document.querySelectorAll('.money');
    moneyInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let valor = e.target.value.replace(/\D/g, '');
            valor = (parseInt(valor) / 100).toFixed(2);
            valor = valor.replace('.', ',');
            valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
            e.target.value = valor;
        });
    });
});

function prepararDadosParaSalvar(event) {
    event.preventDefault();
    
    try {
        // Coletar dados básicos do jogo
        const dadosJogo = {
            id: document.getElementById('jogoId').value || '',
            nome: document.getElementById('nome').value || '',
            minimo_numeros: document.getElementById('minimo_numeros').value || '',
            maximo_numeros: document.getElementById('maximo_numeros').value || '',
            acertos_premio: document.getElementById('acertos_premio').value || '',
            status: document.getElementById('status').value || '1'
        };

        // Validações básicas
        if (!dadosJogo.nome) {
            throw new Error('Por favor, informe o nome do jogo');
        }

        // Coletar valores da tabela
        const valores = [];
        const linhas = document.getElementById('valoresTableBody').getElementsByTagName('tr');
        
        if (linhas.length === 0) {
            throw new Error('Adicione pelo menos um valor de aposta e premiação');
        }

        for (let linha of linhas) {
            const colunas = linha.getElementsByTagName('td');
            const valor = {
                valor_aposta: colunas[0].textContent.replace('R$ ', '').replace(/\./g, '').replace(',', '.'),
                dezenas: parseInt(colunas[1].textContent),
                valor_premio: colunas[2].textContent.replace('R$ ', '').replace(/\./g, '').replace(',', '.')
            };
            valores.push(valor);
        }

        // Criar objeto de dados para envio
        const dadosParaEnvio = {
            ...dadosJogo,
            valores: JSON.stringify(valores)
        };

        // Enviar dados via fetch
        fetch('ajax/salvar_jogo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dadosParaEnvio)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: 'Jogo salvo com sucesso!'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.message || 'Erro ao salvar o jogo');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: error.message || 'Erro ao salvar o jogo'
            });
        });

    } catch (error) {
        console.error('Erro:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message
        });
    }
}
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?>