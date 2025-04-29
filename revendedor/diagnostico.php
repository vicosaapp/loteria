<?php
// Inicialização da sessão e verificação de login
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'revendedor') {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico do Sistema</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card {
            margin-bottom: 20px;
        }
        .status-ok {
            color: #28a745;
        }
        .status-warning {
            color: #ffc107;
        }
        .status-error {
            color: #dc3545;
        }
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
        .card-header {
            font-weight: bold;
        }
        .badge {
            font-size: 85%;
        }
        .recomendacao-item {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .recomendacao-item:last-child {
            border-bottom: none;
        }
        .estatistica-valor {
            font-weight: bold;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    
<?php include_once 'header.php'; ?>

<div class="container mt-4">
    <h1 class="mb-4">Diagnóstico do Sistema</h1>
    
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-info-circle me-2"></i> Status do Sistema
                </div>
                <div class="card-body" id="statusCard">
                    <div class="loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-database me-2"></i> Banco de Dados
                </div>
                <div class="card-body" id="dbCard">
                    <div class="loading">
                        <div class="spinner-border text-info" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-chart-bar me-2"></i> Estatísticas
                </div>
                <div class="card-body" id="statsCard">
                    <div class="loading">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <i class="fas fa-exclamation-triangle me-2"></i> Recomendações
                </div>
                <div class="card-body" id="recCard">
                    <div class="loading">
                        <div class="spinner-border text-warning" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <i class="fas fa-server me-2"></i> Ambiente do Sistema
                </div>
                <div class="card-body" id="envCard">
                    <div class="loading">
                        <div class="spinner-border text-secondary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-folder-open me-2"></i> Estrutura das Tabelas
                </div>
                <div class="card-body" id="tablesCard">
                    <div class="loading">
                        <div class="spinner-border text-dark" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3 mb-5">
        <div class="col-12 text-center">
            <button id="btnRefresh" class="btn btn-primary btn-lg">
                <i class="fas fa-sync-alt me-2"></i> Atualizar Diagnóstico
            </button>
            <button id="btnExport" class="btn btn-success btn-lg ms-3">
                <i class="fas fa-file-export me-2"></i> Exportar Relatório
            </button>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Função para carregar os dados do diagnóstico
        function carregarDiagnostico() {
            // Resetar conteúdo e mostrar spinners
            $('.card-body').each(function() {
                $(this).html('<div class="loading"><div class="spinner-border" role="status"><span class="visually-hidden">Carregando...</span></div></div>');
            });
            
            // Fazer requisição AJAX
            $.ajax({
                url: 'ajax/diagnostico_db_compact.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    renderizarDiagnostico(data);
                },
                error: function(xhr, status, error) {
                    const errorMsg = xhr.responseText || 'Ocorreu um erro ao carregar o diagnóstico.';
                    $('#statusCard').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> 
                            <strong>Erro:</strong> ${errorMsg}
                        </div>
                    `);
                    $('.loading').html('<div class="alert alert-warning">Falha ao carregar dados</div>');
                }
            });
        }
        
        // Função para renderizar os dados
        function renderizarDiagnostico(data) {
            if (data.status !== 'success') {
                $('#statusCard').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> 
                        <strong>Erro:</strong> ${data.message || 'Ocorreu um erro ao processar o diagnóstico.'}
                    </div>
                `);
                return;
            }
            
            // Status do Sistema
            const statusHtml = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="status-ok mb-0">
                            <i class="fas fa-check-circle me-2"></i> Sistema Operacional
                        </h4>
                        <p class="text-muted mb-0">Diagnóstico gerado em: ${data.timestamp}</p>
                    </div>
                    <div>
                        <span class="badge bg-success">Online</span>
                    </div>
                </div>
            `;
            $('#statusCard').html(statusHtml);
            
            // Banco de Dados
            let dbStatusClass = data.banco_dados.conexao === 'ok' ? 'status-ok' : 'status-error';
            let dbStatusIcon = data.banco_dados.conexao === 'ok' ? 'check-circle' : 'times-circle';
            
            const dbHtml = `
                <h4 class="${dbStatusClass}">
                    <i class="fas fa-${dbStatusIcon} me-2"></i> 
                    Conexão: ${data.banco_dados.conexao === 'ok' ? 'Estabelecida' : 'Falha'}
                </h4>
                <p><strong>Versão MySQL:</strong> ${data.banco_dados.versao_mysql}</p>
                
                <h5 class="mt-3">Últimos Concursos</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Código</th>
                                <th>Jogo</th>
                                <th>Data</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${renderizarUltimosConcursos(data.ultimos_concursos)}
                        </tbody>
                    </table>
                </div>
            `;
            $('#dbCard').html(dbHtml);
            
            // Estatísticas
            let statsHtml = '<div class="row">';
            
            // Estatísticas gerais
            for (const [key, value] of Object.entries(data.estatisticas)) {
                let icon = 'chart-bar';
                let title = key.replace(/_/g, ' ').replace(/^total /, '');
                title = title.charAt(0).toUpperCase() + title.slice(1);
                
                if (key.includes('apostas')) icon = 'ticket-alt';
                if (key.includes('concursos')) icon = 'calendar-check';
                if (key.includes('usuarios')) icon = 'users';
                
                statsHtml += `
                    <div class="col-sm-6 col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-${icon} fa-2x text-primary"></i>
                            </div>
                            <div>
                                <div class="text-muted small">${title}</div>
                                <div class="estatistica-valor">${value}</div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            statsHtml += '</div>';
            $('#statsCard').html(statsHtml);
            
            // Recomendações
            let recHtml = '';
            if (data.recomendacoes && data.recomendacoes.length > 0) {
                recHtml = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> 
                        Foram encontradas ${data.recomendacoes.length} recomendações.
                    </div>
                    <div class="list-group">
                `;
                
                data.recomendacoes.forEach(rec => {
                    recHtml += `
                        <div class="recomendacao-item">
                            <i class="fas fa-arrow-right me-2 text-warning"></i> ${rec}
                        </div>
                    `;
                });
                
                recHtml += '</div>';
            } else {
                recHtml = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i> 
                        Não há recomendações no momento. O sistema parece estar configurado adequadamente.
                    </div>
                `;
            }
            $('#recCard').html(recHtml);
            
            // Ambiente
            let envHtml = '<ul class="list-group list-group-flush">';
            for (const [key, value] of Object.entries(data.ambiente)) {
                let label = key.replace(/_/g, ' ');
                label = label.charAt(0).toUpperCase() + label.slice(1);
                
                envHtml += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>${label}</span>
                        <span class="badge bg-primary rounded-pill">${value}</span>
                    </li>
                `;
            }
            envHtml += '</ul>';
            $('#envCard').html(envHtml);
            
            // Tabelas
            let tablesHtml = '<div class="accordion" id="accordionTables">';
            let index = 0;
            
            for (const [tabela, info] of Object.entries(data.tabelas)) {
                const tableExistsClass = info.existe ? 'bg-success' : 'bg-danger';
                const tableExistsText = info.existe ? 'Existente' : 'Não encontrada';
                const tableIcon = info.existe ? 'check-circle' : 'times-circle';
                
                tablesHtml += `
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading${index}">
                            <button class="accordion-button ${info.existe ? '' : 'collapsed'}" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#collapse${index}" 
                                    aria-expanded="${info.existe ? 'true' : 'false'}" aria-controls="collapse${index}">
                                <div class="d-flex justify-content-between align-items-center w-100">
                                    <span><i class="fas fa-table me-2"></i> Tabela: <strong>${tabela}</strong></span>
                                    <span class="badge ${tableExistsClass} ms-auto">
                                        <i class="fas fa-${tableIcon} me-1"></i> ${tableExistsText}
                                    </span>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse${index}" class="accordion-collapse collapse ${info.existe ? 'show' : ''}" 
                             aria-labelledby="heading${index}" data-bs-parent="#accordionTables">
                            <div class="accordion-body">
                                ${renderizarEstruturaTabelaHtml(tabela, info)}
                            </div>
                        </div>
                    </div>
                `;
                index++;
            }
            
            tablesHtml += '</div>';
            $('#tablesCard').html(tablesHtml);
        }
        
        // Funções auxiliares
        function renderizarUltimosConcursos(concursos) {
            if (!concursos || !Array.isArray(concursos) || concursos.length === 0) {
                return '<tr><td colspan="5" class="text-center">Nenhum concurso encontrado</td></tr>';
            }
            
            let html = '';
            concursos.forEach(c => {
                let statusClass = 'bg-secondary';
                if (c.status === 'aberto') statusClass = 'bg-success';
                if (c.status === 'fechado') statusClass = 'bg-warning';
                if (c.status === 'finalizado') statusClass = 'bg-primary';
                
                html += `
                    <tr>
                        <td>${c.id}</td>
                        <td>${c.codigo}</td>
                        <td>${c.jogo}</td>
                        <td>${c.data_sorteio}</td>
                        <td><span class="badge ${statusClass}">${c.status}</span></td>
                    </tr>
                `;
            });
            
            return html;
        }
        
        function renderizarEstruturaTabelaHtml(tabela, info) {
            if (!info.existe) {
                return `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> 
                        A tabela <strong>${tabela}</strong> não existe no banco de dados.
                    </div>
                `;
            }
            
            if (!info.colunas || Object.keys(info.colunas).length === 0) {
                return `
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i> 
                        Não há informações sobre as colunas para esta tabela.
                    </div>
                `;
            }
            
            let html = `
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Coluna</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            for (const [coluna, existe] of Object.entries(info.colunas)) {
                const statusClass = existe ? 'text-success' : 'text-danger';
                const statusIcon = existe ? 'check-circle' : 'times-circle';
                const statusText = existe ? 'OK' : 'Ausente';
                
                html += `
                    <tr>
                        <td><code>${coluna}</code></td>
                        <td class="text-center ${statusClass}">
                            <i class="fas fa-${statusIcon}"></i> ${statusText}
                        </td>
                    </tr>
                `;
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            return html;
        }
        
        // Exportar relatório em formato JSON
        $('#btnExport').click(function() {
            $.ajax({
                url: 'ajax/diagnostico_db_compact.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Converter para string JSON formatada
                    const jsonString = JSON.stringify(data, null, 2);
                    
                    // Criar um Blob com os dados
                    const blob = new Blob([jsonString], {type: 'application/json'});
                    
                    // Criar link para download
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `diagnostico_sistema_${new Date().toISOString().slice(0, 10)}.json`;
                    document.body.appendChild(a);
                    a.click();
                    
                    // Limpar
                    setTimeout(function() {
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);
                    }, 0);
                },
                error: function(xhr, status, error) {
                    alert('Erro ao exportar o relatório: ' + error);
                }
            });
        });
        
        // Atualizar diagnóstico
        $('#btnRefresh').click(function() {
            carregarDiagnostico();
        });
        
        // Carregar diagnóstico ao iniciar
        carregarDiagnostico();
    });
</script>

</body>
</html> 