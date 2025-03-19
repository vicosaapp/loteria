<?php
require_once '../config/database.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Verificação de Banco de Dados</h1>";

// Verificar conexão
echo "<h2>Teste de Conexão</h2>";
try {
    $pdo->query("SELECT 1");
    echo "<p style='color:green'>✓ Conexão com o banco de dados bem-sucedida</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro na conexão: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Verificar tabela de usuários
echo "<h2>Tabela 'usuarios'</h2>";
try {
    $tables = $pdo->query("SHOW TABLES LIKE 'usuarios'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p style='color:green'>✓ Tabela 'usuarios' existe</p>";
        
        // Verificar campos necessários
        $columns = $pdo->query("DESCRIBE usuarios")->fetchAll(PDO::FETCH_COLUMN);
        $required_columns = ['id', 'nome', 'email', 'senha', 'tipo', 'revendedor_id'];
        
        foreach ($required_columns as $column) {
            if (in_array($column, $columns)) {
                echo "<p style='color:green'>✓ Coluna '$column' existe</p>";
            } else {
                echo "<p style='color:red'>✗ Coluna '$column' não existe</p>";
            }
        }
        
        // Verificar revendedores
        $revendedores = $pdo->query("SELECT id, nome, email FROM usuarios WHERE tipo = 'revendedor'")->fetchAll();
        echo "<p>Número de revendedores: " . count($revendedores) . "</p>";
        if (isset($_SESSION['usuario_id'])) {
            echo "<p>ID do revendedor atual: " . htmlspecialchars($_SESSION['usuario_id']) . "</p>";
            
            // Verificar existência do revendedor
            $stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE id = ? AND tipo = 'revendedor'");
            $stmt->execute([$_SESSION['usuario_id']]);
            $revendedor = $stmt->fetch();
            
            if ($revendedor) {
                echo "<p style='color:green'>✓ Revendedor atual existe: " . htmlspecialchars($revendedor['nome']) . " (" . htmlspecialchars($revendedor['email']) . ")</p>";
            } else {
                echo "<p style='color:red'>✗ Revendedor atual não encontrado</p>";
            }
        } else {
            echo "<p style='color:orange'>⚠ Sessão de revendedor não iniciada</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Tabela 'usuarios' não existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro ao verificar tabela 'usuarios': " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Verificar tabela de apostas
echo "<h2>Tabela 'apostas'</h2>";
try {
    $tables = $pdo->query("SHOW TABLES LIKE 'apostas'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p style='color:green'>✓ Tabela 'apostas' existe</p>";
        
        // Verificar campos necessários
        $columns = $pdo->query("DESCRIBE apostas")->fetchAll(PDO::FETCH_COLUMN);
        $required_columns = ['id', 'usuario_id', 'tipo_jogo_id', 'numeros', 'valor_aposta', 'valor_comissao', 'status', 'created_at'];
        
        foreach ($required_columns as $column) {
            if (in_array($column, $columns)) {
                echo "<p style='color:green'>✓ Coluna '$column' existe</p>";
            } else {
                echo "<p style='color:red'>✗ Coluna '$column' não existe</p>";
            }
        }
    } else {
        echo "<p style='color:red'>✗ Tabela 'apostas' não existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro ao verificar tabela 'apostas': " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Verificar tabela de jogos
echo "<h2>Tabela 'jogos'</h2>";
try {
    $tables = $pdo->query("SHOW TABLES LIKE 'jogos'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p style='color:green'>✓ Tabela 'jogos' existe</p>";
        
        // Verificar campos necessários
        $columns = $pdo->query("DESCRIBE jogos")->fetchAll(PDO::FETCH_COLUMN);
        $required_columns = ['id', 'nome', 'descricao'];
        
        foreach ($required_columns as $column) {
            if (in_array($column, $columns)) {
                echo "<p style='color:green'>✓ Coluna '$column' existe</p>";
            } else {
                echo "<p style='color:red'>✗ Coluna '$column' não existe</p>";
            }
        }
    } else {
        echo "<p style='color:red'>✗ Tabela 'jogos' não existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro ao verificar tabela 'jogos': " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Verificar consultas do dashboard
echo "<h2>Teste de Consultas do Dashboard</h2>";

if (isset($_SESSION['usuario_id'])) {
    $revendedor_id = $_SESSION['usuario_id'];
} else {
    // Tentar obter um ID de revendedor para teste
    $revendedor = $pdo->query("SELECT id FROM usuarios WHERE tipo = 'revendedor' LIMIT 1")->fetch();
    $revendedor_id = $revendedor ? $revendedor['id'] : 0;
    echo "<p style='color:orange'>⚠ Usando revendedor de teste ID: $revendedor_id</p>";
}

try {
    // Testar consulta de clientes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE revendedor_id = ? AND tipo = 'apostador'");
    $stmt->execute([$revendedor_id]);
    $total_clientes = $stmt->fetchColumn();
    echo "<p style='color:green'>✓ Consulta de clientes executada: $total_clientes clientes</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro na consulta de clientes: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    // Testar consulta de apostas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM apostas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE u.revendedor_id = ?
    ");
    $stmt->execute([$revendedor_id]);
    $total_apostas = $stmt->fetchColumn();
    echo "<p style='color:green'>✓ Consulta de apostas executada: $total_apostas apostas</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro na consulta de apostas: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    // Testar consulta de comissões
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor_comissao), 0) 
        FROM apostas a 
        JOIN usuarios u ON a.usuario_id = u.id 
        WHERE u.revendedor_id = ? AND a.status = 'aprovada'
    ");
    $stmt->execute([$revendedor_id]);
    $total_comissoes = $stmt->fetchColumn();
    echo "<p style='color:green'>✓ Consulta de comissões executada: R$ " . number_format((float)$total_comissoes, 2, ',', '.') . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro na consulta de comissões: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    // Testar consulta de apostas recentes
    $stmt = $pdo->prepare("
        SELECT 
            a.id, 
            a.created_at, 
            a.numeros, 
            a.valor_aposta, 
            a.status,
            u.nome as cliente_nome,
            j.nome as jogo_nome
        FROM 
            apostas a 
            JOIN usuarios u ON a.usuario_id = u.id 
            JOIN jogos j ON a.tipo_jogo_id = j.id
        WHERE 
            u.revendedor_id = ?
        ORDER BY 
            a.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$revendedor_id]);
    $apostas_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p style='color:green'>✓ Consulta de apostas recentes executada: " . count($apostas_recentes) . " apostas encontradas</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro na consulta de apostas recentes: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Informações da Sessão</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?> 