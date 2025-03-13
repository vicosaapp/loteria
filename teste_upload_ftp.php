<?php
// Script para testar o upload direto via FTP

// Configurações FTP
$ftp_server = "217.196.61.30";
$ftp_user = "patto200";
$ftp_pass = "patto200";
$ftp_port = 21;

// Informações do sistema
echo "<h1>Teste de Upload Direto via FTP</h1>";

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
            
            // Criar arquivo de teste
            $test_content = "Teste de upload FTP direto: " . date('Y-m-d H:i:s') . "\n";
            $test_content .= "ID único: " . uniqid() . "\n";
            $temp_file = tempnam(sys_get_temp_dir(), 'ftp_test');
            file_put_contents($temp_file, $test_content);
            
            // Nome do arquivo no servidor
            $remote_file = "teste_upload_direto_" . date('Ymd_His') . ".txt";
            
            echo "Tentando fazer upload do arquivo '$remote_file'...<br>";
            $upload_result = ftp_put($conn_id, $remote_file, $temp_file, FTP_ASCII);
            
            if (!$upload_result) {
                echo "<p style='color: red;'>❌ Falha ao fazer upload do arquivo.</p>";
            } else {
                echo "<p style='color: green;'>✅ Upload do arquivo bem-sucedido.</p>";
                echo "<p>Arquivo criado no servidor: <strong>$remote_file</strong></p>";
                echo "<p>Conteúdo do arquivo:</p>";
                echo "<pre>" . htmlspecialchars($test_content) . "</pre>";
            }
            
            unlink($temp_file);
            
            // Listar diretório atual
            echo "<h3>Conteúdo do Diretório no Servidor</h3>";
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
        }
        
        // Fechar conexão
        ftp_close($conn_id);
        echo "Conexão FTP fechada.<br>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>Próximos Passos</h2>";
echo "<ol>";
echo "<li>Verifique se o arquivo <strong>$remote_file</strong> foi criado no servidor</li>";
echo "<li>Se o upload direto funcionou, mas o GitHub Actions não, o problema pode estar no workflow</li>";
echo "<li>Verifique os logs do GitHub Actions para identificar o problema</li>";
echo "</ol>";

echo "<p>Data e hora do teste: " . date('Y-m-d H:i:s') . "</p>";
?> 