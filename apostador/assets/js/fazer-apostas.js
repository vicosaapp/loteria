// Elementos DOM
const jogoSelect = document.getElementById('jogo');
const minDezenasInput = document.getElementById('min-dezenas');
const maxDezenasInput = document.getElementById('max-dezenas');
const numerosContainer = document.getElementById('numeros-container');
const contadorDezenas = document.getElementById('contador-dezenas');
const btnLimpar = document.getElementById('btn-limpar');
const btnGerarAleatorio = document.getElementById('btn-gerar-aleatorio');
const btnAdicionar = document.getElementById('btn-adicionar');
const apostasContainer = document.getElementById('apostas-container');
const btnRemoverTodos = document.getElementById('btn-remover-todos');
const btnSalvarApostas = document.getElementById('btn-salvar-apostas');
const btnConfirmarApostas = document.getElementById('btn-confirmar-apostas');
const numerosDisplay = document.getElementById('numeros-display');
const valorAposta = document.getElementById('valor-aposta');
const infoPremiacao = document.getElementById('info-premiacao');
const valorPremiacao = document.getElementById('valor-premiacao');
const resultadoModalBody = document.getElementById('resultadoModalBody');

// Estado da aplicação - torna global para facilitar depuração
window.estado = {
    jogoSelecionado: null,
    jogoId: null,
    minNumeros: 0,
    maxNumeros: 0,
    totalNumeros: 0,
    dezenasSelecionadas: [],
    apostas: []
};

// Inicializar modais
let confirmacaoModal;
let resultadoModal;

document.addEventListener('DOMContentLoaded', () => {
    if (typeof bootstrap !== 'undefined') {
        confirmacaoModal = new bootstrap.Modal(document.getElementById('confirmacaoModal'));
        resultadoModal = new bootstrap.Modal(document.getElementById('resultadoModal'));
    }
    
    // Adicionar event listeners
    jogoSelect.addEventListener('change', onJogoChange);
    btnLimpar.addEventListener('click', limparSelecao);
    btnGerarAleatorio.addEventListener('click', gerarNumerosAleatorios);
    btnAdicionar.addEventListener('click', adicionarAposta);
    btnRemoverTodos.addEventListener('click', removerTodasApostas);
    btnSalvarApostas.addEventListener('click', salvarApostas);
    btnConfirmarApostas.addEventListener('click', confirmarApostas);
    valorAposta.addEventListener('change', atualizarValorPremiacao);
});

// Função para lidar com a mudança de jogo
function onJogoChange() {
    const jogoId = this.value;
    if (!jogoId) {
        limparInterface();
        return;
    }
    
    // Obter dados do jogo selecionado
    const selectedOption = this.options[this.selectedIndex];
    window.estado.jogoId = jogoId;
    window.estado.minNumeros = parseInt(selectedOption.dataset.minNumeros, 10);
    window.estado.maxNumeros = parseInt(selectedOption.dataset.maxNumeros, 10);
    window.estado.totalNumeros = parseInt(selectedOption.dataset.numerosDisponiveis, 10);
    
    // Atualizar os inputs informativos
    minDezenasInput.value = window.estado.minNumeros;
    maxDezenasInput.value = window.estado.maxNumeros;
    
    // Habilitar botão de gerar aleatório
    btnGerarAleatorio.disabled = false;
    
    // Gerar a grade de números
    gerarGradeNumeros();
    
    // Limpar seleção
    limparSelecao();
}

// Função para limpar a interface
function limparInterface() {
    window.estado.jogoSelecionado = null;
    window.estado.jogoId = null;
    window.estado.minNumeros = 0;
    window.estado.maxNumeros = 0;
    window.estado.totalNumeros = 0;
    window.estado.dezenasSelecionadas = [];
    
    minDezenasInput.value = '';
    maxDezenasInput.value = '';
    numerosContainer.innerHTML = '<div class="alert alert-info">Selecione um jogo para ver os números disponíveis</div>';
    contadorDezenas.textContent = '0 dezenas selecionadas';
    numerosDisplay.textContent = 'Nenhum';
    
    btnLimpar.disabled = true;
    btnGerarAleatorio.disabled = true;
    btnAdicionar.disabled = true;
    
    valorAposta.innerHTML = '<option value="">Selecione o valor</option>';
    valorAposta.disabled = true;
    
    infoPremiacao.classList.add('d-none');
}

