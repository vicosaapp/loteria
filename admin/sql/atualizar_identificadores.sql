UPDATE jogos 
SET identificador_api = CASE 
    WHEN nome = '+Milionária' THEN 'maismilionaria'
    WHEN nome = 'Dia de Sorte' THEN 'diadesorte'
END
WHERE nome IN ('+Milionária', 'Dia de Sorte'); 