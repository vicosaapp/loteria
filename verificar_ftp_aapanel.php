<?php
// Script para verificar as configurações FTP do APanel

echo "<h1>Verificação de FTP do APanel</h1>";

// Informações do sistema
echo "<h2>Informações do Sistema</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li><strong>Server Name:</strong> " . $_SERVER['SERVER_NAME'] . "</li>";
echo "<li><strong>Server IP:</strong> " . $_SERVER['SERVER_ADDR'] . "</li>";
echo "<li><strong>Remote IP:</strong> " . $_SERVER['REMOTE_ADDR'] . "</li>";
echo "</ul>";

// Verificar se o FTP está instalado
echo "<h2>Verificação do FTP</h2>";

if (function_exists('ftp_connect')) {
    echo "<p style='color: green;'>✅ Função FTP está disponível no PHP.</p>";
} else {
    echo "<p style='color: red;'>❌ Função FTP não está disponível no PHP. Verifique se a extensão FTP está instalada.</p>";
}

// Testar conexão FTP local
echo "<h2>Teste de Conexão FTP Local</h2>";

$ftp_server = "127.0.0.1";
$ftp_user = "patto200";
$ftp_pass = "patto200";
$ftp_port = 21;

echo "<p>Tentando conectar ao servidor FTP local ($ftp_server)...</p>";

$conn_id = @ftp_connect($ftp_server, $ftp_port, 5);
if (!$conn_id) {
    echo "<p style='color: red;'>❌ Falha ao conectar ao servidor FTP local. Isso pode ser normal se o FTP não estiver configurado para aceitar conexões locais.</p>";
} else {
    echo "<p style='color: green;'>✅ Conectado ao servidor FTP local.</p>";
    
    // Tentar login
    $login_result = @ftp_login($conn_id, $ftp_user, $ftp_pass);
    if (!$login_result) {
        echo "<p style='color: red;'>❌ Falha no login FTP local. Verifique as credenciais.</p>";
    } else {
        echo "<p style='color: green;'>✅ Login FTP local bem-sucedido.</p>";
        
        // Listar diretório
        echo "<p>Tentando listar o diretório...</p>";
        $contents = @ftp_nlist($conn_id, ".");
        if ($contents === false) {
            echo "<p style='color: red;'>❌ Falha ao listar diretório.</p>";
        } else {
            echo "<p style='color: green;'>✅ Listagem de diretório bem-sucedida.</p>";
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
}

// Testar conexão FTP remota
echo "<h2>Teste de Conexão FTP Remota</h2>";

$ftp_server = "217.196.61.30";
$ftp_user = "patto200";
$ftp_pass = "patto200";
$ftp_port = 21;

echo "<p>Tentando conectar ao servidor FTP remoto ($ftp_server)...</p>";

$conn_id = @ftp_connect($ftp_server, $ftp_port, 5);
if (!$conn_id) {
    echo "<p style='color: red;'>❌ Falha ao conectar ao servidor FTP remoto.</p>";
} else {
    echo "<p style='color: green;'>✅ Conectado ao servidor FTP remoto.</p>";
    
    // Tentar login
    $login_result = @ftp_login($conn_id, $ftp_user, $ftp_pass);
    if (!$login_result) {
        echo "<p style='color: red;'>❌ Falha no login FTP remoto. Verifique as credenciais.</p>";
    } else {
        echo "<p style='color: green;'>✅ Login FTP remoto bem-sucedido.</p>";
        
        // Ativar modo passivo
        ftp_pasv($conn_id, true);
        echo "<p>Modo passivo ativado.</p>";
        
        // Listar diretório
        echo "<p>Tentando listar o diretório...</p>";
        $contents = @ftp_nlist($conn_id, ".");
        if ($contents === false) {
            echo "<p style='color: red;'>❌ Falha ao listar diretório.</p>";
        } else {
            echo "<p style='color: green;'>✅ Listagem de diretório bem-sucedida.</p>";
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
        
        // Verificar diretório específico
        $target_dir = "/www/wwwroot/lotominas.site/";
        echo "<p>Tentando verificar o diretório específico: $target_dir</p>";
        
        $result = @ftp_chdir($conn_id, $target_dir);
        if (!$result) {
            echo "<p style='color: red;'>❌ Falha ao acessar o diretório específico. Verifique se o caminho está correto.</p>";
        } else {
            echo "<p style='color: green;'>✅ Diretório específico acessado com sucesso.</p>";
            
            // Listar diretório específico
            $contents = @ftp_nlist($conn_id, ".");
            if ($contents === false) {
                echo "<p style='color: red;'>❌ Falha ao listar diretório específico.</p>";
            } else {
                echo "<p style='color: green;'>✅ Listagem de diretório específico bem-sucedida.</p>";
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
    }
    
    // Fechar conexão
    ftp_close($conn_id);
}

// Verificar configurações do APanel
echo "<h2>Verificação do APanel</h2>";

echo "<p>Para verificar as configurações FTP no APanel, siga estes passos:</p>";
echo "<ol>";
echo "<li>Acesse o painel do APanel</li>";
echo "<li>Vá para 'FTP' ou 'Gerenciamento de FTP'</li>";
echo "<li>Verifique se o usuário 'patto200' existe</li>";
echo "<li>Verifique qual é o diretório raiz configurado para esse usuário</li>";
echo "<li>Verifique se o FTP está ativo e funcionando</li>";
echo "</ol>";

echo "<p>Informações para o GitHub Actions:</p>";
echo "<ul>";
echo "<li><strong>Servidor FTP:</strong> 217.196.61.30</li>";
echo "<li><strong>Usuário FTP:</strong> patto200</li>";
echo "<li><strong>Porta FTP:</strong> 21</li>";
echo "<li><strong>Diretório remoto:</strong> /www/wwwroot/lotominas.site/</li>";
echo "</ul>";

echo "<p>Data e hora da verificação: " . date('Y-m-d H:i:s') . "</p>";
?> 