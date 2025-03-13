INSERT INTO jogos (nome, identificador_api, status) VALUES
('+ Milionária', 'maismilionaria', 1),
('Dia de Sorte', 'diadesorte', 1)
ON DUPLICATE KEY UPDATE
status = 1,
identificador_api = VALUES(identificador_api); 