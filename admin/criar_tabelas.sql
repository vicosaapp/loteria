-- Criar tabela de concursos
CREATE TABLE IF NOT EXISTS `concursos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jogo_id` int NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `data_sorteio` datetime NOT NULL,
  `status` enum('aguardando','finalizado') NOT NULL DEFAULT 'aguardando',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `jogo_id` (`jogo_id`),
  CONSTRAINT `concursos_ibfk_1` FOREIGN KEY (`jogo_id`) REFERENCES `jogos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Criar tabela de números sorteados
CREATE TABLE IF NOT EXISTS `numeros_sorteados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `concurso_id` int NOT NULL,
  `numero` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `concurso_id` (`concurso_id`),
  CONSTRAINT `numeros_sorteados_ibfk_1` FOREIGN KEY (`concurso_id`) REFERENCES `concursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci; 