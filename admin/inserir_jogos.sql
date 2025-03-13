-- Adicionar colunas (ignora se já existirem)
ALTER TABLE jogos
ADD COLUMN identificador_api VARCHAR(20) AFTER nome;

ALTER TABLE jogos
ADD COLUMN valor_acumulado DECIMAL(15,2) DEFAULT NULL;

ALTER TABLE jogos
ADD COLUMN data_proximo_concurso DATE DEFAULT NULL;

ALTER TABLE jogos
ADD COLUMN valor_estimado_proximo DECIMAL(15,2) DEFAULT NULL;

-- Atualizar os identificadores API dos jogos existentes
UPDATE jogos SET identificador_api = 'megasena' WHERE nome LIKE '%MEGA SENA%';
UPDATE jogos SET identificador_api = 'timemania' WHERE nome LIKE '%TIME MANIA%';
UPDATE jogos SET identificador_api = 'lotomania' WHERE nome LIKE '%LOTOMANIA%';
UPDATE jogos SET identificador_api = 'duplasena' WHERE nome LIKE '%DUPLA SENA%';
UPDATE jogos SET identificador_api = 'diadesorte' WHERE nome LIKE '%DIA DE SORTE%';
UPDATE jogos SET identificador_api = 'maismilionaria' WHERE nome LIKE '%MAIS MILIONÁRIA%';

-- Inserir jogos que não existem (usando INSERT IGNORE para evitar duplicatas)
INSERT IGNORE INTO jogos 
(nome, identificador_api, numeros_total, minimo_numeros, maximo_numeros, 
acertos_premio, numeros_disponiveis, total_numeros, dezenas, dezenas_premiar, status) 
VALUES 
('Dupla Sena', 'duplasena', 50, 6, 15, 6, 50, 50, 6, 6, 1);

-- Atualizar nomes para o padrão correto
UPDATE jogos SET nome = 'Mega-Sena' WHERE nome LIKE '%MEGA SENA%';
UPDATE jogos SET nome = 'Lotofácil' WHERE nome LIKE '%LOTOFÁCIL%';
UPDATE jogos SET nome = 'Quina' WHERE nome LIKE '%QUINA%';
UPDATE jogos SET nome = 'Lotomania' WHERE nome LIKE '%LOTOMANIA%';
UPDATE jogos SET nome = 'Timemania' WHERE nome LIKE '%TIME MANIA%';
UPDATE jogos SET nome = 'Dupla Sena' WHERE nome LIKE '%DUPLA SENA%';
UPDATE jogos SET nome = 'Dia de Sorte' WHERE nome LIKE '%DIA DE SORTE%';
UPDATE jogos SET nome = '+Milionária' WHERE nome LIKE '%MAIS MILIONÁRIA%'; 