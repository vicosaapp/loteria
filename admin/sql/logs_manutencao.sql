-- SQL para criar a tabela de logs de manutenção
CREATE TABLE IF NOT EXISTS `logs_manutencao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL COMMENT 'ID do usuário que tentou acessar durante manutenção',
  `ip` varchar(45) NOT NULL COMMENT 'Endereço IP do usuário',
  `pagina` varchar(255) NOT NULL COMMENT 'URI da página solicitada',
  `data_acesso` datetime NOT NULL COMMENT 'Data e hora da tentativa de acesso',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_data_acesso` (`data_acesso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 