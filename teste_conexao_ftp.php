<?php
// Configurações FTP
$ftp_server = "lotominas.site";
$ftp_user = "patto200";
$ftp_pass = "patto200";
$ftp_port = 21;

// Arquivo de teste
$local_file = "teste_ftp_local.txt";
$remote_file = "teste_ftp_remoto.txt";

// Criar arquivo local para teste
file_put_contents($local_file, "Teste de upload FTP: " . date('Y-m-d H:i:s'));

echo "Iniciando teste de conexão FTP...<br>";

// Conectar ao servidor FTP
$conn_id = ftp_connect($ftp_server, $ftp_port, 30);
if (!$conn_id) {
    echo "Falha ao conectar ao servidor FTP!<br>";
    exit;
}

echo "Conectado ao servidor FTP.<br>";

// Login
$login_result = ftp_login($conn_id, $ftp_user, $ftp_pass);
if (!$login_result) {
    echo "Falha no login FTP!<br>";
    ftp_close($conn_id);
    exit;
}

echo "Login FTP bem-sucedido.<br>";

// Ativar modo passivo
ftp_pasv($conn_id, true);
echo "Modo passivo ativado.<br>";

// Listar diretório atual
echo "Conteúdo do diretório atual:<br>";
$contents = ftp_nlist($conn_id, ".");
foreach ($contents as $file) {
    echo "- $file<br>";
}

// Tentar fazer upload
$upload = ftp_put($conn_id, $remote_file, $local_file, FTP_ASCII);
if (!$upload) {
    echo "Falha no upload do arquivo!<br>";
} else {
    echo "Arquivo enviado com sucesso!<br>";
}

// Fechar conexão
ftp_close($conn_id);
echo "Conexão FTP fechada.<br>";

// Limpar arquivo local
unlink($local_file);
?> 