<?php
require_once '../config/database.php';
require_once 'includes/header.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    ob_clean(); // Limpa qualquer saída anterior
    
    try {
        // Alternar status do revendedor
        if (isset($_POST['alternar_status']) && isset($_POST['revendedor_id'])) {
            $revendedor_id = intval($_POST['revendedor_id']);
            
            $stmt = $pdo->prepare("UPDATE usuarios SET status = NOT status WHERE id = ? AND tipo = 'revendedor'");
            if ($stmt->execute([$revendedor_id])) {
                echo json_encode(['success' => true, 'message' => 'Status alterado com sucesso!']);
            } else {
                throw new Exception('Erro ao alterar status do revendedor');
            }
            exit;
        }
        
        // Editar revendedor
        if (isset($_POST['editar_revendedor'])) {
            $id = intval($_POST['id']);
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            $whatsapp = trim($_POST['whatsapp']);
            $comissao = floatval($_POST['comissao']);
            
            if (empty($nome) || empty($email)) {
                throw new Exception('Nome e email são obrigatórios');
            }
            
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, whatsapp = ?, comissao = ? WHERE id = ? AND tipo = 'revendedor'");
            if ($stmt->execute([$nome, $email, $whatsapp, $comissao, $id])) {
                echo json_encode(['success' => true, 'message' => 'Revendedor atualizado com sucesso!']);
            } else {
                throw new Exception('Erro ao atualizar revendedor');
            }
            exit;
        }
        
        // Excluir revendedor
        if (isset($_POST['excluir_revendedor']) && isset($_POST['revendedor_id'])) {
            $revendedor_id = intval($_POST['revendedor_id']);
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND tipo = 'revendedor'");
            if ($stmt->execute([$revendedor_id])) {
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Revendedor excluído com sucesso!']);
            } else {
                throw new Exception('Erro ao excluir revendedor');
            }
            exit;
        }
        
        // Adicionar novo revendedor
        if (isset($_POST['novo_revendedor'])) {
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            $senha = trim($_POST['senha']);
            $whatsapp = trim($_POST['whatsapp']);
            $comissao = floatval($_POST['comissao']);
            
            if (empty($nome) || empty($email) || empty($senha)) {
                throw new Exception('Nome, email e senha são obrigatórios');
            }
            
            // Verifica se o email já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('Este email já está cadastrado');
            }
            
            // Insere o novo revendedor
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, whatsapp, comissao, tipo, status, data_cadastro) VALUES (?, ?, ?, ?, ?, 'revendedor', 1, NOW())");
            if ($stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $whatsapp, $comissao])) {
                echo json_encode(['success' => true, 'message' => 'Revendedor cadastrado com sucesso!']);
            } else {
                throw new Exception('Erro ao cadastrar revendedor');
            }
            exit;
        }
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Se não for uma requisição AJAX, continua com o carregamento normal da página

// Buscar revendedores
$stmt = $pdo->query("SELECT * FROM usuarios WHERE tipo = 'revendedor' ORDER BY nome");
$revendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define constante para segurança
define('ADMIN', true);

// Define a página atual
$currentPage = 'revendedores';

// Carrega a view
ob_start();
?>

<style>
.container-fluid {
    padding: 1.5rem !important;
    padding-left: 15rem !important;
    margin-left: 0 !important;
}

.sidebar {
    margin-right: 0 !important;
    border-right: 1px solid #e3e6f0;
}

.card {
    border-radius: 0.35rem;
    margin: 0 !important;
}

.card-body {
    padding: 1rem !important;
}

/* Remove estilos anteriores que não são necessários */
.admin-container,
.main-content,
#wrapper,
#content-wrapper {
    margin-left: auto !important;
    margin-right: auto !important;
}

/* Ajusta a largura da tabela */
.table-responsive {
    margin: 0 !important;
    padding: 0 !important;
}
</style>

<!-- Modal de Edição -->
<div class="modal fade" id="modalEditarRevendedor" tabindex="-1" role="dialog" aria-labelledby="modalEditarRevendedorLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarRevendedorLabel">Editar Revendedor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEditarRevendedor">
                <div class="modal-body">
                    <input type="hidden" name="id" id="editar_id">
                    <div class="form-group">
                        <label for="editar_nome">Nome</label>
                        <input type="text" class="form-control" id="editar_nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="editar_email">Email</label>
                        <input type="email" class="form-control" id="editar_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="editar_whatsapp">WhatsApp</label>
                        <input type="text" class="form-control" id="editar_whatsapp" name="whatsapp">
                    </div>
                    <div class="form-group">
                        <label for="editar_comissao">Comissão (%)</label>
                        <input type="number" class="form-control" id="editar_comissao" name="comissao" step="0.1" min="0" max="100" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Novo Revendedor -->