// Função para gerar a grade de números
function gerarGradeNumeros() {
    const gridDiv = document.createElement('div');
    gridDiv.className = 'numeros-grid';
    
    for (let i = 1; i <= window.estado.totalNumeros; i++) {
        const numeroDiv = document.createElement('div');
        numeroDiv.className = 'numero-item';
        numeroDiv.textContent = i.toString().padStart(2, '0');
        numeroDiv.dataset.numero = i;
        numeroDiv.addEventListener('click', () => toggleNumero(numeroDiv));
        
        gridDiv.appendChild(numeroDiv);
    }
    
    numerosContainer.innerHTML = '';
    numerosContainer.appendChild(gridDiv);
}

// Função para alternar a seleção de um número
function toggleNumero(elemento) {
    const numero = parseInt(elemento.dataset.numero, 10);
    
    // Se já estiver selecionado, remove da seleção
    if (elemento.classList.contains('selected') || elemento.classList.contains('selecionado')) {
        elemento.classList.remove('selected');
        elemento.classList.remove('selecionado');
        window.estado.dezenasSelecionadas = window.estado.dezenasSelecionadas.filter(n => n !== numero);
    } 
    // Se não estiver selecionado e não exceder o limite máximo
    else if (window.estado.dezenasSelecionadas.length < window.estado.maxNumeros) {
        elemento.classList.add('selected');
        elemento.classList.add('selecionado');
        window.estado.dezenasSelecionadas.push(numero);
        window.estado.dezenasSelecionadas.sort((a, b) => a - b);
    } 
    // Se exceder o limite máximo
    else {
        // Adicionar classe de animação de shake
        elemento.classList.add('shake-animation');
        setTimeout(() => {
            elemento.classList.remove('shake-animation');
        }, 500);
        
        alert(`Você já selecionou o número máximo de dezenas (${window.estado.maxNumeros})`);
        return;
    }
    
    // Atualizar contadores e displays
    atualizarInterface();
}

// Função para atualizar a interface após alterações na seleção
function atualizarInterface() {
    const numDezenas = window.estado.dezenasSelecionadas.length;
    contadorDezenas.textContent = `${numDezenas} dezenas selecionadas`;
    
    // Formatação dos números para exibição
    if (numDezenas > 0) {
        const numerosFormatados = window.estado.dezenasSelecionadas
            .map(n => n.toString().padStart(2, '0'))
            .join(' - ');
        numerosDisplay.textContent = numerosFormatados;
    } else {
        numerosDisplay.textContent = 'Nenhum';
    }
    
    // Habilitar/desabilitar botões
    btnLimpar.disabled = numDezenas === 0;
    btnAdicionar.disabled = numDezenas < window.estado.minNumeros;
    
    // Atualizar valores disponíveis se a quantidade de dezenas atingir o mínimo
    if (numDezenas >= window.estado.minNumeros) {
        atualizarValoresDisponiveis();
    } else {
        valorAposta.innerHTML = '<option value="">Selecione o valor</option>';
        valorAposta.disabled = true;
        infoPremiacao.classList.add('d-none');
    }
}

// Função para atualizar os valores disponíveis para a quantidade selecionada
function atualizarValoresDisponiveis() {
    const numDezenas = window.estado.dezenasSelecionadas.length;
    
    console.log('Atualizando valores disponíveis para', numDezenas, 'dezenas');
    console.log('Jogo ID:', window.estado.jogoId);
    console.log('Valores Jogos:', valoresJogos);
    
    // Obter os valores disponíveis para o jogo e quantidade de dezenas
    const valoresDisponiveis = valoresJogos[window.estado.jogoId]?.filter(v => 
        parseInt(v.dezenas, 10) === numDezenas
    ) || [];
    
    console.log('Valores disponíveis:', valoresDisponiveis);
    
    if (valoresDisponiveis.length > 0) {
        // Criar opções para o select
        valorAposta.innerHTML = '<option value="">Selecione o valor</option>';
        
        valoresDisponiveis.forEach(valor => {
            const option = document.createElement('option');
            option.value = valor.valor_aposta;
            option.dataset.premio = valor.valor_premio;
            option.textContent = `R$ ${parseFloat(valor.valor_aposta).toFixed(2).replace('.', ',')}`;
            valorAposta.appendChild(option);
        });
        
        valorAposta.disabled = false;
    } else {
        valorAposta.innerHTML = '<option value="">Sem valores para esta quantidade</option>';
        valorAposta.disabled = true;
        infoPremiacao.classList.add('d-none');
        
        // Verificar se existem valores para alguma dezena neste jogo
        const todosValoresPossiveisParaJogo = valoresJogos[window.estado.jogoId] || [];
        const dezenasPossiveis = [...new Set(todosValoresPossiveisParaJogo.map(v => parseInt(v.dezenas, 10)))];
        
        if (dezenasPossiveis.length > 0) {
            const proximaDezena = dezenasPossiveis.find(d => d >= numDezenas) || dezenasPossiveis[0];
            
            if (numDezenas < proximaDezena) {
                valorAposta.innerHTML = `<option value="">Selecione mais dezenas (mínimo: ${proximaDezena})</option>`;
            } else if (numDezenas > proximaDezena) {
                valorAposta.innerHTML = `<option value="">Selecione menos dezenas (próxima: ${proximaDezena})</option>`;
            }
        }
    }
}

