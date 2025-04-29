<?php
require_once 'includes/header.php';
require_once '../config/database.php';
require_once '../includes/verificar_manutencao.php';

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Define a página atual para o menu
$currentPage = 'configuracoes';

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['salvar_pausa'])) {
            $status_apostas = isset($_POST['status_apostas']) ? 1 : 0;
            $horario_inicio = $_POST['horario_inicio'];
            $horario_fim = $_POST['horario_fim'];
            $dias_semana = isset($_POST['dias_semana']) ? implode(',', $_POST['dias_semana']) : '';
            $motivo_pausa = $_POST['motivo_pausa'];

            // Atualizar configurações
            $stmt = $pdo->prepare("
                UPDATE configuracoes SET 
                    status_apostas = ?,
                    horario_inicio = ?,
                    horario_fim = ?,
                    dias_semana = ?,
                    motivo_pausa = ?
                WHERE id = 1
            ");

            $stmt->execute([
                $status_apostas,
                $horario_inicio,
                $horario_fim,
                $dias_semana,
                $motivo_pausa
            ]);

            $mensagem = "Configurações de pausa salvas com sucesso!";
            $tipo_mensagem = "success";
        } elseif (isset($_POST['salvar_manutencao'])) {
            $modo_manutencao = isset($_POST['modo_manutencao']) ? 1 : 0;
            $mensagem_manutencao = $_POST['mensagem_manutencao'];
            
            // Atualizar configurações de manutenção
            $stmt = $pdo->prepare("
                UPDATE configuracoes SET 
                    modo_manutencao = ?,
                    mensagem_manutencao = ?
                WHERE id = 1
            ");

            $stmt->execute([
                $modo_manutencao,
                $mensagem_manutencao
            ]);

            $mensagem = "Configurações de manutenção salvas com sucesso!";
            $tipo_mensagem = "success";
        }
    } catch (Exception $e) {
        $mensagem = "Erro ao salvar as configurações: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}

// Buscar configurações atuais
$stmt = $pdo->query("SELECT * FROM configuracoes WHERE id = 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não existir configuração, criar uma padrão
if (!$config) {
    $pdo->exec("
        INSERT INTO configuracoes (
            id, status_apostas, horario_inicio, horario_fim, 
            dias_semana, motivo_pausa, modo_manutencao, mensagem_manutencao
        ) VALUES (
            1, 1, '23:00', '06:00', '', '', 0, 'Sistema em manutenção. Por favor, tente novamente mais tarde.'
        )
    ");
    
    $stmt = $pdo->query("SELECT * FROM configuracoes WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Array com os dias da semana
$dias_semana = [
    0 => 'Domingo',
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado'
];

// Converter string de dias em array
$dias_selecionados = !empty($config['dias_semana']) ? explode(',', $config['dias_semana']) : [];

// Verificação direta de manutenção
try {
    $stmt = $pdo->query("SELECT modo_manutencao FROM configuracoes WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $isAdmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin';
    
    if (isset($config['modo_manutencao']) && $config['modo_manutencao'] == 1 && !$isAdmin) {
        header("Location: /manutencao.php");
        exit;
    }
} catch (Exception $e) {
    // Se houver erro, continuar sem verificação
}
?>

<div class="container-fluid">
    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-cogs"></i> Configurações do Sistema</h1>
            <p>Gerencie as configurações globais do sistema</p>
        </div>
    </div>

    <?php if (isset($mensagem)): ?>
        <div class="alert alert-<?php echo $tipo_mensagem; ?>" role="alert">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

 
        
        <div class="col-md-6">
            <!-- MODO MANUTENÇÃO -->
            <form method="POST" action="">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h3><i class="fas fa-tools"></i> Modo Manutenção</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Atenção: Ativar o modo de manutenção bloqueará o acesso de todos os usuários, exceto administradores.
                            </div>
                            
                            <div class="form-group">
                                <label class="control-label">Ativar Modo Manutenção</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="modo_manutencao" 
                                           name="modo_manutencao" <?php echo ($config['modo_manutencao'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="modo_manutencao">
                                        <span class="status-text">
                                            <?php echo ($config['modo_manutencao'] ?? 0) ? 'Manutenção Ativa' : 'Manutenção Desativada'; ?>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Mensagem de Manutenção</label>
                                <textarea class="form-control" name="mensagem_manutencao" rows="4" 
                                          placeholder="Informe a mensagem que será exibida durante a manutenção"><?php echo $config['mensagem_manutencao'] ?? 'Sistema em manutenção. Por favor, tente novamente mais tarde.'; ?></textarea>
                            </div>
                        </div>
                        <div class="form-actions text-center">
                            <button type="submit" name="salvar_manutencao" class="btn btn-warning">
                                <i class="fas fa-save"></i> Salvar Configurações de Manutenção
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.card {
    margin-bottom: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 10px 10px 0 0;
}

.card-header.bg-warning {
    background: linear-gradient(135deg, #f6c23e 0%, #f4b619 100%);
}

.card-header h3 {
    margin: 0;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
}

.card-header h3 i {
    margin-right: 10px;
}

.card-body {
    padding: 20px;
}

.form-section {
    background: #f8f9fc;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 600;
    color: #4e73df;
    margin-bottom: 8px;
    display: block;
}

.custom-switch {
    padding-left: 2.25rem;
}

.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #1cc88a;
    border-color: #1cc88a;
}

.status-text {
    font-weight: 600;
    margin-left: 10px;
}

.dias-semana-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.form-actions {
    margin-top: 20px;
}

.btn {
    padding: 10px 20px;
    border-radius: 5px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn-primary {
    background: #4e73df;
    border: none;
}

.btn-warning {
    background: #f6c23e;
    border: none;
    color: #212529;
}

.alert {
    border-radius: 10px;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.page-header {
    margin-bottom: 20px;
    padding: 20px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header-content h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.8rem;
    color: #4e73df;
}

.header-content p {
    color: #6c757d;
    margin-top: 5px;
}

@media (max-width: 768px) {
    .card {
        margin-bottom: 15px;
    }
    
    .form-section {
        padding: 15px;
    }
    
    .dias-semana-grid {
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    }
}
</style>

<script>
// Atualizar texto do status quando o switch for alterado
document.addEventListener('DOMContentLoaded', function() {
    // Para o status de apostas
    const statusApostasSwitch = document.getElementById('status_apostas');
    if (statusApostasSwitch) {
        statusApostasSwitch.addEventListener('change', function() {
            const statusText = this.nextElementSibling.querySelector('.status-text');
            statusText.textContent = this.checked ? 'Apostas Liberadas' : 'Apostas Pausadas';
        });
    }
    
    // Para o modo manutenção
    const modoManutencaoSwitch = document.getElementById('modo_manutencao');
    if (modoManutencaoSwitch) {
        modoManutencaoSwitch.addEventListener('change', function() {
            const statusText = this.nextElementSibling.querySelector('.status-text');
            statusText.textContent = this.checked ? 'Manutenção Ativa' : 'Manutenção Desativada';
            
            if (this.checked) {
                Swal.fire({
                    title: 'Atenção!',
                    text: 'Ativar o modo de manutenção bloqueará o acesso a todos os usuários exceto administradores. Deseja continuar?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f6c23e',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, ativar manutenção',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        this.checked = false;
                        statusText.textContent = 'Manutenção Desativada';
                    }
                });
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require_once 'includes/layout.php';
?> 