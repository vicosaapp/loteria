// Log para confirmar carregamento do script
console.log('Script importar-apostas.js carregado!');

// Definição dos preços por jogo e número de dezenas
const precosLotofacil = {
    17: [
        { valor: 1.00, premio: "7.000,00" },
        { valor: 1.50, premio: "10.500,00" },
        { valor: 2.00, premio: "14.000,00" },
        { valor: 2.50, premio: "17.500,00" },
        { valor: 3.00, premio: "21.000,00" },
        { valor: 3.50, premio: "24.500,00" },
        { valor: 4.00, premio: "28.000,00" },
        { valor: 4.30, premio: "30.000,00" }
    ],
    18: [
        { valor: 1.00, premio: "1.500,00" },
        { valor: 1.50, premio: "2.250,00" },
        { valor: 2.00, premio: "3.000,00" },
        { valor: 3.00, premio: "4.500,00" },
        { valor: 5.00, premio: "7.500,00" },
        { valor: 7.00, premio: "10.500,00" },
        { valor: 10.00, premio: "15.000,00" },
        { valor: 15.00, premio: "22.500,00" },
        { valor: 20.00, premio: "30.000,00" }
    ],
    19: [
        { valor: 1.00, premio: "600,00" },
        { valor: 1.50, premio: "900,00" },
        { valor: 2.00, premio: "1.200,00" },
        { valor: 3.00, premio: "1.800,00" },
        { valor: 5.00, premio: "3.000,00" },
        { valor: 7.00, premio: "4.200,00" },
        { valor: 10.00, premio: "6.000,00" },
        { valor: 15.00, premio: "9.000,00" },
        { valor: 20.00, premio: "12.000,00" },
        { valor: 25.00, premio: "15.000,00" },
        { valor: 50.00, premio: "30.000,00" }
    ],
    20: [
        { valor: 1.00, premio: "140,00" },
        { valor: 1.50, premio: "210,00" },
        { valor: 2.00, premio: "280,00" },
        { valor: 3.00, premio: "420,00" },
        { valor: 5.00, premio: "700,00" },
        { valor: 7.00, premio: "980,00" },
        { valor: 10.00, premio: "1.400,00" },
        { valor: 25.00, premio: "3.500,00" },
        { valor: 50.00, premio: "7.000,00" },
        { valor: 100.00, premio: "14.000,00" }
    ],
    21: [
        { valor: 1.00, premio: "50,00" },
        { valor: 1.50, premio: "75,00" },
        { valor: 2.00, premio: "100,00" },
        { valor: 3.00, premio: "150,00" },
        { valor: 5.00, premio: "250,00" },
        { valor: 7.00, premio: "350,00" },
        { valor: 10.00, premio: "500,00" },
        { valor: 15.00, premio: "750,00" },
        { valor: 20.00, premio: "1.000,00" },
        { valor: 25.00, premio: "1.250,00" },
        { valor: 50.00, premio: "2.500,00" },
        { valor: 100.00, premio: "5.000,00" }
    ],
    22: [
        { valor: 1.00, premio: "13,00" },
        { valor: 1.50, premio: "19,50" },
        { valor: 2.00, premio: "26,00" },
        { valor: 3.00, premio: "39,00" },
        { valor: 5.00, premio: "65,00" },
        { valor: 7.00, premio: "91,00" },
        { valor: 10.00, premio: "130,00" },
        { valor: 15.00, premio: "195,00" },
        { valor: 20.00, premio: "260,00" },
        { valor: 25.00, premio: "325,00" },
        { valor: 50.00, premio: "650,00" },
        { valor: 100.00, premio: "1.300,00" }
    ],
    23: [
        { valor: 5.00, premio: "25,00" },
        { valor: 10.00, premio: "50,00" },
        { valor: 25.00, premio: "125,00" },
        { valor: 50.00, premio: "250,00" },
        { valor: 100.00, premio: "500,00" }
    ]
};

const precosDiaDeSorte = {
    15: [
        { valor: 1.00, premio: "265,00" },
        { valor: 1.50, premio: "397,50" },
        { valor: 2.00, premio: "530,00" }
        // Outros valores...
    ],
    // Outros tamanhos...
};

const precosMaisMilionaria = {
    10: [
        { valor: 1.00, premio: "2.000,00" },
        { valor: 1.50, premio: "3.000,00" },
        { valor: 2.00, premio: "4.000,00" },
        { valor: 3.00, premio: "6.000,00" },
        { valor: 4.00, premio: "8.000,00" },
        { valor: 5.00, premio: "10.000,00" },
        { valor: 10.00, premio: "20.000,00" },
        { valor: 15.00, premio: "30.000,00" }
    ],
    15: [
        { valor: 1.00, premio: "350,00" },
        { valor: 1.50, premio: "525,00" },
        { valor: 2.00, premio: "700,00" },
        { valor: 3.00, premio: "1.050,00" },
        { valor: 4.00, premio: "1.400,00" },
        { valor: 5.00, premio: "1.750,00" },
        { valor: 10.00, premio: "3.500,00" },
        { valor: 15.00, premio: "5.250,00" },
        { valor: 20.00, premio: "7.000,00" },
        { valor: 25.00, premio: "8.750,00" },
        { valor: 50.00, premio: "17.500,00" },
        { valor: 86.00, premio: "30.000,00" }
    ],
    20: [
        { valor: 1.00, premio: "135,00" },
        { valor: 1.50, premio: "202,50" },
        { valor: 2.00, premio: "270,00" },
        { valor: 3.00, premio: "405,00" },
        { valor: 4.00, premio: "540,00" },
        { valor: 5.00, premio: "675,00" },
        { valor: 10.00, premio: "1.350,00" },
        { valor: 15.00, premio: "2.025,00" },
        { valor: 20.00, premio: "2.700,00" },
        { valor: 25.00, premio: "3.375,00" },
        { valor: 50.00, premio: "6.750,00" },
        { valor: 100.00, premio: "13.500,00" }
    ],
    25: [
        { valor: 1.00, premio: "45,00" },
        { valor: 1.50, premio: "67,50" },
        { valor: 2.00, premio: "90,00" },
        { valor: 3.00, premio: "135,00" },
        { valor: 4.00, premio: "180,00" },
        { valor: 5.00, premio: "225,00" },
        { valor: 10.00, premio: "450,00" },
        { valor: 15.00, premio: "615,00" },
        { valor: 20.00, premio: "900,00" },
        { valor: 25.00, premio: "1.125,00" },
        { valor: 50.00, premio: "2.250,00" },
        { valor: 100.00, premio: "4.500,00" }
    ],
    30: [
        { valor: 1.00, premio: "15,00" },
        { valor: 1.50, premio: "22,50" },
        { valor: 2.00, premio: "30,00" },
        { valor: 3.00, premio: "45,00" },
        { valor: 4.00, premio: "60,00" },
        { valor: 5.00, premio: "75,00" },
        { valor: 10.00, premio: "150,00" },
        { valor: 15.00, premio: "225,00" },
        { valor: 20.00, premio: "300,00" },
        { valor: 25.00, premio: "375,00" },
        { valor: 50.00, premio: "750,00" },
        { valor: 100.00, premio: "1.500,00" }
    ],
    35: [
        { valor: 1.00, premio: "6,00" },
        { valor: 1.50, premio: "9,00" },
        { valor: 2.00, premio: "12,00" },
        { valor: 3.00, premio: "18,00" },
        { valor: 4.00, premio: "24,00" },
        { valor: 5.00, premio: "30,00" },
        { valor: 10.00, premio: "60,00" },
        { valor: 15.00, premio: "90,00" },
        { valor: 20.00, premio: "120,00" },
        { valor: 25.00, premio: "150,00" },
        { valor: 50.00, premio: "300,00" },
        { valor: 100.00, premio: "600,00" }
    ]
};

