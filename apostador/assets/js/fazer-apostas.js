// Configurações dos jogos
const jogosConfig = {
    'LF': { nome: 'Lotofácil', minDezenas: 15, maxDezenas: 20, totalNumeros: 25 },
    'MS': { nome: 'Mega-Sena', minDezenas: 20, maxDezenas: 45, totalNumeros: 60, dezenasValidas: [20, 25, 30, 35, 40, 45] },
    'QN': { nome: 'Quina', minDezenas: 20, maxDezenas: 50, totalNumeros: 80, dezenasValidas: [20, 25, 30, 35, 40, 45, 50] },
    'LM': { nome: 'Lotomania', minDezenas: 55, maxDezenas: 85, totalNumeros: 100, dezenasValidas: [55, 60, 65, 70, 75, 80, 85] },
    'TM': { nome: 'Timemania', minDezenas: 20, maxDezenas: 55, totalNumeros: 80, dezenasValidas: [20, 25, 30, 35, 40, 45, 50, 55] },
    'DI': { nome: 'Dia de Sorte', minDezenas: 7, maxDezenas: 15, totalNumeros: 31 },
    'MM': { nome: 'Mais Milionária', minDezenas: 6, maxDezenas: 12, totalNumeros: 50 }
};

// Valores de apostas e premiação do Timemania
const timemaniaValores = {
    20: [
        { aposta: 1.00, premio: 2000.00 },
        { aposta: 1.50, premio: 3000.00 },
        { aposta: 2.00, premio: 4000.00 },
        { aposta: 3.00, premio: 6000.00 },
        { aposta: 5.00, premio: 10000.00 },
        { aposta: 10.00, premio: 20000.00 },
        { aposta: 15.00, premio: 30000.00 }
    ],
    25: [
        { aposta: 1.00, premio: 900.00 },
        { aposta: 1.50, premio: 1350.00 },
        { aposta: 2.00, premio: 1800.00 },
        { aposta: 3.00, premio: 2700.00 },
        { aposta: 5.00, premio: 4500.00 },
        { aposta: 10.00, premio: 9000.00 },
        { aposta: 15.00, premio: 13500.00 },
        { aposta: 20.00, premio: 18000.00 },
        { aposta: 25.00, premio: 22500.00 },
        { aposta: 34.00, premio: 30000.00 }
    ],
    30: [
        { aposta: 1.00, premio: 320.00 },
        { aposta: 1.50, premio: 480.00 },
        { aposta: 2.00, premio: 640.00 },
        { aposta: 3.00, premio: 960.00 },
        { aposta: 5.00, premio: 1600.00 },
        { aposta: 10.00, premio: 3200.00 },
        { aposta: 15.00, premio: 4800.00 },
        { aposta: 20.00, premio: 6400.00 },
        { aposta: 25.00, premio: 8000.00 },
        { aposta: 50.00, premio: 16000.00 },
        { aposta: 94.00, premio: 30080.00 }
    ],
    35: [
        { aposta: 1.00, premio: 120.00 },
        { aposta: 1.50, premio: 180.00 },
        { aposta: 2.00, premio: 240.00 },
        { aposta: 3.00, premio: 360.00 },
        { aposta: 5.00, premio: 600.00 },
        { aposta: 10.00, premio: 1200.00 },
        { aposta: 15.00, premio: 1800.00 },
        { aposta: 20.00, premio: 2400.00 },
        { aposta: 25.00, premio: 3000.00 },
        { aposta: 50.00, premio: 6000.00 },
        { aposta: 100.00, premio: 12000.00 }
    ],
    40: [
        { aposta: 1.00, premio: 65.00 },
        { aposta: 1.50, premio: 97.50 },
        { aposta: 2.00, premio: 130.00 },
        { aposta: 3.00, premio: 195.00 },
        { aposta: 5.00, premio: 325.00 },
        { aposta: 10.00, premio: 650.00 },
        { aposta: 15.00, premio: 975.00 },
        { aposta: 20.00, premio: 1300.00 },
        { aposta: 25.00, premio: 1625.00 },
        { aposta: 50.00, premio: 3250.00 },
        { aposta: 100.00, premio: 6500.00 }
    ],
    45: [
        { aposta: 5.00, premio: 160.00 },
        { aposta: 5.50, premio: 176.00 },
        { aposta: 10.00, premio: 320.00 },
        { aposta: 15.00, premio: 480.00 },
        { aposta: 20.00, premio: 640.00 },
        { aposta: 25.00, premio: 800.00 },
        { aposta: 50.00, premio: 1600.00 },
        { aposta: 100.00, premio: 3200.00 }
    ],
    50: [
        { aposta: 5.00, premio: 80.00 },
        { aposta: 5.50, premio: 88.00 },
        { aposta: 10.00, premio: 160.00 },
        { aposta: 15.00, premio: 240.00 },
        { aposta: 20.00, premio: 320.00 },
        { aposta: 25.00, premio: 400.00 },
        { aposta: 50.00, premio: 800.00 },
        { aposta: 100.00, premio: 1600.00 }
    ],
    55: [
        { aposta: 5.00, premio: 50.00 },
        { aposta: 5.50, premio: 55.00 },
        { aposta: 10.00, premio: 100.00 },
        { aposta: 15.00, premio: 150.00 },
        { aposta: 20.00, premio: 200.00 },
        { aposta: 25.00, premio: 250.00 },
        { aposta: 50.00, premio: 500.00 },
        { aposta: 100.00, premio: 1000.00 }
    ]
};

