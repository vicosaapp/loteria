<?php
// Configurações da API da Caixa
return [
    'api_url' => 'https://loteriascaixa-api.herokuapp.com/api',
    'jogos' => [
        'mega-sena' => [
            'identificador' => 'megasena',
            'nome' => 'Mega Sena',
            'dezenas' => 6,
            'valor_minimo' => 4.50
        ],
        'lotofacil' => [
            'identificador' => 'lotofacil',
            'nome' => 'Lotofácil',
            'dezenas' => 15,
            'valor_minimo' => 2.50
        ],
        'quina' => [
            'identificador' => 'quina',
            'nome' => 'Quina',
            'dezenas' => 5,
            'valor_minimo' => 2.00
        ],
        'lotomania' => [
            'identificador' => 'lotomania',
            'nome' => 'Lotomania',
            'dezenas' => 20,
            'valor_minimo' => 2.50
        ],
        'timemania' => [
            'identificador' => 'timemania',
            'nome' => 'Timemania',
            'dezenas' => 7,
            'valor_minimo' => 3.50
        ],
        'dupla-sena' => [
            'identificador' => 'duplasena',
            'nome' => 'Dupla Sena',
            'dezenas' => 6,
            'valor_minimo' => 2.50
        ],
        'dia-de-sorte' => [
            'identificador' => 'diadesorte',
            'nome' => 'Dia de Sorte',
            'dezenas' => 7,
            'valor_minimo' => 2.50
        ],
        'super-sete' => [
            'identificador' => 'supersete',
            'nome' => 'Super Sete',
            'dezenas' => 7,
            'valor_minimo' => 2.50
        ],
        'mais-milionaria' => [
            'identificador' => 'maismilionaria',
            'nome' => '+Milionária',
            'dezenas' => 6,
            'valor_minimo' => 4.50
        ]
    ],
    'atualizacao_automatica' => [
        'intervalo' => 300, // 5 minutos em segundos
        'ativo' => true
    ]
]; 