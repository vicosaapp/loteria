<?php
session_start();
require_once '../config/database.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Define a página atual para o menu
$currentPage = 'usuarios';

// Buscar usuários
try {
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY nome ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro ao buscar usuários: " . $e->getMessage());
}

ob_start();
?>

<div class="page-container">
    <div class="page-header">
        <h1><i class="fas fa-users"></i> Gerenciar Usuários</h1>
        <p>Gerencie os usuários do sistema</p>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user text-success"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count(array_filter($usuarios, function($u) { return $u['tipo'] === 'usuario'; })); ?></h3>
                <p>Usuários</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-shield text-warning"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count(array_filter($usuarios, function($u) { return $u['tipo'] === 'admin'; })); ?></h3>
                <p>Administradores</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users text-info"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo count($usuarios); ?></h3>
                <p>Total de Usuários</p>
            </div>
        </div>
    </div>

    <div class="content-section">
        <button onclick="abrirModalUsuario()" class="btn btn-primary mb-4 actions-bar">
            <i class="fas fa-plus"></i> Novo Usuário
        </button>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>WhatsApp</th>
                        <th>Tipo</th>
                        <th>Cadastro</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo $usuario['telefone'] ? htmlspecialchars($usuario['telefone']) : '-'; ?></td>
                            <td><?php echo $usuario['whatsapp'] ? htmlspecialchars($usuario['whatsapp']) : '-'; ?></td>
                            <td>
                                <span class="badge <?php echo $usuario['tipo'] === 'admin' ? 'badge-warning' : 'badge-success'; ?>">
                                    <?php echo ucfirst($usuario['tipo']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?></td>
                            <td>
                                <button onclick='editarUsuario(<?php echo json_encode($usuario); ?>)' class="btn btn-sm btn-outline">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if($usuario['id'] != $_SESSION['usuario_id']): ?>
                                    <button onclick="confirmarExclusao(<?php echo $usuario['id']; ?>)" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Usuário -->
<div id="usuarioModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user"></i> <span id="modalTitle">Novo Usuário</span></h2>
            <button type="button" class="close" onclick="fecharModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <form id="usuarioForm" onsubmit="salvarUsuario(event)">
                <input type="hidden" id="userId">
                
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" id="nome" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" id="telefone" class="form-control">
                </div>

                <div class="form-group">
                    <label>WhatsApp</label>
                    <input type="text" id="whatsapp" class="form-control">
                </div>

                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" id="senha" class="form-control">
                    <small class="text-muted">Deixe em branco para manter a senha atual (ao editar)</small>
                </div>

                <div class="form-group">
                    <label>Tipo</label>
                    <select id="tipo" class="form-control" required>
                        <option value="usuario">Usuário</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Reset do scroll */
html, body {
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}

/* Container principal ajustado */
.page-container {
    padding: 2px 0; /* Remove padding lateral */
    margin-left: 20px; /* Ajusta margem do menu lateral */
    width: 134%;
    min-height: calc(100vh - 10px);
    overflow-x: hidden;
}

/* Header da página */
.page-header {
    margin: 0 0 30px 0; /* Remove todas as margens */
}

.page-header h1 {
    font-size: 24px;
    color: #333;
    margin-bottom: 5px;
}

.page-header p {
    color: #666;
    margin: 0;
}

/* Cards de estatísticas */
.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin: 0 0 30px 0; /* Remove todas as margens */
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stat-icon {
    font-size: 24px;
}

.text-success { color: #28a745; }
.text-warning { color: #ffc107; }
.text-info { color: #17a2b8; }

.stat-info h3 {
    margin: 0;
    font-size: 24px;
}

.stat-info p {
    margin: 5px 0 0;
    color: #666;
}

.btn-primary {
    background: #007bff;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 5px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.table-responsive {
    background: white;
    margin: 20px 0; /* Mantém apenas margem vertical */
    overflow-x: auto;
}

.table {
    width: 100%;
    min-width: 800px; /* Largura mínima para garantir legibilidade */
}

.table th,
.table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.table th {
    background: #f8f9fa;
    color: #333;
    font-weight: 600;
}

.table tr:hover {
    background: #f8f9fa;
}

.badge {
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
}

.badge.admin {
    background: #fff3cd;
    color: #856404;
}

.badge.usuario {
    background: #d4edda;
    color: #155724;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

/* Botão novo usuário */
.actions-bar {
    margin: 0; /* Remove todas as margens */
}

/* Ajustes responsivos */
@media (max-width: 768px) {
    .page-container {
        margin-left: 0;
        width: 100%;
    }
}

/* Ajuste para scrollbar */
@media screen and (min-width: 768px) {
    /* Quando há scrollbar vertical */
    body {
        padding-right: 0;
    }
    
    /* Previne salto de layout quando abre modal */
    .modal-open {
        padding-right: 17px; /* Largura da scrollbar */
    }
}

/* Estilos do Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    background: white;
    width: 90%;
    max-width: 500px;
    margin: 50px auto;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.modal-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.text-muted {
    color: #666;
    font-size: 12px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<script>
let editandoId = null;

function abrirModalUsuario() {
    document.body.classList.add('modal-open');
    editandoId = null;
    document.getElementById('modalTitle').textContent = 'Novo Usuário';
    document.getElementById('usuarioForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('senha').required = true;
    document.getElementById('usuarioModal').style.display = 'block';
}

function editarUsuario(usuario) {
    editandoId = usuario.id;
    document.getElementById('modalTitle').textContent = 'Editar Usuário';
    document.getElementById('userId').value = usuario.id;
    document.getElementById('nome').value = usuario.nome;
    document.getElementById('email').value = usuario.email;
    document.getElementById('telefone').value = usuario.telefone || '';
    document.getElementById('whatsapp').value = usuario.whatsapp || '';
    document.getElementById('tipo').value = usuario.tipo;
    document.getElementById('senha').required = false;
    document.getElementById('usuarioModal').style.display = 'block';
}

function fecharModal() {
    document.body.classList.remove('modal-open');
    document.getElementById('usuarioModal').style.display = 'none';
}

function salvarUsuario(event) {
    event.preventDefault();
    
    const formData = {
        id: document.getElementById('userId').value,
        nome: document.getElementById('nome').value,
        email: document.getElementById('email').value,
        telefone: document.getElementById('telefone').value,
        whatsapp: document.getElementById('whatsapp').value,
        senha: document.getElementById('senha').value,
        tipo: document.getElementById('tipo').value
    };
    
    fetch('ajax/salvar_usuario.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Erro ao salvar usuário');
        }
    });
}

function confirmarExclusao(id) {
    if(confirm('Tem certeza que deseja excluir este usuário?')) {
        fetch('ajax/excluir_usuario.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({id: id})
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Erro ao excluir usuário');
            }
        });
    }
}

// Ajusta quando clica fora do modal
window.onclick = function(event) {
    if (event.target == document.getElementById('usuarioModal')) {
        fecharModal();
    }
}
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 