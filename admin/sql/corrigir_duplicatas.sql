-- Primeiro, vamos identificar os IDs que queremos manter (os mais recentes)
SET @id_maismilionaria = (
    SELECT id 
    FROM jogos 
    WHERE nome LIKE '%Milionária%' 
    ORDER BY id DESC 
    LIMIT 1
);

SET @id_diadesorte = (
    SELECT id 
    FROM jogos 
    WHERE nome LIKE 'Dia de Sorte%' 
    ORDER BY id DESC 
    LIMIT 1
);

-- Excluir números sorteados dos jogos duplicados
DELETE ns FROM numeros_sorteados ns
INNER JOIN concursos c ON ns.concurso_id = c.id
INNER JOIN jogos j ON c.jogo_id = j.id
WHERE (j.nome LIKE '%Milionária%' AND j.id != @id_maismilionaria)
   OR (j.nome LIKE 'Dia de Sorte%' AND j.id != @id_diadesorte);

-- Excluir concursos dos jogos duplicados
DELETE c FROM concursos c
INNER JOIN jogos j ON c.jogo_id = j.id
WHERE (j.nome LIKE '%Milionária%' AND j.id != @id_maismilionaria)
   OR (j.nome LIKE 'Dia de Sorte%' AND j.id != @id_diadesorte);

-- Excluir jogos duplicados
DELETE FROM jogos 
WHERE (nome LIKE '%Milionária%' AND id != @id_maismilionaria)
   OR (nome LIKE 'Dia de Sorte%' AND id != @id_diadesorte);

-- Atualizar os identificadores dos jogos mantidos
UPDATE jogos 
SET identificador_api = CASE 
    WHEN nome LIKE '%Milionária%' THEN 'maismilionaria'
    WHEN nome LIKE 'Dia de Sorte%' THEN 'diadesorte'
END,
nome = CASE
    WHEN nome LIKE '%Milionária%' THEN '+Milionária'
    WHEN nome LIKE 'Dia de Sorte%' THEN 'Dia de Sorte'
END
WHERE id IN (@id_maismilionaria, @id_diadesorte); 