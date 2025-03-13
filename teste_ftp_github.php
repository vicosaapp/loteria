<?php
// Script para testar a conexão FTP com as mesmas credenciais usadas no GitHub Actions

// Configurações FTP
$ftp_server = "lotominas.site";
$ftp_user = "patto200";
$ftp_pass = "patto200"; // Use a mesma senha configurada no GitHub
$ftp_port = 21;

// Informações do sistema
echo "<h1>Teste de Conexão FTP para GitHub Actions</h1>";

echo "<h2>Informações da Conexão</h2>";
echo "<ul>";
echo "<li><strong>Servidor:</strong> $ftp_server</li>";
echo "<li><strong>Usuário:</strong> $ftp_user</li>";
echo "<li><strong>Porta:</strong> $ftp_port</li>";
echo "</ul>";

// Testar conexão FTP
echo "<h2>Teste de Conexão FTP</h2>";
try {
    // Tentar conectar
    echo "Tentando conectar ao servidor FTP...<br>";
    $conn_id = @ftp_connect($ftp_server, $ftp_port, 10);
    
    if (!$conn_id) {
        echo "<p style='color: red;'>❌ Falha ao conectar ao servidor FTP!</p>";
    } else {
        echo "<p style='color: green;'>✅ Conectado ao servidor FTP.</p>";
        
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
            $test_content = "Teste de upload FTP para GitHub Actions: " . date('Y-m-d H:i:s');
            $temp_file = tempnam(sys_get_temp_dir(), 'ftp_test');
            file_put_contents($temp_file, $test_content);
            
            echo "Tentando fazer upload de um arquivo de teste...<br>";
            $upload_result = ftp_put($conn_id, "teste_github_actions.txt", $temp_file, FTP_ASCII);
            
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

echo "<h2>Recomendações</h2>";
echo "<ol>";
echo "<li>Verifique se as credenciais FTP estão corretas</li>";
echo "<li>Verifique se o servidor FTP permite conexões externas</li>";
echo "<li>Verifique se o servidor FTP tem restrições de IP</li>";
echo "<li>Tente usar FTPS (FTP sobre SSL) se disponível</li>";
echo "</ol>";

echo "<p>Data e hora do teste: " . date('Y-m-d H:i:s') . "</p>";
?> 