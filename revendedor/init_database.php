<?php
require_once '../config/database.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Inicialização de Banco de Dados</h1>";

// Verificar conexão
echo "<h2>Teste de Conexão</h2>";
try {
    $pdo->query("SELECT 1");
    echo "<p style='color:green'>✓ Conexão com o banco de dados bem-sucedida</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro na conexão: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Criar tabela de usuários
echo "<h2>Tabela 'usuarios'</h2>";
try {
    $tables = $pdo->query("SHOW TABLES LIKE 'usuarios'")->fetchAll();
    if (count($tables) === 0) {
        echo "<p>Tabela 'usuarios' não existe. Criando...</p>";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS `usuarios` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `nome` varchar(100) NOT NULL,
          `email` varchar(100) NOT NULL,
          `senha` varchar(255) NOT NULL,
          `tipo` enum('admin','revendedor','apostador') NOT NULL,
          `telefone` varchar(20) DEFAULT NULL,
          `whatsapp` varchar(20) DEFAULT NULL,
          `revendedor_id` int(11) DEFAULT NULL,
          `comissao` decimal(5,2) DEFAULT '0.00',
          `saldo` decimal(10,2) DEFAULT '0.00',
          `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✓ Tabela 'usuarios' criada com sucesso</p>";
        
        // Criar um revendedor de teste se não existir nenhum
        $revendedores = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'revendedor'")->fetchColumn();
        if ($revendedores === 0) {
            echo "<p>Nenhum revendedor encontrado. Criando revendedor padrão...</p>";
            
            $nome = "Revendedor Teste";
            $email = "revendedor@teste.com";
            $senha = password_hash("123456", PASSWORD_DEFAULT);
            $tipo = "revendedor";
            
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nome, email, senha, tipo, comissao) 
                VALUES (?, ?, ?, ?, 10.00)
            ");
            $stmt->execute([$nome, $email, $senha, $tipo]);
            
            $revendedor_id = $pdo->lastInsertId();
            echo "<p style='color:green'>✓ Revendedor padrão criado (ID: $revendedor_id)</p>";
            echo "<p>Credenciais: Email: revendedor@teste.com / Senha: 123456</p>";
        } else {
            echo "<p style='color:blue'>ℹ Revendedores já existem no sistema</p>";
        }
    } else {
        echo "<p style='color:blue'>ℹ Tabela 'usuarios' já existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro ao criar tabela 'usuarios': " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Criar tabela de jogos
echo "<h2>Tabela 'jogos'</h2>";
try {
    $tables = $pdo->query("SHOW TABLES LIKE 'jogos'")->fetchAll();
    if (count($tables) === 0) {
        echo "<p>Tabela 'jogos' não existe. Criando...</p>";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS `jogos` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `nome` varchar(100) NOT NULL,
          `descricao` text,
          `valor_minimo` decimal(10,2) NOT NULL DEFAULT '1.00',
          `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
          `tipo_jogo` varchar(50) NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✓ Tabela 'jogos' criada com sucesso</p>";
        
        // Inserir jogos de exemplo
        $jogos = [
            ['Mega Sena', 'Jogo da Mega Sena', 'mega_sena'],
            ['Lotofácil', 'Jogo da Lotofácil', 'lotofacil'],
            ['Quina', 'Jogo da Quina', 'quina']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO jogos (nome, descricao, tipo_jogo) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($jogos as $jogo) {
            $stmt->execute($jogo);
        }
        
        echo "<p style='color:green'>✓ Jogos de exemplo inseridos</p>";
    } else {
        echo "<p style='color:blue'>ℹ Tabela 'jogos' já existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro ao criar tabela 'jogos': " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Criar tabela de apostas
echo "<h2>Tabela 'apostas'</h2>";
try {
    $tables = $pdo->query("SHOW TABLES LIKE 'apostas'")->fetchAll();
    if (count($tables) === 0) {
        echo "<p>Tabela 'apostas' não existe. Criando...</p>";
        
        $sql = "
        CREATE TABLE IF NOT EXISTS `apostas` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `usuario_id` int(11) NOT NULL,
          `tipo_jogo_id` int(11) NOT NULL,
          `numeros` varchar(255) NOT NULL,
          `valor_aposta` decimal(10,2) NOT NULL,
          `valor_comissao` decimal(10,2) DEFAULT '0.00',
          `status` enum('pendente','aprovada','rejeitada') NOT NULL DEFAULT 'aprovada',
          `resultado` enum('aguardando','ganhou','perdeu') NOT NULL DEFAULT 'aguardando',
          `valor_premio` decimal(10,2) DEFAULT '0.00',
          `concurso` varchar(50) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `usuario_id` (`usuario_id`),
          KEY `tipo_jogo_id` (`tipo_jogo_id`),
          CONSTRAINT `apostas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
          CONSTRAINT `apostas_ibfk_2` FOREIGN KEY (`tipo_jogo_id`) REFERENCES `jogos` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✓ Tabela 'apostas' criada com sucesso</p>";
    } else {
        echo "<p style='color:blue'>ℹ Tabela 'apostas' já existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro ao criar tabela 'apostas': " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Conclusão</h2>";
echo "<p>Inicialização do banco de dados concluída. <a href='check_database.php'>Verificar banco de dados</a></p>";
echo "<p><a href='dashboard.php'>Ir para o Dashboard</a></p>";
?> 