<div class="modal fade" id="modalNovoRevendedor" tabindex="-1" role="dialog" aria-labelledby="modalNovoRevendedorLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovoRevendedorLabel">Novo Revendedor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formNovoRevendedor">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="novo_nome">Nome</label>
                        <input type="text" class="form-control" id="novo_nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="novo_email">Email</label>
                        <input type="email" class="form-control" id="novo_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="novo_senha">Senha</label>
                        <input type="password" class="form-control" id="novo_senha" name="senha" required>
                    </div>
                    <div class="form-group">
                        <label for="novo_whatsapp">WhatsApp</label>
                        <input type="text" class="form-control" id="novo_whatsapp" name="whatsapp">
                    </div>
                    <div class="form-group">
                        <label for="novo_comissao">Comissão (%)</label>
                        <input type="number" class="form-control" id="novo_comissao" name="comissao" step="0.1" min="0" max="100" required value="5">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- HTML da página -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 px-2">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-users"></i> Gerenciar Revendedores
        </h1>
        <button class="btn btn-primary" onclick="novoRevendedor()">
            <i class="fas fa-plus"></i> Novo Revendedor
        </button>
    </div>

    <div class="card shadow">
        <div class="card-body px-2">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>WhatsApp</th>
                            <th>Comissão</th>
                            <th>Cadastro</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revendedores as $revendedor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($revendedor['nome']); ?></td>
                            <td><?php echo htmlspecialchars($revendedor['email']); ?></td>
                            <td>
                                <?php if (!empty($revendedor['whatsapp'])): ?>
                                    <a href="https://wa.me/<?php echo $revendedor['whatsapp']; ?>" target="_blank" class="btn btn-success btn-sm">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $revendedor['comissao']; ?>%</td>
                            <td><?php echo isset($revendedor['data_cadastro']) ? date('d/m/Y', strtotime($revendedor['data_cadastro'])) : 'N/D'; ?></td>
                            <td>
                                <span class="badge badge-<?php echo isset($revendedor['status']) && $revendedor['status'] ? 'success' : 'danger'; ?>">
                                    <?php echo isset($revendedor['status']) && $revendedor['status'] ? 'Ativo' : 'Bloqueado'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm btn-editar" 
                                    data-id="<?php echo $revendedor['id']; ?>"
                                    data-nome="<?php echo htmlspecialchars($revendedor['nome']); ?>"
                                    data-email="<?php echo htmlspecialchars($revendedor['email']); ?>"
                                    data-whatsapp="<?php echo htmlspecialchars($revendedor['whatsapp'] ?? ''); ?>"
                                    data-comissao="<?php echo $revendedor['comissao']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-<?php echo isset($revendedor['status']) && $revendedor['status'] ? 'warning' : 'success'; ?> btn-sm btn-alternar-status" 
                                    data-id="<?php echo $revendedor['id']; ?>"
                                    title="<?php echo isset($revendedor['status']) && $revendedor['status'] ? 'Bloquear' : 'Desbloquear'; ?>">
                                    <i class="fas fa-<?php echo isset($revendedor['status']) && $revendedor['status'] ? 'lock' : 'unlock'; ?>"></i>
                                </button>
                                <button class="btn btn-danger btn-sm btn-excluir" data-id="<?php echo $revendedor['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Função para editar revendedor
function editarRevendedor(id, nome, email, whatsapp, comissao) {
    document.getElementById('editar_id').value = id;
    document.getElementById('editar_nome').value = nome;
    document.getElementById('editar_email').value = email;
    document.getElementById('editar_whatsapp').value = whatsapp || '';
    document.getElementById('editar_comissao').value = comissao;
    $('#modalEditarRevendedor').modal('show');
}

// Função para alternar status
function alternarStatus(id) {
    const botao = document.querySelector(`.btn-alternar-status[data-id="${id}"]`);
    const estaAtivo = botao.classList.contains('btn-warning');
    const mensagem = estaAtivo ? 'Deseja bloquear este revendedor?' : 'Deseja desbloquear este revendedor?';
    
    Swal.fire({
        title: 'Confirmar alteração',
        text: mensagem,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: estaAtivo ? '#d33' : '#28a745',
        cancelButtonColor: '#3085d6',
        confirmButtonText: estaAtivo ? 'Sim, bloquear!' : 'Sim, desbloquear!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('adicionar_revendedor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `alternar_status=1&revendedor_id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: error.message
                });
            });
        }
    });
}

// Função para excluir revendedor
function excluirRevendedor(id) {
    Swal.fire({
        title: 'Confirmar exclusão',
        text: 'Tem certeza que deseja excluir este revendedor?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('adicionar_revendedor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `excluir_revendedor=1&revendedor_id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: error.message
                });
            });
        }
    });
}

// Função para novo revendedor
function novoRevendedor() {
    $('#modalNovoRevendedor').modal('show');
}

// Quando o documento estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    // Eventos para os botões de editar
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.onclick = function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const nome = this.getAttribute('data-nome');
            const email = this.getAttribute('data-email');
            const whatsapp = this.getAttribute('data-whatsapp');
            const comissao = this.getAttribute('data-comissao');
            editarRevendedor(id, nome, email, whatsapp, comissao);
        };
    });

    // Eventos para os botões de alternar status
    document.querySelectorAll('.btn-alternar-status').forEach(btn => {
        btn.onclick = function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            alternarStatus(id);
        };
    });

    // Eventos para os botões de excluir
    document.querySelectorAll('.btn-excluir').forEach(btn => {
        btn.onclick = function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            excluirRevendedor(id);
        };
    });

    // Formulário de edição
    document.getElementById('formEditarRevendedor').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = {};
        formData.forEach((value, key) => data[key] = value);
        
        fetch('adicionar_revendedor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `editar_revendedor=1&${new URLSearchParams(data).toString()}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#modalEditarRevendedor').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: data.message
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: error.message
            });
        });
    };

    // Formulário de novo revendedor
    document.getElementById('formNovoRevendedor').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('novo_revendedor', '1');
        
        fetch('adicionar_revendedor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(formData).toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#modalNovoRevendedor').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: data.message
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: error.message
            });
        });
    };
});
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 