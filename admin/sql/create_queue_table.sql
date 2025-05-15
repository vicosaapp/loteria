-- Criar tabela para fila de envio de comprovantes
CREATE TABLE IF NOT EXISTS `fila_envio_comprovantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `aposta_id` int NOT NULL,
  `status` enum('pendente','enviado','falha') NOT NULL DEFAULT 'pendente',
  `data_enfileiramento` datetime NOT NULL,
  `data_processamento` datetime DEFAULT NULL,
  `tentativas` int NOT NULL DEFAULT '0',
  `ultima_tentativa` datetime DEFAULT NULL,
  `resultado` text,
  PRIMARY KEY (`id`),
  KEY `idx_aposta_id` (`aposta_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_fila_aposta` FOREIGN KEY (`aposta_id`) REFERENCES `apostas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci; 