// Valores de apostas e premiação da Mega-Sena
const megaSenaValores = {
    6: [
        { aposta: 5.00, premio: 0.00 } // Valor padrão para aposta simples (padrão oficial)
    ],
    7: [
        { aposta: 35.00, premio: 0.00 } // Valor padrão para aposta com 7 dezenas
    ],
    8: [
        { aposta: 140.00, premio: 0.00 } // Valor padrão para aposta com 8 dezenas
    ],
    9: [
        { aposta: 420.00, premio: 0.00 } // Valor padrão para aposta com 9 dezenas
    ],
    10: [
        { aposta: 1050.00, premio: 0.00 } // Valor padrão para aposta com 10 dezenas
    ],
    11: [
        { aposta: 2310.00, premio: 0.00 } // Valor padrão para aposta com 11 dezenas
    ],
    12: [
        { aposta: 4620.00, premio: 0.00 } // Valor padrão para aposta com 12 dezenas
    ],
    13: [
        { aposta: 8580.00, premio: 0.00 } // Valor padrão para aposta com 13 dezenas
    ],
    14: [
        { aposta: 15015.00, premio: 0.00 } // Valor padrão para aposta com 14 dezenas
    ],
    15: [
        { aposta: 25025.00, premio: 0.00 } // Valor padrão para aposta com 15 dezenas
    ],
    20: [
        { aposta: 1.00, premio: 800.00 },
        { aposta: 1.50, premio: 1200.00 },
        { aposta: 2.00, premio: 1600.00 },
        { aposta: 3.00, premio: 2400.00 },
        { aposta: 5.00, premio: 4000.00 },
        { aposta: 7.00, premio: 5600.00 },
        { aposta: 10.00, premio: 8000.00 },
        { aposta: 15.00, premio: 12000.00 },
        { aposta: 20.00, premio: 16000.00 },
        { aposta: 25.00, premio: 20000.00 },
        { aposta: 37.50, premio: 30000.00 }
    ],
    25: [
        { aposta: 1.00, premio: 167.00 },
        { aposta: 1.50, premio: 250.50 },
        { aposta: 2.00, premio: 334.00 },
        { aposta: 3.00, premio: 501.00 },
        { aposta: 5.00, premio: 835.00 },
        { aposta: 7.00, premio: 1169.00 },
        { aposta: 10.00, premio: 1670.00 },
        { aposta: 15.00, premio: 2505.00 },
        { aposta: 20.00, premio: 3340.00 },
        { aposta: 25.00, premio: 4175.00 },
        { aposta: 50.00, premio: 8350.00 },
        { aposta: 100.00, premio: 16700.00 }
    ],
    30: [
        { aposta: 1.00, premio: 56.00 },
        { aposta: 1.50, premio: 84.00 },
        { aposta: 2.00, premio: 112.00 },
        { aposta: 3.00, premio: 168.00 },
        { aposta: 5.00, premio: 280.00 },
        { aposta: 7.00, premio: 392.00 },
        { aposta: 10.00, premio: 560.00 },
        { aposta: 15.00, premio: 840.00 },
        { aposta: 20.00, premio: 1120.00 },
        { aposta: 25.00, premio: 1400.00 },
        { aposta: 50.00, premio: 2800.00 },
        { aposta: 100.00, premio: 5600.00 }
    ],
    35: [
        { aposta: 1.00, premio: 22.00 },
        { aposta: 1.50, premio: 33.00 },
        { aposta: 2.00, premio: 44.00 },
        { aposta: 3.00, premio: 66.00 },
        { aposta: 5.00, premio: 110.00 },
        { aposta: 7.00, premio: 154.00 },
        { aposta: 10.00, premio: 220.00 },
        { aposta: 15.00, premio: 330.00 },
        { aposta: 20.00, premio: 440.00 },
        { aposta: 25.00, premio: 550.00 },
        { aposta: 50.00, premio: 1100.00 },
        { aposta: 100.00, premio: 2200.00 }
    ],
    40: [
        { aposta: 5.00, premio: 45.00 },
        { aposta: 5.50, premio: 49.50 },
        { aposta: 10.00, premio: 90.00 },
        { aposta: 15.00, premio: 135.00 },
        { aposta: 20.00, premio: 180.00 },
        { aposta: 25.00, premio: 225.00 },
        { aposta: 50.00, premio: 450.00 },
        { aposta: 100.00, premio: 900.00 }
    ],
    45: [
        { aposta: 5.00, premio: 15.00 },
        { aposta: 5.50, premio: 16.50 },
        { aposta: 10.00, premio: 30.00 },
        { aposta: 15.00, premio: 45.00 },
        { aposta: 20.00, premio: 60.00 },
        { aposta: 25.00, premio: 75.00 },
        { aposta: 50.00, premio: 150.00 },
        { aposta: 100.00, premio: 300.00 }
    ]
};

// Função para obter as opções de valor/premiação da Mega-Sena
function getMegaSenaOptions(numDezenas) {
    // Encontra a categoria mais próxima de dezenas
    if (numDezenas <= 15) {
        // Para apostas oficiais (6 a 15 dezenas), retornar apenas o valor oficial
        return megaSenaValores[numDezenas] || megaSenaValores[6];
    }
    
    // Para apostas personalizadas
    let closest = 20;
    
    if (numDezenas >= 45) {
        closest = 45;
    } else if (numDezenas >= 40) {
        closest = 40;
    } else if (numDezenas >= 35) {
        closest = 35;
    } else if (numDezenas >= 30) {
        closest = 30;
    } else if (numDezenas >= 25) {
        closest = 25;
    } else {
        closest = 20;
    }
    
    return megaSenaValores[closest];
}

