# Diagnóstico de Extensões no Cursor IDE

## Passos para Diagnóstico

1. **Verificar extensões instaladas**
   - Pressione `Ctrl+Shift+X` para abrir o painel de extensões
   - Verifique quais extensões estão instaladas e ativas

2. **Desativar extensões problemáticas**
   - Desative a extensão SFTP clicando no ícone de engrenagem e selecionando "Desativar"
   - Reinicie o Cursor IDE

3. **Testar FTP-Simple**
   - Verifique se a extensão FTP-Simple está instalada e ativa
   - Tente fazer upload de um arquivo usando o comando `ftp-simple: save`

4. **Verificar logs**
   - Pressione `Ctrl+Shift+U` para abrir o console de saída
   - Selecione "ftp-simple" no menu suspenso
   - Verifique se há mensagens de erro

## Solução Alternativa

Se os problemas persistirem, considere usar o VS Code em vez do Cursor IDE:

1. Instale o VS Code a partir de [https://code.visualstudio.com/](https://code.visualstudio.com/)
2. Instale a extensão FTP-Simple no VS Code
3. Copie o arquivo `.vscode/ftp-simple.json` para o seu projeto no VS Code
4. Teste o upload de arquivos no VS Code

## Verificação de Conexão FTP

Se você precisar verificar se a conexão FTP está funcionando corretamente, use o arquivo `teste_ftp_direto.php` que criamos anteriormente. 