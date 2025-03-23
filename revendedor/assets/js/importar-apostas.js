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
    // Outros tamanhos...
};

const precosDiaDeSorte = {
    15: [
        { valor: 1.00, premio: 265.00 },
        { valor: 1.50, premio: 397.50 },
        { valor: 2.00, premio: 530.00 },
        // Outros valores...
    ],
    // Outros tamanhos...
};

const precosMaisMilionaria = {
    10: [
        { valor: 1.00, premio: 2000.00 },
        { valor: 1.50, premio: 3000.00 },
        // Outros valores...
    ],
    // Outros tamanhos...
};

const precosMegaSena = {
    20: [
        { valor: 1.00, premio: 800.00 },
        { valor: 1.50, premio: 1200.00 },
        // Outros valores...
    ],
    // Outros tamanhos...
};

const precosQuina = {
    20: [
        { valor: 1.00, premio: 800.00 },
        { valor: 1.50, premio: 1200.00 },
        // Outros valores...
    ],
    // Outros tamanhos...
};

const precosLotomania = {
    55: [
        { valor: 1.00, premio: 15000.00 },
        { valor: 1.50, premio: 22500.00 },
        // Outros valores...
    ],
    // Outros tamanhos...
};

const precosTimemania = {
    20: [
        { valor: 1.00, premio: 2000.00 },
        { valor: 1.50, premio: 3000.00 },
        // Outros valores...
    ],
    // Outros tamanhos...
};

// Função para atualizar WhatsApp
function atualizarWhatsApp() {
    const apostadorSelect = document.getElementById('apostador');
    const whatsappInput = document.getElementById('whatsapp');
    
    if (!apostadorSelect || !whatsappInput) return;
    
    const selectedOption = apostadorSelect.options[apostadorSelect.selectedIndex];
    
    if (selectedOption) {
        whatsappInput.value = selectedOption.dataset.whatsapp || '';
    } else {
        whatsappInput.value = '';
    }
}