const precosMegaSena = {
    20: [
        { valor: 1.00, premio: "800,00" },
        { valor: 1.50, premio: "1.200,00" },
        { valor: 2.00, premio: "1.600,00" },
        { valor: 3.00, premio: "2.400,00" },
        { valor: 5.00, premio: "4.000,00" },
        { valor: 7.00, premio: "5.600,00" },
        { valor: 10.00, premio: "8.000,00" },
        { valor: 15.00, premio: "12.000,00" },
        { valor: 20.00, premio: "16.000,00" },
        { valor: 25.00, premio: "20.000,00" },
        { valor: 37.50, premio: "30.000,00" }
    ],
    25: [
        { valor: 1.00, premio: "167,00" },
        { valor: 1.50, premio: "250,50" },
        { valor: 2.00, premio: "334,00" },
        { valor: 3.00, premio: "501,00" },
        { valor: 5.00, premio: "835,00" },
        { valor: 7.00, premio: "1.169,00" },
        { valor: 10.00, premio: "1.670,00" },
        { valor: 15.00, premio: "2.505,00" },
        { valor: 20.00, premio: "3.340,00" },
        { valor: 25.00, premio: "4.175,00" },
        { valor: 50.00, premio: "8.350,00" },
        { valor: 100.00, premio: "16.700,00" }
    ],
    30: [
        { valor: 1.00, premio: "56,00" },
        { valor: 1.50, premio: "84,00" },
        { valor: 2.00, premio: "112,00" },
        { valor: 3.00, premio: "168,00" },
        { valor: 5.00, premio: "280,00" },
        { valor: 7.00, premio: "392,00" },
        { valor: 10.00, premio: "560,00" },
        { valor: 15.00, premio: "840,00" },
        { valor: 20.00, premio: "1.120,00" },
        { valor: 25.00, premio: "1.400,00" },
        { valor: 50.00, premio: "2.800,00" },
        { valor: 100.00, premio: "5.600,00" }
    ],
    35: [
        { valor: 1.00, premio: "22,00" },
        { valor: 1.50, premio: "33,00" },
        { valor: 2.00, premio: "44,00" },
        { valor: 3.00, premio: "66,00" },
        { valor: 5.00, premio: "110,00" },
        { valor: 7.00, premio: "154,00" },
        { valor: 10.00, premio: "220,00" },
        { valor: 15.00, premio: "330,00" },
        { valor: 20.00, premio: "440,00" },
        { valor: 25.00, premio: "550,00" },
        { valor: 50.00, premio: "1.100,00" },
        { valor: 100.00, premio: "2.200,00" }
    ],
    40: [
        { valor: 5.00, premio: "45,00" },
        { valor: 5.50, premio: "49,50" },
        { valor: 10.00, premio: "90,00" },
        { valor: 15.00, premio: "135,00" },
        { valor: 20.00, premio: "180,00" },
        { valor: 25.00, premio: "225,00" },
        { valor: 50.00, premio: "450,00" },
        { valor: 100.00, premio: "900,00" }
    ],
    45: [
        { valor: 5.00, premio: "15,00" },
        { valor: 5.50, premio: "16,50" },
        { valor: 10.00, premio: "30,00" },
        { valor: 15.00, premio: "45,00" },
        { valor: 20.00, premio: "60,00" },
        { valor: 25.00, premio: "75,00" },
        { valor: 50.00, premio: "150,00" },
        { valor: 100.00, premio: "300,00" }
    ]
};

const precosQuina = {
    20: [
        { valor: 1.00, premio: "800,00" },
        { valor: 1.50, premio: "1.200,00" },
        { valor: 2.00, premio: "1.600,00" },
        { valor: 3.00, premio: "2.400,00" },
        { valor: 5.00, premio: "4.000,00" },
        { valor: 7.00, premio: "5.600,00" },
        { valor: 10.00, premio: "8.000,00" },
        { valor: 15.00, premio: "12.000,00" },
        { valor: 20.00, premio: "16.000,00" },
        { valor: 25.00, premio: "20.000,00" },
        { valor: 37.50, premio: "30.000,00" }
    ],
    25: [
        { valor: 1.00, premio: "167,00" },
        { valor: 1.50, premio: "250,50" },
        { valor: 2.00, premio: "334,00" },
        { valor: 3.00, premio: "501,00" },
        { valor: 5.00, premio: "835,00" },
        { valor: 7.00, premio: "1.169,00" },
        { valor: 10.00, premio: "1.670,00" },
        { valor: 15.00, premio: "2.505,00" },
        { valor: 20.00, premio: "3.340,00" },
        { valor: 25.00, premio: "4.175,00" },
        { valor: 50.00, premio: "8.350,00" },
        { valor: 100.00, premio: "16.700,00" }
    ],
    30: [
        { valor: 1.00, premio: "56,00" },
        { valor: 1.50, premio: "84,00" },
        { valor: 2.00, premio: "112,00" },
        { valor: 3.00, premio: "168,00" },
        { valor: 5.00, premio: "280,00" },
        { valor: 7.00, premio: "392,00" },
        { valor: 10.00, premio: "560,00" },
        { valor: 15.00, premio: "840,00" },
        { valor: 20.00, premio: "1.120,00" },
        { valor: 25.00, premio: "1.400,00" },
        { valor: 50.00, premio: "2.800,00" },
        { valor: 100.00, premio: "5.600,00" }
    ],
    35: [
        { valor: 1.00, premio: "22,00" },
        { valor: 1.50, premio: "33,00" },
        { valor: 2.00, premio: "44,00" },
        { valor: 3.00, premio: "66,00" },
        { valor: 5.00, premio: "110,00" },
        { valor: 7.00, premio: "154,00" },
        { valor: 10.00, premio: "220,00" },
        { valor: 15.00, premio: "330,00" },
        { valor: 20.00, premio: "440,00" },
        { valor: 25.00, premio: "550,00" },
        { valor: 50.00, premio: "1.100,00" },
        { valor: 100.00, premio: "2.200,00" }
    ],
    40: [
        { valor: 5.00, premio: "45,00" },
        { valor: 5.50, premio: "49,50" },
        { valor: 10.00, premio: "90,00" },
        { valor: 15.00, premio: "135,00" },
        { valor: 20.00, premio: "180,00" },
        { valor: 25.00, premio: "225,00" },
        { valor: 50.00, premio: "450,00" },
        { valor: 100.00, premio: "900,00" }
    ],
    45: [
        { valor: 5.00, premio: "15,00" },
        { valor: 5.50, premio: "16,50" },
        { valor: 10.00, premio: "30,00" },
        { valor: 15.00, premio: "45,00" },
        { valor: 20.00, premio: "60,00" },
        { valor: 25.00, premio: "75,00" },
        { valor: 50.00, premio: "150,00" },
        { valor: 100.00, premio: "300,00" }
    ]
};

const precosLotomania = {
    50: [
        { valor: 1.00, premio: "1.500,00" },
        { valor: 1.50, premio: "2.250,00" },
        { valor: 2.00, premio: "3.000,00" },
        { valor: 3.00, premio: "4.500,00" },
        { valor: 5.00, premio: "7.500,00" },
        { valor: 7.00, premio: "10.500,00" },
        { valor: 10.00, premio: "15.000,00" },
        { valor: 15.00, premio: "22.500,00" },
        { valor: 20.00, premio: "30.000,00" }
    ],
    51: [
        { valor: 1.00, premio: "1.000,00" },
        { valor: 1.50, premio: "1.500,00" },
        { valor: 2.00, premio: "2.000,00" },
        { valor: 3.00, premio: "3.000,00" },
        { valor: 5.00, premio: "5.000,00" },
        { valor: 10.00, premio: "10.000,00" },
        { valor: 15.00, premio: "15.000,00" },
        { valor: 20.00, premio: "20.000,00" },
        { valor: 30.00, premio: "30.000,00" }
    ],
    55: [
        { valor: 1.00, premio: "500,00" },
        { valor: 1.50, premio: "750,00" },
        { valor: 2.00, premio: "1.000,00" },
        { valor: 3.00, premio: "1.500,00" },
        { valor: 5.00, premio: "2.500,00" },
        { valor: 7.00, premio: "3.500,00" },
        { valor: 10.00, premio: "5.000,00" },
        { valor: 15.00, premio: "7.500,00" },
        { valor: 20.00, premio: "10.000,00" },
        { valor: 30.00, premio: "15.000,00" },
        { valor: 60.00, premio: "30.000,00" }
    ]
};

