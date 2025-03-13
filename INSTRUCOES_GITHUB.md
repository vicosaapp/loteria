# Instruções para Configurar o GitHub Actions

## Verificar o Segredo FTP_PASSWORD

1. Acesse seu repositório no GitHub
2. Vá para "Settings" > "Secrets" > "Actions"
3. Verifique se o segredo `FTP_PASSWORD` está configurado
4. Se não estiver, clique em "New repository secret"
5. Nome: `FTP_PASSWORD`
6. Valor: `patto200`
7. Clique em "Add secret"

## Verificar o Workflow

1. Acesse seu repositório no GitHub
2. Vá para a aba "Actions"
3. Verifique se o workflow "Deploy via FTP" está sendo executado
4. Clique no workflow para ver os detalhes
5. Verifique se há erros nos logs

## Testar o Deploy

1. Faça uma alteração no arquivo `teste_simples.php`
2. Faça commit e push das alterações:
   ```bash
   git add teste_simples.php
   git commit -m "Atualizar arquivo de teste"
   git push origin main
   ```
3. Verifique se o workflow "Deploy via FTP" foi acionado
4. Verifique se a execução foi concluída com sucesso
5. Acesse `https://lotominas.site/teste_simples.php` para verificar se o arquivo foi atualizado

## Solução de Problemas

Se o deploy continuar não funcionando, verifique:

1. **Credenciais FTP**: Verifique se o usuário e senha estão corretos
2. **Caminho do Servidor**: Verifique se o caminho `/www/wwwroot/lotominas.site/` está correto
3. **Permissões**: Verifique se o usuário FTP tem permissões de escrita no diretório
4. **Firewall**: Verifique se o servidor permite conexões FTP de IPs externos

## Alternativa: Upload Direto via Cursor IDE

Se o deploy via GitHub Actions continuar não funcionando, você pode usar o upload direto via Cursor IDE:

1. Verifique se o arquivo `.vscode/sftp.json` está configurado corretamente
2. Edite os arquivos no Cursor IDE e salve-os
3. Os arquivos devem ser enviados automaticamente para o servidor 