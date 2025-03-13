<?php
// No início do arquivo, após as configurações iniciais
ini_set('memory_limit', '256M'); // Aumenta o limite de memória
ini_set('max_execution_time', 300); // Aumenta o tempo máximo de execução
set_time_limit(300); // Aumenta o tempo limite do script

session_start();
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
        // Alternar status do usuário
        if (isset($_POST['alternar_status']) && isset($_POST['usuario_id'])) {
            $usuario_id = intval($_POST['usuario_id']);
            
            $stmt = $pdo->prepare("UPDATE usuarios SET status = NOT status WHERE id = ? AND tipo = 'usuario'");
            if ($stmt->execute([$usuario_id])) {
                echo json_encode(['success' => true, 'message' => 'Status alterado com sucesso!']);
            } else {
                throw new Exception('Erro ao alterar status do usuário');
            }
            exit;
        }
        
        // Editar usuário
        if (isset($_POST['editar_usuario'])) {
            $id = intval($_POST['id']);
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            $whatsapp = trim($_POST['whatsapp']);
            
            if (empty($nome) || empty($email)) {
                throw new Exception('Nome e email são obrigatórios');
            }
            
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, whatsapp = ? WHERE id = ? AND tipo = 'usuario'");
            if ($stmt->execute([$nome, $email, $whatsapp, $id])) {
                echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
            } else {
                throw new Exception('Erro ao atualizar usuário');
            }
            exit;
        }
        
        // Excluir usuário
        if (isset($_POST['excluir_usuario']) && isset($_POST['usuario_id'])) {
            $usuario_id = intval($_POST['usuario_id']);
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND tipo = 'usuario'");
            if ($stmt->execute([$usuario_id])) {
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso!']);
            } else {
                throw new Exception('Erro ao excluir usuário');
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

// Definir variáveis de busca e tipo
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$tipo = 'usuario';

// Configuração da paginação
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$itens_por_pagina = 50;
$offset = ($pagina - 1) * $itens_por_pagina;

// Construir a query base
$sql = "SELECT u.*, r.nome as nome_revendedor 
        FROM usuarios u 
        LEFT JOIN usuarios r ON u.revendedor_id = r.id 
        WHERE u.tipo = 'usuario'";
$params = [];

// Adicionar condições de busca
if (!empty($busca)) {
    $sql .= " AND (u.nome LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

// Contar total de registros
$stmt_count = $pdo->prepare(str_replace('u.*, r.nome as nome_revendedor', 'COUNT(*) as total', $sql));
$stmt_count->execute($params);
$total = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

// Adicionar ordenação e limite
$sql .= " ORDER BY u.nome LIMIT {$itens_por_pagina} OFFSET {$offset}";

// Executar query principal
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular total de páginas
$total_paginas = ceil($total / $itens_por_pagina);

// Define constante para segurança
define('ADMIN', true);

// Define a página atual
$currentPage = 'usuarios';

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
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" role="dialog" aria-labelledby="modalEditarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarUsuarioLabel">Editar Usuário</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEditarUsuario">
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- HTML da página -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 px-2">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-users"></i> Gerenciar Usuários
        </h1>
        <button class="btn btn-primary" onclick="novoUsuario()">
            <i class="fas fa-plus"></i> Novo Usuário
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
                            <th>Cadastro</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td>
                                <?php if (!empty($usuario['whatsapp'])): ?>
                                    <a href="https://wa.me/<?php echo $usuario['whatsapp']; ?>" target="_blank" class="btn btn-success btn-sm">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td><?php echo isset($usuario['data_cadastro']) ? date('d/m/Y', strtotime($usuario['data_cadastro'])) : 'N/D'; ?></td>
                            <td>
                                <span class="badge badge-<?php echo isset($usuario['status']) && $usuario['status'] ? 'success' : 'danger'; ?>">
                                    <?php echo isset($usuario['status']) && $usuario['status'] ? 'Ativo' : 'Bloqueado'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm btn-editar" 
                                    data-id="<?php echo $usuario['id']; ?>"
                                    data-nome="<?php echo htmlspecialchars($usuario['nome']); ?>"
                                    data-email="<?php echo htmlspecialchars($usuario['email']); ?>"
                                    data-whatsapp="<?php echo htmlspecialchars($usuario['whatsapp'] ?? ''); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-<?php echo isset($usuario['status']) && $usuario['status'] ? 'warning' : 'success'; ?> btn-sm btn-alternar-status" 
                                    data-id="<?php echo $usuario['id']; ?>"
                                    title="<?php echo isset($usuario['status']) && $usuario['status'] ? 'Bloquear' : 'Desbloquear'; ?>">
                                    <i class="fas fa-<?php echo isset($usuario['status']) && $usuario['status'] ? 'lock' : 'unlock'; ?>"></i>
                                </button>
                                <button class="btn btn-danger btn-sm btn-excluir" data-id="<?php echo $usuario['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Paginação -->
                <div class="d-flex justify-content-center mt-4">
                    <nav aria-label="Navegação de página">
                        <ul class="pagination">
                            <?php if($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>" aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if($pagina < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>" aria-label="Próximo">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Função para editar usuário
function editarUsuario(id, nome, email, whatsapp) {
    document.getElementById('editar_id').value = id;
    document.getElementById('editar_nome').value = nome;
    document.getElementById('editar_email').value = email;
    document.getElementById('editar_whatsapp').value = whatsapp || '';
    $('#modalEditarUsuario').modal('show');
}

// Função para alternar status
function alternarStatus(id) {
    const botao = document.querySelector(`.btn-alternar-status[data-id="${id}"]`);
    const estaAtivo = botao.classList.contains('btn-warning');
    const mensagem = estaAtivo ? 'Deseja bloquear este usuário?' : 'Deseja desbloquear este usuário?';
    
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
            fetch('gerenciar_usuarios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `alternar_status=1&usuario_id=${id}`
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

// Função para excluir usuário
function excluirUsuario(id) {
    Swal.fire({
        title: 'Confirmar exclusão',
        text: 'Tem certeza que deseja excluir este usuário?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('gerenciar_usuarios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `excluir_usuario=1&usuario_id=${id}`
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
            editarUsuario(id, nome, email, whatsapp);
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
            excluirUsuario(id);
        };
    });

    // Formulário de edição
    document.getElementById('formEditarUsuario').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = {};
        formData.forEach((value, key) => data[key] = value);
        
        fetch('gerenciar_usuarios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `editar_usuario=1&${new URLSearchParams(data).toString()}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#modalEditarUsuario').modal('hide');
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