// Valores de apostas e premiação da Quina
const quinaValores = {
    20: [
        { aposta: 1.00, premio: 800.00 },
        { aposta: 1.50, premio: 1200.00 },
        { aposta: 2.00, premio: 1600.00 },
        { aposta: 3.00, premio: 2400.00 },
        { aposta: 5.00, premio: 4000.00 },
        { aposta: 10.00, premio: 8000.00 },
        { aposta: 15.00, premio: 12000.00 },
        { aposta: 20.00, premio: 16000.00 },
        { aposta: 25.00, premio: 20000.00 },
        { aposta: 37.50, premio: 30000.00 }
    ],
    25: [
        { aposta: 1.00, premio: 260.00 },
        { aposta: 1.50, premio: 390.00 },
        { aposta: 2.00, premio: 520.00 },
        { aposta: 3.00, premio: 780.00 },
        { aposta: 5.00, premio: 1300.00 },
        { aposta: 10.00, premio: 2600.00 },
        { aposta: 15.00, premio: 3900.00 },
        { aposta: 20.00, premio: 5200.00 },
        { aposta: 25.00, premio: 6500.00 },
        { aposta: 50.00, premio: 13000.00 },
        { aposta: 100.00, premio: 26000.00 }
    ],
    30: [
        { aposta: 1.00, premio: 115.00 },
        { aposta: 1.50, premio: 172.50 },
        { aposta: 2.00, premio: 230.00 },
        { aposta: 3.00, premio: 345.00 },
        { aposta: 5.00, premio: 575.00 },
        { aposta: 10.00, premio: 1150.00 },
        { aposta: 15.00, premio: 1725.00 },
        { aposta: 20.00, premio: 2300.00 },
        { aposta: 25.00, premio: 2875.00 },
        { aposta: 50.00, premio: 5750.00 },
        { aposta: 100.00, premio: 11500.00 }
    ],
    35: [
        { aposta: 1.00, premio: 55.00 },
        { aposta: 1.50, premio: 82.50 },
        { aposta: 2.00, premio: 110.00 },
        { aposta: 3.00, premio: 165.00 },
        { aposta: 5.00, premio: 275.00 },
        { aposta: 10.00, premio: 550.00 },
        { aposta: 15.00, premio: 825.00 },
        { aposta: 20.00, premio: 1100.00 },
        { aposta: 25.00, premio: 1375.00 },
        { aposta: 50.00, premio: 2750.00 },
        { aposta: 100.00, premio: 5500.00 }
    ],
    40: [
        { aposta: 1.00, premio: 26.00 },
        { aposta: 1.50, premio: 39.00 },
        { aposta: 2.00, premio: 52.00 },
        { aposta: 3.00, premio: 78.00 },
        { aposta: 5.00, premio: 130.00 },
        { aposta: 10.00, premio: 260.00 },
        { aposta: 15.00, premio: 390.00 },
        { aposta: 20.00, premio: 520.00 },
        { aposta: 25.00, premio: 650.00 },
        { aposta: 50.00, premio: 1300.00 },
        { aposta: 100.00, premio: 2600.00 }
    ],
    45: [
        { aposta: 5.00, premio: 65.00 },
        { aposta: 5.50, premio: 71.50 },
        { aposta: 10.00, premio: 130.00 },
        { aposta: 15.00, premio: 195.00 },
        { aposta: 20.00, premio: 260.00 },
        { aposta: 25.00, premio: 325.00 },
        { aposta: 35.00, premio: 455.00 },
        { aposta: 50.00, premio: 650.00 },
        { aposta: 100.00, premio: 1300.00 }
    ],
    50: [
        { aposta: 5.00, premio: 25.00 },
        { aposta: 5.50, premio: 27.50 },
        { aposta: 10.00, premio: 50.00 },
        { aposta: 15.00, premio: 75.00 },
        { aposta: 20.00, premio: 100.00 },
        { aposta: 25.00, premio: 125.00 },
        { aposta: 50.00, premio: 250.00 },
        { aposta: 100.00, premio: 500.00 }
    ]
};

// Função para obter as opções de valor/premiação da Quina
function getQuinaOptions(numDezenas) {
    // Encontra a categoria mais próxima de dezenas
    let closest = 20; // Valor mínimo
    
    if (numDezenas >= 50) {
        closest = 50;
    } else if (numDezenas >= 45) {
        closest = 45;
    } else if (numDezenas >= 40) {
        closest = 40;
    } else if (numDezenas >= 35) {
        closest = 35;
    } else if (numDezenas >= 30) {
        closest = 30;
    } else if (numDezenas >= 25) {
        closest = 25;
    } else {
        closest = 20;
    }
    
    return quinaValores[closest];
}

// Função para obter as opções de valor/premiação do Timemania
function getTimemaniaOptions(numDezenas) {
    // Encontra a categoria mais próxima de dezenas
    let closest = 20; // Valor mínimo
    
    if (numDezenas >= 55) {
        closest = 55;
    } else if (numDezenas >= 50) {
        closest = 50;
    } else if (numDezenas >= 45) {
        closest = 45;
    } else if (numDezenas >= 40) {
        closest = 40;
    } else if (numDezenas >= 35) {
        closest = 35;
    } else if (numDezenas >= 30) {
        closest = 30;
    } else if (numDezenas >= 25) {
        closest = 25;
    } else {
        closest = 20;
    }
    
    return timemaniaValores[closest];
}

// Valores de apostas e premiação da Lotomania
const lotomaniaValores = {
    55: [
        { aposta: 1.00, premio: 15000.00 },
        { aposta: 1.50, premio: 22500.00 },
        { aposta: 2.00, premio: 30000.00 }
    ],
    60: [
        { aposta: 1.00, premio: 10000.00 },
        { aposta: 1.50, premio: 15000.00 },
        { aposta: 2.00, premio: 20000.00 },
        { aposta: 2.50, premio: 25000.00 },
        { aposta: 3.00, premio: 30000.00 }
    ],
    65: [
        { aposta: 1.00, premio: 2000.00 },
        { aposta: 1.50, premio: 3000.00 },
        { aposta: 2.00, premio: 4000.00 },
        { aposta: 2.50, premio: 5000.00 },
        { aposta: 3.00, premio: 6000.00 },
        { aposta: 5.00, premio: 10000.00 },
        { aposta: 7.00, premio: 14000.00 },
        { aposta: 10.00, premio: 20000.00 },
        { aposta: 15.00, premio: 30000.00 }
    ],
    70: [
        { aposta: 1.00, premio: 520.00 },
        { aposta: 1.50, premio: 780.00 },
        { aposta: 2.00, premio: 1040.00 },
        { aposta: 3.00, premio: 1560.00 },
        { aposta: 5.00, premio: 2600.00 },
        { aposta: 7.00, premio: 3640.00 },
        { aposta: 10.00, premio: 5200.00 },
        { aposta: 15.00, premio: 7800.00 },
        { aposta: 20.00, premio: 10400.00 },
        { aposta: 25.00, premio: 13000.00 },
        { aposta: 50.00, premio: 26000.00 },
        { aposta: 58.00, premio: 30000.00 }
    ],
    75: [
        { aposta: 1.00, premio: 280.00 },
        { aposta: 1.50, premio: 420.00 },
        { aposta: 2.00, premio: 560.00 },
        { aposta: 3.00, premio: 840.00 },
        { aposta: 5.00, premio: 1400.00 },
        { aposta: 7.00, premio: 1960.00 },
        { aposta: 10.00, premio: 2800.00 },
        { aposta: 15.00, premio: 4200.00 },
        { aposta: 20.00, premio: 5600.00 },
        { aposta: 25.00, premio: 7000.00 },
        { aposta: 50.00, premio: 14000.00 },
        { aposta: 100.00, premio: 28000.00 }
    ],
    80: [
        { aposta: 1.00, premio: 77.00 },
        { aposta: 1.50, premio: 115.00 },
        { aposta: 2.00, premio: 154.00 },
        { aposta: 3.00, premio: 231.00 },
        { aposta: 5.00, premio: 385.00 },
        { aposta: 7.00, premio: 539.00 },
        { aposta: 10.00, premio: 770.00 },
        { aposta: 15.00, premio: 1155.00 },
        { aposta: 20.00, premio: 1540.00 },
        { aposta: 25.00, premio: 1925.00 },
        { aposta: 50.00, premio: 3850.00 },
        { aposta: 100.00, premio: 7700.00 }
    ],
    85: [
        { aposta: 5.00, premio: 75.00 },
        { aposta: 5.50, premio: 82.50 },
        { aposta: 10.00, premio: 150.00 },
        { aposta: 15.00, premio: 225.00 },
        { aposta: 20.00, premio: 300.00 },
        { aposta: 25.00, premio: 375.00 },
        { aposta: 50.00, premio: 750.00 },
        { aposta: 100.00, premio: 1150.00 }
    ]
};

