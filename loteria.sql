-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 01-Mar-2025 às 04:31
-- Versão do servidor: 8.0.24
-- versão do PHP: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `loteria`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `admins`
--

CREATE TABLE `admins` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2025-01-28 14:55:58', '2025-01-28 14:55:58');

-- --------------------------------------------------------

--
-- Estrutura da tabela `apostas`
--

CREATE TABLE `apostas` (
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `tipo_jogo_id` int DEFAULT NULL,
  `numeros` varchar(100) NOT NULL,
  `status` enum('pendente','aprovada','rejeitada') DEFAULT 'pendente',
  `comprovante_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `valor_aposta` decimal(10,2) DEFAULT NULL,
  `valor_premio` decimal(10,2) DEFAULT NULL,
  `revendedor_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `apostas_importadas`
--

CREATE TABLE `apostas_importadas` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `revendedor_id` int DEFAULT NULL,
  `jogo_nome` varchar(100) DEFAULT NULL,
  `numeros` text NOT NULL,
  `valor_aposta` decimal(10,2) NOT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `valor_premio` decimal(10,2) DEFAULT '0.00',
  `valor_premio_2` decimal(10,2) DEFAULT '0.00',
  `quantidade_dezenas` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int NOT NULL,
  `nome_site` varchar(255) DEFAULT 'Sistema de Loteria',
  `email_contato` varchar(255) DEFAULT NULL,
  `taxa_saque` decimal(5,2) DEFAULT '0.00',
  `saque_minimo` decimal(10,2) DEFAULT '0.00',
  `prazo_saque` int DEFAULT '24',
  `aposta_minima` decimal(10,2) DEFAULT '0.00',
  `aposta_maxima` decimal(10,2) DEFAULT '0.00',
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `nome_site`, `email_contato`, `taxa_saque`, `saque_minimo`, `prazo_saque`, `aposta_minima`, `aposta_maxima`, `data_atualizacao`) VALUES
(1, 'Sistema de Loteria', 'contato@sistema.com', '5.00', '20.00', 24, '5.00', '1000.00', '2025-01-23 21:08:30');

-- --------------------------------------------------------

--
-- Estrutura da tabela `configuracoes_apostas`
--

