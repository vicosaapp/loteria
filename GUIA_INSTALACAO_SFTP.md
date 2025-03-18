# Guia de Instalação da Extensão SFTP no Cursor IDE

Este guia explica como instalar e configurar a extensão SFTP no Cursor IDE para fazer upload automático de arquivos para o servidor.

## 1. Instalar a Extensão SFTP

1. Abra o Cursor IDE
2. Pressione `Ctrl+Shift+X` (ou `Cmd+Shift+X` no Mac) para abrir o painel de extensões
3. Na barra de pesquisa, digite "SFTP"
4. Procure pela extensão "SFTP" desenvolvida por "liximomo"
5. Clique no botão "Instalar"
6. Aguarde a instalação ser concluída
7. Reinicie o Cursor IDE quando solicitado

## 2. Verificar a Configuração

O arquivo `.vscode/sftp.json` já foi criado com as configurações corretas:

```json
{
    "name": "Servidor Lotominas",
    "host": "217.196.61.30",
    "protocol": "ftp",
    "port": 21,
    "username": "patto200",
    "password": "patto200",
    "remotePath": "/lotominas.site/",
    "uploadOnSave": true,
    "ignore": [
        ".vscode",
        ".git",
        ".DS_Store",
        "node_modules",
        "*.log"
    ],
    "watcher": {
        "files": "**/*",
        "autoUpload": true,
        "autoDelete": false
    },
    "concurrency": 1,
    "passive": true,
    "connectTimeout": 10000,
    "debug": true
}
```

## 3. Testar o Upload Automático

1. Abra o arquivo `teste_sftp_configurado.php` no Cursor IDE
2. Faça uma pequena alteração (por exemplo, altere a versão de 1.0 para 1.1)
3. Salve o arquivo pressionando `Ctrl+S` (ou `Cmd+S` no Mac)
4. Acesse `http://lotominas.site/teste_sftp_configurado.php` no navegador
5. Verifique se a alteração foi aplicada

## 4. Verificar os Logs do SFTP

Se o upload automático não estiver funcionando, você pode verificar os logs do SFTP:

1. Pressione `Ctrl+Shift+P` (ou `Cmd+Shift+P` no Mac) para abrir a paleta de comandos
2. Digite "SFTP: Open Log" e pressione Enter
3. Observe os logs para ver se há alguma tentativa de upload ou erro

## 5. Testar o Upload Manual

Você também pode testar o upload manual:

1. Pressione `Ctrl+Shift+P` (ou `Cmd+Shift+P` no Mac) para abrir a paleta de comandos
2. Digite "SFTP: Upload" e pressione Enter
3. Selecione o arquivo `teste_sftp_configurado.php`
4. Acesse `http://lotominas.site/teste_sftp_configurado.php` no navegador
5. Verifique se o arquivo foi enviado para o servidor

## 6. Solução de Problemas

Se você continuar enfrentando problemas:

1. Verifique se a extensão SFTP está instalada corretamente
2. Verifique se o arquivo `.vscode/sftp.json` está configurado corretamente
3. Verifique se as credenciais FTP estão corretas
4. Verifique se o caminho remoto está correto
5. Verifique os logs do SFTP para identificar possíveis erros 