// Função para obter as opções de valor/premiação da Lotomania
function getLotomaniaOptions(numDezenas) {
    // Encontra a categoria mais próxima de dezenas
    let closest = 55; // Valor mínimo
    
    if (numDezenas >= 85) {
        closest = 85;
    } else if (numDezenas >= 80) {
        closest = 80;
    } else if (numDezenas >= 75) {
        closest = 75;
    } else if (numDezenas >= 70) {
        closest = 70;
    } else if (numDezenas >= 65) {
        closest = 65;
    } else if (numDezenas >= 60) {
        closest = 60;
    } else {
        closest = 55;
    }
    
    return lotomaniaValores[closest];
}

// Estado da aplicação
const estado = {
    jogoSelecionado: null,
    codigoJogo: '',
    dezenasSelecionadas: [],
    apostas: []
};

// Elementos DOM
const jogoSelect = document.getElementById('jogo');
const minDezenasInput = document.getElementById('min-dezenas');
const maxDezenasInput = document.getElementById('max-dezenas');
const numerosContainer = document.getElementById('numeros-container');
const contadorDezenas = document.getElementById('contador-dezenas');
const btnLimpar = document.getElementById('btn-limpar');
const btnAdicionar = document.getElementById('btn-adicionar');
const apostasContainer = document.getElementById('apostas-container');
const btnRemoverTodos = document.getElementById('btn-remover-todos');
const btnEnviarWhatsapp = document.getElementById('btn-enviar-whatsapp');
const btnConfirmarEnvio = document.getElementById('btn-confirmar-envio');

// Inicializar o modal de confirmação
let confirmacaoModal;
document.addEventListener('DOMContentLoaded', () => {
    if (typeof bootstrap !== 'undefined') {
        confirmacaoModal = new bootstrap.Modal(document.getElementById('confirmacaoModal'));
    }
});

// Função para gerar os números para seleção
function gerarNumeros(totalNumeros) {
    numerosContainer.innerHTML = '';
    
    const numerosGrid = document.createElement('div');
    numerosGrid.className = 'numeros-grid';
    
    for (let i = 1; i <= totalNumeros; i++) {
        const numero = document.createElement('div');
        numero.className = 'numero-bolinha';
        numero.textContent = i.toString().padStart(2, '0');
        numero.dataset.numero = i;
        
        numero.addEventListener('click', () => toggleNumero(numero));
        
        numerosGrid.appendChild(numero);
    }
    
    numerosContainer.appendChild(numerosGrid);
}

// Função para alternar a seleção de um número
function toggleNumero(elemento) {
    const numero = parseInt(elemento.dataset.numero);
    const jogoConfig = jogosConfig[estado.codigoJogo];
    
    if (!jogoConfig) return;
    
    if (elemento.classList.contains('selecionado')) {
        // Remover da seleção
        elemento.classList.remove('selecionado');
        estado.dezenasSelecionadas = estado.dezenasSelecionadas.filter(n => n !== numero);
    } else {
        // Verificar limite máximo
        if (estado.dezenasSelecionadas.length >= jogoConfig.maxDezenas) {
            alert(`Você já selecionou o número máximo de dezenas (${jogoConfig.maxDezenas}) para ${jogoConfig.nome}`);
            return;
        }
        
        // Verificações específicas para jogos com dezenas válidas
        if (estado.codigoJogo === 'TM' || estado.codigoJogo === 'QN' || estado.codigoJogo === 'MS' || estado.codigoJogo === 'LM') {
            const proximaQuantidade = estado.dezenasSelecionadas.length + 1;
            const dezenasValidas = jogoConfig.dezenasValidas;
            
            // Verificar se estamos no limite máximo e não é uma quantidade válida
            if (proximaQuantidade > jogoConfig.maxDezenas && !dezenasValidas.includes(proximaQuantidade)) {
                alert(`Você atingiu o limite máximo de dezenas para o ${jogoConfig.nome} (${jogoConfig.maxDezenas}).`);
                return;
            }
        }
        
        // Adicionar à seleção
        elemento.classList.add('selecionado');
        estado.dezenasSelecionadas.push(numero);
        // Ordenar os números
        estado.dezenasSelecionadas.sort((a, b) => a - b);
    }
    
    // Atualizar o contador
    atualizarContador();
    
    // Habilitar/desabilitar botões - específico para jogos com dezenas válidas
    let podeAdicionar = estado.dezenasSelecionadas.length >= jogoConfig.minDezenas;
    
    // Para jogos com dezenas válidas, só pode adicionar se o número de dezenas estiver na lista de válidas
    if ((estado.codigoJogo === 'TM' || estado.codigoJogo === 'QN' || estado.codigoJogo === 'MS' || estado.codigoJogo === 'LM') && jogoConfig.dezenasValidas) {
        podeAdicionar = jogoConfig.dezenasValidas.includes(estado.dezenasSelecionadas.length);
    }
    
    btnAdicionar.disabled = !podeAdicionar;
    btnLimpar.disabled = estado.dezenasSelecionadas.length === 0;
}

// Função para atualizar o contador de dezenas
function atualizarContador() {
    const numDezenas = estado.dezenasSelecionadas.length;
    let texto = `${numDezenas} dezenas selecionadas`;
    
    // Informações adicionais para jogos com dezenas válidas específicas
    if ((estado.codigoJogo === 'TM' || estado.codigoJogo === 'QN' || estado.codigoJogo === 'MS' || estado.codigoJogo === 'LM') && jogosConfig[estado.codigoJogo].dezenasValidas) {
        const jogoConfig = jogosConfig[estado.codigoJogo];
        
        if (jogoConfig.dezenasValidas.includes(numDezenas)) {
            // Se atingiu uma quantidade válida
            texto += ` - <span class="text-success">Quantidade válida para o ${jogoConfig.nome} ✓</span>`;
        } else {
            // Encontrar a próxima quantidade válida
            const proximaValida = jogoConfig.dezenasValidas.find(d => d > numDezenas);
            
            if (proximaValida) {
                const faltam = proximaValida - numDezenas;
                texto += ` - <span class="text-primary">Faltam ${faltam} para atingir ${proximaValida} dezenas (válido)</span>`;
            } else {
                // Se já passou do máximo
                texto += ` - <span class="text-danger">Quantidade não válida para o ${jogoConfig.nome}</span>`;
            }
        }
    }
    
    contadorDezenas.innerHTML = texto;
}

