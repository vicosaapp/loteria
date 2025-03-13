# Instruções para Configuração e Diagnóstico do Deploy

## Arquivos de Diagnóstico

Foram criados vários arquivos para diagnosticar problemas com o deploy:

1. **verificar_permissoes_simples.php** - Verifica permissões de arquivos e diretórios
2. **teste_simples.php** - Arquivo simples para testar o deploy
3. **teste_upload_ftp.php** - Testa o upload direto via FTP
4. **verificar_caminho.php** - Verifica o caminho exato do servidor
5. **deploy_debug.yml** - Workflow de GitHub Actions com mais informações de depuração

## Verificação do GitHub Actions

### 1. Verificar o Segredo FTP_PASSWORD

1. Acesse seu repositório no GitHub
2. Clique em "Settings" (Configurações)
3. No menu lateral, clique em "Secrets and variables" > "Actions"
4. Verifique se existe um segredo chamado `FTP_PASSWORD`
5. Se não existir, clique em "New repository secret"
   - Nome: `FTP_PASSWORD`
   - Valor: `patto200` (ou a senha correta do FTP)
6. Clique em "Add secret"

### 2. Verificar a Execução do Workflow

1. Acesse seu repositório no GitHub
2. Clique na aba "Actions"
3. Verifique se o workflow "Deploy via FTP" está sendo executado
4. Clique no workflow mais recente para ver os logs
5. Verifique se há erros nos logs

### 3. Executar o Workflow de Depuração

1. Acesse seu repositório no GitHub
2. Clique na aba "Actions"
3. No menu lateral, clique em "Deploy via FTP (Debug)"
4. Clique no botão "Run workflow" > "Run workflow"
5. Aguarde a execução e verifique os logs detalhados

## Testes Diretos no Servidor

### 1. Verificar Permissões

Acesse: `http://lotominas.site/verificar_permissoes_simples.php`

Este script mostrará:
- Informações do sistema
- Permissões de diretórios
- Lista de arquivos com permissões
- Teste de criação de arquivo

### 2. Testar Upload FTP Direto

Acesse: `http://lotominas.site/teste_upload_ftp.php`

Este script tentará:
- Conectar ao servidor FTP
- Fazer login com as credenciais
- Fazer upload de um arquivo de teste
- Listar arquivos no diretório

### 3. Verificar Caminho do Servidor

Acesse: `http://lotominas.site/verificar_caminho.php`

Este script mostrará:
- Informações detalhadas do sistema
- Verificação de vários caminhos possíveis
- Informações sobre o arquivo atual
- Teste de criação de arquivo

## Solução Alternativa: Upload Direto via Cursor IDE

Se o GitHub Actions continuar falhando, você pode usar o upload direto via Cursor IDE:

1. Verifique se o arquivo `.vscode/sftp.json` está configurado corretamente:

```json
{
    "name": "Lotominas",
    "host": "217.196.61.30",
    "protocol": "ftp",
    "port": 21,
    "username": "patto200",
    "password": "patto200",
    "remotePath": "/www/wwwroot/lotominas.site/",
    "uploadOnSave": true,
    "ignore": [
        ".vscode",
        ".git",
        ".DS_Store",
        "node_modules"
    ]
}
```

2. Edite os arquivos no Cursor IDE
3. Salve os arquivos (Ctrl+S)
4. Os arquivos serão automaticamente enviados para o servidor

## Próximos Passos

1. Execute os scripts de diagnóstico no servidor
2. Verifique os logs do GitHub Actions
3. Se necessário, execute o workflow de depuração
4. Se o problema persistir, use o upload direto via Cursor IDE

## Contato para Suporte

Se você continuar enfrentando problemas, entre em contato com o suporte técnico fornecendo:

1. Resultados dos scripts de diagnóstico
2. Logs do GitHub Actions
3. Descrição detalhada do problema 