// Função para atualizar o valor da premiação com base no valor selecionado
function atualizarValorPremiacao() {
    if (this.value) {
        const selectedOption = this.options[this.selectedIndex];
        const premio = selectedOption.dataset.premio;
        
        if (premio) {
            valorPremiacao.textContent = `R$ ${parseFloat(premio).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            infoPremiacao.classList.remove('d-none');
        } else {
            infoPremiacao.classList.add('d-none');
        }
    } else {
        infoPremiacao.classList.add('d-none');
    }
    
    // Habilitar botão de adicionar se valor estiver selecionado
    const temValor = this.value !== '';
    const temDezenasMinimas = window.estado.dezenasSelecionadas.length >= window.estado.minNumeros;
    btnAdicionar.disabled = !(temValor && temDezenasMinimas);
}

// Função para limpar a seleção de números
function limparSelecao() {
    // Remover classe 'selected' de todos os elementos selecionados
    const numerosElements = document.querySelectorAll('.numero-item.selected, .numero-item.selecionado, .numero-bolinha.selecionado');
    numerosElements.forEach(el => {
        el.classList.remove('selected');
        el.classList.remove('selecionado');
    });
    
    // Limpar array de seleção
    window.estado.dezenasSelecionadas = [];
    
    // Atualizar interface
    atualizarInterface();
}

// Função para gerar números aleatórios
function gerarNumerosAleatorios() {
    // Limpar seleção atual
    limparSelecao();
    
    // Quantidade de números a serem gerados (usar o mínimo para o jogo)
    const quantidade = window.estado.minNumeros;
    
    // Gerar array com todos os números disponíveis
    const numerosDisponiveis = Array.from({length: window.estado.totalNumeros}, (_, i) => i + 1);
    
    // Embaralhar e pegar a quantidade desejada
    const numerosAleatorios = numerosDisponiveis
        .sort(() => Math.random() - 0.5)
        .slice(0, quantidade);
    
    // Ordenar os números selecionados
    numerosAleatorios.sort((a, b) => a - b);
    
    // Atualizar o estado
    window.estado.dezenasSelecionadas = numerosAleatorios;
    
    // Atualizar visualmente os números selecionados
    numerosAleatorios.forEach(numero => {
        const elemento = document.querySelector(`.numero-item[data-numero="${numero}"]`);
        if (elemento) {
            elemento.classList.add('selected');
            elemento.classList.add('selecionado');
        }
    });
    
    // Atualizar interface
    atualizarInterface();
}

// Função para adicionar aposta
function adicionarAposta() {
    console.log("Executando função adicionarAposta()");
    // Verificar se tem números suficientes e valor selecionado
    if (window.estado.dezenasSelecionadas.length < window.estado.minNumeros || !valorAposta.value) {
        console.log("Validação falhou: dezenas ou valor inválidos");
        return;
    }
    
    // Obter valor da aposta e prêmio
    const valor = parseFloat(valorAposta.value);
    const selectedOption = valorAposta.options[valorAposta.selectedIndex];
    const premio = parseFloat(selectedOption.dataset.premio || 0);
    
    console.log("Adicionando aposta com dezenas:", window.estado.dezenasSelecionadas);
    console.log("Valor:", valor, "Prêmio:", premio);
    
    // Adicionar aposta ao estado
    window.estado.apostas.push({
        dezenas: [...window.estado.dezenasSelecionadas],
        valor: valor,
        premio: premio
    });
    
    console.log("Estado de apostas atualizado:", window.estado.apostas);
    
    // Atualizar a lista de apostas
    window.atualizarListaApostas();
    console.log("Lista de apostas atualizada");
    
    // Limpar seleção
    limparSelecao();
    console.log("Seleção limpa");
}

// Função para atualizar a lista de apostas
window.atualizarListaApostas = function() {
    if (window.estado.apostas.length === 0) {
        apostasContainer.innerHTML = '<div class="alert alert-info">Nenhuma aposta adicionada ainda</div>';
        btnSalvarApostas.disabled = true;
        btnRemoverTodos.disabled = true;
        return;
    }
    
    // Limpar container
    apostasContainer.innerHTML = '';
    
    // Adicionar cada aposta
    window.estado.apostas.forEach((aposta, index) => {
        const apostaEl = document.createElement('div');
        apostaEl.className = 'aposta-item';
        
        // Formatar números
        const numerosContainer = document.createElement('div');
        numerosContainer.className = 'aposta-numeros';
        
        aposta.dezenas.forEach(num => {
            const numEl = document.createElement('div');
            numEl.className = 'aposta-numero';
            numEl.textContent = num.toString().padStart(2, '0');
            numerosContainer.appendChild(numEl);
        });
        
        // Informações de valor e prêmio
        const infoEl = document.createElement('div');
        infoEl.className = 'mt-2';
        infoEl.innerHTML = `
            <span class="badge bg-success me-2">Valor: R$ ${aposta.valor.toFixed(2).replace('.', ',')}</span>
            <span class="badge bg-primary">Prêmio: R$ ${aposta.premio.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
        `;
        
        // Botão de remover
        const btnRemover = document.createElement('button');
        btnRemover.className = 'btn-remover-aposta';
        btnRemover.innerHTML = '<i class="fas fa-times"></i>';
        btnRemover.addEventListener('click', () => removerAposta(index));
        
        // Adicionar elementos ao container da aposta
        apostaEl.appendChild(numerosContainer);
        apostaEl.appendChild(infoEl);
        apostaEl.appendChild(btnRemover);
        
        apostasContainer.appendChild(apostaEl);
    });
    
    // Habilitar botões
    btnRemoverTodos.disabled = false;
    btnSalvarApostas.disabled = false;
}

// Função para remover uma aposta
function removerAposta(index) {
    window.estado.apostas.splice(index, 1);
    window.atualizarListaApostas();
}

// Função para remover todas as apostas
function removerTodasApostas() {
    if (confirm('Tem certeza que deseja remover todas as apostas?')) {
        window.estado.apostas = [];
        window.atualizarListaApostas();
    }
}

// Função para iniciar o processo de salvamento das apostas
function salvarApostas() {
    if (window.estado.apostas.length === 0) {
        alert('Você precisa adicionar pelo menos uma aposta antes de salvar.');
        return;
    }
    
    // Mostrar o modal de confirmação
    if (confirmacaoModal) {
        confirmacaoModal.show();
    } else {
        // Se o modal não estiver disponível, chamar diretamente a função de confirmação
        confirmarApostas();
    }
}

// Função para confirmar e salvar as apostas no banco de dados
function confirmarApostas() {
    // Fechar o modal de confirmação
    if (confirmacaoModal) {
        confirmacaoModal.hide();
    }
    
    // Preparar os dados para envio
    const apostasFormatadas = window.estado.apostas.map(aposta => {
        return {
            jogo_id: window.estado.jogoId, 
            dezenas: aposta.dezenas,
            valor: aposta.valor,
            premio: aposta.premio
        };
    });
    
    // Enviar as apostas para o servidor
    fetch('ajax/salvar_apostas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ apostas: apostasFormatadas })
    })
    .then(response => response.json())
    .then(data => {
        // Exibir resultados
        if (data.success) {
            // Sucesso
            let mensagem = `
                <div class="alert alert-success">
                    <strong>${data.message}</strong>
                </div>
            `;
            
            if (data.warnings && data.warnings.length > 0) {
                mensagem += `
                    <div class="alert alert-warning">
                        <strong>Atenção:</strong>
                        <ul>
                            ${data.warnings.map(w => `<li>${w}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            // Limpar apostas após salvar com sucesso
            window.estado.apostas = [];
            window.atualizarListaApostas();
            
            resultadoModalBody.innerHTML = mensagem;
        } else {
            // Erro
            let mensagem = `
                <div class="alert alert-danger">
                    <strong>${data.message}</strong>
                </div>
            `;
            
            if (data.errors && data.errors.length > 0) {
                mensagem += `
                    <div class="alert alert-warning">
                        <strong>Erros:</strong>
                        <ul>
                            ${data.errors.map(e => `<li>${e}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            resultadoModalBody.innerHTML = mensagem;
        }
        
        // Mostrar modal de resultado
        if (resultadoModal) {
            resultadoModal.show();
        }
    })
    .catch(error => {
        console.error('Erro ao salvar apostas:', error);
        
        resultadoModalBody.innerHTML = `
            <div class="alert alert-danger">
                <strong>Erro ao salvar apostas!</strong>
                <p>Ocorreu um erro ao tentar salvar suas apostas. Por favor, tente novamente mais tarde.</p>
                <p>Detalhes: ${error.message}</p>
            </div>
        `;
        
        // Mostrar modal de resultado
        if (resultadoModal) {
            resultadoModal.show();
        }
    });
} 