// Função para limpar dezenas selecionadas
function limparSelecao() {
    const bolinhas = document.querySelectorAll('.numero-bolinha.selecionado');
    bolinhas.forEach(bolinha => bolinha.classList.remove('selecionado'));
    
    estado.dezenasSelecionadas = [];
    atualizarContador();
    
    btnAdicionar.disabled = true;
    btnLimpar.disabled = true;
}

// Função para adicionar aposta
function adicionarAposta() {
    const jogoConfig = jogosConfig[estado.codigoJogo];
    
    if (!jogoConfig) return;
    
    // Verificação especial para Timemania
    if (estado.codigoJogo === 'TM') {
        const numDezenas = estado.dezenasSelecionadas.length;
        
        // Verificar se a quantidade de dezenas é válida
        if (!jogoConfig.dezenasValidas.includes(numDezenas)) {
            alert(`Para o Timemania, você só pode marcar ${jogoConfig.dezenasValidas.join(', ')} dezenas. Você marcou ${numDezenas} dezenas.`);
            return;
        }
        
        // Mostrar seleção de valor
        mostrarSelecaoValorTimemania();
        return;
    }
    
    // Verificação especial para Quina
    if (estado.codigoJogo === 'QN') {
        const numDezenas = estado.dezenasSelecionadas.length;
        
        // Verificar se a quantidade de dezenas é válida
        if (!jogoConfig.dezenasValidas.includes(numDezenas)) {
            alert(`Para a Quina, você só pode marcar ${jogoConfig.dezenasValidas.join(', ')} dezenas. Você marcou ${numDezenas} dezenas.`);
            return;
        }
        
        // Mostrar seleção de valor para Quina
        mostrarSelecaoValorQuina();
        return;
    }
    
    // Verificação especial para Mega-Sena
    if (estado.codigoJogo === 'MS') {
        const numDezenas = estado.dezenasSelecionadas.length;
        
        // Verificar se a quantidade de dezenas é válida
        if (!jogoConfig.dezenasValidas.includes(numDezenas)) {
            alert(`Para a Mega-Sena, você só pode marcar ${jogoConfig.dezenasValidas.join(', ')} dezenas. Você marcou ${numDezenas} dezenas.`);
            return;
        }
        
        // Para apostas oficiais (6 a 15 dezenas), adicionar diretamente com o valor padrão
        if (numDezenas >= 6 && numDezenas <= 15) {
            const valorPadrao = megaSenaValores[numDezenas][0];
            estado.apostas.push({
                dezenas: [...estado.dezenasSelecionadas],
                valor: valorPadrao.aposta,
                premio: valorPadrao.premio
            });
            
            // Atualizar a interface
            atualizarListaApostas();
            
            // Limpar a seleção atual
            limparSelecao();
            
            // Habilitar botões de remover e enviar
            btnRemoverTodos.disabled = false;
            btnEnviarWhatsapp.disabled = false;
            return;
        }
        
        // Para apostas com mais de 15 dezenas, mostrar seleção de valor
        mostrarSelecaoValorMegaSena();
        return;
    }
    
    // Verificação especial para Lotomania
    if (estado.codigoJogo === 'LM') {
        const numDezenas = estado.dezenasSelecionadas.length;
        
        // Verificar se a quantidade de dezenas é válida
        if (!jogoConfig.dezenasValidas.includes(numDezenas)) {
            alert(`Para a Lotomania, você só pode marcar ${jogoConfig.dezenasValidas.join(', ')} dezenas. Você marcou ${numDezenas} dezenas.`);
            return;
        }
        
        // Mostrar seleção de valor para Lotomania
        mostrarSelecaoValorLotomania();
        return;
    }
    
    // Para outros jogos, verificar apenas o mínimo
    if (estado.dezenasSelecionadas.length < jogoConfig.minDezenas) {
        alert(`Você precisa selecionar pelo menos ${jogoConfig.minDezenas} dezenas para ${jogoConfig.nome}`);
        return;
    }
    
    // Adicionar aposta ao estado sem valor específico para os outros jogos
    estado.apostas.push({
        dezenas: [...estado.dezenasSelecionadas],
        valor: null,
        premio: null
    });
    
    // Atualizar a interface
    atualizarListaApostas();
    
    // Limpar a seleção atual
    limparSelecao();
    
    // Habilitar botões de remover e enviar
    btnRemoverTodos.disabled = false;
    btnEnviarWhatsapp.disabled = false;
}

// Função para mostrar modal de seleção de valor para Timemania
function mostrarSelecaoValorTimemania() {
    const numDezenas = estado.dezenasSelecionadas.length;
    const opcoes = getTimemaniaOptions(numDezenas);
    
    // Criar elemento modal para seleção de valor
    const modalEl = document.createElement('div');
    modalEl.className = 'modal fade';
    modalEl.id = 'modalValorTimemania';
    modalEl.setAttribute('tabindex', '-1');
    
    // Conteúdo do modal
    modalEl.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selecione o Valor da Aposta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você selecionou ${numDezenas} dezenas. Escolha o valor da aposta:</p>
                    <div class="list-group">
                        ${opcoes.map((opcao, idx) => `
                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                                    data-valor="${opcao.aposta}" data-premio="${opcao.premio}">
                                <span>R$ ${opcao.aposta.toFixed(2).replace('.', ',')}</span>
                                <span class="badge bg-primary rounded-pill">Prêmio: R$ ${opcao.premio.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                            </button>
                        `).join('')}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Adicionar modal ao corpo do documento
    document.body.appendChild(modalEl);
    
    // Inicializar modal do Bootstrap
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    
    // Adicionar handlers de eventos aos botões
    const botoesOpcao = modalEl.querySelectorAll('.list-group-item');
    botoesOpcao.forEach(botao => {
        botao.addEventListener('click', () => {
            const valorAposta = parseFloat(botao.getAttribute('data-valor'));
            const valorPremio = parseFloat(botao.getAttribute('data-premio'));
            
            // Adicionar aposta com valor e prêmio
            estado.apostas.push({
                dezenas: [...estado.dezenasSelecionadas],
                valor: valorAposta,
                premio: valorPremio
            });
            
            // Fechar o modal
            modal.hide();
            
            // Remover o modal do DOM após fechado
            modalEl.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modalEl);
            });
            
            // Atualizar a interface
            atualizarListaApostas();
            
            // Limpar a seleção atual
            limparSelecao();
            
            // Habilitar botões de remover e enviar
            btnRemoverTodos.disabled = false;
            btnEnviarWhatsapp.disabled = false;
        });
    });
    
    // Remover o modal do DOM quando fechado pelo X ou backdrop
    modalEl.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modalEl);
    });
}

