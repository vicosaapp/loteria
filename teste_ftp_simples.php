<?php
// Configurações FTP
$ftp_server = "lotominas.site";
$ftp_user = "patto200";
$ftp_pass = "patto200";
$ftp_port = 21;

// Informações do sistema
echo "<h2>Informações do Sistema</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Server Name: " . $_SERVER['SERVER_NAME'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current Script: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";

// Verificar extensões PHP
echo "<h2>Extensões PHP</h2>";
echo "FTP Extension: " . (extension_loaded('ftp') ? 'Disponível' : 'Não disponível') . "<br>";
echo "cURL Extension: " . (extension_loaded('curl') ? 'Disponível' : 'Não disponível') . "<br>";

// Testar conexão FTP local (mesmo servidor)
echo "<h2>Teste de Conexão FTP Local</h2>";
try {
    $conn_id = @ftp_connect($ftp_server, $ftp_port, 5);
    if (!$conn_id) {
        echo "Falha ao conectar ao servidor FTP!<br>";
    } else {
        echo "Conectado ao servidor FTP.<br>";
        
        $login_result = @ftp_login($conn_id, $ftp_user, $ftp_pass);
        if (!$login_result) {
            echo "Falha no login FTP!<br>";
        } else {
            echo "Login FTP bem-sucedido.<br>";
            
            // Ativar modo passivo
            ftp_pasv($conn_id, true);
            echo "Modo passivo ativado.<br>";
            
            // Listar diretório atual
            echo "Conteúdo do diretório atual:<br>";
            $contents = ftp_nlist($conn_id, ".");
            if ($contents === false) {
                echo "Falha ao listar diretório.<br>";
            } else {
                echo "<ul>";
                foreach ($contents as $file) {
                    echo "<li>$file</li>";
                }
                echo "</ul>";
            }
        }
        
        // Fechar conexão
        ftp_close($conn_id);
        echo "Conexão FTP fechada.<br>";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "<br>";
}

// Testar permissões de arquivo
echo "<h2>Teste de Permissões de Arquivo</h2>";
$test_file = "teste_permissao_" . time() . ".txt";
try {
    $content = "Teste de permissões: " . date('Y-m-d H:i:s');
    $result = file_put_contents($test_file, $content);
    
    if ($result === false) {
        echo "Falha ao criar arquivo de teste!<br>";
    } else {
        echo "Arquivo de teste criado com sucesso.<br>";
        echo "Conteúdo: " . htmlspecialchars(file_get_contents($test_file)) . "<br>";
        echo "Permissões: " . substr(sprintf('%o', fileperms($test_file)), -4) . "<br>";
        
        // Tentar excluir o arquivo
        if (unlink($test_file)) {
            echo "Arquivo de teste excluído com sucesso.<br>";
        } else {
            echo "Falha ao excluir arquivo de teste.<br>";
        }
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "<br>";
}
?> 