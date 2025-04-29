-- SQL para criar a tabela de configurações
CREATE TABLE IF NOT EXISTS `configuracoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_apostas` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Status das apostas: 1 = liberado, 0 = pausado',
  `horario_inicio` time DEFAULT '23:00:00' COMMENT 'Horário de início da pausa nas apostas',
  `horario_fim` time DEFAULT '06:00:00' COMMENT 'Horário de fim da pausa nas apostas',
  `dias_semana` varchar(20) DEFAULT NULL COMMENT 'Dias da semana para pausa (0=Domingo, 1=Segunda, etc)',
  `motivo_pausa` text DEFAULT NULL COMMENT 'Motivo da pausa nas apostas',
  `modo_manutencao` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Modo de manutenção: 0 = desativado, 1 = ativado',
  `mensagem_manutencao` text DEFAULT 'Sistema em manutenção. Por favor, tente novamente mais tarde.' COMMENT 'Mensagem exibida durante a manutenção',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inserir configuração padrão se não existir
INSERT INTO `configuracoes` (`id`, `status_apostas`, `horario_inicio`, `horario_fim`, `dias_semana`, `motivo_pausa`, `modo_manutencao`, `mensagem_manutencao`)
SELECT 1, 1, '23:00:00', '06:00:00', '', '', 0, 'Sistema em manutenção. Por favor, tente novamente mais tarde.'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `configuracoes` WHERE `id` = 1); 