// Função para mostrar modal de seleção de valor para Quina
function mostrarSelecaoValorQuina() {
    const numDezenas = estado.dezenasSelecionadas.length;
    const opcoes = getQuinaOptions(numDezenas);
    
    // Criar elemento modal para seleção de valor
    const modalEl = document.createElement('div');
    modalEl.className = 'modal fade';
    modalEl.id = 'modalValorQuina';
    modalEl.setAttribute('tabindex', '-1');
    
    // Conteúdo do modal
    modalEl.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selecione o Valor da Aposta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você selecionou ${numDezenas} dezenas. Escolha o valor da aposta:</p>
                    <div class="list-group">
                        ${opcoes.map((opcao, idx) => `
                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                                    data-valor="${opcao.aposta}" data-premio="${opcao.premio}">
                                <span>R$ ${opcao.aposta.toFixed(2).replace('.', ',')}</span>
                                <span class="badge bg-primary rounded-pill">Prêmio: R$ ${opcao.premio.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                            </button>
                        `).join('')}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Adicionar modal ao corpo do documento
    document.body.appendChild(modalEl);
    
    // Inicializar modal do Bootstrap
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    
    // Adicionar handlers de eventos aos botões
    const botoesOpcao = modalEl.querySelectorAll('.list-group-item');
    botoesOpcao.forEach(botao => {
        botao.addEventListener('click', () => {
            const valorAposta = parseFloat(botao.getAttribute('data-valor'));
            const valorPremio = parseFloat(botao.getAttribute('data-premio'));
            
            // Adicionar aposta com valor e prêmio
            estado.apostas.push({
                dezenas: [...estado.dezenasSelecionadas],
                valor: valorAposta,
                premio: valorPremio
            });
            
            // Fechar o modal
            modal.hide();
            
            // Remover o modal do DOM após fechado
            modalEl.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modalEl);
            });
            
            // Atualizar a interface
            atualizarListaApostas();
            
            // Limpar a seleção atual
            limparSelecao();
            
            // Habilitar botões de remover e enviar
            btnRemoverTodos.disabled = false;
            btnEnviarWhatsapp.disabled = false;
        });
    });
    
    // Remover o modal do DOM quando fechado pelo X ou backdrop
    modalEl.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modalEl);
    });
}

// Função para mostrar modal de seleção de valor para Mega-Sena
function mostrarSelecaoValorMegaSena() {
    const numDezenas = estado.dezenasSelecionadas.length;
    const opcoes = getMegaSenaOptions(numDezenas);
    
    // Criar elemento modal para seleção de valor
    const modalEl = document.createElement('div');
    modalEl.className = 'modal fade';
    modalEl.id = 'modalValorMegaSena';
    modalEl.setAttribute('tabindex', '-1');
    
    // Conteúdo do modal
    modalEl.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selecione o Valor da Aposta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você selecionou ${numDezenas} dezenas. Escolha o valor da aposta:</p>
                    <div class="list-group">
                        ${opcoes.map((opcao, idx) => `
                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                                    data-valor="${opcao.aposta}" data-premio="${opcao.premio}">
                                <span>R$ ${opcao.aposta.toFixed(2).replace('.', ',')}</span>
                                <span class="badge bg-primary rounded-pill">Prêmio: R$ ${opcao.premio.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                            </button>
                        `).join('')}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Adicionar modal ao corpo do documento
    document.body.appendChild(modalEl);
    
    // Inicializar modal do Bootstrap
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    
    // Adicionar handlers de eventos aos botões
    const botoesOpcao = modalEl.querySelectorAll('.list-group-item');
    botoesOpcao.forEach(botao => {
        botao.addEventListener('click', () => {
            const valorAposta = parseFloat(botao.getAttribute('data-valor'));
            const valorPremio = parseFloat(botao.getAttribute('data-premio'));
            
            // Adicionar aposta com valor e prêmio
            estado.apostas.push({
                dezenas: [...estado.dezenasSelecionadas],
                valor: valorAposta,
                premio: valorPremio
            });
            
            // Fechar o modal
            modal.hide();
            
            // Remover o modal do DOM após fechado
            modalEl.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modalEl);
            });
            
            // Atualizar a interface
            atualizarListaApostas();
            
            // Limpar a seleção atual
            limparSelecao();
            
            // Habilitar botões de remover e enviar
            btnRemoverTodos.disabled = false;
            btnEnviarWhatsapp.disabled = false;
        });
    });
    
    // Remover o modal do DOM quando fechado pelo X ou backdrop
    modalEl.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modalEl);
    });
}