// Função para formatar valor em moeda brasileira
function formatarMoeda(valor) {
    return valor.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Função para contar dezenas em uma linha de aposta
function contarDezenas(linha) {
    const numeros = linha.match(/\d+/g);
    return numeros ? numeros.length : 0;
}

// Função para processar o texto das apostas
function processarApostas() {
    const textarea = document.getElementById('apostas');
    const qtdDezenasField = document.getElementById('qtd_dezenas');
    
    if (!textarea || !qtdDezenasField) {
        console.error("Elementos necessários não encontrados");
        return;
    }
    
    const texto = textarea.value.trim();
    const linhas = texto.split('\n').filter(linha => linha.trim());
    
    if (linhas.length >= 1) {
        const nomeJogoLinha = linhas[0].trim().toUpperCase(); // Nome do jogo na primeira linha
        let nomeJogo = '';
        
        // Extrai o código do jogo (QN, DI, MM, MS, LF, LM, TM)
        if (nomeJogoLinha.includes('QN')) nomeJogo = 'QN';
        else if (nomeJogoLinha.includes('DI')) nomeJogo = 'DI';
        else if (nomeJogoLinha.includes('MM')) nomeJogo = 'MM';
        else if (nomeJogoLinha.includes('MS')) nomeJogo = 'MS';
        else if (nomeJogoLinha.includes('LF')) nomeJogo = 'LF';
        else if (nomeJogoLinha.includes('LM')) nomeJogo = 'LM';
        else if (nomeJogoLinha.includes('TM')) nomeJogo = 'TM';
        
        // Se houver uma segunda linha com números
        if (linhas.length >= 2) {
            const primeiraAposta = linhas[1]; // A segunda linha é a primeira aposta
            const numDezenas = contarDezenas(primeiraAposta);
            
            // Atualiza o campo de quantidade de dezenas
            qtdDezenasField.value = numDezenas + ' dezenas';
            
            // Atualiza as opções de valor baseado no nome do jogo e número de dezenas
            atualizarOpcoesValor(nomeJogo, numDezenas);
            
            console.log('Jogo detectado:', nomeJogo);
            console.log('Dezenas detectadas:', numDezenas);
        } else {
            qtdDezenasField.value = '0 dezenas';
            atualizarOpcoesValor(nomeJogo, 0);
        }
    } else {
        qtdDezenasField.value = '0 dezenas';
        atualizarOpcoesValor('', 0);
    }
}

// Função para calcular premiação
async function calcularPremiacao() {
    try {
        const textarea = document.getElementById('apostas');
        const valorApostaSelect = document.getElementById('valor_aposta');
        const premiacaoInput = document.getElementById('valor_premiacao');
        const debugDiv = document.querySelector('.debug-info');
        
        if (!textarea || !valorApostaSelect || !premiacaoInput) {
            console.error('Elementos necessários não encontrados', {
                textarea: !!textarea,
                valorApostaSelect: !!valorApostaSelect,
                premiacaoInput: !!premiacaoInput
            });
            return;
        }
        
        const texto = textarea.value.trim();
        const valorSelecionado = valorApostaSelect.value;
        
        if (debugDiv) debugDiv.innerHTML = ''; // Limpar debug anterior
        
        if (!texto || !valorSelecionado) {
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
                valor_aposta: valorSelecionado
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
            if (debugDiv && data.jogo.debug) {
                const valorBaseAposta = parseFloat(data.jogo.debug.valor_base_aposta);
                const valorBasePremio = parseFloat(data.jogo.debug.valor_base_premio);
                
                debugDiv.innerHTML = `
                    <p>Valor base da aposta: R$ ${valorBaseAposta.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                    <p>Valor base do prêmio: R$ ${valorBasePremio.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                    <p>Multiplicador: ${data.jogo.debug.multiplicador}</p>
                `;
            }
        } else {
            throw new Error(data.message || 'Erro desconhecido');
        }
    } catch (error) {
        console.error('Erro:', error);
        const premiacaoInput = document.getElementById('valor_premiacao');
        if (premiacaoInput) {
            premiacaoInput.value = '0,00';
        }
        
        const debugDiv = document.querySelector('.debug-info');
        if (debugDiv) {
            debugDiv.innerHTML = `<p class="error">Erro: ${error.message}</p>`;
        }
    }
}

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

// Função para visualizar apostas
function visualizarApostas() {
    const textarea = document.getElementById('apostas');
    if (!textarea) return;
    
    const texto = textarea.value.trim();
    if (!texto) return;
    
    const linhas = texto.split('\n').filter(linha => linha.trim());
    
    if (linhas.length < 2) {
        alert('Formato inválido. O texto deve conter pelo menos duas linhas.');
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
    
    const resumoEl = document.getElementById('resumoApostas');
    if (resumoEl) resumoEl.innerHTML = html;
    
    // Usar Bootstrap 5 para mostrar o modal
    const modalEl = document.getElementById('visualizarModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

// Função para atualizar as opções de valor baseado no nome do jogo e número de dezenas
function atualizarOpcoesValor(nomeJogo, numDezenas) {
    const selectValor = document.getElementById('valor_aposta');
    if (!selectValor) {
        console.error('Elemento select valor_aposta não encontrado');
        return;
    }
    
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
        
        // Se encontrou preços, calcular a premiação após o carregamento das opções
        setTimeout(calcularPremiacao, 100);
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
    selectValor.addEventListener('change', calcularPremiacao);
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar WhatsApp
    atualizarWhatsApp();
    
    // Configurar event listeners
    const apostadorSelect = document.getElementById('apostador');
    if (apostadorSelect) {
        apostadorSelect.addEventListener('change', atualizarWhatsApp);
    }
    
    // Processar apostas inicialmente se já existirem
    const textarea = document.getElementById('apostas');
    if (textarea) {
        // Eventos para apostas
        textarea.addEventListener('input', debounce(processarApostas, 500));
        textarea.addEventListener('paste', function() {
            setTimeout(processarApostas, 100);
        });
        
        // Inicializar se já tiver conteúdo
        if (textarea.value.trim()) {
            setTimeout(processarApostas, 100);
        }
    }
    
    // Botão visualizar
    const btnVisualizar = document.getElementById('btnVisualizar');
    if (btnVisualizar) {
        btnVisualizar.addEventListener('click', visualizarApostas);
    }
    
    // Valor da aposta
    const valorApostaSelect = document.getElementById('valor_aposta');
    if (valorApostaSelect) {
        valorApostaSelect.addEventListener('change', calcularPremiacao);
    }
}); 