CREATE TABLE `configuracoes_apostas` (
  `id` int NOT NULL,
  `tipo_jogo_id` int NOT NULL,
  `quantidade_numeros` int NOT NULL,
  `valor_aposta` decimal(10,2) NOT NULL,
  `valor_premio` decimal(10,2) NOT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `configuracoes_apostas`
--

INSERT INTO `configuracoes_apostas` (`id`, `tipo_jogo_id`, `quantidade_numeros`, `valor_aposta`, `valor_premio`, `status`, `created_at`) VALUES
(1, 1, 10, '5.00', '5000.00', 'ativo', '2025-01-15 17:17:24'),
(2, 1, 10, '10.00', '10000.00', 'ativo', '2025-01-15 17:17:24'),
(3, 1, 10, '15.00', '15000.00', 'ativo', '2025-01-15 17:17:24'),
(4, 1, 10, '20.00', '25000.00', 'ativo', '2025-01-15 17:17:24'),
(5, 1, 15, '5.00', '1500.00', 'ativo', '2025-01-15 17:17:24'),
(6, 1, 15, '10.00', '3700.00', 'ativo', '2025-01-15 17:17:24'),
(7, 1, 15, '15.00', '5900.00', 'ativo', '2025-01-15 17:17:24'),
(8, 1, 15, '20.00', '7500.00', 'ativo', '2025-01-15 17:17:24'),
(9, 1, 20, '5.00', '1000.00', 'ativo', '2025-01-15 17:17:24'),
(10, 1, 20, '10.00', '2700.00', 'ativo', '2025-01-15 17:17:24'),
(11, 1, 20, '15.00', '4200.00', 'ativo', '2025-01-15 17:17:24'),
(12, 1, 20, '20.00', '5500.00', 'ativo', '2025-01-15 17:17:24'),
(13, 1, 25, '5.00', '800.00', 'ativo', '2025-01-15 17:17:24'),
(14, 1, 25, '10.00', '1700.00', 'ativo', '2025-01-15 17:17:24'),
(15, 1, 25, '15.00', '2600.00', 'ativo', '2025-01-15 17:17:24'),
(16, 1, 25, '20.00', '3500.00', 'ativo', '2025-01-15 17:17:24'),
(17, 1, 30, '5.00', '300.00', 'ativo', '2025-01-15 17:17:24'),
(18, 1, 30, '10.00', '650.00', 'ativo', '2025-01-15 17:17:24'),
(19, 1, 30, '15.00', '950.00', 'ativo', '2025-01-15 17:17:24'),
(20, 1, 30, '20.00', '1300.00', 'ativo', '2025-01-15 17:17:24'),
(21, 1, 35, '5.00', '100.00', 'ativo', '2025-01-15 17:17:24'),
(22, 1, 35, '10.00', '240.00', 'ativo', '2025-01-15 17:17:24'),
(23, 1, 35, '15.00', '360.00', 'ativo', '2025-01-15 17:17:24'),
(24, 1, 35, '20.00', '480.00', 'ativo', '2025-01-15 17:17:24'),
(25, 1, 40, '5.00', '60.00', 'ativo', '2025-01-15 17:17:24'),
(26, 1, 40, '10.00', '115.00', 'ativo', '2025-01-15 17:17:24'),
(27, 1, 40, '15.00', '170.00', 'ativo', '2025-01-15 17:17:24'),
(28, 1, 40, '20.00', '200.00', 'ativo', '2025-01-15 17:17:24');

-- --------------------------------------------------------

--
-- Estrutura da tabela `ganhadores`
--

CREATE TABLE `ganhadores` (
  `id` int NOT NULL,
  `resultado_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `aposta_id` int NOT NULL,
  `premio` decimal(10,2) NOT NULL,
  `status` enum('pendente','pago') DEFAULT 'pendente',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `jogos`
--

CREATE TABLE `jogos` (
  `id` int NOT NULL,
  `nome` varchar(255) NOT NULL,
  `titulo_importacao` varchar(100) DEFAULT NULL,
  `identificador_importacao` varchar(100) DEFAULT NULL,
  `numeros_total` int NOT NULL DEFAULT '100',
  `minimo_numeros` int NOT NULL DEFAULT '10',
  `maximo_numeros` int NOT NULL DEFAULT '25',
  `acertos_premio` int NOT NULL DEFAULT '10',
  `valor_aposta` decimal(10,2) NOT NULL DEFAULT '0.00',
  `valor_premio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `numeros_disponiveis` int NOT NULL DEFAULT '60',
  `total_numeros` int NOT NULL DEFAULT '60',
  `dezenas` int NOT NULL DEFAULT '0',
  `dezenas_premiar` int NOT NULL DEFAULT '0',
  `valor` decimal(10,2) NOT NULL DEFAULT '0.00',
  `premio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `numero_concurso` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `jogos`
--

INSERT INTO `jogos` (`id`, `nome`, `titulo_importacao`, `identificador_importacao`, `numeros_total`, `minimo_numeros`, `maximo_numeros`, `acertos_premio`, `valor_aposta`, `valor_premio`, `numeros_disponiveis`, `total_numeros`, `dezenas`, `dezenas_premiar`, `valor`, `premio`, `status`, `created_at`, `data_criacao`, `data_atualizacao`, `numero_concurso`) VALUES
(3, 'LotoFácil', 'Loterias Mobile: LF', 'Loterias Mobile: LF', 100, 17, 23, 15, '0.00', '0.00', 60, 100, 20, 5, '5.00', '5000.00', 1, '2025-01-18 11:59:10', '2025-01-23 22:25:35', '2025-02-18 18:08:37', '007'),
(6, 'LOTOMANIA', 'Loterias Mobile: LM', 'Loterias Mobile: LF', 100, 55, 85, 20, '0.00', '0.00', 90, 60, 55, 20, '0.00', '0.00', 1, '2025-02-14 21:09:32', '2025-02-14 21:09:32', '2025-02-26 17:54:00', '006'),
(8, 'QUINA', 'Loterias Mobile: QN', 'Loterias Mobile: LF', 80, 20, 50, 5, '0.00', '0.00', 80, 60, 20, 5, '0.00', '0.00', 1, '2025-02-14 21:20:59', '2025-02-14 21:20:59', '2025-02-18 18:10:33', '005'),
(9, 'MEGA SENA', 'Loterias Mobile: MS', 'Loterias Mobile: LF', 60, 20, 45, 6, '0.00', '0.00', 60, 60, 13, 6, '0.00', '0.00', 1, '2025-02-14 21:29:30', '2025-02-14 21:29:30', '2025-02-26 16:52:37', '004'),
(10, 'DIA DE SORTE', 'Loterias Mobile: DI', 'Loterias Mobile: LF', 31, 15, 22, 7, '0.00', '0.00', 31, 60, 10, 7, '0.00', '0.00', 1, '2025-02-14 21:38:55', '2025-02-14 21:38:55', '2025-02-26 16:20:33', '003'),
(11, 'TIME MANIA', 'Loterias Mobile: TM', 'Loterias Mobile: LF', 80, 20, 55, 7, '0.00', '0.00', 80, 60, 15, 7, '0.00', '0.00', 1, '2025-02-14 21:43:48', '2025-02-14 21:43:48', '2025-02-26 15:48:02', '002'),
(12, 'MAIS MILIONÁRIA', 'Loterias Mobile: MM', 'Loterias Mobile: LF', 50, 10, 35, 6, '0.00', '0.00', 50, 60, 10, 0, '0.00', '0.00', 1, '2025-02-14 21:48:36', '2025-02-14 21:48:36', '2025-02-18 18:11:30', '001');

-- --------------------------------------------------------

--
-- Estrutura da tabela `resultados`
--

CREATE TABLE `resultados` (
  `id` int NOT NULL,
  `tipo_jogo_id` int DEFAULT NULL,
  `numeros` varchar(255) NOT NULL,
  `data_sorteio` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `resultados`
--

INSERT INTO `resultados` (`id`, `tipo_jogo_id`, `numeros`, `data_sorteio`, `created_at`) VALUES
(10, 1, '36,45,17,31,1', '2025-01-24', '2025-01-24 14:28:14'),
(11, 2, '12,33,43,38,27,6,15,35', '2025-01-29', '2025-01-29 14:32:54'),
(12, 4, '15,32,17,11,3,24,14,18,37,36,43,42,41,9,39', '2025-02-12', '2025-02-12 00:43:18');

-- --------------------------------------------------------

--
-- Estrutura da tabela `tipos_jogos`
--

CREATE TABLE `tipos_jogos` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `regras` text,
  `min_numeros` int NOT NULL,
  `max_numeros` int NOT NULL,
  `range_numeros` int NOT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `tipos_jogos`
--

INSERT INTO `tipos_jogos` (`id`, `nome`, `descricao`, `regras`, `min_numeros`, `max_numeros`, `range_numeros`, `status`, `created_at`) VALUES
(1, 'Loto Sena', 'Jogo tradicional de loteria', 'Marque até 40 dezenas das 60 dezenas. Acerte as sorteadas e fature a premiação!', 10, 40, 60, 'ativo', '2025-01-15 17:17:24');

-- --------------------------------------------------------

--
-- Estrutura da tabela `trips`
--

CREATE TABLE `trips` (
  `id` varchar(36) NOT NULL,
  `passenger_id` varchar(36) NOT NULL,
  `driver_id` varchar(36) DEFAULT NULL,
  `origin_lat` decimal(10,8) NOT NULL,
  `origin_lng` decimal(11,8) NOT NULL,
  `destination_lat` decimal(10,8) NOT NULL,
  `destination_lng` decimal(11,8) NOT NULL,
  `status` enum('SEARCHING','ACCEPTED','STARTED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'SEARCHING',
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `id` varchar(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `type` enum('PASSENGER','DRIVER') NOT NULL,
  `phone` varchar(20) NOT NULL,
  `car_model` varchar(50) DEFAULT NULL,
  `car_plate` varchar(10) DEFAULT NULL,
  `car_color` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('admin','usuario','revendedor') DEFAULT 'usuario',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `revendedor_id` int DEFAULT NULL,
  `comissao` decimal(5,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `telefone`, `whatsapp`, `senha`, `tipo`, `created_at`, `revendedor_id`, `comissao`) VALUES
(1, 'Administrador', 'admin@admin.com', NULL, NULL, '$2a$12$3AmrALLp8FxMLhOwek8eguFi3oa/3dvJ03TWYqFe1jg19vLOzvx6W', 'admin', '2025-01-10 17:02:01', NULL, '0.00'),
(2, 'Evandro', 'e@e.com', '31971016685', NULL, '$2y$10$zw5VZeErzLl6YyT.GO7frucU1pcxl5l0zinEBuww5wMxrhw/GY0rq', 'usuario', '2025-01-10 17:07:19', NULL, '0.00'),
(3, 'Administrador', 'admin@sistema.com', NULL, NULL, '$2a$12$3AmrALLp8FxMLhOwek8eguFi3oa/3dvJ03TWYqFe1jg19vLOzvx6W', 'admin', '2025-01-10 17:57:57', NULL, '0.00'),
(5, 'Evandro2', 'a@a.com', '', '(31) 97101-6600', '$2y$10$3khZIqjnyuTgeFEQxIaKTuocscHLf2y4wbl34pq0yDXlBnxAILNMu', 'usuario', '2025-01-10 18:21:13', NULL, '0.00'),
(6, 'Lucas Santos', 'l@l.com', NULL, '(31) 99999-8888', '$2y$10$T0sBQwZkeSmF0rWvNR5ecOe350KLmqqecmvqkS4Q0z42qHt5WoBnK', 'usuario', '2025-01-24 17:03:59', NULL, '0.00'),
(7, 'João da silva', 'j@j.com', NULL, '(31) 97101-6611', '$2y$10$zDcIBNRIqQSmmBsyXuWW7uHt.nVsO9FuWWwpOe0tMf94jxH02ylRq', 'revendedor', '2025-01-25 16:16:06', NULL, '50.00'),
(8, 'José dos Santos', 'js@j.com', NULL, '(31) 99999-0000', '$2y$10$DWuYe13MHkSCK27WxK4kGemcATpL7IadM4Xz7NzKeXG6VWnw/pQAa', 'revendedor', '2025-02-06 23:45:40', NULL, '20.00'),
(9, 'Adriano Cunha', 'ad@ad.com', NULL, '35999988889', '$2y$10$ru0ybP6x8lqfJIm0eH0xi.C/I2uWNsepq2SLQKXJiV0.1iQq.lF7e', 'revendedor', '2025-02-12 00:12:05', NULL, '20.00'),
(10, 'Adriano Cunha apostador', 'aa@a.com', NULL, '(35) 99999-0000', '$2y$10$UY.R1O350fDuH/VKhyMMhOX3ZHG6s9vF9a4JLjKblj3o0qO/IzZ5K', 'usuario', '2025-02-12 00:46:46', NULL, '0.00');

-- --------------------------------------------------------

--
-- Estrutura da tabela `valores_jogos`
--

CREATE TABLE `valores_jogos` (
  `id` int NOT NULL,
  `jogo_id` int NOT NULL,
  `valor_aposta` decimal(15,2) NOT NULL,
  `dezenas` int NOT NULL,
  `valor_premio` decimal(15,2) NOT NULL,
  `valor_total_premio` decimal(15,2) GENERATED ALWAYS AS (`valor_premio`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `valores_jogos`
--

INSERT INTO `valores_jogos` (`id`, `jogo_id`, `valor_aposta`, `dezenas`, `valor_premio`) VALUES
(1081, 12, '1.00', 10, '2000.00'),
(1082, 12, '1.50', 10, '3000.00'),
(1083, 12, '2.00', 10, '4000.00'),
(1084, 12, '3.00', 10, '6000.00'),
(1085, 12, '4.00', 10, '8000.00'),
(1086, 12, '5.00', 10, '10000.00'),
(1087, 12, '10.00', 10, '20000.00'),
(1088, 12, '15.00', 10, '30000.00'),
(1089, 12, '1.00', 15, '350.00'),
(1090, 12, '1.50', 15, '525.00'),
(1091, 12, '2.00', 15, '700.00'),
(1092, 12, '3.00', 15, '1050.00'),
(1093, 12, '4.00', 15, '1400.00'),
(1094, 12, '5.00', 15, '1750.00'),
(1095, 12, '10.00', 15, '3500.00'),
(1096, 12, '15.00', 15, '5250.00'),
(1097, 12, '20.00', 15, '7000.00'),
(1098, 12, '25.00', 15, '8750.00'),
(1099, 12, '50.00', 15, '17500.00'),
(1100, 12, '86.00', 15, '30000.00'),
(1101, 12, '1.00', 20, '135.00'),
(1102, 12, '1.50', 20, '202.50'),
(1103, 12, '2.00', 20, '270.00'),
(1104, 12, '3.00', 20, '405.00'),
(1105, 12, '4.00', 20, '540.00'),
(1106, 12, '5.00', 20, '675.00'),
(1107, 12, '10.00', 20, '1350.00'),
(1108, 12, '15.00', 20, '2025.00'),
(1109, 12, '20.00', 20, '2700.00'),
(1110, 12, '25.00', 20, '3375.00'),
(1111, 12, '50.00', 20, '6750.00'),
(1112, 12, '100.00', 20, '13500.00'),
(1113, 12, '1.00', 25, '45.00'),
(1114, 12, '1.50', 25, '67.50'),
(1115, 12, '2.00', 25, '90.00'),
(1116, 12, '3.00', 25, '135.00'),
(1117, 12, '4.00', 25, '180.00'),
(1118, 12, '5.00', 25, '225.00'),
(1119, 12, '10.00', 25, '450.00'),
(1120, 12, '15.00', 25, '615.00'),
(1121, 12, '20.00', 25, '900.00'),
(1122, 12, '25.00', 25, '1125.00'),
(1123, 12, '50.00', 25, '2250.00'),
(1124, 12, '100.00', 25, '4500.00'),
(1125, 12, '1.00', 30, '15.00'),
(1126, 12, '1.50', 30, '22.50'),
(1127, 12, '2.00', 30, '30.00'),
(1128, 12, '3.00', 30, '45.00'),
(1129, 12, '4.00', 30, '60.00'),
(1130, 12, '5.00', 30, '75.00'),
(1131, 12, '10.00', 30, '150.00'),
(1132, 12, '15.00', 30, '225.00'),
(1133, 12, '20.00', 30, '300.00'),
(1134, 12, '25.00', 30, '375.00'),
(1135, 12, '50.00', 30, '750.00'),
(1136, 12, '100.00', 30, '1500.00'),
(1137, 12, '1.00', 35, '6.00'),
(1138, 12, '1.50', 35, '9.00'),
(1139, 12, '2.00', 35, '12.00'),
(1140, 12, '3.00', 35, '18.00'),
(1141, 12, '4.00', 35, '24.00'),
(1142, 12, '5.00', 35, '30.00'),
(1143, 12, '10.00', 35, '60.00'),
(1144, 12, '15.00', 35, '90.00'),
(1145, 12, '20.00', 35, '120.00'),
(1146, 12, '25.00', 35, '150.00'),
(1147, 12, '50.00', 35, '300.00'),
(1148, 12, '100.00', 35, '600.00'),
(1257, 11, '1.00', 20, '2000.00'),
(1258, 11, '1.50', 20, '3000.00'),
(1259, 11, '2.00', 20, '4000.00'),
(1260, 11, '3.00', 20, '6000.00'),
(1261, 11, '5.00', 20, '10000.00'),
(1262, 11, '10.00', 20, '20000.00'),
(1263, 11, '15.00', 20, '30000.00'),
(1264, 11, '1.00', 25, '900.00'),
(1265, 11, '1.50', 25, '1350.00'),
(1266, 11, '2.00', 25, '1800.00'),
(1267, 11, '3.00', 25, '2700.00'),
(1268, 11, '5.00', 25, '4500.00'),
(1269, 11, '10.00', 25, '9000.00'),
(1270, 11, '15.00', 25, '13500.00'),
(1271, 11, '20.00', 25, '18000.00'),
(1272, 11, '25.00', 25, '22500.00'),
(1273, 11, '34.00', 25, '30000.00'),
(1274, 11, '1.00', 30, '320.00'),
(1275, 11, '1.50', 30, '480.00'),
(1276, 11, '2.00', 30, '640.00'),
(1277, 11, '3.00', 30, '960.00'),
(1278, 11, '5.00', 30, '1600.00'),
(1279, 11, '10.00', 30, '3200.00'),
(1280, 11, '15.00', 30, '4800.00'),
(1281, 11, '20.00', 30, '6400.00'),
(1282, 11, '25.00', 30, '8000.00'),
(1283, 11, '50.00', 30, '16000.00'),
(1284, 11, '94.00', 30, '30000.00'),
(1285, 11, '1.00', 35, '120.00'),
(1286, 11, '1.50', 35, '180.00'),
(1287, 11, '2.00', 35, '240.00'),
(1288, 11, '3.00', 35, '360.00'),
(1289, 11, '5.00', 35, '600.00'),
(1290, 11, '10.00', 35, '1200.00'),
(1291, 11, '15.00', 35, '1800.00'),
(1292, 11, '20.00', 35, '2400.00'),
(1293, 11, '25.00', 35, '3000.00'),
(1294, 11, '50.00', 35, '6000.00'),
(1295, 11, '100.00', 35, '12000.00'),
(1296, 11, '1.00', 40, '65.00'),
(1297, 11, '1.50', 40, '97.50'),
(1298, 11, '2.00', 40, '130.00'),
(1299, 11, '3.00', 40, '195.00'),
(1300, 11, '5.00', 40, '325.00'),
(1301, 11, '10.00', 40, '650.00'),
(1302, 11, '15.00', 40, '975.00'),
(1303, 11, '20.00', 40, '1300.00'),
(1304, 11, '25.00', 40, '1625.00'),
(1305, 11, '50.00', 40, '3250.00'),
(1306, 11, '100.00', 40, '6500.00'),
(1307, 11, '5.00', 45, '160.00'),
(1308, 11, '5.50', 45, '176.00'),
(1309, 11, '10.00', 45, '320.00'),
(1310, 11, '15.00', 45, '480.00'),
(1311, 11, '20.00', 45, '640.00'),
(1312, 11, '25.00', 45, '800.00'),
(1313, 11, '50.00', 45, '1600.00'),
(1314, 11, '100.00', 45, '3200.00'),
(1315, 11, '5.00', 50, '80.00'),
(1316, 11, '5.50', 50, '88.00'),
(1317, 11, '10.00', 50, '160.00'),
(1318, 11, '15.00', 50, '240.00'),
(1319, 11, '20.00', 50, '320.00'),
(1320, 11, '25.00', 50, '400.00'),
(1321, 11, '50.00', 50, '800.00'),
(1322, 11, '100.00', 50, '1600.00'),
(1435, 10, '1.00', 15, '265.00'),
(1436, 10, '1.50', 15, '397.00'),
(1437, 10, '2.00', 15, '530.00'),
(1438, 10, '3.00', 15, '795.00'),
(1439, 10, '5.00', 15, '1325.00'),
(1440, 10, '10.00', 15, '2650.00'),
(1441, 10, '15.00', 15, '3975.00'),
(1442, 10, '20.00', 15, '5300.00'),
(1443, 10, '25.00', 15, '6625.00'),
(1444, 10, '50.00', 15, '13250.00'),
(1445, 10, '100.00', 15, '26500.00'),
(1446, 10, '1.00', 16, '152.00'),
(1447, 10, '1.50', 16, '228.00'),
(1448, 10, '3.00', 16, '456.00'),
(1449, 10, '5.00', 16, '760.00'),
(1450, 10, '10.00', 16, '1520.00'),
(1451, 10, '15.00', 16, '2280.00'),
(1452, 10, '20.00', 16, '3040.00'),
(1453, 10, '25.00', 16, '3800.00'),
(1454, 10, '50.00', 16, '7600.00'),
(1455, 10, '100.00', 16, '15200.00'),
(1456, 10, '1.00', 17, '90.00'),
(1457, 10, '1.50', 17, '135.00'),
(1458, 10, '3.00', 17, '270.00'),
(1459, 10, '5.00', 17, '450.00'),
(1460, 10, '10.00', 17, '900.00'),
(1461, 10, '15.00', 17, '1350.00'),
(1462, 10, '20.00', 17, '1800.00'),
(1463, 10, '25.00', 17, '2250.00'),
(1464, 10, '50.00', 17, '4500.00'),
(1465, 10, '100.00', 17, '9000.00'),
(1466, 10, '1.00', 18, '55.00'),
(1467, 10, '1.50', 18, '82.50'),
(1468, 10, '3.00', 18, '165.00'),
(1469, 10, '5.00', 18, '275.00'),
(1470, 10, '10.00', 18, '550.00'),
(1471, 10, '15.00', 18, '825.00'),
(1472, 10, '20.00', 18, '1100.00'),
(1473, 10, '25.00', 18, '1375.00'),
(1474, 10, '50.00', 18, '2750.00'),
(1475, 10, '100.00', 18, '5500.00'),
(1476, 10, '1.00', 19, '36.00'),
(1477, 10, '1.50', 19, '54.00'),
(1478, 10, '3.00', 19, '108.00'),
(1479, 10, '5.00', 19, '180.00'),
(1480, 10, '10.00', 19, '360.00'),
(1481, 10, '15.00', 19, '540.00'),
(1482, 10, '20.00', 19, '720.00'),
(1483, 10, '25.00', 19, '900.00'),
(1484, 10, '50.00', 19, '1800.00'),
(1485, 10, '100.00', 19, '3600.00'),
(1486, 10, '1.00', 20, '23.00'),
(1487, 10, '1.50', 20, '34.50'),
(1488, 10, '3.00', 20, '69.00'),
(1489, 10, '5.00', 20, '115.00'),
(1490, 10, '10.00', 20, '230.00'),
(1491, 10, '15.00', 20, '345.00'),
(1492, 10, '20.00', 20, '460.00'),
(1493, 10, '25.00', 20, '575.00'),
(1494, 10, '50.00', 20, '1150.00'),
(1495, 10, '100.00', 20, '2300.00'),
(1496, 10, '1.00', 21, '16.00'),
(1497, 10, '1.50', 21, '24.00'),
(1498, 10, '3.00', 21, '48.00'),
(1499, 10, '5.00', 21, '80.00'),
(1500, 10, '10.00', 21, '160.00'),
(1501, 10, '15.00', 21, '240.00'),
(1502, 10, '20.00', 21, '320.00'),
(1503, 10, '25.00', 21, '400.00'),
(1504, 10, '50.00', 21, '800.00'),
(1505, 10, '100.00', 21, '1600.00'),
(1616, 9, '1.00', 20, '800.00'),
(1617, 9, '1.50', 20, '1200.00'),
(1618, 9, '2.00', 20, '1600.00'),
(1619, 9, '3.00', 20, '2400.00'),
(1620, 9, '5.00', 20, '4000.00'),
(1621, 9, '7.00', 20, '5600.00'),
(1622, 9, '10.00', 20, '8000.00'),
(1623, 9, '15.00', 20, '12000.00'),
(1624, 9, '20.00', 20, '16000.00'),
(1625, 9, '25.00', 20, '20000.00'),
(1626, 9, '37.50', 20, '30000.00'),
(1627, 9, '1.00', 25, '167.00'),
(1628, 9, '1.50', 25, '250.50'),
(1629, 9, '2.00', 25, '334.00'),
(1630, 9, '3.00', 25, '501.00'),
(1631, 9, '5.00', 25, '835.00'),
(1632, 9, '7.00', 25, '1169.00'),
(1633, 9, '10.00', 25, '1670.00'),
(1634, 9, '15.00', 25, '2505.00'),
(1635, 9, '20.00', 25, '3340.00'),
(1636, 9, '25.00', 25, '4175.00'),
(1637, 9, '50.00', 25, '8350.00'),
(1638, 9, '100.00', 25, '16700.00'),
(1639, 9, '1.00', 30, '56.00'),
(1640, 9, '1.50', 30, '84.00'),
(1641, 9, '2.00', 30, '112.00'),
(1642, 9, '3.00', 30, '168.00'),
(1643, 9, '5.00', 30, '280.00'),
(1644, 9, '7.00', 30, '392.00'),
(1645, 9, '10.00', 30, '560.00'),
(1646, 9, '15.00', 30, '840.00'),
(1647, 9, '20.00', 30, '1120.00'),
(1648, 9, '25.00', 30, '1400.00'),
(1649, 9, '50.00', 30, '2800.00'),
(1650, 9, '100.00', 30, '5600.00'),
(1651, 9, '1.00', 35, '22.00'),
(1652, 9, '1.50', 35, '33.00'),
(1653, 9, '2.00', 35, '44.00'),
(1654, 9, '3.00', 35, '66.00'),
(1655, 9, '5.00', 35, '110.00'),
(1656, 9, '7.00', 35, '154.00'),
(1657, 9, '10.00', 35, '220.00'),
(1658, 9, '15.00', 35, '330.00'),
(1659, 9, '20.00', 35, '440.00'),
(1660, 9, '25.00', 35, '550.00'),
(1661, 9, '50.00', 35, '1100.00'),
(1662, 9, '100.00', 35, '2200.00'),
(1663, 9, '5.00', 40, '45.00'),
(1664, 9, '5.50', 40, '49.50'),
(1665, 9, '10.00', 40, '90.00'),
(1666, 9, '15.00', 40, '135.00'),
(1667, 9, '20.00', 40, '180.00'),
(1668, 9, '25.00', 40, '225.00'),
(1669, 9, '50.00', 40, '450.00'),
(1670, 9, '100.00', 40, '900.00'),
(1671, 9, '5.00', 45, '15.00'),
(1672, 9, '5.50', 45, '16.50'),
(1673, 9, '10.00', 45, '30.00'),
(1674, 9, '15.00', 45, '45.00'),
(1675, 9, '20.00', 45, '60.00'),
(1676, 9, '25.00', 45, '75.00'),
(1677, 9, '50.00', 45, '150.00'),
(1678, 9, '100.00', 45, '300.00'),
(1792, 8, '1.00', 20, '800.00'),
(1793, 8, '1.50', 20, '1200.00'),
(1794, 8, '2.00', 20, '1600.00'),
(1795, 8, '3.00', 20, '2400.00'),
(1796, 8, '5.00', 20, '4000.00'),
(1797, 8, '10.00', 20, '8000.00'),
(1798, 8, '15.00', 20, '12000.00'),
(1799, 8, '20.00', 20, '16000.00'),
(1800, 8, '25.00', 20, '20000.00'),
(1801, 8, '37.50', 20, '30000.00'),
(1802, 8, '1.00', 25, '260.00'),
(1803, 8, '1.50', 25, '390.00'),
(1804, 8, '2.00', 25, '520.00'),
(1805, 8, '3.00', 25, '780.00'),
(1806, 8, '5.00', 25, '1300.00'),
(1807, 8, '10.00', 25, '2600.00'),
(1808, 8, '15.00', 25, '3900.00'),
(1809, 8, '20.00', 25, '5200.00'),
(1810, 8, '25.00', 25, '6500.00'),
(1811, 8, '50.00', 25, '13000.00'),
(1812, 8, '100.00', 25, '26000.00'),
(1813, 8, '1.00', 30, '115.00'),
(1814, 8, '1.50', 30, '172.50'),
(1815, 8, '2.00', 30, '230.00'),
(1816, 8, '3.00', 30, '345.00'),
(1817, 8, '5.00', 30, '575.00'),
(1818, 8, '10.00', 30, '1150.00'),
(1819, 8, '15.00', 30, '1725.00'),
(1820, 8, '20.00', 30, '2300.00'),
(1821, 8, '25.00', 30, '2875.00'),
(1822, 8, '50.00', 30, '5750.00'),
(1823, 8, '100.00', 30, '11500.00'),
(1824, 8, '1.00', 35, '55.00'),
(1825, 8, '1.50', 35, '82.50'),
(1826, 8, '2.00', 35, '110.00'),
(1827, 8, '3.00', 35, '165.00'),
(1828, 8, '5.00', 35, '275.00'),
(1829, 8, '10.00', 35, '550.00'),
(1830, 8, '15.00', 35, '825.00'),
(1831, 8, '20.00', 35, '1100.00'),
(1832, 8, '25.00', 35, '1375.00'),
(1833, 8, '50.00', 35, '2750.00'),
(1834, 8, '100.00', 35, '5500.00'),
(1835, 8, '1.00', 40, '26.00'),
(1836, 8, '1.50', 40, '39.00'),
(1837, 8, '2.00', 40, '52.00'),
(1838, 8, '3.00', 40, '78.00'),
(1839, 8, '5.00', 40, '130.00'),
(1840, 8, '10.00', 40, '260.00'),
(1841, 8, '15.00', 40, '390.00'),
(1842, 8, '20.00', 40, '520.00'),
(1843, 8, '25.00', 40, '650.00'),
(1844, 8, '50.00', 40, '1300.00'),
(1845, 8, '100.00', 40, '2600.00'),
(1846, 8, '5.00', 45, '65.00'),
(1847, 8, '10.00', 45, '130.00'),
(1848, 8, '15.00', 45, '195.00'),
(1849, 8, '20.00', 45, '260.00'),
(1850, 8, '25.00', 45, '325.00'),
(1851, 8, '35.00', 45, '585.00'),
(1852, 8, '50.00', 45, '650.00'),
(1853, 8, '100.00', 45, '1300.00'),
(1854, 8, '5.00', 50, '25.00'),
(1855, 8, '5.50', 50, '27.00'),
(1909, 6, '1.00', 55, '15000.00'),
(1910, 6, '1.50', 55, '22500.00'),
(1911, 6, '2.00', 55, '30000.00'),
(1912, 6, '1.00', 60, '10000.00'),
(1913, 6, '1.50', 60, '15000.00'),
(1914, 6, '2.00', 60, '20000.00'),
(1915, 6, '2.50', 60, '25000.00'),
(1916, 6, '3.00', 60, '30000.00'),
(1917, 6, '1.00', 65, '2000.00'),
(1918, 6, '1.50', 65, '3000.00'),
(1919, 6, '2.00', 65, '4000.00'),
(1920, 6, '2.50', 65, '5000.00'),
(1921, 6, '3.00', 65, '6000.00'),
(1922, 6, '5.00', 65, '10000.00'),
(1923, 6, '7.00', 65, '14000.00'),
(1924, 6, '10.00', 65, '20000.00'),
(1925, 6, '15.00', 65, '30000.00'),
(1926, 6, '1.00', 70, '520.00'),
(1927, 6, '1.50', 70, '780.00'),
(1928, 6, '2.00', 70, '1040.00'),
(1929, 6, '3.00', 70, '1560.00'),
(1930, 6, '5.00', 70, '2600.00'),
(1931, 6, '7.00', 70, '3640.00'),
(1932, 6, '10.00', 70, '5200.00'),
(1933, 6, '15.00', 70, '7800.00'),
(1934, 6, '20.00', 70, '10400.00'),
(1935, 6, '25.00', 70, '13000.00'),
(1936, 6, '50.00', 70, '26000.00'),
(1937, 6, '58.00', 70, '30000.00'),
(1938, 6, '1.00', 75, '280.00'),
(1939, 6, '1.50', 75, '420.00'),
(1940, 6, '2.00', 75, '560.00'),
(1941, 6, '3.00', 75, '840.00'),
(1942, 6, '5.00', 75, '1400.00'),
(1943, 6, '7.00', 75, '1960.00'),
(1944, 6, '10.00', 75, '2800.00'),
(1945, 6, '15.00', 75, '4200.00'),
(1946, 6, '20.00', 75, '5600.00'),
(1947, 6, '25.00', 75, '7000.00'),
(1948, 6, '50.00', 75, '14000.00'),
(1949, 6, '100.00', 75, '28000.00'),
(1950, 6, '1.00', 80, '77.00'),
(1951, 6, '1.50', 80, '115.00'),
(1952, 6, '2.00', 80, '154.00'),
(1953, 6, '3.00', 80, '231.00'),
(1954, 6, '5.00', 80, '385.00'),
(1955, 6, '7.00', 80, '539.00'),
(1956, 6, '10.00', 80, '770.00'),
(1957, 6, '15.00', 80, '1155.00'),
(1958, 6, '20.00', 80, '1540.00'),
(1959, 6, '25.00', 80, '1925.00'),
(1960, 6, '50.00', 80, '3850.00'),
(1961, 6, '100.00', 80, '7700.00'),
(1962, 6, '5.00', 85, '75.00'),
(1963, 6, '5.50', 85, '82.50'),
(1964, 6, '10.00', 85, '150.00'),
(1965, 6, '15.00', 85, '225.00'),
(1966, 6, '20.00', 85, '300.00'),
(1967, 6, '25.00', 85, '375.00'),
(1968, 6, '50.00', 85, '750.00'),
(1969, 6, '100.00', 85, '1150.00'),
(2021, 3, '1.00', 17, '7000.00'),
(2022, 3, '1.50', 17, '10500.00'),
(2023, 3, '2.00', 17, '14000.00'),
(2024, 3, '2.50', 17, '17500.00'),
(2025, 3, '3.00', 17, '21000.00'),
(2026, 3, '3.50', 17, '24500.00'),
(2027, 3, '4.00', 17, '28000.00'),
(2028, 3, '4.30', 17, '30000.00'),
(2029, 3, '1.00', 18, '1500.00'),
(2030, 3, '1.50', 18, '2250.00'),
(2031, 3, '2.00', 18, '3000.00'),
(2032, 3, '3.00', 18, '4500.00'),
(2033, 3, '5.00', 18, '7500.00'),
(2034, 3, '7.00', 18, '10500.00'),
(2035, 3, '10.00', 18, '15000.00'),
(2036, 3, '15.00', 18, '22500.00'),
(2037, 3, '20.00', 18, '30000.00'),
(2038, 3, '1.00', 19, '600.00'),
(2039, 3, '1.50', 19, '900.00'),
(2040, 3, '2.00', 19, '1200.00'),
(2041, 3, '3.00', 19, '1800.00'),
(2042, 3, '5.00', 19, '3000.00'),
(2043, 3, '7.00', 19, '4200.00'),
(2044, 3, '10.00', 19, '6000.00'),
(2045, 3, '15.00', 19, '9000.00'),
(2046, 3, '20.00', 19, '12000.00'),
(2047, 3, '25.00', 19, '15000.00'),
(2048, 3, '50.00', 19, '30000.00'),
(2049, 3, '1.00', 20, '140.00'),
(2050, 3, '1.50', 20, '210.00'),
(2051, 3, '2.00', 20, '280.00'),
(2052, 3, '3.00', 20, '420.00'),
(2053, 3, '5.00', 20, '700.00'),
(2054, 3, '7.00', 20, '980.00'),
(2055, 3, '10.00', 20, '1400.00'),
(2056, 3, '25.00', 20, '3500.00'),
(2057, 3, '50.00', 20, '7000.00'),
(2058, 3, '100.00', 20, '14000.00'),
(2059, 3, '1.00', 21, '50.00'),
(2060, 3, '1.50', 21, '75.00'),
(2061, 3, '2.00', 21, '100.00'),
(2062, 3, '3.00', 21, '150.00'),
(2063, 3, '5.00', 21, '250.00'),
(2064, 3, '7.00', 21, '350.00'),
(2065, 3, '10.00', 21, '500.00'),
(2066, 3, '15.00', 21, '750.00'),
(2067, 3, '20.00', 21, '1000.00'),
(2068, 3, '25.00', 21, '1250.00'),
(2069, 3, '50.00', 21, '2500.00'),
(2070, 3, '100.00', 21, '5000.00'),
(2071, 3, '1.00', 22, '13.00'),
(2072, 3, '1.50', 22, '19.50'),
(2073, 3, '2.00', 22, '26.00'),
(2074, 3, '3.00', 22, '39.00'),
(2075, 3, '5.00', 22, '65.00'),
(2076, 3, '7.00', 22, '91.00'),
(2077, 3, '10.00', 22, '130.00'),
(2078, 3, '15.00', 22, '195.00'),
(2079, 3, '20.00', 22, '260.00'),
(2080, 3, '25.00', 22, '325.00'),
(2081, 3, '50.00', 22, '650.00'),
(2082, 3, '100.00', 22, '1300.00'),
(2083, 3, '5.00', 23, '25.00'),
(2084, 3, '10.00', 23, '50.00'),
(2085, 3, '25.00', 23, '125.00'),
(2086, 3, '50.00', 23, '250.00'),
(2087, 3, '100.00', 23, '500.00');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `apostas`
--
ALTER TABLE `apostas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `apostas_ibfk_2` (`tipo_jogo_id`),
  ADD KEY `revendedor_id` (`revendedor_id`);

--
-- Índices para tabela `apostas_importadas`
--
ALTER TABLE `apostas_importadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `revendedor_id` (`revendedor_id`);

--
-- Índices para tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `configuracoes_apostas`
--
ALTER TABLE `configuracoes_apostas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo_jogo_id` (`tipo_jogo_id`);

--
-- Índices para tabela `ganhadores`
--
ALTER TABLE `ganhadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resultado_id` (`resultado_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `aposta_id` (`aposta_id`);

--
-- Índices para tabela `jogos`
--
ALTER TABLE `jogos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `resultados`
--
ALTER TABLE `resultados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jogo_id` (`tipo_jogo_id`);

--
-- Índices para tabela `tipos_jogos`
--
ALTER TABLE `tipos_jogos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `trips`
--
ALTER TABLE `trips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `passenger_id` (`passenger_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `revendedor_id` (`revendedor_id`);

--
-- Índices para tabela `valores_jogos`
--
ALTER TABLE `valores_jogos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jogo_dezenas` (`jogo_id`,`dezenas`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `apostas`
--
ALTER TABLE `apostas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `apostas_importadas`
--
ALTER TABLE `apostas_importadas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=382;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `configuracoes_apostas`
--
ALTER TABLE `configuracoes_apostas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `ganhadores`
--
ALTER TABLE `ganhadores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `jogos`
--
ALTER TABLE `jogos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `resultados`
--
ALTER TABLE `resultados`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `tipos_jogos`
--
ALTER TABLE `tipos_jogos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `valores_jogos`
--
ALTER TABLE `valores_jogos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2088;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `apostas`
--
ALTER TABLE `apostas`
  ADD CONSTRAINT `apostas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `apostas_ibfk_3` FOREIGN KEY (`revendedor_id`) REFERENCES `usuarios` (`id`);

--
-- Limitadores para a tabela `apostas_importadas`
--
ALTER TABLE `apostas_importadas`
  ADD CONSTRAINT `apostas_importadas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `apostas_importadas_ibfk_2` FOREIGN KEY (`revendedor_id`) REFERENCES `usuarios` (`id`);

--
-- Limitadores para a tabela `configuracoes_apostas`
--
ALTER TABLE `configuracoes_apostas`
  ADD CONSTRAINT `configuracoes_apostas_ibfk_1` FOREIGN KEY (`tipo_jogo_id`) REFERENCES `tipos_jogos` (`id`);

--
-- Limitadores para a tabela `ganhadores`
--
ALTER TABLE `ganhadores`
  ADD CONSTRAINT `ganhadores_ibfk_1` FOREIGN KEY (`resultado_id`) REFERENCES `resultados` (`id`),
  ADD CONSTRAINT `ganhadores_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `ganhadores_ibfk_3` FOREIGN KEY (`aposta_id`) REFERENCES `apostas` (`id`);

--
-- Limitadores para a tabela `trips`
--
ALTER TABLE `trips`
  ADD CONSTRAINT `trips_ibfk_1` FOREIGN KEY (`passenger_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `trips_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`);

--
-- Limitadores para a tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`revendedor_id`) REFERENCES `usuarios` (`id`);

--
-- Limitadores para a tabela `valores_jogos`
--
ALTER TABLE `valores_jogos`
  ADD CONSTRAINT `valores_jogos_ibfk_1` FOREIGN KEY (`jogo_id`) REFERENCES `jogos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
