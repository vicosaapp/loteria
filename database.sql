-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS loteria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE loteria;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    whatsapp VARCHAR(20),
    tipo ENUM('admin', 'usuario') DEFAULT 'usuario',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de apostas
CREATE TABLE IF NOT EXISTS apostas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    numeros VARCHAR(100) NOT NULL,
    status ENUM('pendente', 'aprovada', 'rejeitada') DEFAULT 'pendente',
    comprovante_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Inserir usuário admin padrão (senha: admin123)
INSERT INTO usuarios (nome, email, senha, tipo) VALUES 
('Administrador', 'admin@sistema.com', '$2y$10$8tDjcKhUE3FBxQwYY5d6UOXlxqnQI9.WHhS/cqWm3Ot1wHhQwgqVi', 'admin'); 