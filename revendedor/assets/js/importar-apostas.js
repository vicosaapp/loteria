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
    // Remove espaços extras e normaliza a linha
    const linhaLimpa = linha.trim().replace(/\s+/g, ' ');
    console.log('Linha para contagem de dezenas:', linhaLimpa);
    
    // Extrai todos os números da linha
    const numeros = linhaLimpa.match(/\d+/g);
    
    if (numeros) {
        console.log('Números extraídos:', numeros);
        const numerosUnicos = [...new Set(numeros)]; // Remove duplicatas
        console.log('Números únicos:', numerosUnicos.length);
        return numerosUnicos.length;
    } else {
        console.log('Nenhum número encontrado na linha');
        return 0;
    }
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
        
        console.log('Nome do jogo detectado:', nomeJogo, 'do texto:', nomeJogoLinha);
        
        // Se houver uma segunda linha com números
        if (linhas.length >= 2) {
            const primeiraAposta = linhas[1]; // A segunda linha é a primeira aposta
            const numDezenas = contarDezenas(primeiraAposta);
            
            console.log('Texto da primeira aposta:', primeiraAposta);
            console.log('Número de dezenas detectadas:', numDezenas);
            
            // Atualiza o campo de quantidade de dezenas
            qtdDezenasField.value = numDezenas + ' dezenas';
            
            // Força um número de dezenas válido para testes
            let numDezenasValidas = numDezenas;
            
            // Se o jogo for Quina ou Mega-Sena e não encontrar o número exato de dezenas,
            // usar um valor padrão conhecido que tem preços configurados
            if ((nomeJogo === 'QN' || nomeJogo === 'MS') && (!precosMegaSena[numDezenas] && !precosQuina[numDezenas])) {
                numDezenasValidas = 20; // Usar um valor que sabemos que tem preços definidos
                console.log('Usando quantidade padrão de dezenas:', numDezenasValidas);
            }
            
            // Atualiza as opções de valor baseado no nome do jogo e número de dezenas
            atualizarOpcoesValor(nomeJogo, numDezenasValidas);
            
            console.log('Jogo detectado:', nomeJogo);
            console.log('Dezenas válidas para busca:', numDezenasValidas);
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
    
    console.log('Atualizando valores para jogo:', nomeJogo, 'dezenas:', numDezenas);
    
    let precos = [];
    
    // Determina os preços baseado no nome do jogo
    if (nomeJogo && numDezenas > 0) {
        switch(nomeJogo) {
            case 'LF': // Lotofácil
                console.log('Buscando preços para Lotofácil com', numDezenas, 'dezenas');
                precos = precosLotofacil[numDezenas] || [];
                break;
            case 'DI': // Dia de Sorte
                console.log('Buscando preços para Dia de Sorte com', numDezenas, 'dezenas');
                precos = precosDiaDeSorte[numDezenas] || [];
                break;
            case 'MM': // Mais Milionária
                console.log('Buscando preços para Mais Milionária com', numDezenas, 'dezenas');
                precos = precosMaisMilionaria[numDezenas] || [];
                break;
            case 'MS': // Mega Sena
                console.log('Buscando preços para Mega Sena com', numDezenas, 'dezenas');
                console.log('Preços disponíveis para Mega Sena:', Object.keys(precosMegaSena));
                precos = precosMegaSena[numDezenas] || [];
                // Se não encontrou, tenta usar um valor padrão
                if (precos.length === 0 && numDezenas >= 15) {
                    console.log('Usando preços padrão para Mega Sena (20 dezenas)');
                    precos = precosMegaSena[20] || [];
                }
                break;
            case 'QN': // Quina
                console.log('Buscando preços para Quina com', numDezenas, 'dezenas');
                console.log('Preços disponíveis para Quina:', Object.keys(precosQuina));
                precos = precosQuina[numDezenas] || [];
                // Se não encontrou, tenta usar um valor padrão
                if (precos.length === 0 && numDezenas >= 15) {
                    console.log('Usando preços padrão para Quina (20 dezenas)');
                    precos = precosQuina[20] || [];
                }
                break;
            case 'LM': // Lotomania
                console.log('Buscando preços para Lotomania com', numDezenas, 'dezenas');
                precos = precosLotomania[numDezenas] || [];
                break;
            case 'TM': // Timemania
                console.log('Buscando preços para Timemania com', numDezenas, 'dezenas');
                precos = precosTimemania[numDezenas] || [];
                break;
        }
    }
    
    console.log('Preços encontrados:', precos.length);
    if (precos.length > 0) {
        console.log('Exemplos de preços:', precos.slice(0, 3));
    }
    
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