// Função para mostrar modal de seleção de valor para Lotomania
function mostrarSelecaoValorLotomania() {
    const numDezenas = estado.dezenasSelecionadas.length;
    const opcoes = getLotomaniaOptions(numDezenas);
    
    // Criar elemento modal para seleção de valor
    const modalEl = document.createElement('div');
    modalEl.className = 'modal fade';
    modalEl.id = 'modalValorLotomania';
    modalEl.setAttribute('tabindex', '-1');
    
    // Conteúdo do modal
    modalEl.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selecione o Valor da Aposta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você selecionou ${numDezenas} dezenas. Escolha o valor da aposta:</p>
                    <div class="list-group">
                        ${opcoes.map((opcao, idx) => `
                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                                    data-valor="${opcao.aposta}" data-premio="${opcao.premio}">
                                <span>R$ ${opcao.aposta.toFixed(2).replace('.', ',')}</span>
                                <span class="badge bg-primary rounded-pill">Prêmio: R$ ${opcao.premio.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                            </button>
                        `).join('')}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Adicionar modal ao corpo do documento
    document.body.appendChild(modalEl);
    
    // Inicializar modal do Bootstrap
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    
    // Adicionar handlers de eventos aos botões
    const botoesOpcao = modalEl.querySelectorAll('.list-group-item');
    botoesOpcao.forEach(botao => {
        botao.addEventListener('click', () => {
            const valorAposta = parseFloat(botao.getAttribute('data-valor'));
            const valorPremio = parseFloat(botao.getAttribute('data-premio'));
            
            // Adicionar aposta com valor e prêmio
            estado.apostas.push({
                dezenas: [...estado.dezenasSelecionadas],
                valor: valorAposta,
                premio: valorPremio
            });
            
            // Fechar o modal
            modal.hide();
            
            // Remover o modal do DOM após fechado
            modalEl.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modalEl);
            });
            
            // Atualizar a interface
            atualizarListaApostas();
            
            // Limpar a seleção atual
            limparSelecao();
            
            // Habilitar botões de remover e enviar
            btnRemoverTodos.disabled = false;
            btnEnviarWhatsapp.disabled = false;
        });
    });
    
    // Remover o modal do DOM quando fechado pelo X ou backdrop
    modalEl.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modalEl);
    });
}

// Função para atualizar a lista de apostas na interface
function atualizarListaApostas() {
    if (estado.apostas.length === 0) {
        apostasContainer.innerHTML = '<div class="alert alert-info">Nenhuma aposta adicionada ainda</div>';
        return;
    }
    
    apostasContainer.innerHTML = '';
    
    estado.apostas.forEach((aposta, index) => {
        const apostaItem = document.createElement('div');
        apostaItem.className = 'aposta-item';
        
        const apostaNumeros = document.createElement('div');
        apostaNumeros.className = 'aposta-numeros';
        
        aposta.dezenas.forEach(numero => {
            const numeroSpan = document.createElement('div');
            numeroSpan.className = 'aposta-numero';
            numeroSpan.textContent = numero.toString().padStart(2, '0');
            apostaNumeros.appendChild(numeroSpan);
        });
        
        const btnRemover = document.createElement('button');
        btnRemover.className = 'btn-remover-aposta';
        btnRemover.innerHTML = '<i class="fas fa-times"></i>';
        btnRemover.addEventListener('click', () => removerAposta(index));
        
        // Adicionar informações de valor e prêmio se disponíveis
        let infoHTML = '';
        if (aposta.valor && aposta.premio) {
            infoHTML = `
                <div class="aposta-info mt-2">
                    <span class="badge bg-success me-2">Valor: R$ ${aposta.valor.toFixed(2).replace('.', ',')}</span>
                    <span class="badge bg-primary">Prêmio: R$ ${aposta.premio.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                </div>
            `;
        }
        
        apostaItem.appendChild(apostaNumeros);
        
        if (infoHTML) {
            const infoDiv = document.createElement('div');
            infoDiv.innerHTML = infoHTML;
            apostaItem.appendChild(infoDiv);
        }
        
        apostaItem.appendChild(btnRemover);
        
        apostasContainer.appendChild(apostaItem);
    });
}

// Função para remover uma aposta específica
function removerAposta(index) {
    estado.apostas.splice(index, 1);
    atualizarListaApostas();
    
    btnRemoverTodos.disabled = estado.apostas.length === 0;
    btnEnviarWhatsapp.disabled = estado.apostas.length === 0;
}

// Função para remover todas as apostas
function removerTodasApostas() {
    if (confirm('Tem certeza que deseja remover todas as apostas?')) {
        estado.apostas = [];
        atualizarListaApostas();
        
        btnRemoverTodos.disabled = true;
        btnEnviarWhatsapp.disabled = true;
    }
}

// Função para enviar apostas para o WhatsApp
function enviarParaWhatsapp() {
    if (estado.apostas.length === 0) return;
    
    // Mostrar o modal de confirmação se estiver disponível
    if (confirmacaoModal) {
        confirmacaoModal.show();
    } else {
        // Se o modal não estiver disponível, chamar diretamente a função de envio
        confirmarEnvio();
    }
}

