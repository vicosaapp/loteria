# Guia de Configuração do SFTP no Cursor IDE

Este guia explica como configurar o Cursor IDE para fazer upload automático de arquivos para o servidor APanel via SFTP/FTP.

## 1. Configuração do SFTP

### 1.1. Criar o arquivo de configuração

Crie um arquivo chamado `.vscode/sftp.json` na raiz do seu projeto com o seguinte conteúdo:

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

### 1.2. Verificar a estrutura de diretórios

Certifique-se de que a estrutura de diretórios esteja correta:

```
seu-projeto/
  ├── .vscode/
  │   └── sftp.json
  ├── ... (outros arquivos e diretórios)
```

## 2. Testando a Configuração

### 2.1. Criar um arquivo de teste

Crie um arquivo de teste chamado `teste_sftp_cursor.php` com o seguinte conteúdo:

```php
<?php
// Arquivo de teste para verificar o upload automático via SFTP no Cursor IDE
echo "<h1>Teste de Upload Automático via SFTP</h1>";
echo "<p>Este arquivo foi criado para testar o upload automático via SFTP no Cursor IDE.</p>";
echo "<p>Data e hora: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Versão: 1.0</p>";
echo "<p>ID único para evitar cache: " . uniqid() . "</p>";
?>
```

### 2.2. Salvar o arquivo

Salve o arquivo no Cursor IDE pressionando `Ctrl+S` (ou `Cmd+S` no Mac).

### 2.3. Verificar o upload

Acesse o arquivo no navegador:

```
http://lotominas.site/teste_sftp_cursor.php
```

Se o arquivo for exibido corretamente, a configuração do SFTP está funcionando.

## 3. Solução de Problemas

### 3.1. Verificar as credenciais FTP

Certifique-se de que as credenciais FTP estão corretas:

- **Host**: 217.196.61.30
- **Usuário**: patto200
- **Senha**: patto200
- **Porta**: 21
- **Caminho remoto**: /lotominas.site/

### 3.2. Verificar o caminho remoto

Certifique-se de que o caminho remoto está correto. Você pode verificar o caminho correto usando o FileZilla.

### 3.3. Verificar o modo passivo

Alguns servidores FTP requerem modo passivo. Certifique-se de que a opção `"passive": true` está definida no arquivo de configuração.

### 3.4. Verificar os logs

Se o upload não estiver funcionando, verifique os logs do Cursor IDE:

1. Pressione `Ctrl+Shift+P` (ou `Cmd+Shift+P` no Mac) para abrir a paleta de comandos
2. Digite "Developer: Toggle Developer Tools" e pressione Enter
3. Vá para a aba "Console" para ver os logs

### 3.5. Testar com o FileZilla

Se o Cursor IDE não estiver funcionando, teste a conexão FTP com o FileZilla para verificar se as credenciais e o caminho estão corretos.

## 4. Fluxo de Trabalho Recomendado

1. **Desenvolva localmente**: Faça alterações nos arquivos localmente no Cursor IDE
2. **Salve os arquivos**: Pressione `Ctrl+S` (ou `Cmd+S` no Mac) para salvar os arquivos
3. **Verificação automática**: Os arquivos serão enviados automaticamente para o servidor
4. **Teste no navegador**: Acesse o site para verificar as alterações

## 5. Recursos Adicionais

- [Documentação do SFTP no VS Code](https://marketplace.visualstudio.com/items?itemName=liximomo.sftp)
- [Guia do APanel para FTP](https://www.aapanel.com/new/ftp.html)
- [Melhores práticas de segurança para FTP](https://www.aapanel.com/new/security.html) 