const precosTimemania = {
    20: [
        { valor: 1.00, premio: "2.000,00" },
        { valor: 1.50, premio: "3.000,00" },
        { valor: 2.00, premio: "4.000,00" },
        { valor: 3.00, premio: "6.000,00" },
        { valor: 5.00, premio: "10.000,00" },
        { valor: 10.00, premio: "20.000,00" },
        { valor: 15.00, premio: "30.000,00" }
    ],
    25: [
        { valor: 1.00, premio: "900,00" },
        { valor: 1.50, premio: "1.350,00" },
        { valor: 2.00, premio: "1.800,00" },
        { valor: 3.00, premio: "2.700,00" },
        { valor: 5.00, premio: "4.500,00" },
        { valor: 10.00, premio: "9.000,00" },
        { valor: 15.00, premio: "13.500,00" },
        { valor: 20.00, premio: "18.000,00" },
        { valor: 25.00, premio: "22.500,00" },
        { valor: 30.00, premio: "27.000,00" }
    ],
    30: [
        { valor: 1.00, premio: "320,00" },
        { valor: 1.50, premio: "480,00" },
        { valor: 2.00, premio: "640,00" },
        { valor: 3.00, premio: "960,00" },
        { valor: 5.00, premio: "1.600,00" },
        { valor: 10.00, premio: "3.200,00" },
        { valor: 15.00, premio: "4.800,00" },
        { valor: 20.00, premio: "6.400,00" },
        { valor: 25.00, premio: "8.000,00" },
        { valor: 50.00, premio: "16.000,00" },
        { valor: 94.00, premio: "30.000,00" }
    ],
    35: [
        { valor: 1.00, premio: "120,00" },
        { valor: 1.50, premio: "180,00" },
        { valor: 2.00, premio: "240,00" },
        { valor: 3.00, premio: "360,00" },
        { valor: 5.00, premio: "600,00" },
        { valor: 10.00, premio: "1.200,00" },
        { valor: 15.00, premio: "1.800,00" },
        { valor: 20.00, premio: "2.400,00" },
        { valor: 25.00, premio: "3.000,00" },
        { valor: 50.00, premio: "6.000,00" },
        { valor: 100.00, premio: "12.000,00" }
    ],
    40: [
        { valor: 1.00, premio: "65,00" },
        { valor: 1.50, premio: "97,50" },
        { valor: 2.00, premio: "130,00" },
        { valor: 3.00, premio: "195,00" },
        { valor: 5.00, premio: "325,00" },
        { valor: 10.00, premio: "650,00" },
        { valor: 15.00, premio: "975,00" },
        { valor: 20.00, premio: "1.300,00" },
        { valor: 25.00, premio: "1.625,00" },
        { valor: 50.00, premio: "3.250,00" },
        { valor: 100.00, premio: "6.500,00" }
    ],
    45: [
        { valor: 5.00, premio: "160,00" },
        { valor: 5.50, premio: "176,00" },
        { valor: 10.00, premio: "320,00" },
        { valor: 15.00, premio: "480,00" },
        { valor: 20.00, premio: "640,00" },
        { valor: 25.00, premio: "800,00" },
        { valor: 50.00, premio: "1.600,00" },
        { valor: 100.00, premio: "3.200,00" }
    ],
    50: [
        { valor: 5.00, premio: "80,00" },
        { valor: 5.50, premio: "88,00" },
        { valor: 10.00, premio: "160,00" },
        { valor: 15.00, premio: "240,00" },
        { valor: 20.00, premio: "320,00" },
        { valor: 25.00, premio: "400,00" },
        { valor: 50.00, premio: "800,00" },
        { valor: 100.00, premio: "1.600,00" }
    ],
    55: [
        { valor: 5.00, premio: "50,00" },
        { valor: 5.50, premio: "55,00" },
        { valor: 10.00, premio: "100,00" },
        { valor: 15.00, premio: "150,00" },
        { valor: 20.00, premio: "200,00" },
        { valor: 25.00, premio: "250,00" },
        { valor: 50.00, premio: "500,00" },
        { valor: 100.00, premio: "1.000,00" }
    ]
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
    // Se valor for uma string, tenta converter para número
    let valorNumerico = valor;
    if (typeof valor === 'string') {
        // Remove pontos e substitui vírgula por ponto para converter para número
        valorNumerico = parseFloat(valor.replace(/\./g, '').replace(',', '.'));
    }
    
    // Se não for um número válido, retorna 0,00
    if (isNaN(valorNumerico)) {
        return '0,00';
    }
    
    // Formata no padrão brasileiro
    return valorNumerico.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Função para contar dezenas em uma linha de aposta
function contarDezenas(linha) {
    console.log('*** Contagem de dezenas - início ***');
    console.log('Linha original:', linha);
    
    // Verifica se a linha está vazia
    if (!linha || !linha.trim()) {
        console.log('Linha vazia ou inválida');
        return 0;
    }
    
    // Remove espaços extras e normaliza a linha
    const linhaLimpa = linha.trim().replace(/\s+/g, ' ');
    console.log('Linha para contagem de dezenas (normalizada):', linhaLimpa);
    
    // Caso especial: se a linha contém 15 números, provavelmente é uma aposta de Lotofácil
    if (linhaLimpa.split(' ').length === 15) {
        console.log('Detectada possível aposta de Lotofácil com 15 números');
        // Verificar se está no formato padrão (01 02 03...)
        const numerosLotofacil = linhaLimpa.match(/\b\d{1,2}\b/g);
        if (numerosLotofacil && numerosLotofacil.length === 15) {
            console.log('Confirmada aposta de Lotofácil com 15 números');
            console.log('*** Contagem de dezenas - fim (resultado: 15) ***');
            return 15;
        }
    }
    
    // Extrai todos os números da linha (apenas números inteiros)
    const numeros = linhaLimpa.match(/\b\d+\b/g);
    
    if (numeros && numeros.length > 0) {
        console.log('Números extraídos:', numeros);
        
        // Filtrar apenas números válidos para jogos de loteria (normalmente entre 1 e 99)
        const numerosValidos = numeros.filter(num => {
            const n = parseInt(num, 10);
            return n >= 1 && n <= 99;
        });
        
        console.log('Números válidos para jogos:', numerosValidos);
        
        // Remove duplicatas
        const numerosUnicos = [...new Set(numerosValidos)];
        console.log('Números únicos:', numerosUnicos.length);
        console.log('*** Contagem de dezenas - fim (resultado:', numerosUnicos.length, ') ***');
        
        return numerosUnicos.length;
    } else {
        console.log('Nenhum número encontrado na linha');
        console.log('*** Contagem de dezenas - fim (resultado: 0) ***');
        return 0;
    }
}

// Função para processar o texto das apostas
function processarApostas() {
    const apostas = apostasTextarea.value.trim().split('\n');
    const numerosValidos = [];
    const numerosInvalidos = [];

    // Detectar quantidade de dezenas da primeira linha válida
    let dezenasDetectadas = null;
    for (let i = 0; i < apostas.length; i++) {
        const aposta = apostas[i].trim();
        if (!aposta) continue;
        const numeros = aposta.split(/\s+/).map(n => parseInt(n)).filter(n => !isNaN(n));
        dezenasDetectadas = numeros.length;
        break;
    }

    // Obter id do jogo selecionado
    const selectedOption = jogoSelect.options[jogoSelect.selectedIndex];
    const jogoId = selectedOption ? selectedOption.value : null;
    if (!jogoId || !dezenasDetectadas) {
        valorApostaSelect.innerHTML = '<option value="">Selecione o valor</option>';
        valorPremiacaoInput.value = '0,00';
        return;
    }

    // Buscar valores do backend e popular o select
    buscarValoresAposta(jogoId, dezenasDetectadas).then(data => {
        valorApostaSelect.innerHTML = '<option value="">Selecione o valor</option>';
        valorApostaDropdown.innerHTML = '';
        if (data.success && data.valores.length > 0) {
            data.valores.forEach(v => {
                const option = document.createElement('option');
                option.value = v.valor_aposta;
                option.textContent = `R$ ${parseFloat(v.valor_aposta).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
                option.setAttribute('data-premio', v.valor_premio);
                valorApostaSelect.appendChild(option);

                const dropdownItem = document.createElement('a');
                dropdownItem.className = 'dropdown-item';
                dropdownItem.href = '#';
                dropdownItem.textContent = `R$ ${parseFloat(v.valor_aposta).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
                dropdownItem.addEventListener('click', function(e) {
                    e.preventDefault();
                    valorApostaSelect.value = v.valor_aposta;
                    valorApostaDisplay.value = `R$ ${parseFloat(v.valor_aposta).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
                    valorPremiacaoInput.value = v.valor_premio;
                });
                valorApostaDropdown.appendChild(dropdownItem);
            });
            // Selecionar o primeiro valor automaticamente
            if (valorApostaSelect.options.length > 1) {
                valorApostaSelect.selectedIndex = 1;
                valorPremiacaoInput.value = valorApostaSelect.options[1].getAttribute('data-premio');
            }
        } else {
            valorApostaSelect.innerHTML = '<option value="">Nenhum valor disponível</option>';
            valorPremiacaoInput.value = '0,00';
        }
    });

    // Validação das apostas (mantém igual)
    for (let i = 0; i < apostas.length; i++) {
        const aposta = apostas[i].trim();
        if (!aposta) continue;
        const numeros = aposta.split(/\s+/).map(n => parseInt(n)).filter(n => !isNaN(n));
        if (numeros.length !== dezenasDetectadas) {
            numerosInvalidos.push({
                linha: i + 1,
                numeros: numeros,
                motivo: `Quantidade de números inválida (${numeros.length}). Deve ter ${dezenasDetectadas} números.`
            });
            continue;
        }
        const numerosUnicos = [...new Set(numeros)];
        if (numerosUnicos.length !== numeros.length) {
            numerosInvalidos.push({
                linha: i + 1,
                numeros: numeros,
                motivo: 'Números duplicados encontrados.'
            });
            continue;
        }
        numerosValidos.push({
            linha: i + 1,
            numeros: numeros
        });
    }
    atualizarResumo(numerosValidos, numerosInvalidos);
}

// Atualizar premiação ao trocar valor
valorApostaSelect.addEventListener('change', function() {
    const option = valorApostaSelect.options[valorApostaSelect.selectedIndex];
    valorPremiacaoInput.value = option && option.getAttribute('data-premio') ? option.getAttribute('data-premio') : '0,00';
});

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

        // Obtém o valor do prêmio do atributo data-premio da opção selecionada
        const opcaoSelecionada = valorApostaSelect.options[valorApostaSelect.selectedIndex];
        if (opcaoSelecionada && opcaoSelecionada.dataset.premio) {
            console.log('Definindo valor da premiação diretamente do data-premio:', opcaoSelecionada.dataset.premio);
            premiacaoInput.value = opcaoSelecionada.dataset.premio;
            
            // Atualizar também o elemento de exibição
            const valorPremiacaoDisplay = document.getElementById('valor_premiacao_display');
            if (valorPremiacaoDisplay) {
                valorPremiacaoDisplay.textContent = opcaoSelecionada.dataset.premio;
            }
            
            return;
        }

        // Se já existe um valor no campo de premiação, não fazemos nada - confiamos no valor definido
        if (premiacaoInput.value && premiacaoInput.value !== '0,00') {
            console.log('Valor de premiação já está definido, mantendo:', premiacaoInput.value);
            return;
        }

        // Somente se não conseguiu obter o valor via dataset e não tem valor definido, tenta buscar do servidor
        console.log('Buscando valor de premiação do servidor...');
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
            console.log('Valor de premiação recebido do servidor:', data.jogo.valor_premio);
            
            // Formatar o prêmio no formato brasileiro
            let valorPremioFormatado = data.jogo.valor_premio;
            
            // Se está no formato americano (123.45), converte para brasileiro (123,45)
            if (valorPremioFormatado.indexOf('.') !== -1 && valorPremioFormatado.indexOf(',') === -1) {
                const valor = parseFloat(valorPremioFormatado);
                valorPremioFormatado = valor.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            }
            
            premiacaoInput.value = valorPremioFormatado;
            console.log('Valor de premiação definido:', premiacaoInput.value);

            // Atualizar também o elemento de exibição
            const valorPremiacaoDisplay = document.getElementById('valor_premiacao_display');
            if (valorPremiacaoDisplay) {
                valorPremiacaoDisplay.textContent = valorPremioFormatado;
            }

            // Atualizar informações de debug
            if (debugDiv && data.jogo.debug) {
                const valorBaseAposta = data.jogo.debug.valor_base_aposta;
                const valorBasePremio = data.jogo.debug.valor_base_premio;
                
                debugDiv.innerHTML = `
                    <p>Valor base da aposta: R$ ${valorBaseAposta}</p>
                    <p>Valor base do prêmio: R$ ${valorBasePremio}</p>
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
    const modal = new bootstrap.Modal(document.getElementById('visualizarModal'));
    const resumoDiv = document.getElementById('resumoApostas');
    const apostasTextarea = document.getElementById('apostas');
    const apostadorSelect = document.getElementById('apostador');
    const valorApostaSelect = document.getElementById('valor_aposta');
    const jogoSelect = document.getElementById('jogo');
    
    if (!resumoDiv || !apostasTextarea || !apostadorSelect || !valorApostaSelect || !jogoSelect) {
        console.error("Elementos necessários não encontrados");
        return;
    }
    
    const apostas = apostasTextarea.value.trim();
    if (!apostas) {
        resumoDiv.innerHTML = '<div class="alert alert-warning">Nenhuma aposta informada.</div>';
        modal.show();
        return;
    }
    
    const apostadorOption = apostadorSelect.options[apostadorSelect.selectedIndex];
    const valorApostaOption = valorApostaSelect.options[valorApostaSelect.selectedIndex];
    const jogoOption = jogoSelect.options[jogoSelect.selectedIndex];
    
    if (!apostadorOption || apostadorOption.value === '') {
        resumoDiv.innerHTML = '<div class="alert alert-warning">Selecione um apostador.</div>';
        modal.show();
        return;
    }
    
    if (!jogoOption || jogoOption.value === '') {
        resumoDiv.innerHTML = '<div class="alert alert-warning">Selecione um jogo.</div>';
        modal.show();
        return;
    }
    
    if (!valorApostaOption || valorApostaOption.value === '') {
        resumoDiv.innerHTML = '<div class="alert alert-warning">Selecione um valor para a aposta.</div>';
        modal.show();
        return;
    }
    
    const nomeApostador = apostadorOption.textContent;
    const nomeJogo = jogoOption.textContent;
    const valorFormatado = parseFloat(valorApostaOption.value).toFixed(2).replace('.', ',');
    const linhasApostas = apostas.split('\n').filter(linha => linha.trim());
    
    let html = `
        <div class="alert alert-info">
            <strong>Apostador:</strong> ${nomeApostador}<br>
            <strong>Jogo:</strong> ${nomeJogo}<br>
            <strong>Valor:</strong> R$ ${valorFormatado}
        </div>
        <div class="apostas-preview">
    `;
    
    linhasApostas.forEach((linha, index) => {
        const numeros = linha.match(/\d+/g) || [];
        
        html += `<div class="aposta-item">
            <div class="d-flex flex-wrap gap-1">`;
            
        numeros.forEach(numero => {
            html += `<span class="numero-bolinha">${numero}</span>`;
        });
        
        html += `</div></div>`;
    });
    
    html += '</div>';
    resumoDiv.innerHTML = html;
    modal.show();
}

// Função para atualizar as opções de valor baseado no nome do jogo e número de dezenas
function atualizarOpcoesValor(nomeJogo, numDezenas) {
    console.log("### INÍCIO DA FUNÇÃO atualizarOpcoesValor() ###");
    console.log("Parâmetros recebidos - nomeJogo:", nomeJogo, "numDezenas:", numDezenas);
    
    const valorApostaSelect = document.getElementById('valor_aposta');
    const valorApostaDisplay = document.getElementById('valor_aposta_display');
    const valorApostaDropdown = document.getElementById('valor_aposta_dropdown');
    const valorPremiacao = document.getElementById('valor_premiacao');
    const valorPremiacaoDisplay = document.getElementById('valor_premiacao_display');
    
    if (!valorApostaSelect || !valorPremiacao) {
        console.error('Elementos necessários não encontrados', {
            valorApostaSelect: !!valorApostaSelect,
            valorPremiacao: !!valorPremiacao,
            valorApostaDropdown: !!valorApostaDropdown,
            valorApostaDisplay: !!valorApostaDisplay
        });
        return;
    }
    
    // Limpar o select e o dropdown
    valorApostaSelect.innerHTML = '<option value="">Selecione o valor</option>';
    if (valorApostaDisplay) {
        valorApostaDisplay.value = '';
        valorApostaDisplay.placeholder = 'Processando...';
    }
    if (valorApostaDropdown) {
        valorApostaDropdown.innerHTML = '';
    }
    
    console.log('Atualizando valores para jogo:', nomeJogo, 'dezenas:', numDezenas);
    
    // Verificar variáveis globais definidas
    console.log("Verificando variáveis globais:");
    console.log("precosLotofacil definido?", typeof precosLotofacil !== 'undefined');
    console.log("precosDiaDeSorte definido?", typeof precosDiaDeSorte !== 'undefined');
    console.log("precosMaisMilionaria definido?", typeof precosMaisMilionaria !== 'undefined');
    console.log("precosMegaSena definido?", typeof precosMegaSena !== 'undefined');
    console.log("precosQuina definido?", typeof precosQuina !== 'undefined');
    console.log("precosLotomania definido?", typeof precosLotomania !== 'undefined');
    console.log("precosTimemania definido?", typeof precosTimemania !== 'undefined');
    
    // Verificar se todas as tabelas estão definidas
    const todasDefinidasCorretamente = 
        typeof precosLotofacil !== 'undefined' && 
        typeof precosDiaDeSorte !== 'undefined' && 
        typeof precosMaisMilionaria !== 'undefined' && 
        typeof precosMegaSena !== 'undefined' && 
        typeof precosQuina !== 'undefined' && 
        typeof precosLotomania !== 'undefined' && 
        typeof precosTimemania !== 'undefined';
    
    if (!todasDefinidasCorretamente) {
        console.error("Algumas tabelas de preços não estão definidas. Criando tabelas padrão...");
        
        // Criar tabelas padrão para todas as variáveis ausentes
        if (typeof precosLotofacil === 'undefined') {
            window.precosLotofacil = {
                15: [
                    { valor: 1.00, premio: "1.500,00" },
                    { valor: 2.00, premio: "3.000,00" },
                    { valor: 5.00, premio: "7.500,00" },
                    { valor: 10.00, premio: "15.000,00" },
                    { valor: 20.00, premio: "30.000,00" }
                ]
            };
            console.log("Criada tabela padrão para Lotofácil");
        }
        
        if (typeof precosDiaDeSorte === 'undefined') {
            window.precosDiaDeSorte = {
                7: [
                    { valor: 1.00, premio: "1.500,00" },
                    { valor: 2.00, premio: "3.000,00" },
                    { valor: 5.00, premio: "7.500,00" },
                    { valor: 10.00, premio: "15.000,00" },
                    { valor: 20.00, premio: "30.000,00" }
                ]
            };
            console.log("Criada tabela padrão para Dia de Sorte");
        }
        
        if (typeof precosMaisMilionaria === 'undefined') {
            window.precosMaisMilionaria = {
                6: [
                    { valor: 1.00, premio: "1.500,00" },
                    { valor: 2.00, premio: "3.000,00" },
                    { valor: 5.00, premio: "7.500,00" },
                    { valor: 10.00, premio: "15.000,00" },
                    { valor: 20.00, premio: "30.000,00" }
                ]
            };
            console.log("Criada tabela padrão para Mais Milionária");
        }
        
        if (typeof precosMegaSena === 'undefined') {
            window.precosMegaSena = {
                6: [
                    { valor: 1.00, premio: "1.500,00" },
                    { valor: 2.00, premio: "3.000,00" },
                    { valor: 5.00, premio: "7.500,00" },
                    { valor: 10.00, premio: "15.000,00" },
                    { valor: 20.00, premio: "30.000,00" }
                ]
            };
            console.log("Criada tabela padrão para Mega Sena");
        }
        
        if (typeof precosQuina === 'undefined') {
            window.precosQuina = {
                5: [
                    { valor: 1.00, premio: "1.500,00" },
                    { valor: 2.00, premio: "3.000,00" },
                    { valor: 5.00, premio: "7.500,00" },
                    { valor: 10.00, premio: "15.000,00" },
                    { valor: 20.00, premio: "30.000,00" }
                ]
            };
            console.log("Criada tabela padrão para Quina");
        }
        
        if (typeof precosLotomania === 'undefined') {
            window.precosLotomania = {
                50: [
                    { valor: 1.00, premio: "1.500,00" },
                    { valor: 1.50, premio: "2.250,00" },
                    { valor: 2.00, premio: "3.000,00" },
                    { valor: 3.00, premio: "4.500,00" },
                    { valor: 5.00, premio: "7.500,00" },
                    { valor: 7.00, premio: "10.500,00" },
                    { valor: 10.00, premio: "15.000,00" },
                    { valor: 15.00, premio: "22.500,00" },
                    { valor: 20.00, premio: "30.000,00" }
                ],
                51: [
                    { valor: 1.00, premio: "1.000,00" },
                    { valor: 1.50, premio: "1.500,00" },
                    { valor: 2.00, premio: "2.000,00" },
                    { valor: 3.00, premio: "3.000,00" },
                    { valor: 5.00, premio: "5.000,00" },
                    { valor: 10.00, premio: "10.000,00" },
                    { valor: 15.00, premio: "15.000,00" },
                    { valor: 20.00, premio: "20.000,00" },
                    { valor: 30.00, premio: "30.000,00" }
                ],
                55: [
                    { valor: 1.00, premio: "500,00" },
                    { valor: 1.50, premio: "750,00" },
                    { valor: 2.00, premio: "1.000,00" },
                    { valor: 3.00, premio: "1.500,00" },
                    { valor: 5.00, premio: "2.500,00" },
                    { valor: 7.00, premio: "3.500,00" },
                    { valor: 10.00, premio: "5.000,00" },
                    { valor: 15.00, premio: "7.500,00" },
                    { valor: 20.00, premio: "10.000,00" },
                    { valor: 30.00, premio: "15.000,00" },
                    { valor: 60.00, premio: "30.000,00" }
                ]
            };
            
            console.log("Tabela de preços da Lotomania redefinida manualmente");
        }
        
        if (typeof precosTimemania === 'undefined') {
            window.precosTimemania = {
                10: [
                    { valor: 1.00, premio: "1.500,00" },
                    { valor: 2.00, premio: "3.000,00" },
                    { valor: 5.00, premio: "7.500,00" },
                    { valor: 10.00, premio: "15.000,00" },
                    { valor: 20.00, premio: "30.000,00" }
                ]
            };
            console.log("Criada tabela padrão para Timemania");
        }
    }
    
    let precos = [];
    let dezenasAtual = numDezenas;
    
    // Verificações detalhadas para depuração
    if (!nomeJogo) {
        console.error('Nome do jogo não definido!');
        valorApostaDisplay.placeholder = 'Selecione um jogo válido';
        return;
    }
    
    if (numDezenas <= 0) {
        console.error('Número de dezenas inválido:', numDezenas);
        valorApostaDisplay.placeholder = 'Digite números válidos';
        return;
    }
    
    // Log dos objetos disponíveis
    console.log('Verificando tabelas de preços disponíveis:');
    
    // Usando uma abordagem diferente para verificar os objetos
    let tabelaPrecos = null;
    
    switch(nomeJogo) {
        case 'LF': // Lotofácil
            tabelaPrecos = precosLotofacil;
            console.log('Buscando preços para Lotofácil com', numDezenas, 'dezenas');
            break;
        case 'DI': // Dia de Sorte
            tabelaPrecos = precosDiaDeSorte;
            console.log('Buscando preços para Dia de Sorte com', numDezenas, 'dezenas');
            break;
        case 'MM': // Mais Milionária
            tabelaPrecos = precosMaisMilionaria;
            console.log('Buscando preços para Mais Milionária com', numDezenas, 'dezenas');
            break;
        case 'MS': // Mega Sena
            tabelaPrecos = precosMegaSena;
            console.log('Buscando preços para Mega Sena com', numDezenas, 'dezenas');
            break;
        case 'QN': // Quina
            tabelaPrecos = precosQuina;
            console.log('Buscando preços para Quina com', numDezenas, 'dezenas');
            break;
        case 'LM': // Lotomania
            console.log('*** PROCESSANDO LOTOMANIA ***');
            console.log('precosLotomania está definido?', typeof precosLotomania !== 'undefined');
            if (typeof precosLotomania !== 'undefined') {
                console.log('Chaves disponíveis em precosLotomania:', Object.keys(precosLotomania));
                console.log('Valor precosLotomania[50]:', precosLotomania[50]);
                console.log('Valor precosLotomania[55]:', precosLotomania[55]);
            }
            
            // Se a tabela não for válida, criar uma temporária
            if (typeof precosLotomania === 'undefined' || !precosLotomania[50]) {
                console.log('Criando tabela temporária para Lotomania');
                // Criar tabela temporária
                window.precosLotomania = {
                    50: [
                        { valor: 1.00, premio: "1.500,00" },
                        { valor: 1.50, premio: "2.250,00" },
                        { valor: 2.00, premio: "3.000,00" },
                        { valor: 3.00, premio: "4.500,00" },
                        { valor: 5.00, premio: "7.500,00" },
                        { valor: 7.00, premio: "10.500,00" },
                        { valor: 10.00, premio: "15.000,00" },
                        { valor: 15.00, premio: "22.500,00" },
                        { valor: 20.00, premio: "30.000,00" }
                    ],
                    51: [
                        { valor: 1.00, premio: "1.000,00" },
                        { valor: 1.50, premio: "1.500,00" },
                        { valor: 2.00, premio: "2.000,00" },
                        { valor: 3.00, premio: "3.000,00" },
                        { valor: 5.00, premio: "5.000,00" },
                        { valor: 10.00, premio: "10.000,00" },
                        { valor: 15.00, premio: "15.000,00" },
                        { valor: 20.00, premio: "20.000,00" },
                        { valor: 30.00, premio: "30.000,00" }
                    ],
                    55: [
                        { valor: 1.00, premio: "500,00" },
                        { valor: 1.50, premio: "750,00" },
                        { valor: 2.00, premio: "1.000,00" },
                        { valor: 3.00, premio: "1.500,00" },
                        { valor: 5.00, premio: "2.500,00" },
                        { valor: 7.00, premio: "3.500,00" },
                        { valor: 10.00, premio: "5.000,00" },
                        { valor: 15.00, premio: "7.500,00" },
                        { valor: 20.00, premio: "10.000,00" },
                        { valor: 30.00, premio: "15.000,00" },
                        { valor: 60.00, premio: "30.000,00" }
                    ]
                };
            }
            
            tabelaPrecos = precosLotomania;
            console.log('Buscando preços para Lotomania com', numDezenas, 'dezenas');
            break;
        case 'TM': // Timemania
            tabelaPrecos = precosTimemania;
            console.log('Buscando preços para Timemania com', numDezenas, 'dezenas');
            break;
        default:
            console.error('Jogo não reconhecido:', nomeJogo);
            valorApostaDisplay.placeholder = 'Jogo não reconhecido';
            return;
    }
    
    if (!tabelaPrecos) {
        console.error('Tabela de preços não encontrada para o jogo:', nomeJogo);
        valorApostaDisplay.placeholder = 'Tabela de preços indisponível';
        return;
    }
    
    console.log('Tabela de preços para', nomeJogo, ':', tabelaPrecos);
    console.log('Chaves disponíveis:', Object.keys(tabelaPrecos));
    
    // Verificar se a tabela tem a quantidade de dezenas
    if (tabelaPrecos[numDezenas]) {
        precos = tabelaPrecos[numDezenas];
        console.log('Preços encontrados para', numDezenas, 'dezenas:', precos);
    } else {
        console.log('Número de dezenas não encontrado na tabela. Buscando valor próximo...');
        
        // Primeiro, obter as chaves disponíveis e ordená-las
        const chaves = Object.keys(tabelaPrecos).map(k => parseInt(k, 10));
        if (chaves.length > 0) {
            chaves.sort((a, b) => a - b);
            console.log('Chaves disponíveis ordenadas:', chaves);
            
            // Encontrar o valor mais próximo
            if (numDezenas < chaves[0]) {
                // Se menor que o mínimo, usar o mínimo
                dezenasAtual = chaves[0];
            } else if (numDezenas > chaves[chaves.length - 1]) {
                // Se maior que o máximo, usar o máximo
                dezenasAtual = chaves[chaves.length - 1];
            } else {
                // Encontrar o valor mais próximo
                let menorDiferenca = Infinity;
                for (const chave of chaves) {
                    const diferenca = Math.abs(chave - numDezenas);
                    if (diferenca < menorDiferenca) {
                        menorDiferenca = diferenca;
                        dezenasAtual = chave;
                    }
                }
            }
            console.log('Usando valor mais próximo:', dezenasAtual, 'dezenas');
        } else {
            console.error('Não há valores disponíveis na tabela de preços');
            valorApostaDisplay.placeholder = 'Sem opções de valor disponíveis';
            return;
        }
        
        // Tentar novamente com o valor ajustado
        precos = tabelaPrecos[dezenasAtual] || [];
        console.log('Usando valor ajustado:', dezenasAtual, 'dezenas. Preços encontrados:', precos.length);
    }
    
    console.log(`Preços encontrados para ${nomeJogo} com ${dezenasAtual} dezenas:`, precos.length);
    if (precos.length > 0) {
        console.log('Exemplos de preços:', precos.slice(0, 3));
    } else {
        console.error('Nenhum preço encontrado, mesmo após ajuste');
        valorApostaDisplay.placeholder = 'Sem opções de valor para este jogo';
        return;
    }
    
    // Atualizar o campo de quantidade de dezenas para refletir o valor real usado
    if (dezenasAtual !== numDezenas) {
        const qtdDezenasField = document.getElementById('qtd_dezenas');
        if (qtdDezenasField) {
            qtdDezenasField.value = dezenasAtual + ' dezenas (ajustado)';
            console.log('Quantidade de dezenas ajustada para:', dezenasAtual);
        }
    }
    
    if (precos.length > 0) {
        // Adicionar opção padrão no dropdown
        const defaultItem = document.createElement('button');
        defaultItem.className = 'dropdown-item';
        defaultItem.setAttribute('onclick', "selecionarValor('', '')");
        defaultItem.textContent = 'Selecione o valor';
        valorApostaDropdown.appendChild(defaultItem);
        
        // Também adicionar ao select oculto
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Selecione o valor';
        valorApostaSelect.appendChild(defaultOption);
        
        // Adicionar cada preço como um item no dropdown
        precos.forEach(preco => {
            const valorNumerico = parseFloat(preco.valor).toFixed(2);
            const valorFormatado = valorNumerico.replace('.', ',');
            const premioFormatado = preco.premio;
            
            // Adicionar ao select oculto
            const option = document.createElement('option');
            option.value = valorNumerico;
            option.setAttribute('data-premio', premioFormatado);
            option.textContent = `R$ ${valorFormatado}`;
            valorApostaSelect.appendChild(option);
            
            // Adicionar ao dropdown visível
            const item = document.createElement('button');
            item.className = 'dropdown-item';
            item.setAttribute('data-value', valorNumerico);
            item.setAttribute('data-premio', premioFormatado);
            item.setAttribute('onclick', `selecionarValor('${valorNumerico}', 'R$ ${valorFormatado}', '${premioFormatado}')`);
            item.textContent = `R$ ${valorFormatado}`;
            valorApostaDropdown.appendChild(item);
            
            console.log(`Opção criada: valor=${valorNumerico}, texto=R$ ${valorFormatado}, premio=${premioFormatado}`);
        });
        
        // Selecionar a primeira opção automaticamente
        setTimeout(() => {
            console.log('Selecionando primeira opção de valor automaticamente');
            const primeiraOpcao = valorApostaDropdown.querySelectorAll('.dropdown-item')[1]; // Primeira opção após "Selecione o valor"
            if (primeiraOpcao) {
                // Extrair dados
                const valor = primeiraOpcao.getAttribute('data-value');
                const premio = primeiraOpcao.getAttribute('data-premio');
                
                // Atualizar valores
                valorApostaDisplay.value = `R$ ${valor.replace('.', ',')}`;
                valorApostaSelect.value = valor;
                
                if (premio) {
                    valorPremiacao.value = premio;
                    if (valorPremiacaoDisplay) {
                        valorPremiacaoDisplay.textContent = premio;
                    }
                }
                
                // Destacar item selecionado
                primeiraOpcao.classList.add('dropdown-item-selected');
            }
        }, 100);
        
        // Atualizar o placeholder
        valorApostaDisplay.placeholder = 'Selecione o valor';
    } else {
        // Sem preços disponíveis
        const mensagem = !nomeJogo ? "Selecione um jogo válido" : 
                      numDezenas === 0 ? "Digite os números da aposta" : 
                      `Não há preços para ${numDezenas} dezenas neste jogo`;
        
        valorApostaDisplay.placeholder = mensagem;
        console.error(mensagem);
    }

    console.log("### FIM DA FUNÇÃO atualizarOpcoesValor() ###");
}

// Função para testar se o texto contém referência a Mais Milionária
function testarTextoMaisMilionaria(texto) {
    const textoUpper = texto.toUpperCase();
    
    // Lista de padrões possíveis para Mais Milionária
    const padroes = [
        'MM',
        'MAIS MILIONÁRIA',
        'MAIS MILIONARIA',
        '+MILIONÁRIA',
        '+MILIONARIA',
        'MILIONÁRIA',
        'MILIONARIA',
        'MAIS MILION',
        '+MILION',
        'LOTERIAS MOBILE: MM',
        'MOBILE MM'
    ];
    
    // Verificar cada padrão
    const padroesEncontrados = padroes.filter(padrao => textoUpper.includes(padrao));
    
    return {
        encontrado: padroesEncontrados.length > 0,
        padroes: padroesEncontrados
    };
}

// Função para testar se o texto contém referência a Timemania
function testarTextoTimemania(texto) {
    const textoUpper = texto.toUpperCase();
    
    // Lista de padrões possíveis para Timemania
    const padroes = [
        'TM',
        'TIMEMANIA',
        'TIME MANIA',
        'TIME-MANIA',
        'LOTERIAS MOBILE: TM',
        'MOBILE TM',
        'LOTERIA TM',
        'LOTERIAS TM'
    ];
    
    // Verificar cada padrão
    const padroesEncontrados = padroes.filter(padrao => textoUpper.includes(padrao));
    
    return {
        encontrado: padroesEncontrados.length > 0,
        padroes: padroesEncontrados
    };
}

// Função de diagnóstico para verificar elementos da interface
function diagnosticarInterface() {
    console.log("=== DIAGNÓSTICO DE INTERFACE ===");
    
    // Verificar elementos críticos
    const elementos = {
        'apostador': document.getElementById('apostador'),
        'whatsapp': document.getElementById('whatsapp'),
        'jogo': document.getElementById('jogo'),
        'apostas': document.getElementById('apostas'),
        'valor_aposta': document.getElementById('valor_aposta'),
        'valor_premiacao': document.getElementById('valor_premiacao'),
        'qtd_dezenas': document.getElementById('qtd_dezenas'),
        'btnVisualizar': document.getElementById('btnVisualizar')
    };
    
    console.log("Verificação de elementos:");
    for (const [id, elemento] of Object.entries(elementos)) {
        console.log(`- ${id}: ${elemento ? 'ENCONTRADO' : 'NÃO ENCONTRADO'}`);
        if (elemento) {
            // Verificar se o elemento é visível
            const estilo = window.getComputedStyle(elemento);
            const visivel = estilo.display !== 'none' && estilo.visibility !== 'hidden';
            console.log(`  - Visível: ${visivel ? 'SIM' : 'NÃO'}`);
            
            // Para selects, verificar opções
            if (elemento.tagName === 'SELECT') {
                console.log(`  - Opções: ${elemento.options.length}`);
                if (elemento.options.length > 0) {
                    console.log(`  - Primeira opção: ${elemento.options[0].textContent}`);
                }
            }
            
            // Verificar dimensões
            console.log(`  - Dimensões: ${elemento.offsetWidth}x${elemento.offsetHeight}px`);
            
            // Verificar se há problemas de sobreposição (z-index)
            console.log(`  - Z-index: ${estilo.zIndex}`);
            
            // Verificar se há elementos sobrepostos
            const rect = elemento.getBoundingClientRect();
            const elementosSobrepostos = document.elementsFromPoint(
                rect.left + rect.width / 2, 
                rect.top + rect.height / 2
            );
            console.log(`  - Elementos sobrepostos: ${elementosSobrepostos.length}`);
            if (elementosSobrepostos.length > 1) {
                console.log(`  - Primeiro elemento sobreposto: ${elementosSobrepostos[0].tagName}#${elementosSobrepostos[0].id || 'sem-id'}`);
            }
        }
    }
    
    console.log("=== FIM DO DIAGNÓSTICO ===");
}

// Função para forçar reinicialização do formulário
function reinicializarFormulario() {
    console.log("Reinicializando formulário...");
    
    // Verificar elementos essenciais
    const valorApostaSelect = document.getElementById('valor_aposta');
    const jogoSelect = document.getElementById('jogo');
    const apostasTextarea = document.getElementById('apostas');
    
    if (!valorApostaSelect || !jogoSelect || !apostasTextarea) {
        console.error("Elementos essenciais não encontrados durante reinicialização");
        return false;
    }
    
    // Forçar estilos visíveis
    if (valorApostaSelect) {
        valorApostaSelect.style.display = 'block';
        valorApostaSelect.style.visibility = 'visible';
        valorApostaSelect.style.opacity = '1';
        valorApostaSelect.style.height = 'auto';
        valorApostaSelect.style.minHeight = '38px';
        valorApostaSelect.style.width = '100%';
        valorApostaSelect.style.zIndex = '10';
        
        // Verificar se tem conteúdo
        if (valorApostaSelect.options.length <= 1 && jogoSelect.value && apostasTextarea.value.trim()) {
            console.log("Select de valor vazio, tentando reprocessar apostas...");
            processarApostas();
        }
    }
    
    // Adicionar botão de forçar processamento, se não existir
    const btnForcarProcessamento = document.getElementById('btnForcarProcessamento');
    if (!btnForcarProcessamento) {
        console.log("Adicionando botão de forçar processamento");
        const divRow = document.querySelector('.row:has(#valor_aposta)');
        if (divRow) {
            const botao = document.createElement('button');
            botao.id = 'btnForcarProcessamento';
            botao.type = 'button';
            botao.className = 'btn btn-warning btn-sm';
            botao.style.marginTop = '10px';
            botao.textContent = 'Recarregar Valores';
            botao.onclick = function() {
                processarApostas();
                setTimeout(diagnosticarInterface, 500);
            };
            
            const col = document.querySelector('.col-md-6:has(#valor_aposta)');
            if (col) {
                col.appendChild(botao);
            } else {
                divRow.appendChild(botao);
            }
        }
    }
    
    return true;
}

// Função para implementar um dropdown nativo (sem depender do Bootstrap)
function inicializarDropdownNativo(inputElement, dropdownElement) {
    console.log('Inicializando dropdown nativo para', inputElement.id);
    
    if (!inputElement || !dropdownElement) {
        console.error('Elementos necessários não encontrados para o dropdown nativo');
        return;
    }
    
    // Posicionar o dropdown abaixo do input
    function posicionarDropdown() {
        const inputRect = inputElement.getBoundingClientRect();
        dropdownElement.style.position = 'absolute';
        dropdownElement.style.top = `${inputRect.bottom}px`;
        dropdownElement.style.left = `${inputRect.left}px`;
        dropdownElement.style.width = `${inputRect.width}px`;
        dropdownElement.style.zIndex = '1000';
    }
    
    // Mostrar o dropdown ao clicar no input
    inputElement.addEventListener('click', function(e) {
        e.stopPropagation();
        posicionarDropdown();
        dropdownElement.style.display = dropdownElement.style.display === 'block' ? 'none' : 'block';
        
        // Adicionar classe show para estilização
        if (dropdownElement.style.display === 'block') {
            dropdownElement.classList.add('show');
        } else {
            dropdownElement.classList.remove('show');
        }
    });
    
    // Fechar o dropdown ao clicar fora
    document.addEventListener('click', function(e) {
        if (e.target !== inputElement && !dropdownElement.contains(e.target)) {
            dropdownElement.style.display = 'none';
            dropdownElement.classList.remove('show');
        }
    });
    
    // Inicialmente, esconder o dropdown
    dropdownElement.style.display = 'none';
}

// Função específica para lidar com a Lotofácil (caso especial)
function inicializarLotofacil() {
    console.log("Inicializando configurações especiais para Lotofácil");
    
    // Verificar se a tabela de preços está devidamente carregada
    if (typeof precosLotofacil === 'undefined' || !precosLotofacil[15] || precosLotofacil[15].length === 0) {
        console.error("Tabela de preços da Lotofácil não está corretamente definida!");
        
        // Definir a tabela manualmente para garantir que exista
        window.precosLotofacil = {
            15: [
                { valor: 2.50, premio: "9.500,00" },
                { valor: 5.00, premio: "19.000,00" },
                { valor: 10.00, premio: "38.000,00" },
                { valor: 20.00, premio: "76.000,00" },
                { valor: 30.00, premio: "114.000,00" },
                { valor: 50.00, premio: "190.000,00" }
            ],
            16: [
                { valor: 2.50, premio: "3.800,00" },
                { valor: 5.00, premio: "7.600,00" },
                { valor: 10.00, premio: "15.200,00" },
                { valor: 20.00, premio: "30.400,00" },
                { valor: 30.00, premio: "45.600,00" },
                { valor: 50.00, premio: "76.000,00" }
            ],
            17: [
                { valor: 2.50, premio: "1.900,00" },
                { valor: 5.00, premio: "3.800,00" },
                { valor: 10.00, premio: "7.600,00" },
                { valor: 20.00, premio: "15.200,00" },
                { valor: 30.00, premio: "22.800,00" },
                { valor: 50.00, premio: "38.000,00" }
            ],
            18: [
                { valor: 2.50, premio: "950,00" },
                { valor: 5.00, premio: "1.900,00" },
                { valor: 10.00, premio: "3.800,00" },
                { valor: 20.00, premio: "7.600,00" },
                { valor: 30.00, premio: "11.400,00" },
                { valor: 50.00, premio: "19.000,00" }
            ],
            19: [
                { valor: 2.50, premio: "430,00" },
                { valor: 5.00, premio: "860,00" },
                { valor: 10.00, premio: "1.720,00" },
                { valor: 20.00, premio: "3.440,00" },
                { valor: 30.00, premio: "5.160,00" },
                { valor: 50.00, premio: "8.600,00" }
            ],
            20: [
                { valor: 2.50, premio: "190,00" },
                { valor: 5.00, premio: "380,00" },
                { valor: 10.00, premio: "760,00" },
                { valor: 20.00, premio: "1.520,00" },
                { valor: 30.00, premio: "2.280,00" },
                { valor: 50.00, premio: "3.800,00" }
            ]
        };
        
        console.log("Tabela de preços da Lotofácil redefinida manualmente");
    }
    
    // Definir a quantidade padrão de dezenas para Lotofácil
    const qtdDezenasField = document.getElementById('qtd_dezenas');
    if (qtdDezenasField) {
        qtdDezenasField.value = '15 dezenas (padrão Lotofácil)';
    }
    
    // Carregar valores usando a quantidade padrão
    if (typeof atualizarOpcoesValor === 'function') {
        console.log("Carregando valores da Lotofácil com 15 dezenas");
        atualizarOpcoesValor('LF', 15);
    } else {
        console.error("Função atualizarOpcoesValor não disponível!");
    }
}

// Função específica para lidar com a Lotomania (caso especial)
function inicializarLotomania() {
    console.log("Inicializando configurações especiais para Lotomania");
    
    // Verificar se a tabela de preços está devidamente carregada
    if (typeof precosLotomania === 'undefined' || !precosLotomania[50] || precosLotomania[50].length === 0) {
        console.error("Tabela de preços da Lotomania não está corretamente definida!");
        
        // Definir a tabela manualmente para garantir que exista
        window.precosLotomania = {
            50: [
                { valor: 1.00, premio: "1.500,00" },
                { valor: 1.50, premio: "2.250,00" },
                { valor: 2.00, premio: "3.000,00" },
                { valor: 3.00, premio: "4.500,00" },
                { valor: 5.00, premio: "7.500,00" },
                { valor: 7.00, premio: "10.500,00" },
                { valor: 10.00, premio: "15.000,00" },
                { valor: 15.00, premio: "22.500,00" },
                { valor: 20.00, premio: "30.000,00" }
            ],
            51: [
                { valor: 1.00, premio: "1.000,00" },
                { valor: 1.50, premio: "1.500,00" },
                { valor: 2.00, premio: "2.000,00" },
                { valor: 3.00, premio: "3.000,00" },
                { valor: 5.00, premio: "5.000,00" },
                { valor: 10.00, premio: "10.000,00" },
                { valor: 15.00, premio: "15.000,00" },
                { valor: 20.00, premio: "20.000,00" },
                { valor: 30.00, premio: "30.000,00" }
            ],
            55: [
                { valor: 1.00, premio: "500,00" },
                { valor: 1.50, premio: "750,00" },
                { valor: 2.00, premio: "1.000,00" },
                { valor: 3.00, premio: "1.500,00" },
                { valor: 5.00, premio: "2.500,00" },
                { valor: 7.00, premio: "3.500,00" },
                { valor: 10.00, premio: "5.000,00" },
                { valor: 15.00, premio: "7.500,00" },
                { valor: 20.00, premio: "10.000,00" },
                { valor: 30.00, premio: "15.000,00" },
                { valor: 60.00, premio: "30.000,00" }
            ]
        };
        
        console.log("Tabela de preços da Lotomania redefinida manualmente");
    }
    
    // Definir a quantidade padrão de dezenas para Lotomania
    const qtdDezenasField = document.getElementById('qtd_dezenas');
    if (qtdDezenasField) {
        qtdDezenasField.value = '50 dezenas (padrão Lotomania)';
    }
    
    // Carregar valores usando a quantidade padrão
    if (typeof atualizarOpcoesValor === 'function') {
        console.log("Carregando valores da Lotomania com 50 dezenas");
        atualizarOpcoesValor('LM', 50);
    } else {
        console.error("Função atualizarOpcoesValor não disponível!");
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script de importação de apostas carregado');

    // Preços dos jogos
    const precos = {
        'Lotofacil': {
            '15': 2.50,
            '16': 40.00,
            '17': 340.00,
            '18': 2040.00,
            '19': 9690.00,
            '20': 38760.00
        },
        'Dia de Sorte': {
            '7': 2.00,
            '8': 16.00,
            '9': 72.00,
            '10': 240.00,
            '11': 660.00,
            '12': 1584.00,
            '13': 3432.00,
            '14': 6864.00,
            '15': 12870.00
        },
        'Mais Milionária': {
            '6': 3.00,
            '7': 21.00,
            '8': 84.00,
            '9': 252.00,
            '10': 630.00,
            '11': 1386.00,
            '12': 2772.00
        },
        'Mega-Sena': {
            '6': 4.50,
            '7': 31.50,
            '8': 126.00,
            '9': 378.00,
            '10': 945.00,
            '11': 2079.00,
            '12': 4158.00,
            '13': 7722.00,
            '14': 13513.50,
            '15': 22522.50
        }
    };

    // Elementos do DOM
    const apostadorSelect = document.getElementById('apostador');
    const whatsappInput = document.getElementById('whatsapp');
    const jogoSelect = document.getElementById('jogo');
    const valorPremiacaoInput = document.getElementById('valor_premiacao');
    const valorApostaSelect = document.getElementById('valor_aposta');
    const valorApostaDisplay = document.getElementById('valor_aposta_display');
    const valorApostaDropdown = document.getElementById('valor_aposta_dropdown');
    const qtdDezenasInput = document.getElementById('qtd_dezenas');
    const apostasTextarea = document.getElementById('apostas');
    const btnVisualizar = document.getElementById('btnVisualizar');
    const resumoApostas = document.getElementById('resumoApostas');

    // Atualizar WhatsApp quando selecionar apostador
    apostadorSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const whatsapp = selectedOption.getAttribute('data-whatsapp');
        whatsappInput.value = whatsapp || '';
    });

    // Processar apostas quando o campo mudar
    apostasTextarea.addEventListener('change', processarApostas);
    apostasTextarea.addEventListener('paste', function(e) {
        setTimeout(processarApostas, 100);
    });

    // Carregar configuração do jogo quando selecionar
    jogoSelect.addEventListener('change', carregarConfigJogo);

    // Visualizar apostas
    btnVisualizar.addEventListener('click', visualizarApostas);

    function carregarConfigJogo() {
        const selectedOption = jogoSelect.options[jogoSelect.selectedIndex];
        const codigo = selectedOption.getAttribute('data-codigo');
        const minNumeros = parseInt(selectedOption.getAttribute('data-min-numeros'));
        const maxNumeros = parseInt(selectedOption.getAttribute('data-max-numeros'));
        const qtdDezenas = parseInt(selectedOption.getAttribute('data-qtd-dezenas'));

        // Limpar opções anteriores
        valorApostaSelect.innerHTML = '<option value="">Selecione o valor</option>';
        valorApostaDropdown.innerHTML = '';

        // Carregar valores disponíveis
        const jogoNome = selectedOption.text;
        const valores = precos[jogoNome] || {};
        
        for (const [qtd, valor] of Object.entries(valores)) {
            const option = document.createElement('option');
            option.value = valor;
            option.textContent = `${qtd} números - R$ ${valor.toFixed(2)}`;
            valorApostaSelect.appendChild(option);

            const dropdownItem = document.createElement('a');
            dropdownItem.className = 'dropdown-item';
            dropdownItem.href = '#';
            dropdownItem.textContent = `${qtd} números - R$ ${valor.toFixed(2)}`;
            dropdownItem.addEventListener('click', function(e) {
                e.preventDefault();
                valorApostaSelect.value = valor;
                valorApostaDisplay.value = `${qtd} números - R$ ${valor.toFixed(2)}`;
                processarApostas();
            });
            valorApostaDropdown.appendChild(dropdownItem);
        }

        // Atualizar quantidade de dezenas
        qtdDezenasInput.value = qtdDezenas;
    }

    function processarApostas() {
        const apostas = apostasTextarea.value.trim().split('\n');
        const numerosValidos = [];
        const numerosInvalidos = [];

        // Detectar quantidade de dezenas da primeira linha válida
        let dezenasDetectadas = null;
        for (let i = 0; i < apostas.length; i++) {
            const aposta = apostas[i].trim();
            if (!aposta) continue;
            const numeros = aposta.split(/\s+/).map(n => parseInt(n)).filter(n => !isNaN(n));
            dezenasDetectadas = numeros.length;
            break;
        }

        // Obter id do jogo selecionado
        const selectedOption = jogoSelect.options[jogoSelect.selectedIndex];
        const jogoId = selectedOption ? selectedOption.value : null;
        if (!jogoId || !dezenasDetectadas) {
            valorApostaSelect.innerHTML = '<option value="">Selecione o valor</option>';
            valorPremiacaoInput.value = '0,00';
            return;
        }

        // Buscar valores do backend e popular o select
        buscarValoresAposta(jogoId, dezenasDetectadas).then(data => {
            valorApostaSelect.innerHTML = '<option value="">Selecione o valor</option>';
            valorApostaDropdown.innerHTML = '';
            if (data.success && data.valores.length > 0) {
                data.valores.forEach(v => {
                    const option = document.createElement('option');
                    option.value = v.valor_aposta;
                    option.textContent = `R$ ${parseFloat(v.valor_aposta).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
                    option.setAttribute('data-premio', v.valor_premio);
                    valorApostaSelect.appendChild(option);

                    const dropdownItem = document.createElement('a');
                    dropdownItem.className = 'dropdown-item';
                    dropdownItem.href = '#';
                    dropdownItem.textContent = `R$ ${parseFloat(v.valor_aposta).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
                    dropdownItem.addEventListener('click', function(e) {
                        e.preventDefault();
                        valorApostaSelect.value = v.valor_aposta;
                        valorApostaDisplay.value = `R$ ${parseFloat(v.valor_aposta).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
                        valorPremiacaoInput.value = v.valor_premio;
                    });
                    valorApostaDropdown.appendChild(dropdownItem);
                });
                // Selecionar o primeiro valor automaticamente
                if (valorApostaSelect.options.length > 1) {
                    valorApostaSelect.selectedIndex = 1;
                    valorPremiacaoInput.value = valorApostaSelect.options[1].getAttribute('data-premio');
                }
            } else {
                valorApostaSelect.innerHTML = '<option value="">Nenhum valor disponível</option>';
                valorPremiacaoInput.value = '0,00';
            }
        });

        // Validação das apostas (mantém igual)
        for (let i = 0; i < apostas.length; i++) {
            const aposta = apostas[i].trim();
            if (!aposta) continue;
            const numeros = aposta.split(/\s+/).map(n => parseInt(n)).filter(n => !isNaN(n));
            if (numeros.length !== dezenasDetectadas) {
                numerosInvalidos.push({
                    linha: i + 1,
                    numeros: numeros,
                    motivo: `Quantidade de números inválida (${numeros.length}). Deve ter ${dezenasDetectadas} números.`
                });
                continue;
            }
            const numerosUnicos = [...new Set(numeros)];
            if (numerosUnicos.length !== numeros.length) {
                numerosInvalidos.push({
                    linha: i + 1,
                    numeros: numeros,
                    motivo: 'Números duplicados encontrados.'
                });
                continue;
            }
            numerosValidos.push({
                linha: i + 1,
                numeros: numeros
            });
        }
        atualizarResumo(numerosValidos, numerosInvalidos);
    }

    function atualizarResumo(numerosValidos, numerosInvalidos) {
        let html = '<div class="table-responsive">';
        html += '<table class="table table-bordered">';
        html += '<thead><tr><th>Linha</th><th>Números</th><th>Status</th></tr></thead>';
        html += '<tbody>';

        // Adicionar apostas válidas
        numerosValidos.forEach(aposta => {
            html += `<tr class="table-success">`;
            html += `<td>${aposta.linha}</td>`;
            html += `<td>${aposta.numeros.join(' ')}</td>`;
            html += `<td>Válida</td>`;
            html += `</tr>`;
        });

        // Adicionar apostas inválidas
        numerosInvalidos.forEach(aposta => {
            html += `<tr class="table-danger">`;
            html += `<td>${aposta.linha}</td>`;
            html += `<td>${aposta.numeros.join(' ')}</td>`;
            html += `<td>${aposta.motivo}</td>`;
            html += `</tr>`;
        });

        html += '</tbody></table>';
        html += '</div>';

        // Adicionar resumo
        html += `<div class="alert alert-info mt-3">`;
        html += `<strong>Resumo:</strong><br>`;
        html += `- Apostas válidas: ${numerosValidos.length}<br>`;
        html += `- Apostas inválidas: ${numerosInvalidos.length}<br>`;
        html += `- Valor total: R$ ${(parseFloat(valorApostaSelect.value) * numerosValidos.length).toFixed(2)}`;
        html += `</div>`;

        resumoApostas.innerHTML = html;
    }

    function visualizarApostas() {
        const modal = new bootstrap.Modal(document.getElementById('visualizarModal'));
        modal.show();
    }

    // Função para buscar valores de aposta do backend
    async function buscarValoresAposta(jogoId, dezenas) {
        try {
            const response = await fetch('ajax/valores_jogo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ jogo_id: jogoId, dezenas })
            });
            return await response.json();
        } catch (e) {
            return { success: false, message: 'Erro de comunicação com o servidor.' };
        }
    }
});