// Função para confirmar o envio após o modal
function confirmarEnvio() {
    const jogoConfig = jogosConfig[estado.codigoJogo];
    
    if (!jogoConfig) return;
    
    // Formatar o texto para o WhatsApp
    let texto = `Loterias Mobile: ${estado.codigoJogo}\n\n`;
    
    estado.apostas.forEach(aposta => {
        const numerosFormatados = aposta.dezenas.map(n => n.toString().padStart(2, '0')).join(' ');
        texto += `${numerosFormatados}`;
        
        // Adicionar informações de valor e prêmio para o jogos especiais
        if ((estado.codigoJogo === 'TM' || estado.codigoJogo === 'QN' || estado.codigoJogo === 'MS' || estado.codigoJogo === 'LM') && aposta.valor && aposta.premio) {
            texto += ` | R$ ${aposta.valor.toFixed(2).replace('.', ',')} | Prêmio: R$ ${aposta.premio.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }
        
        texto += '\n';
    });
    
    // Adicionar nome do apostador se disponível
    if (window.apostadorNome) {
        texto += `\nApostador: ${window.apostadorNome}`;
    }
    
    // Obter o número do WhatsApp do revendedor
    let whatsappNumero = window.revendedorWhatsApp || prompt('Digite o número do WhatsApp para enviar as apostas:');
    
    if (!whatsappNumero) return;
    
    // Remover formatação do número
    whatsappNumero = whatsappNumero.replace(/\D/g, '');
    
    // Verificar se o número começa com 55 (código do Brasil)
    if (!whatsappNumero.startsWith('55')) {
        whatsappNumero = '55' + whatsappNumero;
    }
    
    // Criar o link do WhatsApp
    const whatsappLink = `https://api.whatsapp.com/send?phone=${whatsappNumero}&text=${encodeURIComponent(texto)}`;
    
    // Abrir o link em uma nova aba
    window.open(whatsappLink, '_blank');
    
    // Fechar o modal se estiver aberto
    if (confirmacaoModal) {
        confirmacaoModal.hide();
    }
}

// Event Listeners
jogoSelect.addEventListener('change', function() {
    const jogoId = this.value;
    if (!jogoId) {
        estado.jogoSelecionado = null;
        estado.codigoJogo = '';
        numerosContainer.innerHTML = '<div class="alert alert-info">Selecione um jogo para ver os números disponíveis</div>';
        minDezenasInput.value = '';
        maxDezenasInput.value = '';
        limparSelecao();
        return;
    }
    
    // Obter o código do jogo selecionado
    const selectedOption = this.options[this.selectedIndex];
    const codigoJogo = selectedOption.dataset.codigo;
    
    if (!codigoJogo || !jogosConfig[codigoJogo]) {
        alert('Código de jogo inválido. Entre em contato com o administrador.');
        return;
    }
    
    estado.jogoSelecionado = jogoId;
    estado.codigoJogo = codigoJogo;
    
    const jogoConfig = jogosConfig[codigoJogo];
    
    // Atualizar informações na interface
    minDezenasInput.value = jogoConfig.minDezenas;
    maxDezenasInput.value = jogoConfig.maxDezenas;
    
    // Reiniciar variáveis globais de alerta para o novo jogo
    if (codigoJogo !== 'TM') {
        window.timemaniaAlertShown = false;
    }
    
    if (codigoJogo !== 'QN') {
        window.quinaAlertShown = false;
    }
    
    if (codigoJogo !== 'MS') {
        window.megaSenaAlertShown = false;
    }
    
    if (codigoJogo !== 'LM') {
        window.lotomaniaAlertShown = false;
    }
    
    // Mostrar informações específicas para jogos com dezenas válidas
    if (codigoJogo === 'TM') {
        // Para Timemania, mostrar as dezenas válidas
        const dezenasStrings = jogoConfig.dezenasValidas.map(d => `<span class="badge bg-primary">${d}</span>`).join(' ');
        
        // Mostrar mensagem informativa sobre as dezenas válidas de forma menos intrusiva
        numerosContainer.innerHTML = `
            <div class="alert alert-primary mb-3">
                <strong>Atenção:</strong> Para o Timemania, só é possível marcar exatamente: 
                <div class="mt-2 mb-2">
                    ${dezenasStrings}
                </div>
                dezenas. Continue marcando até atingir uma destas quantidades.
            </div>
        `;
        
        // Mostrar um alerta apenas uma vez ao selecionar o jogo
        if (!window.timemaniaAlertShown) {
            setTimeout(() => {
                alert(`Para o Timemania, você só pode marcar ${jogoConfig.dezenasValidas.join(', ')} dezenas.`);
                window.timemaniaAlertShown = true;
            }, 300);
        }
    } 
    else if (codigoJogo === 'QN') {
        // Para Quina, mostrar as dezenas válidas
        const dezenasStrings = jogoConfig.dezenasValidas.map(d => `<span class="badge bg-primary">${d}</span>`).join(' ');
        
        // Mostrar mensagem informativa sobre as dezenas válidas de forma menos intrusiva
        numerosContainer.innerHTML = `
            <div class="alert alert-primary mb-3">
                <strong>Atenção:</strong> Para a Quina, você pode marcar:
                <div class="mt-2 mb-2">
                    ${dezenasStrings}
                </div>
                dezenas para realizar sua aposta.
            </div>
        `;
        
        // Mostrar um alerta apenas uma vez ao selecionar o jogo
        if (!window.quinaAlertShown) {
            setTimeout(() => {
                alert(`Para a Quina, você pode marcar ${jogoConfig.dezenasValidas.join(', ')} dezenas.`);
                window.quinaAlertShown = true;
            }, 300);
        }
    }
    else if (codigoJogo === 'MS') {
        // Para Mega-Sena, mostrar as dezenas válidas
        const dezenasOficiais = jogoConfig.dezenasValidas.filter(d => d <= 15).map(d => `<span class="badge bg-success">${d}</span>`).join(' ');
        const dezenasEspeciais = jogoConfig.dezenasValidas.filter(d => d > 15).map(d => `<span class="badge bg-primary">${d}</span>`).join(' ');
        
        // Mostrar mensagem informativa sobre as dezenas válidas de forma menos intrusiva
        numerosContainer.innerHTML = `
            <div class="alert alert-primary mb-3">
                <strong>Atenção:</strong> Para a Mega-Sena, você pode marcar:
                <div class="mt-2">
                    <div><strong>Apostas Oficiais:</strong> ${dezenasOficiais}</div>
                    <div class="mt-2"><strong>Apostas Especiais:</strong> ${dezenasEspeciais}</div>
                </div>
                <div class="mt-2">
                    <small>* Apostas oficiais têm valores fixos definidos pela loteria.</small>
                </div>
            </div>
        `;
        
        // Mostrar um alerta apenas uma vez ao selecionar o jogo
        if (!window.megaSenaAlertShown) {
            setTimeout(() => {
                alert(`Para a Mega-Sena, você pode marcar ${jogoConfig.dezenasValidas.join(', ')} dezenas.`);
                window.megaSenaAlertShown = true;
            }, 300);
        }
    }
    else if (codigoJogo === 'LM') {
        // Para Lotomania, mostrar as dezenas válidas
        const dezenasStrings = jogoConfig.dezenasValidas.map(d => `<span class="badge bg-primary">${d}</span>`).join(' ');
        
        // Mostrar mensagem informativa sobre as dezenas válidas de forma menos intrusiva
        numerosContainer.innerHTML = `
            <div class="alert alert-primary mb-3">
                <strong>Atenção:</strong> Para a Lotomania, você pode marcar:
                <div class="mt-2 mb-2">
                    ${dezenasStrings}
                </div>
                dezenas para realizar sua aposta.
            </div>
        `;
        
        // Mostrar um alerta apenas uma vez ao selecionar o jogo
        if (!window.lotomaniaAlertShown) {
            setTimeout(() => {
                alert(`Para a Lotomania, você pode marcar ${jogoConfig.dezenasValidas.join(', ')} dezenas. Marque até 85 dezenas, acerte as 20 sorteadas e fature a premiação!`);
                window.lotomaniaAlertShown = true;
            }, 300);
        }
    }
    else {
        // Para outros jogos, mostrar normalmente
        numerosContainer.innerHTML = '';
    }
    
    // Gerar os números para seleção
    gerarNumeros(jogoConfig.totalNumeros);
    
    // Limpar seleção anterior
    limparSelecao();
});

btnLimpar.addEventListener('click', limparSelecao);
btnAdicionar.addEventListener('click', adicionarAposta);
btnRemoverTodos.addEventListener('click', removerTodasApostas);
btnEnviarWhatsapp.addEventListener('click', enviarParaWhatsapp);
btnConfirmarEnvio.addEventListener('click', confirmarEnvio); 