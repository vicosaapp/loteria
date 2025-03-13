<?php
// Script para testar a conexão FTP com endereço IPv6

// Configurações FTP
$ftp_server = "2a02:4780:14:4dda::1";
$ftp_user = "patto200";
$ftp_pass = "patto200";
$ftp_port = 21;

// Informações do sistema
echo "<h1>Teste de Conexão FTP com IPv6</h1>";

echo "<h2>Informações da Conexão</h2>";
echo "<ul>";
echo "<li><strong>Servidor:</strong> $ftp_server (IPv6)</li>";
echo "<li><strong>Usuário:</strong> $ftp_user</li>";
echo "<li><strong>Porta:</strong> $ftp_port</li>";
echo "</ul>";

// Verificar suporte a IPv6
echo "<h2>Verificação de Suporte a IPv6</h2>";
if (defined('AF_INET6')) {
    echo "<p style='color: green;'>✅ PHP tem suporte a IPv6 (AF_INET6 está definido).</p>";
} else {
    echo "<p style='color: red;'>❌ PHP não tem suporte a IPv6 (AF_INET6 não está definido).</p>";
}

// Testar conexão FTP
echo "<h2>Teste de Conexão FTP</h2>";
try {
    // Tentar conectar
    echo "Tentando conectar ao servidor FTP (IPv6)...<br>";
    $conn_id = @ftp_connect($ftp_server, $ftp_port, 10);
    
    if (!$conn_id) {
        echo "<p style='color: red;'>❌ Falha ao conectar ao servidor FTP!</p>";
        echo "<p>Tentando conectar usando o nome de domínio...</p>";
        
        $conn_id = @ftp_connect("lotominas.site", $ftp_port, 10);
        if (!$conn_id) {
            echo "<p style='color: red;'>❌ Falha ao conectar usando o nome de domínio também.</p>";
        } else {
            echo "<p style='color: green;'>✅ Conectado ao servidor FTP usando o nome de domínio.</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Conectado ao servidor FTP usando IPv6.</p>";
    }
    
    if ($conn_id) {
        // Tentar login
        echo "Tentando fazer login com o usuário '$ftp_user'...<br>";
        $login_result = @ftp_login($conn_id, $ftp_user, $ftp_pass);
        
        if (!$login_result) {
            echo "<p style='color: red;'>❌ Falha no login FTP! Verifique o nome de usuário e senha.</p>";
        } else {
            echo "<p style='color: green;'>✅ Login FTP bem-sucedido.</p>";
            
            // Ativar modo passivo
            ftp_pasv($conn_id, true);
            echo "Modo passivo ativado.<br>";
            
            // Listar diretório atual
            echo "Conteúdo do diretório atual:<br>";
            $contents = ftp_nlist($conn_id, ".");
            if ($contents === false) {
                echo "<p style='color: red;'>❌ Falha ao listar diretório.</p>";
            } else {
                echo "<ul>";
                $count = 0;
                foreach ($contents as $file) {
                    if ($count < 10) {
                        echo "<li>$file</li>";
                        $count++;
                    } else {
                        echo "<li>... e mais arquivos</li>";
                        break;
                    }
                }
                echo "</ul>";
            }
            
            // Criar arquivo de teste
            $test_content = "Teste de upload FTP com IPv6: " . date('Y-m-d H:i:s');
            $temp_file = tempnam(sys_get_temp_dir(), 'ftp_test');
            file_put_contents($temp_file, $test_content);
            
            echo "Tentando fazer upload de um arquivo de teste...<br>";
            $upload_result = ftp_put($conn_id, "teste_ipv6.txt", $temp_file, FTP_ASCII);
            
            if (!$upload_result) {
                echo "<p style='color: red;'>❌ Falha ao fazer upload do arquivo de teste.</p>";
            } else {
                echo "<p style='color: green;'>✅ Upload do arquivo de teste bem-sucedido.</p>";
            }
            
            unlink($temp_file);
        }
        
        // Fechar conexão
        ftp_close($conn_id);
        echo "Conexão FTP fechada.<br>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>Recomendações para IPv6</h2>";
echo "<ol>";
echo "<li>Verifique se o GitHub Actions suporta conexões FTP via IPv6</li>";
echo "<li>Tente usar o nome de domínio em vez do endereço IPv6</li>";
echo "<li>Verifique se o servidor FTP está configurado para aceitar conexões IPv6 externas</li>";
echo "<li>Configure um endereço IPv4 para o servidor FTP, se possível</li>";
echo "</ol>";

echo "<p>Data e hora do teste: " . date('Y-m-d H:i:s') . "</p>";
?> 