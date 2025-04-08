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
    55: [
        { valor: 1.00, premio: "15.000,00" },
        { valor: 1.50, premio: "22.500,00" }
        // Outros valores...
    ],
    // Outros tamanhos...
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
        let dezenasForcar = 0; // Para forçar um número específico de dezenas em casos especiais
        
        console.log('Linha de nome do jogo original:', nomeJogoLinha);
        
        // Verificar se é Mais Milionária
        const resultadoMM = testarTextoMaisMilionaria(nomeJogoLinha);
        if (resultadoMM.encontrado) {
            console.log('Detectado Mais Milionária pelos padrões:', resultadoMM.padroes);
            nomeJogo = 'MM';
        }
        // Verificar se é Timemania via Mobile
        else if ((/LOTER[IA][SA]S?\s+MOBILE/i.test(nomeJogoLinha) || 
            /MOBILE\s+LOTER[IA][SA]S?/i.test(nomeJogoLinha)) && 
            nomeJogoLinha.includes('TM')) {
            console.log('Detectado "Loterias Mobile: TM" ou variação - Timemania');
            nomeJogo = 'TM';
            dezenasForcar = 20; // Forçar para 20 dezenas para este caso específico
        } 
        // Caso específico para Mais Milionária via Mobile
        else if ((/LOTER[IA][SA]S?\s+MOBILE/i.test(nomeJogoLinha) || 
            /MOBILE\s+LOTER[IA][SA]S?/i.test(nomeJogoLinha)) && 
            (nomeJogoLinha.includes('MM') || nomeJogoLinha.includes('MILIONÁRIA') || nomeJogoLinha.includes('MILIONARIA'))) {
            console.log('Detectado "Loterias Mobile: MM" ou variação - Mais Milionária');
            nomeJogo = 'MM';
        }
        // Caso específico do exemplo na imagem para Timemania
        else if (nomeJogoLinha.includes('MOBILE') && nomeJogoLinha.includes('TM')) {
            console.log('Detectado formato "Mobile TM" - Timemania');
            nomeJogo = 'TM';
            dezenasForcar = 20;
        }
        // Caso específico para Mais Milionária via Mobile simplificado
        else if (nomeJogoLinha.includes('MOBILE') && (nomeJogoLinha.includes('MM') || nomeJogoLinha.includes('MILIONÁRIA') || nomeJogoLinha.includes('MILIONARIA'))) {
            console.log('Detectado formato "Mobile MM" - Mais Milionária');
            nomeJogo = 'MM';
        }
        else {
        // Extrai o código do jogo (QN, DI, MM, MS, LF, LM, TM)
        if (nomeJogoLinha.includes('QN')) nomeJogo = 'QN';
        else if (nomeJogoLinha.includes('DI')) nomeJogo = 'DI';
        else if (nomeJogoLinha.includes('MM')) nomeJogo = 'MM';
        else if (nomeJogoLinha.includes('MS')) nomeJogo = 'MS';
        else if (nomeJogoLinha.includes('LF')) nomeJogo = 'LF';
            else if (nomeJogoLinha.includes('LM') && !nomeJogoLinha.includes('TM')) nomeJogo = 'LM';
            else if (nomeJogoLinha.includes('TM') || nomeJogoLinha.includes('TIME')) nomeJogo = 'TM';
            
            // Verificações adicionais específicas por nome completo
            if (nomeJogoLinha.includes('TIMEMANIA') || nomeJogoLinha.includes('TIME MANIA')) {
                nomeJogo = 'TM';
            } else if (nomeJogoLinha.includes('MAIS MILIONÁRIA') || nomeJogoLinha.includes('MAIS MILIONARIA') || nomeJogoLinha.includes('+MILIONÁRIA') || nomeJogoLinha.includes('+MILIONARIA')) {
                nomeJogo = 'MM';
            }
            
            // Verifica por formatos como "Loterias Mobile: XX"
            if (nomeJogoLinha.includes('MOBILE') || nomeJogoLinha.includes('LOTERIAS')) {
                if (nomeJogoLinha.includes('TM')) nomeJogo = 'TM';
                else if (nomeJogoLinha.includes('LF')) nomeJogo = 'LF';
                else if (nomeJogoLinha.includes('MS')) nomeJogo = 'MS';
                else if (nomeJogoLinha.includes('QN')) nomeJogo = 'QN';
        else if (nomeJogoLinha.includes('LM')) nomeJogo = 'LM';
                else if (nomeJogoLinha.includes('MM') || nomeJogoLinha.includes('MILIONÁRIA') || nomeJogoLinha.includes('MILIONARIA')) nomeJogo = 'MM';
            }
            
            // Se ainda não identificou, procura por nomes completos
            if (!nomeJogo) {
                if (nomeJogoLinha.includes('TIMEMANIA')) nomeJogo = 'TM';
                else if (nomeJogoLinha.includes('LOTOFACIL')) nomeJogo = 'LF';
                else if (nomeJogoLinha.includes('MEGA') && nomeJogoLinha.includes('SENA')) nomeJogo = 'MS';
                else if (nomeJogoLinha.includes('QUINA')) nomeJogo = 'QN';
                else if (nomeJogoLinha.includes('LOTOMANIA')) nomeJogo = 'LM';
                else if (nomeJogoLinha.includes('MAIS') && (nomeJogoLinha.includes('MILIONÁRIA') || nomeJogoLinha.includes('MILIONARIA'))) nomeJogo = 'MM';
                else if (nomeJogoLinha.includes('+MILIONÁRIA') || nomeJogoLinha.includes('+MILIONARIA')) nomeJogo = 'MM';
            }
        }
        
        console.log('Nome do jogo detectado:', nomeJogo, 'do texto:', nomeJogoLinha);
        
        // Se houver uma segunda linha com números
        if (linhas.length >= 2) {
            const primeiraAposta = linhas[1]; // A segunda linha é a primeira aposta
            let numDezenas = dezenasForcar > 0 ? dezenasForcar : contarDezenas(primeiraAposta);
            
            console.log('Texto da primeira aposta:', primeiraAposta);
            console.log('Número de dezenas detectadas:', numDezenas, dezenasForcar > 0 ? '(forçado)' : '');
            
            // Garantir que Mais Milionária tenha pelo menos 10 dezenas
            if (nomeJogo === 'MM' && numDezenas < 10) {
                console.log('Forçando Mais Milionária para ter no mínimo 10 dezenas');
                numDezenas = Math.max(10, numDezenas);
            }
            
            // Atualiza o campo de quantidade de dezenas
            qtdDezenasField.value = numDezenas + ' dezenas';
            
            // Atualiza as opções de valor baseado no nome do jogo e número de dezenas
            atualizarOpcoesValor(nomeJogo, numDezenas);
            
            // Selecionar automaticamente a primeira opção e definir o prêmio correspondente
            setTimeout(() => {
                const valorApostaSelect = document.getElementById('valor_aposta');
                if (valorApostaSelect && valorApostaSelect.options.length > 1) {
                    valorApostaSelect.selectedIndex = 1; // Seleciona a primeira opção válida
                    const selectedOption = valorApostaSelect.options[1];
                    if (selectedOption && selectedOption.dataset.premio) {
                        const premiacaoInput = document.getElementById('valor_premiacao');
                        if (premiacaoInput) {
                            premiacaoInput.value = selectedOption.dataset.premio;
                            console.log('Prêmio automaticamente definido para:', selectedOption.dataset.premio);
                        }
                    }
                }
            }, 200);
            
            console.log('Jogo detectado:', nomeJogo);
            console.log('Número de dezenas detectado:', numDezenas);
        } else {
            qtdDezenasField.value = '0 dezenas';
            atualizarOpcoesValor(nomeJogo, 0);
        }
    } else {
        qtdDezenasField.value = '0 dezenas';
        atualizarOpcoesValor(nomeJogo, 0);
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

        // Obtém o valor do prêmio do atributo data-premio da opção selecionada
        const opcaoSelecionada = valorApostaSelect.options[valorApostaSelect.selectedIndex];
        if (opcaoSelecionada && opcaoSelecionada.dataset.premio) {
            console.log('Definindo valor da premiação diretamente do data-premio:', opcaoSelecionada.dataset.premio);
            premiacaoInput.value = opcaoSelecionada.dataset.premio;
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
    let dezenasAtual = numDezenas;
    
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
                console.log('Preços disponíveis para Mais Milionária:', Object.keys(precosMaisMilionaria));
                precos = precosMaisMilionaria[numDezenas] || [];
                
                // Se não encontrou preços para o número exato, tenta valores padrão
                if (precos.length === 0) {
                    // Verificar qual número de dezenas usar por padrão
                    const dezenasPossiveis = [10, 15, 20, 25, 30, 35];
                    
                    // Encontrar o valor mais próximo
                    if (numDezenas < 10) {
                        dezenasAtual = 10;
                    } else if (numDezenas > 35) {
                        dezenasAtual = 35;
                    } else {
                        let diferenca = 100;
                        for (let d of dezenasPossiveis) {
                            const diff = Math.abs(numDezenas - d);
                            if (diff < diferenca) {
                                diferenca = diff;
                                dezenasAtual = d;
                            }
                        }
                    }
                    
                    console.log('Usando valor próximo para Mais Milionária:', dezenasAtual, 'dezenas');
                    precos = precosMaisMilionaria[dezenasAtual] || [];
                    
                    // Se ainda não encontrou, usar 20 dezenas por padrão
                    if (precos.length === 0 && dezenasAtual !== 20) {
                        console.log('Forçando para 20 dezenas para Mais Milionária');
                        precos = precosMaisMilionaria[20] || [];
                        dezenasAtual = 20;
                    }
                }
                break;
            case 'MS': // Mega Sena
                console.log('Buscando preços para Mega Sena com', numDezenas, 'dezenas');
                console.log('Preços disponíveis para Mega Sena:', Object.keys(precosMegaSena));
                precos = precosMegaSena[numDezenas] || [];
                // Se não encontrou, tenta usar um valor padrão
                if (precos.length === 0 && numDezenas >= 15) {
                    console.log('Usando preços padrão para Mega Sena (20 dezenas)');
                    precos = precosMegaSena[20] || [];
                    dezenasAtual = 20;
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
                    dezenasAtual = 20;
                }
                break;
            case 'LM': // Lotomania
                console.log('Buscando preços para Lotomania com', numDezenas, 'dezenas');
                precos = precosLotomania[numDezenas] || [];
                break;
            case 'TM': // Timemania
                console.log('Buscando preços para Timemania com', numDezenas, 'dezenas');
                console.log('Preços disponíveis para Timemania:', Object.keys(precosTimemania));
                
                // Primeiro tenta pegar os preços para o número exato de dezenas
                precos = precosTimemania[numDezenas] || [];
                console.log(`Tentativa de buscar preços para Timemania com ${numDezenas} dezenas:`, precos.length > 0 ? 'Encontrado' : 'Não encontrado');
                
                // Se não encontrar preços para o número exato de dezenas, procura o valor mais próximo
                if (precos.length === 0) {
                    // Tenta valores próximos ou valores comuns da Timemania
                    const dezenasPossiveis = [20, 25, 30, 35, 40, 45, 50, 55];
                    dezenasAtual = 20; // Valor padrão
                    
                    // Encontra o valor mais próximo
                    if (numDezenas > 55) {
                        dezenasAtual = 55;
                    } else if (numDezenas > 15) {
                        let diferenca = 100;
                        for (let d of dezenasPossiveis) {
                            const diff = Math.abs(numDezenas - d);
                            if (diff < diferenca) {
                                diferenca = diff;
                                dezenasAtual = d;
                            }
                        }
                    }
                    
                    console.log('Usando valor próximo para Timemania:', dezenasAtual, 'dezenas');
                    precos = precosTimemania[dezenasAtual] || [];
                    console.log(`Preços encontrados para ${dezenasAtual} dezenas:`, precos.length);
                    
                    // Se ainda não encontrou, força para 20 dezenas
                    if (precos.length === 0 && dezenasAtual !== 20) {
                        console.log('Forçando para 20 dezenas');
                        precos = precosTimemania[20] || [];
                        dezenasAtual = 20;
                    }
                }
                
                break;
        }
    }
    
    console.log(`Preços encontrados para ${nomeJogo} com ${dezenasAtual} dezenas:`, precos.length);
    if (precos.length > 0) {
        console.log('Exemplos de preços:', precos.slice(0, 3));
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
        precos.forEach(preco => {
            const option = document.createElement('option');
            option.value = preco.valor.toFixed(2);
            const valorFormatado = preco.valor.toFixed(2).replace('.', ',');
            
            // Garantir que o valor do prêmio seja exibido exatamente como definido no objeto
            option.textContent = `R$ ${valorFormatado} → R$ ${preco.premio}`;
            option.dataset.premio = preco.premio;
            
            // Log para verificar o que está sendo passado para o option
            console.log(`Opção criada: valor=${option.value}, texto=${option.textContent}, premio=${option.dataset.premio}`);
            
            selectValor.appendChild(option);
        });
        
        // Forçar o cálculo da premiação após preencher as opções
        setTimeout(() => {
            // Selecionar a primeira opção válida automaticamente
            if (selectValor.options.length > 1) {
                selectValor.selectedIndex = 1; // Primeira opção após "Selecione o valor"
                
                // Definir valor da premiação imediatamente usando a primeira opção
                const selectedOption = selectValor.options[1];
                if (selectedOption && selectedOption.dataset.premio) {
                    const premiacaoInput = document.getElementById('valor_premiacao');
                    if (premiacaoInput) {
                        premiacaoInput.value = selectedOption.dataset.premio;
                        console.log('Valor inicial de premiação definido:', premiacaoInput.value);
                    }
                }
            }
        }, 100);
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

    // Remove todos os event listeners antigos para evitar duplicação
    const newSelect = selectValor.cloneNode(true);
    selectValor.parentNode.replaceChild(newSelect, selectValor);
    
    // Adiciona novo event listener
    newSelect.addEventListener('change', function() {
        const selectedIndex = this.selectedIndex;
        if (selectedIndex > 0) {
            const selectedOption = this.options[selectedIndex];
            const premio = selectedOption.dataset.premio;
            
            console.log('Opção selecionada, premio:', premio);
            
            if (premio) {
                const premiacaoInput = document.getElementById('valor_premiacao');
                if (premiacaoInput) {
                    premiacaoInput.value = premio;
                    console.log('Valor da premiação definido:', premiacaoInput.value);
                }
            }
        }
    });
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

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Debug para verificar se as constantes estão definidas corretamente
    console.log('Verificando objetos de preços:');
    console.log('precosMaisMilionaria definido?', typeof precosMaisMilionaria !== 'undefined');
    if (typeof precosMaisMilionaria !== 'undefined') {
        console.log('Chaves em precosMaisMilionaria:', Object.keys(precosMaisMilionaria));
        console.log('Conteúdo de precosMaisMilionaria[20]:', precosMaisMilionaria[20]);
    } else {
        console.error('precosMaisMilionaria não está definido!');
    }
    
    // Adicionar listener para depurar reconhecimento de Mais Milionária
    const apostasTextarea = document.getElementById('apostas');
    if (apostasTextarea) {
        apostasTextarea.addEventListener('input', function() {
            const textoJogo = apostasTextarea.value.split('\n')[0] || '';
            if (textoJogo) {
                const resultado = testarTextoMaisMilionaria(textoJogo);
                if (resultado.encontrado) {
                    console.log('DETECTADO MAIS MILIONÁRIA:', resultado.padroes);
                } else {
                    console.log('Mais Milionária NÃO detectada no texto:', textoJogo);
                }
            }
        });
    }
    
    // Verificação dos objetos de preços para debug
    console.log('Verificando valores da Lotofácil para 18 dezenas:');
    if (precosLotofacil && precosLotofacil[18]) {
        precosLotofacil[18].forEach(item => {
            console.log(`Valor: ${item.valor.toFixed(2)}, Prêmio: ${item.premio}, Tipo prêmio: ${typeof item.premio}`);
        });
    } else {
        console.error('Valores da Lotofácil para 18 dezenas não encontrados');
    }
    
    // Verificação dos valores da Mais Milionária
    console.log('Verificando valores da Mais Milionária:');
    console.log('Chaves disponíveis em precosMaisMilionaria:', Object.keys(precosMaisMilionaria));
    
    if (precosMaisMilionaria && precosMaisMilionaria[20]) {
        console.log('Exemplo de preços para Mais Milionária com 20 dezenas:');
        precosMaisMilionaria[20].forEach(item => {
            console.log(`Valor: ${item.valor.toFixed(2)}, Prêmio: ${item.premio}, Tipo prêmio: ${typeof item.premio}`);
        });
    } else {
        console.error('Valores da Mais Milionária para 20 dezenas não encontrados');
    }
    
    // Verificação dos objetos de preços da Timemania
    console.log('Verificando valores da Timemania:');
    console.log('Chaves disponíveis em precosTimemania:', Object.keys(precosTimemania));
    
    if (precosTimemania && precosTimemania[20]) {
        console.log('Exemplo de preços para Timemania com 20 dezenas:');
        precosTimemania[20].forEach(item => {
            console.log(`Valor: ${item.valor.toFixed(2)}, Prêmio: ${item.premio}, Tipo prêmio: ${typeof item.premio}`);
        });
    } else {
        console.error('Valores da Timemania para 20 dezenas não encontrados');
    }
    
    // Verificar a formatação dos valores no input
    setTimeout(() => {
        const premiacaoInput = document.getElementById('valor_premiacao');
        if (premiacaoInput) {
            console.log('Valor inicial do campo premiação:', premiacaoInput.value);
            // Verificar se é visível e corretamente estilizado
            console.log('Estilos do campo premiação:', 
                window.getComputedStyle(premiacaoInput).getPropertyValue('text-align'),
                window.getComputedStyle(premiacaoInput).getPropertyValue('display')
            );
        }
    }, 500);
    
    // Inicializar WhatsApp
    atualizarWhatsApp();
    
    // Configurar event listeners
    const apostadorSelect = document.getElementById('apostador');
    if (apostadorSelect) {
        apostadorSelect.addEventListener('change', atualizarWhatsApp);
    }
    
    // Processar apostas inicialmente se já existirem
    if (apostasTextarea) {
        // Eventos para apostas
        apostasTextarea.addEventListener('input', debounce(processarApostas, 500));
        apostasTextarea.addEventListener('paste', function() {
            setTimeout(processarApostas, 100);
        });
        
        // Inicializar se já tiver conteúdo
        if (apostasTextarea.value.trim()) {
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
        valorApostaSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.premio) {
                const premiacaoInput = document.getElementById('valor_premiacao');
                if (premiacaoInput) {
                    console.log('Change event: definindo premiação para', selectedOption.dataset.premio);
                    premiacaoInput.value = selectedOption.dataset.premio;
                }
            }
        });
    }
    
    // Capturar o formulário para formatar os valores antes de enviar
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Garantir que o valor da premiação está no formato correto para o backend
            const premiacaoInput = document.getElementById('valor_premiacao');
            if (premiacaoInput) {
                // Já está no formato correto, não precisa de conversão adicional
                console.log('Valor de premiação a ser enviado:', premiacaoInput.value);
            }
        });
    }
}); 